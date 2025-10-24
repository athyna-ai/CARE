<?php
/**
 * Security Monitor API - New Working Version
 * 
 * @author Security Framework Team
 * @version 2.0 - Simplified
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Start session
session_start();

// Get parameters
$action = $_GET['action'] ?? 'check_alerts';

// Simple authentication check
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Include database connection
    require_once __DIR__ . '/../core/config.php';
    $pdo = get_pdo();
    
    switch ($action) {
        case 'check_alerts':
            $alerts = getRecentAlerts($pdo);
            echo json_encode([
                'success' => true,
                'alerts' => $alerts,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'get_stats':
            $stats = getSecurityStats($pdo);
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log('Security monitor error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

/**
 * Get recent security alerts
 */
function getRecentAlerts($pdo) {
    try {
        $sql = "SELECT id, timestamp, action as event_type, description, ip_address, success
                FROM activity_logs 
                WHERE action IN ('SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach')
                AND action NOT LIKE '%unauthorized_access%'
                AND action NOT LIKE '%Unauthorized%'
                AND action NOT LIKE '%login_failed%'
                AND timestamp > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ORDER BY timestamp DESC
                LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $alerts;
    } catch (Exception $e) {
        error_log('Error getting alerts: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get security statistics
 */
function getSecurityStats($pdo) {
    try {
        $today = date('Y-m-d');
        $userId = $_SESSION['user']['id'] ?? 0;
        
        // Get today's security alerts (excluding unauthorized_access and login_failed)
        $sql = "SELECT id, timestamp, action, description, ip_address
                FROM activity_logs 
                WHERE action IN ('SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach')
                AND action NOT LIKE '%unauthorized_access%'
                AND action NOT LIKE '%Unauthorized%'
                AND action NOT LIKE '%login_failed%'
                AND DATE(timestamp) = ?
                ORDER BY timestamp DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today]);
        $securityAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count unread security alerts
        $unreadSecurityCount = 0;
        foreach ($securityAlerts as $alert) {
            $notificationId = 'security_' . md5($alert['action'] . ' from IP ' . $alert['ip_address'] . ' - ' . $alert['description'] . $alert['timestamp']);
            
            // Check if this notification has been read
            $isRead = false;
            
            // Check session first
            if (isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications'])) {
                $isRead = true;
            } else {
                // Check database
                try {
                    $readStmt = $pdo->prepare("SELECT COUNT(*) as count FROM notification_reads WHERE user_id = ? AND notification_id = ?");
                    $readStmt->execute([$userId, $notificationId]);
                    if ($readStmt->fetch()['count'] > 0) {
                        $isRead = true;
                    }
                } catch (Exception $e) {
                    // Ignore errors in read check
                }
            }
            
            if (!$isRead) {
                $unreadSecurityCount++;
            }
        }
        
        return [
            'today_critical' => 0,
            'today_high' => $unreadSecurityCount,
            'today_medium' => 0,
            'total_attempts' => count($securityAlerts),
            'blocked_ips' => 0
        ];
    } catch (Exception $e) {
        error_log('Error getting stats: ' . $e->getMessage());
        return [
            'today_critical' => 0,
            'today_high' => 0,
            'today_medium' => 0,
            'total_attempts' => 0,
            'blocked_ips' => 0
        ];
    }
}
?>