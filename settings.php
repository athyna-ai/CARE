<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

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
        // Get all activity logs for today
        $activityLogs = $pdo->prepare("SELECT * FROM activity_logs WHERE DATE(created_at) = ? ORDER BY created_at");
        $activityLogs->execute([$today]);
        $activityData = $activityLogs->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all visitation logs for today
        $visitationLogs = $pdo->prepare("
            SELECT vl.*, s.name as student_name, f.name as faculty_name 
            FROM visitation_logs vl 
            LEFT JOIN students s ON vl.student_id = s.id 
            LEFT JOIN faculty f ON vl.faculty_id = f.id 
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
            
            // Clear today's logs from main tables
            $pdo->prepare("DELETE FROM activity_logs WHERE DATE(created_at) = ?")->execute([$today]);
            $pdo->prepare("DELETE FROM visitation_logs WHERE DATE(created_at) = ?")->execute([$today]);
            
            // Log this action
            log_activity($pdo, (int)$user['id'], 'logs_auto_archived', "Auto-archived logs for {$today}", 'settings');
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

// Get daily logs for display
$dailyLogs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM daily_logs ORDER BY log_date DESC LIMIT 50");
    $stmt->execute();
    $dailyLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Table might not exist yet
}

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
<?php $pageTitle = 'Settings'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>

<div class="min-h-[calc(100vh-5rem)] p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-clinic-dark">Settings</h1>
        </div>

        <!-- Settings Navigation - Responsive Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 max-w-6xl mx-auto">
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
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-clinic-dark">Main Logs</h2>
                    <p class="text-clinic-dark/60 mt-2">Logs are automatically archived daily. Today's logs are merged and saved as a complete daily log entry.</p>
                </div>

                <!-- Search and Filter -->
                <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="text" id="logSearch" placeholder="Search logs..." class="px-4 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                    <select id="logTypeFilter" class="px-4 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        <option value="">All Types</option>
                        <option value="activity">Activity Logs</option>
                        <option value="visitation">Visitation Logs</option>
                    </select>
                    <select id="logDateFilter" class="px-4 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                        Clear Filters
                    </button>
                </div>

                <!-- Daily Logs Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[600px]">
                        <thead class="bg-clinic-ivory/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">ID</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Activities</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Visitations</th>
                                <th class="px-4 py-3 text-left font-semibold text-clinic-dark">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (empty($dailyLogs)): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-clinic-dark/60">No archived logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dailyLogs as $log): ?>
                                    <tr class="border-b border-clinic-tea/20 hover:bg-clinic-ivory/30">
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['id']) ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-2 h-2 bg-clinic-blue rounded-full"></span>
                                                Daily Archive
                                            </span>
                                        </td>
                                        <td class="px-4 py-3"><?= date('M d, Y', strtotime($log['log_date'])) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['total_activities']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['total_visitations']) ?></td>
                                        <td class="px-4 py-3">
                                            <button onclick="viewLogDetails(<?= $log['id'] ?>)" class="text-clinic-blue hover:text-clinic-tea font-medium">
                                                View Details
                                            </button>
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
    <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
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
        <div class="p-6 overflow-y-auto max-h-[60vh]" id="logDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
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
    const rows = document.querySelectorAll('#logsTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const typeMatch = !typeFilter || text.includes(typeFilter);
        const searchMatch = !search || text.includes(search);
        
        // Date filtering logic would go here
        const dateMatch = true; // Simplified for now
        
        if (typeMatch && searchMatch && dateMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function viewLogDetails(logId) {
    // This would fetch and display log details
    // For now, just show a placeholder
    document.getElementById('logDetailsContent').innerHTML = `
        <div class="text-center py-8">
            <p class="text-clinic-dark/60">Loading log details for ID: ${logId}</p>
            <p class="text-sm text-clinic-dark/40 mt-2">This feature will show detailed logs for the selected date.</p>
        </div>
    `;
    document.getElementById('logDetailsModal').classList.remove('hidden');
    document.getElementById('logDetailsModal').classList.add('flex');
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
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
