<?php
/**
 * Clear IP Blocks Script
 * Allows admin to manually unblock IPs
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Require admin authentication
require_admin_auth();

header('Content-Type: application/json');

try {
    $pdo = get_pdo();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_ip_blocks') {
        // Get count of blocked IPs before clearing
        $blockedCount = $pdo->query('SELECT COUNT(*) FROM activity_logs WHERE action = "ip_blocked"')->fetchColumn();
        
        // Clear all IP blocks
        $stmt = $pdo->prepare('DELETE FROM activity_logs WHERE action = "ip_blocked"');
        $stmt->execute();
        
        // Log this action
        $userId = $_SESSION['user']['id'] ?? null;
        log_activity($pdo, $userId, 'ip_blocks_cleared', "Admin manually cleared {$blockedCount} IP blocks", 'admin/settings');
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully cleared {$blockedCount} IP blocks",
            'count' => $blockedCount
        ]);
        
    } elseif ($action === 'get_blocked_ips') {
        // Get list of currently blocked IPs
        $stmt = $pdo->query('
            SELECT ip_address, description, timestamp 
            FROM activity_logs 
            WHERE action = "ip_blocked" 
            ORDER BY timestamp DESC
        ');
        $blockedIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'blocked_ips' => $blockedIPs,
            'count' => count($blockedIPs)
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('Error in clear_ip_blocks.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
