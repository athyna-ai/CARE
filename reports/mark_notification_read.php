<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

header('Content-Type: application/json');

// Handle POST request to mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    // Get all current notifications to mark them as read
    $notificationIds = [];
    
    try {
        $pdo = get_pdo();
        
        // 1. Security alerts
        $securityAlerts = $pdo->query("
            SELECT 
                CONCAT(action, ' from IP ', ip_address, ' - ', description) as message,
                timestamp
            FROM activity_logs 
            WHERE (success = 0 AND user_type = 'system') 
                OR action IN ('login_failed', 'SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach', 'Unauthorized Admin Access', 'Unauthorized Medical Access')
                AND timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY timestamp DESC
            LIMIT 20
        ")->fetchAll();
        
        foreach ($securityAlerts as $alert) {
            $notificationIds[] = 'security_' . md5($alert['message'] . $alert['timestamp']);
        }
        
        // 2. Patient visits
        $recentVisits = $pdo->query("
            SELECT 
                CONCAT('New visit recorded for ', 
                    CASE 
                        WHEN vl.patient_type = 'student' THEN COALESCE(s.name, 'Unknown') 
                        WHEN vl.patient_type = 'faculty' THEN COALESCE(f.name, 'Unknown')
                        ELSE 'Unknown'
                    END
                ) as message,
                vl.visit_date as timestamp
            FROM visitation_logs vl
            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
            WHERE vl.visit_date > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY vl.visit_date DESC
            LIMIT 20
        ")->fetchAll();
        
        foreach ($recentVisits as $visit) {
            $notificationIds[] = 'patient_' . md5($visit['timestamp'] . $visit['message']);
        }
        
        // 3. Medical updates
        $medicalUpdates = $pdo->query("
            SELECT 
                CONCAT('Medical record updated for ', 
                    CASE 
                        WHEN mr.patient_type = 'student' THEN COALESCE(s.name, 'Unknown') 
                        WHEN mr.patient_type = 'faculty' THEN COALESCE(f.name, 'Unknown')
                        ELSE 'Unknown'
                    END
                ) as message,
                mr.updated_at as timestamp
            FROM medical_records mr
            LEFT JOIN students s ON mr.patient_id = s.id AND mr.patient_type = 'student'
            LEFT JOIN faculty f ON mr.patient_id = f.id AND mr.patient_type = 'faculty'
            WHERE mr.updated_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY mr.updated_at DESC
            LIMIT 15
        ")->fetchAll();
        
        foreach ($medicalUpdates as $medical) {
            $notificationIds[] = 'medical_' . md5($medical['timestamp'] . $medical['message']);
        }
        
        // 4. System alerts
        $systemAlerts = $pdo->query("
            SELECT 
                CONCAT('System alert: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action IN ('system_error', 'database_error', 'security_breach')
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($systemAlerts as $alert) {
            $notificationIds[] = 'system_' . md5($alert['timestamp'] . $alert['message']);
        }
        
        // 5. Archive activities
        $archiveActivities = $pdo->query("
            SELECT 
                CONCAT('Record archived: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action LIKE '%archive%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($archiveActivities as $activity) {
            $notificationIds[] = 'archive_' . md5($activity['timestamp'] . $activity['message']);
        }
        
        // 6. Registration activities
        $registrationActivities = $pdo->query("
            SELECT 
                CONCAT('New user registered: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action LIKE '%register%' OR action LIKE '%registration%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($registrationActivities as $activity) {
            $notificationIds[] = 'registration_' . md5($activity['timestamp'] . $activity['message']);
        }
        
        // 7. Edit activities
        $editActivities = $pdo->query("
            SELECT 
                CONCAT('Record edited: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action LIKE '%edit%' OR action LIKE '%update%' OR action LIKE '%modify%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($editActivities as $activity) {
            $notificationIds[] = 'edit_' . md5($activity['timestamp'] . $activity['message']);
        }
        
        // 8. Login activities
        $loginActivities = $pdo->query("
            SELECT 
                CONCAT('Successful login: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action LIKE '%login%' AND action NOT LIKE '%failed%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($loginActivities as $activity) {
            $notificationIds[] = 'login_' . md5($activity['timestamp'] . $activity['message']);
        }
        
        // 9. Logout activities
        $logoutActivities = $pdo->query("
            SELECT 
                CONCAT('User logged out: ', action, ' at ', TIME(timestamp)) as message,
                timestamp
            FROM activity_logs 
            WHERE action LIKE '%logout%'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ")->fetchAll();
        
        foreach ($logoutActivities as $activity) {
            $notificationIds[] = 'logout_' . md5($activity['timestamp'] . $activity['message']);
        }
        
        // Store all notification IDs as read
        $_SESSION['read_notifications'] = $notificationIds;
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'count' => count($notificationIds)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to mark notifications as read: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle POST request to mark individual notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['notification_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Notification ID required'
        ]);
        exit;
    }
    
    $notificationId = $input['notification_id'];
    
    // Initialize read notifications array if it doesn't exist
    if (!isset($_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'] = [];
    }
    
    // Add notification ID to read list if not already there
    if (!in_array($notificationId, $_SESSION['read_notifications'])) {
        $_SESSION['read_notifications'][] = $notificationId;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
    exit;
}

// If not a POST request, return error
echo json_encode([
    'success' => false,
    'error' => 'Invalid request method'
]);
?>
