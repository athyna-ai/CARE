<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();

// Include security breach detection AFTER authentication
require_once __DIR__ . '/../security_breach_detector.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Clear all failed attempts for all users
    $stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE failed_attempts > 0');
    $stmt->execute();
    
    $affectedRows = $stmt->rowCount();
    
    // Log this security action
    log_activity($pdo, $_SESSION['user']['id'], 'security_action', 'Cleared all failed login attempts for ' . $affectedRows . ' users', 'admin/settings');
    
    echo json_encode([
        'success' => true, 
        'message' => "Cleared failed attempts for {$affectedRows} users"
    ]);
    
} catch (Exception $e) {
    error_log('Error clearing failed attempts: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>