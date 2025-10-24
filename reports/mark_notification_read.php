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

// Enhanced notification management class
class NotificationManager {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    public function markAsRead($notificationId) {
        // Ensure session array exists
        if (!isset($_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'] = [];
        }
        
        // Add to session array
        if (!in_array($notificationId, $_SESSION['read_notifications'])) {
            $_SESSION['read_notifications'][] = $notificationId;
        }
        
        // Also store in database for persistence across sessions
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_reads (user_id, notification_id, read_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE read_at = NOW()
            ");
            $stmt->execute([$this->userId, $notificationId]);
        } catch (Exception $e) {
            // If database fails, at least session will work
            error_log("Database notification read failed: " . $e->getMessage());
        }
        
        return true;
    }
    
    public function markAllAsRead() {
        // Get all current notification IDs
        $notificationIds = $this->getAllNotificationIds();
        
        // Mark all in session
        $_SESSION['read_notifications'] = $notificationIds;
        
        // Mark all in database
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_reads (user_id, notification_id, read_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE read_at = NOW()
            ");
            
            foreach ($notificationIds as $notificationId) {
                $stmt->execute([$this->userId, $notificationId]);
            }
        } catch (Exception $e) {
            error_log("Database mark all read failed: " . $e->getMessage());
        }
        
        return count($notificationIds);
    }
    
    public function isRead($notificationId) {
        // Check session first (faster)
        if (isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications'])) {
            return true;
        }
        
        // Check database as fallback
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notification_reads 
                WHERE user_id = ? AND notification_id = ?
            ");
            $stmt->execute([$this->userId, $notificationId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                // Sync with session
                if (!isset($_SESSION['read_notifications'])) {
                    $_SESSION['read_notifications'] = [];
                }
                if (!in_array($notificationId, $_SESSION['read_notifications'])) {
                    $_SESSION['read_notifications'][] = $notificationId;
                }
                return true;
            }
        } catch (Exception $e) {
            error_log("Database read check failed: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function getAllNotificationIds() {
        $notificationIds = [];
        
        try {
            // Security alerts
            $securityAlerts = $this->pdo->query("
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
            
            // Patient visits
            $recentVisits = $this->pdo->query("
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
            
            // Medical updates
            $medicalUpdates = $this->pdo->query("
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
            
            // Admin activities
            $adminActivities = $this->pdo->query("
                SELECT 
                    CONCAT('Admin action: ', action, ' by ', COALESCE(u.name, 'System')) as message,
                    timestamp
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.user_type = 'admin' 
                AND al.timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
                AND al.action IN ('login', 'logout', 'settings_update', 'user_created', 'user_updated')
                ORDER BY al.timestamp DESC
                LIMIT 3
            ")->fetchAll();
            
            foreach ($adminActivities as $admin) {
                $notificationIds[] = 'admin_' . md5($admin['timestamp'] . $admin['message']);
            }
            
        } catch (Exception $e) {
            error_log("Error getting notification IDs: " . $e->getMessage());
        }
        
        return $notificationIds;
    }
}

// Create notification_reads table if it doesn't exist
try {
    $pdo = get_pdo();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notification_reads (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            notification_id VARCHAR(255) NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_notification (user_id, notification_id),
            INDEX idx_user_id (user_id),
            INDEX idx_notification_id (notification_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {
    error_log("Error creating notification_reads table: " . $e->getMessage());
}

$userId = $_SESSION['user']['id'];
$notificationManager = new NotificationManager($pdo, $userId);

// Handle POST request to mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    $count = $notificationManager->markAllAsRead();
    
    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read',
        'count' => $count
    ]);
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
    $success = $notificationManager->markAsRead($notificationId);
    
    echo json_encode([
        'success' => $success,
        'message' => 'Notification marked as read',
        'debug' => [
            'notification_id' => $notificationId,
            'session_count' => isset($_SESSION['read_notifications']) ? count($_SESSION['read_notifications']) : 0
        ]
    ]);
    exit;
}

// If not a POST request, return error
echo json_encode([
    'success' => false,
    'error' => 'Invalid request method'
]);
?>