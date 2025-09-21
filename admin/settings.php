<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();
$user = $_SESSION['user'];
$errors = [];
$info = [];
$currentSection = $_GET['section'] ?? 'main_logs';

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


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'cleanup_logs':
                try {
                    $today = date('Y-m-d');
                    
                    // First, archive today's logs before cleanup
                    // Get today's activity logs with user names
                    $activityLogs = $pdo->prepare("
                        SELECT al.*, u.name as user_name, u.email as user_email, u.rfid as user_rfid
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        WHERE DATE(al.timestamp) = ?
                        ORDER BY al.timestamp
                    ");
                    $activityLogs->execute([$today]);
                    $activityData = $activityLogs->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get today's visitation logs with joined data
                    $visitationLogs = $pdo->prepare("
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
                        WHERE DATE(vl.visit_date) = ?
                    ");
                    $visitationLogs->execute([$today]);
                    $visitationData = $visitationLogs->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Debug: Show what we're archiving
                    echo "<!-- Debug: Archiving {$today} - Activity logs: " . count($activityData) . ", Visitation logs: " . count($visitationData) . " -->";
                    
                    if (!empty($activityData) || !empty($visitationData)) {
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
                        
                        // Check if logs for today are already archived
                        $checkArchived = $pdo->prepare("SELECT id, activity_logs_data, visitation_logs_data, total_activities, total_visitations FROM daily_logs WHERE log_date = ?");
                        $checkArchived->execute([$today]);
                        $existingArchive = $checkArchived->fetch();
                        
                        if (!$existingArchive) {
                            // Insert into daily_logs
                            $insertDaily = $pdo->prepare("
                                INSERT INTO daily_logs (log_date, activity_logs_data, visitation_logs_data, total_activities, total_visitations, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            $insertDaily->execute([
                                $today,
                                json_encode($activityData),
                                json_encode($visitationData),
                                count($activityData),
                                count($visitationData)
                            ]);
                        } else {
                            // Merge with existing archive
                            $existingActivityData = json_decode($existingArchive['activity_logs_data'], true) ?: [];
                            $existingVisitationData = json_decode($existingArchive['visitation_logs_data'], true) ?: [];
                            
                            // Merge activity logs (avoid duplicates by ID)
                            $existingActivityIds = array_column($existingActivityData, 'id');
                            foreach ($activityData as $newLog) {
                                if (!in_array($newLog['id'], $existingActivityIds)) {
                                    $existingActivityData[] = $newLog;
                                }
                            }
                            
                            // Merge visitation logs (avoid duplicates by ID)
                            $existingVisitationIds = array_column($existingVisitationData, 'id');
                            foreach ($visitationData as $newLog) {
                                if (!in_array($newLog['id'], $existingVisitationIds)) {
                                    $existingVisitationData[] = $newLog;
                                }
                            }
                            
                            // Update the archive with merged data
                            $updateDaily = $pdo->prepare("
                                UPDATE daily_logs 
                                SET activity_logs_data = ?, 
                                    visitation_logs_data = ?, 
                                    total_activities = ?, 
                                    total_visitations = ?,
                                    created_at = NOW()
                                WHERE log_date = ?
                            ");
                            $updateDaily->execute([
                                json_encode($existingActivityData),
                                json_encode($existingVisitationData),
                                count($existingActivityData),
                                count($existingVisitationData),
                                $today
                            ]);
                        }
                        
                        // Mark today's logs as archived instead of deleting them
                        $pdo->prepare("UPDATE activity_logs SET archived = 1 WHERE DATE(timestamp) = ?")->execute([$today]);
                        $pdo->prepare("UPDATE visitation_logs SET archived = 1 WHERE DATE(visit_date) = ?")->execute([$today]);
                    }
                    
                     // Log this action
                     log_activity($pdo, (int)$user['id'], 'logs_archived', "Archived today's logs to main logs display", 'settings');
                     
                     $success = "Today's logs have been archived to main logs and cleared from individual pages. Patient view logs remain unchanged.";
                } catch (Throwable $e) {
                    $errors[] = "Error cleaning up logs: " . $e->getMessage();
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

<div class="h-[calc(100vh-5rem)] flex items-start md:items-center justify-center p-4 md:p-8 overflow-hidden">
    <div class="w-full max-w-5xl flex flex-col" style="max-height: 80vh;">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-clinic-dark">Settings</h1>
        </div>

        <!-- Settings Navigation - Responsive Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Main Logs -->
            <a href="?section=main_logs" class="group flex flex-col items-center p-4 sm:p-6 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[140px] <?= $currentSection === 'main_logs' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-clinic-blue/10 flex items-center justify-center group-hover:bg-clinic-blue/20 transition-colors duration-200 mb-3">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Main Logs</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">View archived logs</p>
            </a>

            <!-- Account Settings -->
            <a href="?section=account_settings" class="group flex flex-col items-center p-4 sm:p-6 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[140px] <?= $currentSection === 'account_settings' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-clinic-tea/20 flex items-center justify-center group-hover:bg-clinic-tea/30 transition-colors duration-200 mb-3">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Account</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">Manage profile</p>
            </a>

            <!-- Register Admin -->
            <a href="?section=register_admin" class="group flex flex-col items-center p-4 sm:p-6 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[140px] <?= $currentSection === 'register_admin' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors duration-200 mb-3">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors text-center">Register</h3>
                <p class="text-xs sm:text-sm text-clinic-dark/60 text-center mt-1">New admin user</p>
            </a>

            <!-- Keyboard Shortcuts -->
            <a href="?section=keyboard_toggles" class="group flex flex-col items-center p-4 sm:p-6 rounded-2xl bg-white shadow-lg border border-clinic-tea/20 hover:shadow-xl hover:border-clinic-blue/30 transition-all duration-300 min-h-[140px] <?= $currentSection === 'keyboard_toggles' ? 'ring-2 ring-clinic-blue bg-clinic-blue/5' : '' ?>">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200 mb-3">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                         <button onclick="archiveTodaysLogs()" class="px-3 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-blue/90 transition-colors text-sm">
                             Archive Today's Logs
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

// Archive today's logs function
function archiveTodaysLogs() {
    if (confirm('Are you sure you want to archive today\'s logs? This will move today\'s logs to the main logs display and clear them from the individual pages.')) {
        // Show loading notification
        showNotification('Archiving today\'s logs...', 'info');
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'cleanup_logs');
        formData.append('csrf_token', '<?= csrf_token() ?>');
        
        // Submit via AJAX
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Check if the response indicates success
            if (data.includes('archived') || data.includes('success') || data.includes('Today\'s logs have been archived')) {
                showNotification('Today\'s logs archived successfully!', 'success');
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
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
