<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Handle clear all notifications action
if (isset($_POST['action']) && $_POST['action'] === 'clear_all') {
    // Since notifications are generated dynamically, we'll mark them as "cleared" in session
    $_SESSION['notifications_cleared'] = time();
    
    // Redirect to prevent resubmission
    header('Location: notifications.php?cleared=1');
    exit;
}

// Handle mark as read action
if (isset($_POST['action']) && $_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
    $notificationId = $_POST['notification_id'];
    
    // Store read notifications in session
    if (!isset($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [];
    }
    $_SESSION['read_notifications'][] = $notificationId;
    
    // Redirect to prevent resubmission
    header('Location: notifications.php');
    exit;
}

// Get all notifications using the same logic as get_notifications.php
$notifications = [];

try {
    // 1. Security alerts from activity logs (all security breach types)
    try {
        $securityAlerts = $pdo->query("
            SELECT 
                'security' as type,
                CASE 
                    WHEN action LIKE '%SQL Injection%' THEN 'SQL Injection Attempt'
                    WHEN action LIKE '%XSS Attack%' THEN 'XSS Attack Attempt'
                    WHEN action LIKE '%Directory Traversal%' THEN 'Directory Traversal Attempt'
                    WHEN action LIKE '%Admin Directory%' THEN 'Admin Directory Breach'
                    WHEN action LIKE '%Unauthorized%' THEN 'Unauthorized Access Attempt'
                    WHEN action LIKE '%login_failed%' THEN 'Failed Login Attempt'
                    ELSE 'Security Breach Attempt'
                END as title,
                CONCAT(action, ' from IP ', ip_address, ' - ', description) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END as read_status,
                CONCAT('logs/logs.php?filter=security&ip=', ip_address) as action_url
            FROM activity_logs 
            WHERE (success = 0 AND user_type = 'system') 
                OR action IN ('login_failed', 'SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach', 'Unauthorized Admin Access', 'Unauthorized Medical Access')
                AND timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY timestamp DESC
            LIMIT 20
        ")->fetchAll();
    } catch (Exception $e) {
        $securityAlerts = [];
    }
    
    foreach ($securityAlerts as $alert) {
        $notificationId = 'security_' . md5($alert['message'] . $alert['timestamp']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $alert['title'],
            'message' => $alert['message'],
            'type' => $alert['type'],
            'timestamp' => $alert['timestamp'],
            'read' => $isRead,
            'action_url' => $alert['action_url']
        ];
    }
    
    // 2. Recent patient visits
    try {
        $recentVisits = $pdo->query("
            SELECT 
                'patient' as type,
                'Patient Visit' as title,
                CONCAT('New visit recorded for ', 
                    CASE 
                        WHEN vl.patient_type = 'student' THEN COALESCE(s.name, 'Unknown') 
                        WHEN vl.patient_type = 'faculty' THEN COALESCE(f.name, 'Unknown')
                        ELSE 'Unknown'
                    END
                ) as message,
                vl.created_at as timestamp,
                CASE WHEN vl.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                CONCAT('patients/patient_view.php?id=', vl.patient_id) as action_url
            FROM visitation_logs vl
            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
            WHERE vl.created_at > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY vl.created_at DESC
            LIMIT 20
        ")->fetchAll();
    } catch (Exception $e) {
        $recentVisits = [];
    }
    
    foreach ($recentVisits as $visit) {
        $notificationId = 'patient_' . md5($visit['timestamp'] . $visit['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $visit['title'],
            'message' => $visit['message'],
            'type' => $visit['type'],
            'timestamp' => $visit['timestamp'],
            'read' => $isRead,
            'action_url' => $visit['action_url']
        ];
    }
    
    // 3. Recent medical record updates
    try {
        $medicalUpdates = $pdo->query("
            SELECT 
                'medical' as type,
                'Medical Record Update' as title,
                CONCAT('Medical record updated for patient ID ', 
                    CASE 
                        WHEN mr.patient_type = 'student' THEN COALESCE(s.name, 'Unknown') 
                        WHEN mr.patient_type = 'faculty' THEN COALESCE(f.name, 'Unknown')
                        ELSE 'Unknown'
                    END
                ) as message,
                mr.updated_at as timestamp,
                CASE WHEN mr.updated_at > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                CONCAT('patients/patient_view.php?id=', mr.patient_id) as action_url
            FROM medical_records mr
            LEFT JOIN students s ON mr.patient_id = s.id AND mr.patient_type = 'student'
            LEFT JOIN faculty f ON mr.patient_id = f.id AND mr.patient_type = 'faculty'
            WHERE mr.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY mr.updated_at DESC
            LIMIT 15
        ")->fetchAll();
    } catch (Exception $e) {
        $medicalUpdates = [];
    }
    
    foreach ($medicalUpdates as $update) {
        $notificationId = 'medical_' . md5($update['timestamp'] . $update['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $update['title'],
            'message' => $update['message'],
            'type' => $update['type'],
            'timestamp' => $update['timestamp'],
            'read' => $isRead,
            'action_url' => $update['action_url']
        ];
    }
    
    // 4. System alerts and warnings
    try {
        $systemAlerts = $pdo->query("
            SELECT 
                'system' as type,
                'System Alert' as title,
                CONCAT('System alert: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php' as action_url
            FROM activity_logs 
            WHERE action IN ('system_error', 'database_error', 'security_breach')
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $systemAlerts = [];
    }
    
    foreach ($systemAlerts as $alert) {
        $notificationId = 'system_' . md5($alert['timestamp'] . $alert['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $alert['title'],
            'message' => $alert['message'],
            'type' => $alert['type'],
            'timestamp' => $alert['timestamp'],
            'read' => $isRead,
            'action_url' => $alert['action_url']
        ];
    }
    
    // 5. Archive activities
    try {
        $archiveActivities = $pdo->query("
            SELECT 
                'archive' as type,
                'Archive Activity' as title,
                CONCAT('Record archived: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php?filter=archive' as action_url
            FROM activity_logs 
            WHERE action LIKE '%archive%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $archiveActivities = [];
    }
    
    foreach ($archiveActivities as $activity) {
        $notificationId = 'archive_' . md5($activity['timestamp'] . $activity['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $activity['title'],
            'message' => $activity['message'],
            'type' => $activity['type'],
            'timestamp' => $activity['timestamp'],
            'read' => $isRead,
            'action_url' => $activity['action_url']
        ];
    }
    
    // 6. Registration activities
    try {
        $registrationActivities = $pdo->query("
            SELECT 
                'registration' as type,
                'New Registration' as title,
                CONCAT('New user registered: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php?filter=registration' as action_url
            FROM activity_logs 
            WHERE action LIKE '%register%' OR action LIKE '%registration%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $registrationActivities = [];
    }
    
    foreach ($registrationActivities as $activity) {
        $notificationId = 'registration_' . md5($activity['timestamp'] . $activity['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $activity['title'],
            'message' => $activity['message'],
            'type' => $activity['type'],
            'timestamp' => $activity['timestamp'],
            'read' => $isRead,
            'action_url' => $activity['action_url']
        ];
    }
    
    // 7. Edit activities
    try {
        $editActivities = $pdo->query("
            SELECT 
                'edit' as type,
                'Record Updated' as title,
                CONCAT('Record edited: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php?filter=edit' as action_url
            FROM activity_logs 
            WHERE action LIKE '%edit%' OR action LIKE '%update%' OR action LIKE '%modify%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $editActivities = [];
    }
    
    foreach ($editActivities as $activity) {
        $notificationId = 'edit_' . md5($activity['timestamp'] . $activity['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $activity['title'],
            'message' => $activity['message'],
            'type' => $activity['type'],
            'timestamp' => $activity['timestamp'],
            'read' => $isRead,
            'action_url' => $activity['action_url']
        ];
    }
    
    // 8. Login activities (successful)
    try {
        $loginActivities = $pdo->query("
            SELECT 
                'login' as type,
                'User Login' as title,
                CONCAT('Successful login: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php?filter=login' as action_url
            FROM activity_logs 
            WHERE action LIKE '%login%' AND action NOT LIKE '%failed%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $loginActivities = [];
    }
    
    foreach ($loginActivities as $activity) {
        $notificationId = 'login_' . md5($activity['timestamp'] . $activity['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $activity['title'],
            'message' => $activity['message'],
            'type' => $activity['type'],
            'timestamp' => $activity['timestamp'],
            'read' => $isRead,
            'action_url' => $activity['action_url']
        ];
    }
    
    // 9. Logout activities
    try {
        $logoutActivities = $pdo->query("
            SELECT 
                'logout' as type,
                'User Logout' as title,
                CONCAT('User logged out: ', action, ' at ', TIME(timestamp)) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END as read_status,
                'logs/logs.php?filter=logout' as action_url
            FROM activity_logs 
            WHERE action LIKE '%logout%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAYS)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $logoutActivities = [];
    }
    
    foreach ($logoutActivities as $activity) {
        $notificationId = 'logout_' . md5($activity['timestamp'] . $activity['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $activity['title'],
            'message' => $activity['message'],
            'type' => $activity['type'],
            'timestamp' => $activity['timestamp'],
            'read' => $isRead,
            'action_url' => $activity['action_url']
        ];
    }
    
} catch (Exception $e) {
    $notifications = [];
}

// Handle mark all as read action (after notifications are loaded)
if (isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    // Mark all notifications as read by storing their IDs in session
    if (!isset($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [];
    }
    
    // Get all notification IDs and mark them as read
    foreach ($notifications as $notification) {
        if (!in_array($notification['id'], $_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'][] = $notification['id'];
        }
    }
    
    // Redirect to prevent resubmission
    header('Location: notifications.php?marked_all_read=1');
    exit;
}

// Sort notifications by timestamp (newest first)
usort($notifications, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Count unread notifications
$unreadCount = count(array_filter($notifications, function($notification) {
    return !$notification['read'];
}));

$pageTitle = "Notifications";
$showTopNav = true;
$showSidebar = false; // Disable sidebar for notifications page
include __DIR__ . '/partials/header.php';

?>

<!-- Success Alerts - Fixed at Top Right -->
<?php if (isset($_GET['cleared']) && $_GET['cleared'] == '1'): ?>
<div id="successAlert" class="fixed top-24 right-6 z-50 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg max-w-md shadow-lg">
    <button onclick="closeAlert()" class="absolute top-2 right-2 text-green-600 hover:text-green-800 transition-colors duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    All notifications have been cleared successfully.
</div>
<?php endif; ?>

<?php if (isset($_GET['marked_all_read']) && $_GET['marked_all_read'] == '1'): ?>
<div id="successAlert" class="fixed top-24 right-6 z-50 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg max-w-md shadow-lg">
    <button onclick="closeAlert()" class="absolute top-2 right-2 text-blue-600 hover:text-blue-800 transition-colors duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    All notifications have been marked as read successfully.
</div>
<?php endif; ?>

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla pt-8">
    <!-- Full screen content -->
    <div class="w-full px-4">
        <div class="w-full">
            
            <!-- Header -->
            <div class="mb-2">
                <!-- Back Button -->
                <div class="mb-6">
                    <button onclick="goBack()" class="flex items-center gap-2 px-4 py-2 bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back
                    </button>
                </div>
                
                <!-- Title and Description -->
                <div class="text-center">
                    <h1 class="text-3xl md:text-4xl font-bold text-clinic-dark mb-2">Notifications</h1>
                    <p class="text-clinic-dark/70 text-lg">View and manage all system notifications</p>
                </div>
            </div>

            <!-- Notification Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-red/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Total</p>
                    <p class="text-2xl font-bold text-clinic-dark"><?= count($notifications) ?></p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-red/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Unread</p>
                    <p class="text-2xl font-bold text-clinic-red"><?= $unreadCount ?></p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-blue/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Read</p>
                    <p class="text-2xl font-bold text-clinic-blue"><?= count($notifications) - $unreadCount ?></p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-tea/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-tea" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Last 7 Days</p>
                    <p class="text-2xl font-bold text-clinic-tea"><?= count($notifications) ?></p>
                </div>
                
                <!-- Additional stats for full screen -->
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-purple/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Security</p>
                    <p class="text-2xl font-bold text-clinic-purple"><?= count(array_filter($notifications, function($n) { return $n['type'] === 'security'; })) ?></p>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20 p-4 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <div class="p-3 bg-clinic-green/10 rounded-lg">
                            <svg class="w-6 h-6 text-clinic-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-clinic-dark/60 mb-1">Patients</p>
                    <p class="text-2xl font-bold text-clinic-green"><?= count(array_filter($notifications, function($n) { return $n['type'] === 'patient'; })) ?></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" onclick="return confirm('Are you sure you want to mark all notifications as read?')" 
                            class="px-6 py-3 bg-clinic-blue hover:bg-clinic-blue/80 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Mark All Read
                    </button>
                </form>
                
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" onclick="return confirm('Are you sure you want to clear all notifications? This action cannot be undone.')" 
                            class="px-6 py-3 bg-clinic-red hover:bg-clinic-red/80 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                </form>
                
                <a href="logs/logs.php" class="px-6 py-3 bg-clinic-blue hover:bg-clinic-blue/80 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    View Logs
                </a>
                
                <button onclick="location.reload()" class="px-6 py-3 bg-clinic-tea hover:bg-clinic-tea/80 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>

            <!-- Notifications List -->
            <div class="bg-white rounded-xl shadow-lg border border-clinic-tea/20">
                <?php if (empty($notifications)): ?>
                <div class="p-12 text-center">
                    <svg class="w-20 h-20 mx-auto mb-6 text-clinic-tea/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-3">No Notifications</h3>
                    <p class="text-clinic-dark/60 text-lg">All caught up! No notifications to display.</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-clinic-tea/10">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item p-6 hover:bg-clinic-ivory/30 transition-colors duration-200 <?= $notification['read'] ? 'opacity-75' : 'bg-clinic-ivory/20' ?>">
                        <div class="flex items-start gap-6">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <?php
                                $iconClass = '';
                                $iconPath = '';
                                switch ($notification['type']) {
                                    case 'security':
                                        $iconClass = 'text-clinic-red';
                                        $iconPath = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z';
                                        break;
                                    case 'patient':
                                        $iconClass = 'text-clinic-blue';
                                        $iconPath = 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
                                        break;
                                    case 'medical':
                                        $iconClass = 'text-clinic-tea';
                                        $iconPath = 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
                                        break;
                                    case 'system':
                                        $iconClass = 'text-orange-500';
                                        $iconPath = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z';
                                        break;
                                    case 'archive':
                                        $iconClass = 'text-purple-500';
                                        $iconPath = 'M5 8a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V8zM5 8a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2V8z';
                                        break;
                                    case 'registration':
                                        $iconClass = 'text-green-500';
                                        $iconPath = 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z';
                                        break;
                                    case 'edit':
                                        $iconClass = 'text-blue-500';
                                        $iconPath = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
                                        break;
                                    case 'login':
                                        $iconClass = 'text-green-600';
                                        $iconPath = 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1';
                                        break;
                                    case 'logout':
                                        $iconClass = 'text-red-600';
                                        $iconPath = 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1';
                                        break;
                                    default:
                                        $iconClass = 'text-gray-500';
                                        $iconPath = 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';
                                }
                                ?>
                                <div class="p-3 rounded-lg bg-gray-100">
                                    <svg class="w-6 h-6 <?= $iconClass ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $iconPath ?>"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-lg font-semibold text-clinic-dark"><?= htmlspecialchars($notification['title']) ?></h4>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-clinic-dark/60"><?= date('M j, Y g:i A', strtotime($notification['timestamp'])) ?></span>
                                        <?php if (!$notification['read']): ?>
                                        <div class="w-3 h-3 bg-clinic-red rounded-full"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-clinic-dark/80 mb-3 break-words"><?= htmlspecialchars($notification['message']) ?></p>
                                <?php if ($notification['action_url']): ?>
                                <a href="<?= htmlspecialchars($notification['action_url']) ?>" class="text-sm text-clinic-blue hover:text-clinic-blue/80 font-medium underline">
                                    View Details →
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex-shrink-0">
                                <?php if (!$notification['read']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notification['id']) ?>">
                                    <button type="submit" class="px-4 py-2 text-sm bg-clinic-blue hover:bg-clinic-blue/80 text-white rounded-lg transition-colors duration-200">
                                        Mark Read
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="px-4 py-2 text-sm bg-gray-200 text-gray-600 rounded-lg">Read</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Back button functionality
function goBack() {
    // Direct navigation to dashboard
    window.location.href = 'admin/dashboard.php';
}

// Auto-dismiss and close functionality for success alerts
function closeAlert() {
    const alert = document.getElementById('successAlert');
    if (alert) {
        alert.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

// Auto-dismiss after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('successAlert');
    if (alert) {
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            closeAlert();
        }, 5000);
        
        // Add fade-in animation when alert appears
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease-in, transform 0.3s ease-in';
            alert.style.opacity = '1';
            alert.style.transform = 'translateY(0)';
        }, 100);
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
