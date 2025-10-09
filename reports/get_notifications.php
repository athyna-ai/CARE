<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in but don't require admin auth for notifications
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated',
        'notifications' => [],
        'unread_count' => 0
    ]);
    exit;
}

$pdo = get_pdo();

header('Content-Type: application/json');

try {
    // Get notifications from various sources
    $notifications = [];
    
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
            LIMIT 15
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
                vl.visit_date as timestamp,
                CASE WHEN vl.visit_date > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 0 ELSE 1 END as read_status,
                CONCAT('patients/patient_view.php?id=', vl.patient_id) as action_url
            FROM visitation_logs vl
            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
            WHERE vl.visit_date > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY vl.visit_date DESC
            LIMIT 10
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
                CONCAT('Medical record updated for ', 
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
            WHERE mr.updated_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY mr.updated_at DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        $medicalUpdates = [];
    }
    
    foreach ($medicalUpdates as $medical) {
        $notificationId = 'medical_' . md5($medical['timestamp'] . $medical['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $medical['title'],
            'message' => $medical['message'],
            'type' => $medical['type'],
            'timestamp' => $medical['timestamp'],
            'read' => $isRead,
            'action_url' => $medical['action_url']
        ];
    }
    
    // 4. System notifications (recent admin activities)
    try {
        $adminActivities = $pdo->query("
            SELECT 
                'system' as type,
                'Admin Activity' as title,
                CONCAT('Admin action: ', action, ' by ', COALESCE(u.name, 'System')) as message,
                timestamp,
                CASE WHEN timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END as read_status,
                '../logs/logs.php?filter=admin' as action_url
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.user_type = 'admin' 
            AND al.timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND al.action IN ('login', 'logout', 'settings_update', 'user_created', 'user_updated')
            ORDER BY al.timestamp DESC
            LIMIT 3
        ")->fetchAll();
    } catch (Exception $e) {
        $adminActivities = [];
    }
    
    foreach ($adminActivities as $admin) {
        $notificationId = 'admin_' . md5($admin['timestamp'] . $admin['message']);
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        
        $notifications[] = [
            'id' => $notificationId,
            'title' => $admin['title'],
            'message' => $admin['message'],
            'type' => $admin['type'],
            'timestamp' => $admin['timestamp'],
            'read' => $isRead,
            'action_url' => $admin['action_url']
        ];
    }
    
    // Sort notifications by timestamp (newest first)
    usort($notifications, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Count unread notifications
    $unreadCount = count(array_filter($notifications, function($n) {
        return !$n['read'];
    }));
    
    echo json_encode([
        'success' => true,
        'notifications' => array_slice($notifications, 0, 20), // Limit to 20 most recent
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load notifications',
        'notifications' => [],
        'unread_count' => 0
    ]);
}
?>
