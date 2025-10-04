<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Include security breach detection
require_once __DIR__ . '/../security_breach_detector.php';

require_admin_auth();
$pdo = get_pdo();
$user = $_SESSION['user'];
$errors = [];
$info = [];
// Validate section parameter to prevent unauthorized access
$allowedSections = ['main_logs', 'account_settings', 'create_admin', 'account_management', 'shortcuts'];
$currentSection = $_GET['section'] ?? 'main_logs';

// Additional validation for section parameter
if (!is_string($currentSection) || strlen($currentSection) > 50) {
    logSecurityBreach('INVALID_SETTINGS_SECTION_FORMAT', 'Invalid section parameter format', [
        'section' => $currentSection,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    $currentSection = 'main_logs';
} elseif (!in_array($currentSection, $allowedSections)) {
    logSecurityBreach('INVALID_SETTINGS_SECTION', 'Invalid settings section attempted', [
        'invalid_section' => $currentSection,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    $currentSection = 'main_logs'; // Default to safe section
}

// Rate limiting for settings page access
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$rateLimitKey = 'settings_access_' . $ip;
$rateLimitFile = __DIR__ . '/../logs/rate_limits.json';

// Check rate limit (max 10 requests per minute)
$rateLimits = [];
if (file_exists($rateLimitFile)) {
    $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

$currentTime = time();
$minuteAgo = $currentTime - 60;

// Clean old entries
$rateLimits = array_filter($rateLimits, function($timestamp) use ($minuteAgo) {
    return $timestamp > $minuteAgo;
});

// Check if IP is rate limited
$ipRequests = array_filter($rateLimits, function($timestamp, $key) use ($ip) {
    return strpos($key, $ip) === 0;
}, ARRAY_FILTER_USE_BOTH);

if (count($ipRequests) >= 10) {
    logSecurityBreach('SETTINGS_RATE_LIMIT_EXCEEDED', 'Settings page rate limit exceeded', [
        'ip' => $ip,
        'requests_count' => count($ipRequests),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    http_response_code(429);
    die('Rate limit exceeded. Please try again later.');
}

// Add current request to rate limit
$rateLimits[$rateLimitKey . '_' . $currentTime] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimits));

// Log all settings page access
logSecurityBreach('SETTINGS_PAGE_ACCESS', 'Settings page accessed', [
    'section' => $currentSection,
    'ip' => $ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);

        // Auto-clear logs functionality (runs automatically)
try {
    // Check if we need to auto-clear logs (run this check on every page load)
    $today = date('Y-m-d');
    
    // Check if today's logs have already been archived
    $checkArchived = $pdo->prepare("SELECT id FROM daily_logs WHERE log_date = ?");
    $checkArchived->execute([$today]);
    
    if (!$checkArchived->fetch()) {
        // Get all activity logs for today with user names
        $activityLogs = $pdo->prepare("
            SELECT al.*, u.name as user_name, u.email as user_email, u.rfid as user_rfid
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE DATE(al.timestamp) = ?
            ORDER BY al.timestamp
        ");
        $activityLogs->execute([$today]);
        $activityData = $activityLogs->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all visitation logs for today
        $visitationLogs = $pdo->prepare("
            SELECT vl.*, 
                CASE 
                    WHEN vl.patient_type = 'student' THEN s.name
                    WHEN vl.patient_type = 'faculty' THEN f.name
                END as patient_name
            FROM visitation_logs vl 
            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
            WHERE DATE(vl.created_at) = ? 
            ORDER BY vl.created_at
        ");
        $visitationLogs->execute([$today]);
        $visitationData = $visitationLogs->fetchAll(PDO::FETCH_ASSOC);
        
        // Only archive if there are logs to archive
        if (!empty($activityData) || !empty($visitationData)) {
            // Insert into daily_logs table
            $insertDaily = $pdo->prepare("
                INSERT INTO daily_logs (log_date, activity_data, visitation_data, total_activities, total_visitations, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insertDaily->execute([
                $today,
                json_encode($activityData),
                json_encode($visitationData),
                count($activityData),
                count($visitationData)
            ]);
            
             // Mark today's logs as archived instead of deleting them
             $pdo->prepare("UPDATE activity_logs SET archived = 1 WHERE DATE(timestamp) = ?")->execute([$today]);
             $pdo->prepare("UPDATE visitation_logs SET archived = 1 WHERE DATE(visit_date) = ?")->execute([$today]);
            
            // Log this action
            log_activity($pdo, (int)$user['id'], 'logs_auto_archived', "Auto-archived logs for {$today}", 'settings');
        }
    }
    
    // Check for weekly and monthly archiving
    $currentWeek = date('Y-W'); // Year-Week format
    $currentMonth = date('Y-m'); // Year-Month format
    
    // Check if weekly logs need to be archived (every Sunday)
    if (date('w') == 0) { // Sunday
        $weekStart = date('Y-m-d', strtotime('monday this week -7 days'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week -7 days'));
        
        $checkWeeklyArchived = $pdo->prepare("SELECT id FROM weekly_logs WHERE week_start = ?");
        $checkWeeklyArchived->execute([$weekStart]);
        
        if (!$checkWeeklyArchived->fetch()) {
            // Get all daily logs for the week
            $weeklyLogs = $pdo->prepare("SELECT * FROM daily_logs WHERE log_date BETWEEN ? AND ? ORDER BY log_date");
            $weeklyLogs->execute([$weekStart, $weekEnd]);
            $weeklyData = $weeklyLogs->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($weeklyData)) {
                // Create weekly_logs table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS weekly_logs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    week_start DATE NOT NULL,
                    week_end DATE NOT NULL,
                    daily_logs_data JSON NOT NULL,
                    total_activities INT NOT NULL DEFAULT 0,
                    total_visitations INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_week_start (week_start),
                    INDEX idx_week_end (week_end)
                ) ENGINE=InnoDB');
                
                // Calculate totals
                $totalActivities = array_sum(array_column($weeklyData, 'total_activities'));
                $totalVisitations = array_sum(array_column($weeklyData, 'total_visitations'));
                
                // Insert weekly archive
                $insertWeekly = $pdo->prepare("
                    INSERT INTO weekly_logs (week_start, week_end, daily_logs_data, total_activities, total_visitations, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $insertWeekly->execute([
                    $weekStart,
                    $weekEnd,
                    json_encode($weeklyData),
                    $totalActivities,
                    $totalVisitations
                ]);
                
                // Delete the daily logs that were archived
                $pdo->prepare("DELETE FROM daily_logs WHERE log_date BETWEEN ? AND ?")->execute([$weekStart, $weekEnd]);
                
                log_activity($pdo, (int)$user['id'], 'logs_weekly_archived', "Weekly archive created for week {$weekStart} to {$weekEnd}", 'settings');
            }
        }
    }
    
    // Check if monthly logs need to be archived (first day of month)
    if (date('j') == 1) { // First day of month
        $lastMonth = date('Y-m', strtotime('first day of last month'));
        $monthStart = date('Y-m-01', strtotime('first day of last month'));
        $monthEnd = date('Y-m-t', strtotime('last day of last month'));
        
        $checkMonthlyArchived = $pdo->prepare("SELECT id FROM monthly_logs WHERE month_year = ?");
        $checkMonthlyArchived->execute([$lastMonth]);
        
        if (!$checkMonthlyArchived->fetch()) {
            // Get all daily logs for the month
            $monthlyLogs = $pdo->prepare("SELECT * FROM daily_logs WHERE log_date BETWEEN ? AND ? ORDER BY log_date");
            $monthlyLogs->execute([$monthStart, $monthEnd]);
            $monthlyData = $monthlyLogs->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($monthlyData)) {
                // Create monthly_logs table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS monthly_logs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    month_year VARCHAR(7) NOT NULL,
                    month_start DATE NOT NULL,
                    month_end DATE NOT NULL,
                    daily_logs_data JSON NOT NULL,
                    total_activities INT NOT NULL DEFAULT 0,
                    total_visitations INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_month_year (month_year),
                    INDEX idx_month_start (month_start)
                ) ENGINE=InnoDB');
                
                // Calculate totals
                $totalActivities = array_sum(array_column($monthlyData, 'total_activities'));
                $totalVisitations = array_sum(array_column($monthlyData, 'total_visitations'));
                
                // Insert monthly archive
                $insertMonthly = $pdo->prepare("
                    INSERT INTO monthly_logs (month_year, month_start, month_end, daily_logs_data, total_activities, total_visitations, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertMonthly->execute([
                    $lastMonth,
                    $monthStart,
                    $monthEnd,
                    json_encode($monthlyData),
                    $totalActivities,
                    $totalVisitations
                ]);
                
                // Delete the daily logs that were archived
                $pdo->prepare("DELETE FROM daily_logs WHERE log_date BETWEEN ? AND ?")->execute([$monthStart, $monthEnd]);
                
                log_activity($pdo, (int)$user['id'], 'logs_monthly_archived', "Monthly archive created for {$lastMonth}", 'settings');
            }
        }
    }
    
} catch (Throwable $e) {
    // Silently handle auto-clear errors to not disrupt user experience
    error_log("Auto-clear logs error: " . $e->getMessage());
}


// Handle form submissions with enhanced security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log all POST attempts to settings
    logSecurityBreach('SETTINGS_POST_ATTEMPT', 'POST request to settings page', [
        'action' => $_POST['action'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        logSecurityBreach('CSRF_TOKEN_INVALID', 'Invalid CSRF token in settings', [
            'provided_token' => $_POST['csrf_token'] ?? 'none',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Validate action parameter
        $allowedActions = ['archive_all_logs', 'update_account', 'create_admin', 'delete_user', 'activate_user', 'deactivate_user'];
        if (!in_array($action, $allowedActions)) {
            logSecurityBreach('INVALID_SETTINGS_ACTION', 'Invalid action attempted in settings', [
                'invalid_action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);
            $errors[] = 'Invalid action specified.';
            $action = ''; // Reset to prevent processing
        }
        
        switch ($action) {
            case 'archive_all_logs':
                try {
                    // Get all activity logs grouped by date (including already archived ones for today)
                    $today = date('Y-m-d');
                    $activityLogsByDate = $pdo->query("
                        SELECT DATE(timestamp) as log_date, 
                               COUNT(*) as total_activities
                        FROM activity_logs 
                        WHERE DATE(timestamp) = '$today'
                        GROUP BY DATE(timestamp)
                        ORDER BY log_date DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Also get unarchived logs from other dates
                    $otherDatesLogs = $pdo->query("
                        SELECT DATE(timestamp) as log_date, 
                               COUNT(*) as total_activities
                        FROM activity_logs 
                        WHERE archived = 0 AND DATE(timestamp) != '$today'
                        GROUP BY DATE(timestamp)
                        ORDER BY log_date DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Merge the results
                    $activityLogsByDate = array_merge($activityLogsByDate, $otherDatesLogs);
                    
                    // Get all unarchived visitation logs grouped by date
                    $visitationLogsByDate = $pdo->query("
                        SELECT DATE(visit_date) as log_date,
                               COUNT(*) as total_visitations
                        FROM visitation_logs 
                        WHERE archived = 0
                        GROUP BY DATE(visit_date)
                        ORDER BY log_date DESC
                    ")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Create daily_logs table if it doesn't exist
                    $pdo->exec('CREATE TABLE IF NOT EXISTS daily_logs (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        log_date DATE NOT NULL,
                        activity_logs_data JSON NOT NULL,
                        visitation_logs_data JSON NOT NULL,
                        total_activities INT NOT NULL DEFAULT 0,
                        total_visitations INT NOT NULL DEFAULT 0,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_log_date (log_date)
                    ) ENGINE=InnoDB');
                    
                    $archivedDates = [];
                    $totalActivities = 0;
                    $totalVisitations = 0;
                    
                    // Process each date
                    foreach ($activityLogsByDate as $activityLog) {
                        $logDate = $activityLog['log_date'];
                        $archivedDates[] = $logDate;
                        $totalActivities += $activityLog['total_activities'];
                        
                        // Get detailed activity logs for this date (ALL logs, not just unarchived)
                        $activityDetails = $pdo->prepare("
                            SELECT al.*, u.name as user_name, u.email as user_email, u.rfid as user_rfid
                            FROM activity_logs al
                            LEFT JOIN users u ON al.user_id = u.id
                            WHERE DATE(al.timestamp) = ?
                            ORDER BY al.timestamp
                        ");
                        $activityDetails->execute([$logDate]);
                        $activityData = $activityDetails->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Get detailed visitation logs for this date
                        $visitationDetails = $pdo->prepare("
                            SELECT vl.*, 
                            CASE 
                                WHEN vl.patient_type = 'student' THEN s.name
                                WHEN vl.patient_type = 'faculty' THEN f.name
                            END as patient_name,
                            CASE 
                                WHEN vl.patient_type = 'student' THEN s.rfid
                                WHEN vl.patient_type = 'faculty' THEN f.rfid
                            END as patient_rfid,
                            CASE 
                                WHEN vl.patient_type = 'student' THEN 
                                    CASE 
                                        WHEN s.level IN ('Elementary', 'High School', 'Senior High School') THEN s.year_grade
                                        WHEN s.level = 'College' THEN s.year_grade
                                        ELSE s.level
                                    END
                                WHEN vl.patient_type = 'faculty' THEN f.department
                            END as grade_level_department,
                            CASE 
                                WHEN vl.patient_type = 'student' THEN 
                                    CASE 
                                        WHEN s.level IN ('Elementary', 'High School') THEN s.section
                                        WHEN s.level = 'Senior High School' THEN s.strand
                                        WHEN s.level = 'College' THEN s.course
                                        ELSE s.section
                                    END
                                WHEN vl.patient_type = 'faculty' THEN f.position
                            END as course_section_strand
                            FROM visitation_logs vl
                            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
                            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
                            WHERE DATE(vl.visit_date) = ? AND vl.archived = 0
                            ORDER BY vl.visit_date
                        ");
                        $visitationDetails->execute([$logDate]);
                        $visitationData = $visitationDetails->fetchAll(PDO::FETCH_ASSOC);
                        
                        $totalVisitations += count($visitationData);
                        
                        // Check if already archived
                        $checkArchived = $pdo->prepare("SELECT id, activity_logs_data, total_activities FROM daily_logs WHERE log_date = ?");
                        $checkArchived->execute([$logDate]);
                        $existingArchive = $checkArchived->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$existingArchive) {
                            // Insert new archive
                            $insertDaily = $pdo->prepare("
                                INSERT INTO daily_logs (log_date, activity_logs_data, visitation_logs_data, total_activities, total_visitations, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            $insertDaily->execute([
                                $logDate,
                                json_encode($activityData),
                                json_encode($visitationData),
                                count($activityData),
                                count($visitationData)
                            ]);
                        } else {
                            // Update existing archive with new logs
                            $existingActivityData = json_decode($existingArchive['activity_logs_data'], true) ?: [];
                            $mergedActivityData = array_merge($existingActivityData, $activityData);
                            
                            $updateDaily = $pdo->prepare("
                                UPDATE daily_logs 
                                SET activity_logs_data = ?, total_activities = ?
                                WHERE log_date = ?
                            ");
                            $updateDaily->execute([
                                json_encode($mergedActivityData),
                                count($mergedActivityData),
                                $logDate
                            ]);
                        }
                    }
                    
                    // Process remaining visitation dates that don't have activity logs
                    foreach ($visitationLogsByDate as $visitationLog) {
                        $logDate = $visitationLog['log_date'];
                        if (!in_array($logDate, $archivedDates)) {
                            $archivedDates[] = $logDate;
                            
                            // Get detailed visitation logs for this date
                            $visitationDetails = $pdo->prepare("
                                SELECT vl.*, 
                                CASE 
                                    WHEN vl.patient_type = 'student' THEN s.name
                                    WHEN vl.patient_type = 'faculty' THEN f.name
                                END as patient_name,
                                CASE 
                                    WHEN vl.patient_type = 'student' THEN s.rfid
                                    WHEN vl.patient_type = 'faculty' THEN f.rfid
                                END as patient_rfid,
                                CASE 
                                    WHEN vl.patient_type = 'student' THEN 
                                        CASE 
                                            WHEN s.level IN ('Elementary', 'High School', 'Senior High School') THEN s.year_grade
                                            WHEN s.level = 'College' THEN s.year_grade
                                            ELSE s.level
                                        END
                                    WHEN vl.patient_type = 'faculty' THEN f.department
                                END as grade_level_department,
                                CASE 
                                    WHEN vl.patient_type = 'student' THEN 
                                        CASE 
                                            WHEN s.level IN ('Elementary', 'High School') THEN s.section
                                            WHEN s.level = 'Senior High School' THEN s.strand
                                            WHEN s.level = 'College' THEN s.course
                                            ELSE s.section
                                        END
                                    WHEN vl.patient_type = 'faculty' THEN f.position
                                END as course_section_strand
                                FROM visitation_logs vl
                                LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
                                LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
                                WHERE DATE(vl.visit_date) = ? AND vl.archived = 0
                                ORDER BY vl.visit_date
                            ");
                            $visitationDetails->execute([$logDate]);
                            $visitationData = $visitationDetails->fetchAll(PDO::FETCH_ASSOC);
                            
                            $totalVisitations += count($visitationData);
                            
                            // Check if already archived
                            $checkArchived = $pdo->prepare("SELECT id FROM daily_logs WHERE log_date = ?");
                            $checkArchived->execute([$logDate]);
                            
                            if (!$checkArchived->fetch()) {
                                // Insert new archive with empty activity data
                                $insertDaily = $pdo->prepare("
                                    INSERT INTO daily_logs (log_date, activity_logs_data, visitation_logs_data, total_activities, total_visitations, created_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW())
                                ");
                                $insertDaily->execute([
                                    $logDate,
                                    json_encode([]),
                                    json_encode($visitationData),
                                    0,
                                    count($visitationData)
                                ]);
                            }
                        }
                    }
                    
                    // Also archive file-based security alerts
                    $alertsFile = __DIR__ . '/../logs/alerts.log';
                    if (file_exists($alertsFile)) {
                        $alertLines = file($alertsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $fileAlertsCount = 0;
                        
                        foreach ($alertLines as $line) {
                            $parts = explode(' - ', $line, 3);
                            if (count($parts) >= 3) {
                                // Insert file-based alerts into activity_logs as archived
                                $insertAlert = $pdo->prepare("
                                    INSERT INTO activity_logs (user_id, user_type, action, description, action_description, location, ip_address, user_agent, success, error_message, timestamp, archived) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");
                                $insertAlert->execute([
                                    null, // user_id
                                    'system', // user_type
                                    $parts[1], // action
                                    $parts[2], // description
                                    $parts[2], // action_description
                                    'Security Framework', // location
                                    'System', // ip_address
                                    'File-based Alert', // user_agent
                                    0, // success (false for security alerts)
                                    'File-based security alert', // error_message
                                    $parts[0], // timestamp
                                    1 // archived (mark as already archived)
                                ]);
                                $fileAlertsCount++;
                            }
                        }
                        
                        if ($fileAlertsCount > 0) {
                            // Clear the alerts file after archiving
                            file_put_contents($alertsFile, '');
                        }
                    }
                    
                    // Mark all unarchived logs as archived
                    $pdo->prepare("UPDATE activity_logs SET archived = 1 WHERE archived = 0")->execute();
                    $pdo->prepare("UPDATE visitation_logs SET archived = 1 WHERE archived = 0")->execute();
                    
                    // Log this action
                    $fileAlertsCount = isset($fileAlertsCount) ? $fileAlertsCount : 0;
                    log_activity($pdo, (int)$user['id'], 'logs_all_archived', "Archived all unarchived logs (" . count($archivedDates) . " dates, {$totalActivities} activities, {$totalVisitations} visitations, {$fileAlertsCount} file alerts)", 'settings');
                    
                    $success = "All unarchived logs have been archived successfully! (" . count($archivedDates) . " dates processed, {$fileAlertsCount} file alerts archived)";
                } catch (Throwable $e) {
                    $errors[] = "Error archiving all logs: " . $e->getMessage();
                }
                break;
                
            case 'update_account':
                $name = sanitize_string($_POST['name'] ?? $user['name']);
                $email = sanitize_string($_POST['email'] ?? $user['email']);
                $newPassword = (string)($_POST['new_password'] ?? '');
                $rfid = sanitize_string($_POST['rfid'] ?? '');
                $deactivate = isset($_POST['deactivate_account']);
                
                try {
                    if ($deactivate) {
                        // Check if is_active column exists first
                        $checkColumn = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_active'");
                        $checkColumn->execute();
                        $hasIsActive = $checkColumn->fetch();
                        
                        if ($hasIsActive) {
                            // Deactivate account
                            $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([(int)$user['id']]);
                            $info[] = 'Account deactivated successfully.';
                        } else {
                            $info[] = 'Account deactivation not available (is_active column not found).';
                        }
                        log_activity($pdo, (int)$user['id'], 'account_deactivated', "Account deactivated", 'settings');
                    } else {
                        // Update profile
                        $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, (int)$user['id']]);
                        $info[] = 'Profile updated successfully.';
                        
                        // Update password if provided
                        if ($newPassword !== '') {
                            if (!is_strong_password($newPassword)) {
                                $errors[] = 'New password must be strong.';
                            } else {
                                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, (int)$user['id']]);
                                $info[] = 'Password updated successfully.';
                            }
                        }
                        
                        // Update RFID if provided
                        if ($rfid !== '') {
                            $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
                            $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ?')->execute([$rfidHash, (int)$user['id']]);
                            $info[] = 'RFID updated successfully.';
                        }
                        
                        // Refresh session
                        $_SESSION['user']['name'] = $name;
                        $_SESSION['user']['email'] = $email;
                        
                        log_activity($pdo, (int)$user['id'], 'profile_updated', "Profile updated", 'settings');
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Error updating account: ' . $e->getMessage();
                }
                break;
                
            case 'create_admin':
                // Log admin creation attempt
                logSecurityBreach('ADMIN_CREATION_ATTEMPT', 'Admin creation attempt in settings', [
                    'admin_name' => $_POST['new_name'] ?? 'unknown',
                    'admin_email' => $_POST['new_email'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]);
                
                $newName = sanitize_string($_POST['new_name'] ?? '');
                $newEmail = sanitize_string($_POST['new_email'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $newRfid = sanitize_string($_POST['new_rfid'] ?? '');
                
                if ($newName === '' || $newEmail === '' || $newPassword === '') {
                    $errors[] = 'Please fill in all required fields.';
                } else if (!is_strong_password($newPassword)) {
                    $errors[] = 'Password must be strong.';
                } else {
                    try {
                        // Check if email already exists
                        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR rfid = ? LIMIT 1');
                        $check->execute([$newEmail, $newRfid]);
                        if ($check->fetch()) {
                            $errors[] = 'Email or RFID already in use.';
                        } else {
                            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                            $rfidHash = $newRfid ? password_hash($newRfid, PASSWORD_DEFAULT) : null;
                            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, rfid, is_admin, is_active) VALUES (?, ?, ?, ?, 1, 1)');
                            $ins->execute([$newName, $newEmail, $hash, $rfidHash]);
                            $info[] = 'New admin account created successfully.';
                            
                            log_activity($pdo, (int)$user['id'], 'admin_created', "Created new admin: {$newName}", 'settings');
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'Error creating admin account: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                // Log user deletion attempt
                logSecurityBreach('USER_DELETION_ATTEMPT', 'User deletion attempt in settings', [
                    'target_user_id' => $userId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]);
                
                if ($userId <= 0) {
                    $errors[] = 'Invalid user ID.';
                } else if ($userId === (int)$user['id']) {
                    $errors[] = 'You cannot delete your own account.';
                } else {
                    try {
                        // Check if user exists
                        $checkUser = $pdo->prepare("SELECT id, name, email, is_admin FROM users WHERE id = ?");
                        $checkUser->execute([$userId]);
                        $targetUser = $checkUser->fetch();
                        
                        if (!$targetUser) {
                            $errors[] = 'User not found.';
                        } else {
                            // Delete the user
                            $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $deleteUser->execute([$userId]);
                            
                            if ($deleteUser->rowCount() > 0) {
                                $info[] = "User '{$targetUser['name']}' has been deleted successfully.";
                                log_activity($pdo, (int)$user['id'], 'user_deleted', "Deleted user: {$targetUser['name']} ({$targetUser['email']})", 'settings');
                            } else {
                                $errors[] = 'Failed to delete user.';
                            }
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'Error deleting user: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'update_user_role':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newRole = $_POST['new_role'] ?? '';
                
                if ($userId <= 0) {
                    $errors[] = 'Invalid user ID.';
                } else if ($userId === (int)$user['id']) {
                    $errors[] = 'You cannot change your own role.';
                } else if (!in_array($newRole, ['admin', 'regular'])) {
                    $errors[] = 'Invalid role specified.';
                } else {
                    try {
                        // Check if user exists
                        $checkUser = $pdo->prepare("SELECT id, name, email, is_admin FROM users WHERE id = ?");
                        $checkUser->execute([$userId]);
                        $targetUser = $checkUser->fetch();
                        
                        if (!$targetUser) {
                            $errors[] = 'User not found.';
                        } else {
                            $isAdmin = $newRole === 'admin' ? 1 : 0;
                            $updateRole = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
                            $updateRole->execute([$isAdmin, $userId]);
                            
                            if ($updateRole->rowCount() > 0) {
                                $roleText = $isAdmin ? 'Admin' : 'Regular User';
                                $info[] = "User '{$targetUser['name']}' role updated to {$roleText}.";
                                log_activity($pdo, (int)$user['id'], 'user_role_updated', "Updated user role: {$targetUser['name']} to {$roleText}", 'settings');
                            } else {
                                $errors[] = 'Failed to update user role.';
                            }
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'Error updating user role: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_user_status':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId <= 0) {
                    $errors[] = 'Invalid user ID.';
                } else if ($userId === (int)$user['id']) {
                    $errors[] = 'You cannot deactivate your own account.';
                } else {
                    try {
                        // Check if user exists and get current status
                        $checkUser = $pdo->prepare("SELECT id, name, email, is_active FROM users WHERE id = ?");
                        $checkUser->execute([$userId]);
                        $targetUser = $checkUser->fetch();
                        
                        if (!$targetUser) {
                            $errors[] = 'User not found.';
                        } else {
                            $newStatus = $targetUser['is_active'] ? 0 : 1;
                            $statusText = $newStatus ? 'activated' : 'deactivated';
                            
                            $updateStatus = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                            $updateStatus->execute([$newStatus, $userId]);
                            
                            if ($updateStatus->rowCount() > 0) {
                                $info[] = "User '{$targetUser['name']}' has been {$statusText}.";
                                log_activity($pdo, (int)$user['id'], 'user_status_updated', "User {$statusText}: {$targetUser['name']}", 'settings');
                            } else {
                                $errors[] = 'Failed to update user status.';
                            }
                        }
                    } catch (Throwable $e) {
                        $errors[] = 'Error updating user status: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'bulk_action':
                $action = $_POST['bulk_action'] ?? '';
                $userIds = $_POST['user_ids'] ?? [];
                
                if (empty($userIds) || !is_array($userIds)) {
                    $errors[] = 'No users selected for bulk action.';
                } else {
                    // Remove current user from bulk actions for safety
                    $userIds = array_filter($userIds, function($id) use ($user) {
                        return (int)$id !== (int)$user['id'];
                    });
                    
                    if (empty($userIds)) {
                        $errors[] = 'No valid users selected for bulk action.';
                    } else {
                        try {
                            $successCount = 0;
                            $userNames = [];
                            
                            foreach ($userIds as $userId) {
                                $userId = (int)$userId;
                                
                                // Get user info for logging
                                $checkUser = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
                                $checkUser->execute([$userId]);
                                $targetUser = $checkUser->fetch();
                                
                                if ($targetUser) {
                                    $userNames[] = $targetUser['name'];
                                    
                                    switch ($action) {
                                        case 'delete':
                                            $deleteUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                                            $deleteUser->execute([$userId]);
                                            if ($deleteUser->rowCount() > 0) $successCount++;
                                            break;
                                            
                                        case 'activate':
                                            $updateStatus = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                                            $updateStatus->execute([$userId]);
                                            if ($updateStatus->rowCount() > 0) $successCount++;
                                            break;
                                            
                                        case 'deactivate':
                                            $updateStatus = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                                            $updateStatus->execute([$userId]);
                                            if ($updateStatus->rowCount() > 0) $successCount++;
                                            break;
                                            
                                        case 'make_admin':
                                            $updateRole = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                                            $updateRole->execute([$userId]);
                                            if ($updateRole->rowCount() > 0) $successCount++;
                                            break;
                                            
                                        case 'make_regular':
                                            $updateRole = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                                            $updateRole->execute([$userId]);
                                            if ($updateRole->rowCount() > 0) $successCount++;
                                            break;
                                    }
                                }
                            }
                            
                            if ($successCount > 0) {
                                $actionText = ucfirst(str_replace('_', ' ', $action));
                                $info[] = "Bulk action '{$actionText}' completed successfully on {$successCount} user(s).";
                                log_activity($pdo, (int)$user['id'], 'bulk_action', "Bulk {$action} on {$successCount} users: " . implode(', ', $userNames), 'settings');
                            } else {
                                $errors[] = 'No users were affected by the bulk action.';
                            }
                        } catch (Throwable $e) {
                            $errors[] = 'Error performing bulk action: ' . $e->getMessage();
                        }
                    }
                }
                break;
        }
    }
}

// Note: Only showing current logs now, no archived logs

// Get all users for account management
$allUsers = [];
try {
    // Check if is_active column exists first
    $checkColumn = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'is_active'");
    $checkColumn->execute();
    $hasIsActive = $checkColumn->fetch();
    
    if ($hasIsActive) {
        $stmt = $pdo->prepare("SELECT id, name, email, is_admin, is_active, created_at FROM users ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, is_admin, 1 as is_active, created_at FROM users ORDER BY created_at DESC");
    }
    $stmt->execute();
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Error loading users: ' . $e->getMessage();
}

?>
<?php $pageTitle = 'Settings'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/../partials/header.php'; ?>

<!-- Notification Container -->
<div id="notificationContainer" class="fixed top-20 right-4 z-50"></div>

<div class="h-[calc(100vh-5rem)] flex items-start justify-center p-4 md:p-6 overflow-hidden">
    <div class="w-full max-w-7xl flex flex-col" style="max-height: 85vh;">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-clinic-dark">Settings</h1>
        </div>

        <!-- Settings Navigation - Single Row -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <!-- Main Logs -->
            <a href="?section=main_logs" class="group flex flex-col items-center p-4 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[120px] <?= $currentSection === 'main_logs' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-16 h-16 rounded-full bg-clinic-blue/10 flex items-center justify-center group-hover:bg-clinic-blue/20 transition-colors duration-200 mb-3">
                    <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Main Logs</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">View archived logs</p>
            </a>

            <!-- Account Settings -->
            <a href="?section=account_settings" class="group flex flex-col items-center p-4 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[120px] <?= $currentSection === 'account_settings' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-16 h-16 rounded-full bg-clinic-tea/20 flex items-center justify-center group-hover:bg-clinic-tea/30 transition-colors duration-200 mb-3">
                    <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Account</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">Manage profile</p>
            </a>

            <!-- Register Admin -->
            <a href="?section=register_admin" class="group flex flex-col items-center p-4 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[120px] <?= $currentSection === 'register_admin' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors duration-200 mb-3">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Register</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">New admin user</p>
            </a>

            <!-- Account Management -->
            <a href="?section=account_management" class="group flex flex-col items-center p-4 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[120px] <?= $currentSection === 'account_management' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-200 transition-colors duration-200 mb-3">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Account Mgmt</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">Manage users</p>
            </a>

            <!-- Keyboard Shortcuts -->
            <a href="?section=keyboard_toggles" class="group flex flex-col items-center p-4 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[120px] <?= $currentSection === 'keyboard_toggles' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200 mb-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Shortcuts</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">Keyboard guide</p>
            </a>

        </div>

        <!-- Notifications -->
        <?php if ($errors): ?>
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm p-4">
                <ul class="list-disc pl-5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <?php if ($info): ?>
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm p-4">
                <ul class="list-disc pl-5"><?php foreach ($info as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>


        <!-- Main Logs Section -->
        <?php if ($currentSection === 'main_logs'): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 flex flex-col flex-1 min-h-0">
                <div class="mb-4">
                    <h2 class="text-2xl font-bold text-clinic-dark">Main Logs</h2>
                </div>

                <!-- Search and Filter -->
                <div class="mb-4 space-y-3">
                    <!-- Search Row -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" id="logSearch" placeholder="Search logs..." class="flex-1 px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue text-sm">
                        <div class="flex gap-2">
                            <select id="logTypeFilter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue text-sm">
                        <option value="">All Types</option>
                        <option value="activity">Activity Logs</option>
                        <option value="visitation">Visitation Logs</option>
                    </select>
                            <select id="logDateFilter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue text-sm">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                        </div>
                    </div>
                    
                    <!-- Action Buttons Row -->
                    <div class="flex flex-wrap gap-2">
                        <button onclick="clearFilters()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm">
                        Clear Filters
                    </button>
                         <button onclick="archiveAllLogs()" class="px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm">
                             Archive All Logs
                         </button>
                    </div>
                </div>

                <!-- Logs Display with Tabs -->
                <div class="mb-4">
                    <div class="flex space-x-1 bg-clinic-ivory/30 p-1 rounded-lg">
                        <button onclick="showLogTab('daily')" id="dailyTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors bg-white text-clinic-blue shadow-sm">
                            All Logs (Consolidated)
                        </button>
                        <button onclick="showLogTab('weekly')" id="weeklyTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors text-clinic-dark/60 hover:text-clinic-blue">
                            Weekly Archives
                        </button>
                        <button onclick="showLogTab('monthly')" id="monthlyTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors text-clinic-dark/60 hover:text-clinic-blue">
                            Monthly Archives
                        </button>
                    </div>
                </div>

                <!-- Daily Logs Table -->
                <div id="dailyLogsTable" class="flex flex-col border border-clinic-tea/20 rounded-lg overflow-hidden flex-1 min-h-0">
                    <div class="bg-clinic-ivory/50 px-4 py-3 border-b border-clinic-tea/20">
                        <div class="grid grid-cols-4 gap-4 text-sm font-semibold text-clinic-dark">
                            <div>Date</div>
                            <div>Type</div>
                            <div>Total Records</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <div id="dailyLogsTableBody" class="min-w-full">
                            <?php 
                            // Create consolidated view by date - only current logs
                            $consolidatedLogs = [];
                            
                            // Only show archived logs (not current day logs)
                            $today = date('Y-m-d');
                            
                            // Debug: Check total counts first
                            $totalActivityLogs = $pdo->query("SELECT COUNT(*) as count FROM activity_logs")->fetch()['count'];
                            $totalVisitationLogs = $pdo->query("SELECT COUNT(*) as count FROM visitation_logs")->fetch()['count'];
                            $totalDailyLogs = $pdo->query("SELECT COUNT(*) as count FROM daily_logs")->fetch()['count'];
                            echo "<!-- Debug: Total activity logs: $totalActivityLogs, Total visitation logs: $totalVisitationLogs, Total daily logs: $totalDailyLogs -->";
                            
                            // Get archived logs from daily_logs table (including today's archived logs)
                            $archivedLogs = $pdo->query("SELECT log_date, total_activities, total_visitations FROM daily_logs ORDER BY log_date DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Debug: Show archived logs info
                            echo "<!-- Debug: Archived logs count: " . count($archivedLogs) . " -->";
                            if (!empty($archivedLogs)) {
                                echo "<!-- Debug: First archived log: " . json_encode($archivedLogs[0]) . " -->";
                            }
                            
                            // Process archived logs and merge by date and type
                            $mergedLogs = [];
                            
                            // First, group by date to handle duplicates
                            $groupedLogs = [];
                            foreach ($archivedLogs as $log) {
                                $date = $log['log_date'];
                                if (!isset($groupedLogs[$date])) {
                                    $groupedLogs[$date] = [
                                        'total_activities' => 0,
                                        'total_visitations' => 0
                                    ];
                                }
                                $groupedLogs[$date]['total_activities'] += $log['total_activities'];
                                $groupedLogs[$date]['total_visitations'] += $log['total_visitations'];
                            }
                            
                            // Now create merged logs from grouped data
                            foreach ($groupedLogs as $date => $totals) {
                                // Add activity logs if any
                                if ($totals['total_activities'] > 0) {
                                    $mergedLogs[$date . '_activity'] = [
                                        'date' => $date,
                                        'type' => 'Activity Logs',
                                        'total_records' => $totals['total_activities'],
                                        'log_type' => 'archived'
                                    ];
                                }
                                
                                // Add visitation logs if any
                                if ($totals['total_visitations'] > 0) {
                                    $mergedLogs[$date . '_visitation'] = [
                                        'date' => $date,
                                        'type' => 'Visitation Logs',
                                        'total_records' => $totals['total_visitations'],
                                        'log_type' => 'archived'
                                    ];
                                }
                            }
                            
                            // Add merged logs to consolidated logs
                            foreach ($mergedLogs as $key => $log) {
                                $consolidatedLogs[$key] = $log;
                            }
                            
                            // Only archived logs are shown (previous days, not current day)
                            
                            // Sort by date descending
                            krsort($consolidatedLogs);
                            ?>
                            
                            <?php if (empty($consolidatedLogs)): ?>
                                <div class="px-4 py-8 text-center text-clinic-dark/60">No logs found</div>
                            <?php else: ?>
                                <?php foreach ($consolidatedLogs as $log): ?>
                                    <div class="grid grid-cols-4 gap-4 px-4 py-3 border-b border-clinic-tea/20 hover:bg-clinic-ivory/30">
                                        <div class="font-medium text-clinic-dark">
                                            <?= date('M d, Y', strtotime($log['date'])) ?>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-2 h-2 <?= 
                                                    isset($log['log_type']) && $log['log_type'] === 'current' ? 'bg-green-500' : 
                                                    (isset($log['log_type']) && $log['log_type'] === 'weekly' ? 'bg-clinic-tea' : 
                                                    (isset($log['log_type']) && $log['log_type'] === 'monthly' ? 'bg-clinic-vanilla' : 'bg-clinic-blue')) 
                                                ?> rounded-full"></span>
                                                <?= htmlspecialchars($log['type']) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?= htmlspecialchars((string)$log['total_records']) ?> records
                                            </span>
                                        </div>
                                        <div>
                                            <div class="flex gap-2">
                                                <button onclick="viewFullLogs('<?= $log['date'] ?>', '<?= strtolower(str_replace(' Logs', '', $log['type'])) ?>', '<?= $log['log_type'] ?>')" class="text-clinic-blue hover:text-clinic-tea font-medium text-sm">
                                                    View Full
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Weekly Logs Table -->
                <div id="weeklyLogsTable" class="flex flex-col border border-clinic-tea/20 rounded-lg overflow-hidden hidden flex-1 min-h-0">
                    <table class="w-full text-sm min-w-[600px]">
                        <thead class="bg-clinic-ivory/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Week Period</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Activities</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Visitations</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Total Records</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="weeklyLogsTableBody">
                            <?php if (empty($weeklyLogs)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-clinic-dark/60">No weekly logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($weeklyLogs as $log): ?>
                                    <tr class="border-b border-clinic-tea/20 hover:bg-clinic-ivory/30">
                                        <td class="px-4 py-3 font-medium text-clinic-dark">
                                            <?= date('M d', strtotime($log['week_start'])) ?> - <?= date('M d, Y', strtotime($log['week_end'])) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($log['total_activities']) ?> activities
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?= htmlspecialchars($log['total_visitations']) ?> visitations
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?= htmlspecialchars($log['total_activities'] + $log['total_visitations']) ?> total
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <button onclick="viewLogDetails(<?= $log['id'] ?>, 'weekly')" class="text-clinic-blue hover:text-clinic-tea font-medium text-sm">
                                                    View Details
                                                </button>
                                                <button onclick="viewFullLogs('<?= $log['week_start'] ?>', 'weekly')" class="text-clinic-tea hover:text-clinic-blue font-medium text-sm">
                                                    View Full
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Monthly Logs Table -->
                <div id="monthlyLogsTable" class="flex flex-col border border-clinic-tea/20 rounded-lg overflow-hidden hidden flex-1 min-h-0">
                    <table class="w-full text-sm min-w-[600px]">
                        <thead class="bg-clinic-ivory/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Month</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Activities</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Visitations</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Total Records</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="monthlyLogsTableBody">
                            <?php if (empty($monthlyLogs)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-clinic-dark/60">No monthly logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthlyLogs as $log): ?>
                                    <tr class="border-b border-clinic-tea/20 hover:bg-clinic-ivory/30">
                                        <td class="px-4 py-3 font-medium text-clinic-dark">
                                            <?= date('F Y', strtotime($log['month_year'] . '-01')) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($log['total_activities']) ?> activities
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?= htmlspecialchars($log['total_visitations']) ?> visitations
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?= htmlspecialchars($log['total_activities'] + $log['total_visitations']) ?> total
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <button onclick="viewLogDetails(<?= $log['id'] ?>, 'monthly')" class="text-clinic-blue hover:text-clinic-tea font-medium text-sm">
                                                View Details
                                            </button>
                                                <button onclick="viewFullLogs('<?= $log['month_year'] ?>', 'monthly')" class="text-clinic-tea hover:text-clinic-blue font-medium text-sm">
                                                    View Full
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Account Settings Section -->
        <?php if ($currentSection === 'account_settings'): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <h2 class="text-2xl font-bold text-clinic-dark mb-6">Account Settings</h2>
                
                <form method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                    <input type="hidden" name="action" value="update_account" />
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">New Password</label>
                            <input type="password" name="new_password" class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                            <small class="text-clinic-dark/60">Leave blank to keep current password</small>
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">RFID</label>
                            <input type="text" name="rfid" class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                            <small class="text-clinic-dark/60">Leave blank to keep current RFID</small>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="bg-clinic-blue hover:bg-clinic-tea text-white px-6 py-3 rounded-lg font-medium transition">
                            Update Account
                        </button>
                        <button type="submit" name="deactivate_account" value="1" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition" onclick="return confirm('Are you sure you want to deactivate your account?')">
                            Deactivate Account
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Register Admin Section -->
        <?php if ($currentSection === 'register_admin'): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <!-- Back Button -->
                <div class="mb-4">
                    <button onclick="goBackToSettings()" class="inline-flex items-center gap-2 px-4 py-2 text-clinic-blue hover:text-clinic-tea hover:bg-clinic-blue/5 rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Settings
                    </button>
                </div>
                <h2 class="text-2xl font-bold text-clinic-dark mb-6">Register New Admin User</h2>
                
                <form method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                    <input type="hidden" name="action" value="create_admin" />
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">Name *</label>
                            <input type="text" name="new_name" required class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">Email *</label>
                            <input type="email" name="new_email" required class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">Password *</label>
                            <input type="password" name="new_password" required class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                            <small class="text-clinic-dark/60">Must be strong password (8+ chars, upper, lower, number, special)</small>
                        </div>
                        <div>
                            <label class="block text-clinic-dark font-medium mb-2">RFID</label>
                            <input type="text" name="new_rfid" class="w-full px-4 py-3 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                            <small class="text-clinic-dark/60">Optional - for RFID login</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium transition">
                        Create Admin Account
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Keyboard Shortcuts Section -->
        <?php if ($currentSection === 'keyboard_toggles'): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <h2 class="text-2xl font-bold text-clinic-dark mb-6">Keyboard Shortcuts</h2>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold text-clinic-dark mb-4">Navigation</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Toggle Sidebar</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Tab</kbd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Go to Dashboard</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Ctrl + D</kbd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Search Patients</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Ctrl + K</kbd>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-clinic-dark mb-4">Forms & Actions</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Save Form</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Ctrl + S</kbd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Cancel/Close</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Esc</kbd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-clinic-tea/20">
                                <span class="text-clinic-dark">Refresh Page</span>
                                <kbd class="px-2 py-1 bg-clinic-ivory text-clinic-dark rounded text-sm">Ctrl + R</kbd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 p-4 bg-clinic-ivory/30 rounded-lg">
                    <p class="text-clinic-dark/80 text-sm">
                        <strong>Note:</strong> These shortcuts are available throughout the application. 
                        Use them to navigate more efficiently and improve your workflow.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Account Management Section -->
        <?php if ($currentSection === 'account_management'): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 flex flex-col flex-1 min-h-0">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-clinic-dark">Account Management</h2>
                    <p class="text-clinic-dark/60 mt-2">Manage all registered users in the system</p>
                </div>



                <!-- Users Table -->
                <div class="flex-1 border border-clinic-tea/20 rounded-lg overflow-hidden">
                    <div class="bg-clinic-ivory/50 px-4 py-3 border-b border-clinic-tea/20">
                        <div class="grid grid-cols-6 gap-4 text-sm font-semibold text-clinic-dark">
                            <div>Name</div>
                            <div>Email</div>
                            <div>Type</div>
                            <div>Status</div>
                            <div>Created</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <div class="overflow-y-auto max-h-96">
                        <div id="usersTableBody">
                            <?php if (!empty($allUsers)): ?>
                                <?php foreach ($allUsers as $userData): ?>
                                    <div class="user-row grid grid-cols-6 gap-4 px-4 py-3 border-b border-clinic-tea/10 hover:bg-clinic-ivory/30 transition-colors">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full bg-clinic-blue/10 flex items-center justify-center mr-3">
                                                <svg class="w-4 h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                            <span class="font-medium text-clinic-dark"><?= htmlspecialchars($userData['name']) ?></span>
                                        </div>
                                        <div class="flex items-center text-clinic-dark/80">
                                            <?= htmlspecialchars($userData['email']) ?>
                                        </div>
                                        <div class="flex items-center">
                                            <?php if ($userData['is_admin']): ?>
                                                <span class="px-2 py-1 bg-clinic-blue/10 text-clinic-blue text-xs font-medium rounded-full">Admin</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">User</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center">
                                            <?php if ($userData['is_active']): ?>
                                                <span class="px-2 py-1 bg-green-100 text-green-600 text-xs font-medium rounded-full">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-red-100 text-red-600 text-xs font-medium rounded-full">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center text-clinic-dark/60 text-sm">
                                            <?= date('M j, Y', strtotime($userData['created_at'])) ?>
                                        </div>
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <?php if ($userData['id'] != $user['id']): ?>
                                                <!-- Role Change -->
                                                <form method="post" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                                                    <input type="hidden" name="action" value="update_user_role" />
                                                    <input type="hidden" name="user_id" value="<?= $userData['id'] ?>" />
                                                    <input type="hidden" name="new_role" value="<?= $userData['is_admin'] ? 'regular' : 'admin' ?>" />
                                                    <button type="submit" class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-600 text-xs font-medium rounded transition-colors" 
                                                            onclick="return confirm('Are you sure you want to change this user\'s role?')">
                                                        <?= $userData['is_admin'] ? 'Make User' : 'Make Admin' ?>
                                                    </button>
                                                </form>
                                                
                                                <!-- Status Toggle -->
                                                <form method="post" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                                                    <input type="hidden" name="action" value="toggle_user_status" />
                                                    <input type="hidden" name="user_id" value="<?= $userData['id'] ?>" />
                                                    <button type="submit" class="px-2 py-1 <?= $userData['is_active'] ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-600' : 'bg-green-100 hover:bg-green-200 text-green-600' ?> text-xs font-medium rounded transition-colors"
                                                            onclick="return confirm('Are you sure you want to <?= $userData['is_active'] ? 'deactivate' : 'activate' ?> this user?')">
                                                        <?= $userData['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                
                                                <!-- Delete -->
                                                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                                                    <input type="hidden" name="action" value="delete_user" />
                                                    <input type="hidden" name="user_id" value="<?= $userData['id'] ?>" />
                                                    <button type="submit" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-600 text-xs font-medium rounded transition-colors">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-gray-100 text-gray-400 text-xs font-medium rounded">Current User</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-clinic-dark/60">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-clinic-dark/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <p>No users found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-6 p-4 bg-clinic-ivory/30 rounded-lg">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-clinic-dark/80">
                            Total Users: <strong><?= count($allUsers) ?></strong>
                        </span>
                        <span class="text-clinic-dark/80">
                            Admin Users: <strong><?= count(array_filter($allUsers, fn($u) => $u['is_admin'])) ?></strong>
                        </span>
                        <span class="text-clinic-dark/80">
                            Active Users: <strong><?= count(array_filter($allUsers, fn($u) => $u['is_active'])) ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-7xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-clinic-tea/20">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-clinic-dark">Log Details</h3>
                <button onclick="closeLogDetails()" class="text-clinic-dark/60 hover:text-clinic-dark">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-[75vh]" id="logDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
// Notification system
function showNotification(message, type = 'success', duration = 3000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    notification.className = `transform transition-all duration-500 ease-out translate-x-full opacity-0 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-6 ${
        type === 'success' ? 'border-l-4 border-l-clinic-tea' : 
        type === 'error' ? 'border-l-4 border-l-red-500' : 
        type === 'warning' ? 'border-l-4 border-l-clinic-vanilla' : 
        'border-l-4 border-l-clinic-blue'
    }`;
    
    // Create content
    notification.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">
                    ${type === 'success' ? '✅' : 
                      type === 'error' ? '❌' : 
                      type === 'warning' ? '⚠️' : 
                      'ℹ️'}
                </div>
            </div>
            <div class="flex-1">
                <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${message}</p>
            </div>
            <button class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Add event listener to close button
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        closeNotification(closeBtn);
    });
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(closeBtn);
        }, duration);
    }
}

function closeNotification(button) {
    const notification = button.closest('div[id^="notification-"]');
    if (!notification) return;
    
    // Animate out
    notification.classList.remove('translate-x-0', 'opacity-100');
    notification.classList.add('translate-x-full', 'opacity-0');
    
    // Remove from DOM after animation
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 500);
}

// Log search and filtering
document.getElementById('logSearch').addEventListener('input', filterLogs);
document.getElementById('logTypeFilter').addEventListener('change', filterLogs);
document.getElementById('logDateFilter').addEventListener('change', filterLogs);

// Auto-focus search field
document.addEventListener('DOMContentLoaded', function() {
    const searchField = document.getElementById('logSearch');
    if (searchField) {
        searchField.focus();
    }
});

function filterLogs() {
    const search = document.getElementById('logSearch').value.toLowerCase();
    const typeFilter = document.getElementById('logTypeFilter').value;
    const dateFilter = document.getElementById('logDateFilter').value;
    
    // Filter daily logs (div-based layout)
    const dailyLogsContainer = document.getElementById('dailyLogsTableBody');
    if (dailyLogsContainer) {
        const dailyLogItems = dailyLogsContainer.querySelectorAll('.grid.grid-cols-4');
        dailyLogItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            const typeMatch = !typeFilter || text.includes(typeFilter.toLowerCase());
            const searchMatch = !search || text.includes(search);
            
            // Date filtering logic
            let dateMatch = true;
            if (dateFilter) {
                const dateText = item.querySelector('div:first-child')?.textContent.toLowerCase() || '';
                const today = new Date();
                const itemDate = new Date(dateText);
                
                switch (dateFilter) {
                    case 'today':
                        dateMatch = itemDate.toDateString() === today.toDateString();
                        break;
                    case 'week':
                        const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                        dateMatch = itemDate >= weekAgo;
                        break;
                    case 'month':
                        const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                        dateMatch = itemDate >= monthAgo;
                        break;
                    case 'year':
                        const yearAgo = new Date(today.getTime() - 365 * 24 * 60 * 60 * 1000);
                        dateMatch = itemDate >= yearAgo;
                        break;
                }
            }
            
            if (typeMatch && searchMatch && dateMatch) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // Filter weekly and monthly logs (table-based layout)
    const tableBodies = ['weeklyLogsTableBody', 'monthlyLogsTableBody'];
    tableBodies.forEach(tableBodyId => {
        const tbody = document.getElementById(tableBodyId);
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const typeMatch = !typeFilter || text.includes(typeFilter.toLowerCase());
                const searchMatch = !search || text.includes(search);
                
                // Date filtering logic for table rows
                let dateMatch = true;
                if (dateFilter) {
                    const dateCell = row.querySelector('td:first-child');
                    if (dateCell) {
                        const dateText = dateCell.textContent.toLowerCase();
                        const today = new Date();
                        const itemDate = new Date(dateText);
                        
                        switch (dateFilter) {
                            case 'today':
                                dateMatch = itemDate.toDateString() === today.toDateString();
                                break;
                            case 'week':
                                const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                                dateMatch = itemDate >= weekAgo;
                                break;
                            case 'month':
                                const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                                dateMatch = itemDate >= monthAgo;
                                break;
                            case 'year':
                                const yearAgo = new Date(today.getTime() - 365 * 24 * 60 * 60 * 1000);
                                dateMatch = itemDate >= yearAgo;
                                break;
                        }
                    }
                }
                
                if (typeMatch && searchMatch && dateMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
}

function showLogTab(tabType) {
    // Hide all tables
    document.getElementById('dailyLogsTable').classList.add('hidden');
    document.getElementById('weeklyLogsTable').classList.add('hidden');
    document.getElementById('monthlyLogsTable').classList.add('hidden');
    
    // Remove active state from all tabs
    document.getElementById('dailyTab').classList.remove('bg-white', 'text-clinic-blue', 'shadow-sm');
    document.getElementById('dailyTab').classList.add('text-clinic-dark/60');
    document.getElementById('weeklyTab').classList.remove('bg-white', 'text-clinic-blue', 'shadow-sm');
    document.getElementById('weeklyTab').classList.add('text-clinic-dark/60');
    document.getElementById('monthlyTab').classList.remove('bg-white', 'text-clinic-blue', 'shadow-sm');
    document.getElementById('monthlyTab').classList.add('text-clinic-dark/60');
    
    // Show selected table and activate tab
    if (tabType === 'daily') {
        document.getElementById('dailyLogsTable').classList.remove('hidden');
        document.getElementById('dailyTab').classList.add('bg-white', 'text-clinic-blue', 'shadow-sm');
        document.getElementById('dailyTab').classList.remove('text-clinic-dark/60');
    } else if (tabType === 'weekly') {
        document.getElementById('weeklyLogsTable').classList.remove('hidden');
        document.getElementById('weeklyTab').classList.add('bg-white', 'text-clinic-blue', 'shadow-sm');
        document.getElementById('weeklyTab').classList.remove('text-clinic-dark/60');
    } else if (tabType === 'monthly') {
        document.getElementById('monthlyLogsTable').classList.remove('hidden');
        document.getElementById('monthlyTab').classList.add('bg-white', 'text-clinic-blue', 'shadow-sm');
        document.getElementById('monthlyTab').classList.remove('text-clinic-dark/60');
    }
}

function viewLogDetails(logId, logType) {
    // This would fetch and display log details
    // For now, just show a placeholder with the log type
    document.getElementById('logDetailsContent').innerHTML = `
        <div class="text-center py-8">
            <p class="text-clinic-dark/60">Loading ${logType} log details for ID: ${logId}</p>
            <p class="text-sm text-clinic-dark/40 mt-2">This feature will show detailed logs for the selected ${logType} period.</p>
            <div class="mt-4 p-4 bg-clinic-ivory/30 rounded-lg">
                <p class="text-sm text-clinic-dark/80">
                    <strong>Log Type:</strong> ${logType.charAt(0).toUpperCase() + logType.slice(1)} Archive<br>
                    <strong>Log ID:</strong> ${logId}<br>
                    <strong>Features:</strong> View detailed activity and visitation logs for this period
                </p>
            </div>
        </div>
    `;
    document.getElementById('logDetailsModal').classList.remove('hidden');
    document.getElementById('logDetailsModal').classList.add('flex');
}

function showLogDetails(date, logType, logStatus = 'current') {
    // Show loading state
    document.getElementById('logDetailsContent').innerHTML = `
        <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-clinic-blue mx-auto mb-4"></div>
            <p class="text-clinic-dark/60">Loading logs for ${date}...</p>
        </div>
    `;
    document.getElementById('logDetailsModal').classList.remove('hidden');
    document.getElementById('logDetailsModal').classList.add('flex');
    
    // Fetch log data via AJAX
    fetch(`../logs/get_log_details.php?date=${encodeURIComponent(date)}&type=${encodeURIComponent(logType)}&log_type=${encodeURIComponent(logStatus)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('logDetailsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('logDetailsContent').innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600">Error loading logs: ${error.message}</p>
                </div>
            `;
        });
}

function viewFullLogs(date, logType, logStatus) {
    // Show modal with the appropriate log data
    showLogDetails(date, logType, logStatus);
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').classList.add('hidden');
    document.getElementById('logDetailsModal').classList.remove('flex');
}

function clearFilters() {
    document.getElementById('logSearch').value = '';
    document.getElementById('logTypeFilter').value = '';
    document.getElementById('logDateFilter').value = '';
    // Trigger the filter function if it exists
    if (typeof filterLogs === 'function') {
        filterLogs();
    }
}

// Close modal when clicking outside
document.getElementById('logDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogDetails();
    }
});

// Archive all logs function
function archiveAllLogs() {
    if (confirm('Are you sure you want to archive ALL unarchived logs? This will move all unarchived logs to the main logs display and clear them from the individual pages. This action cannot be undone.')) {
        // Show loading notification
        showNotification('Archiving all unarchived logs...', 'info');
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'archive_all_logs');
        formData.append('csrf_token', '<?= csrf_token() ?>');
        
        // Submit via AJAX
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Check if the response indicates success
            if (data.includes('archived') || data.includes('success') || data.includes('All unarchived logs have been archived')) {
                showNotification('All unarchived logs archived successfully!', 'success');
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error archiving logs. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error archiving logs. Please try again.', 'error');
        });
    }
}


// Back navigation function for settings
function goBackToSettings() {
    // Go back to main settings view
    window.location.href = '?section=main_logs';
}

// Security action functions
function clearFailedAttempts() {
    if (confirm('Are you sure you want to clear all failed login attempts? This will reset all user failed attempt counters.')) {
        fetch('clear_failed_attempts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_failed_attempts'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Failed attempts cleared successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error clearing failed attempts: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error clearing failed attempts', 'error');
        });
    }
}

function viewSecurityLogs() {
    // Open security logs in a new window or redirect to detailed logs
    window.open('../logs/logs.php?filter=security', '_blank');
}

function exportSecurityReport() {
    // Generate and download security report
    fetch('export_security_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=export_report'
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'security_report_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        showNotification('Security report downloaded successfully', 'success');
    })
    .catch(error => {
        showNotification('Error generating security report', 'error');
    });
}


</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
