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
                WHERE (success = 0 OR user_type = 'system') 
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
        
        // Get today's failed attempts
        $sql = "SELECT COUNT(*) as count FROM activity_logs 
                WHERE success = 0 AND DATE(timestamp) = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today]);
        $failed_attempts = $stmt->fetch()['count'];
        
        // Get today's system events
        $sql = "SELECT COUNT(*) as count FROM activity_logs 
                WHERE user_type = 'system' AND DATE(timestamp) = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today]);
        $system_events = $stmt->fetch()['count'];
        
        return [
            'today_critical' => 0,
            'today_high' => $failed_attempts,
            'today_medium' => $system_events,
            'total_attempts' => $failed_attempts + $system_events,
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