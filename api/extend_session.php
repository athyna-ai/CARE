<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Verify CSRF token
$input = json_decode(file_get_contents('php://input'), true);
if (!verify_csrf($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Log the session extension
    $pdo = get_pdo();
    log_activity($pdo, $_SESSION['user']['id'] ?? 0, 'session_extended', 'User extended session to prevent timeout', 'session_monitor');
    
    echo json_encode([
        'success' => true,
        'message' => 'Session extended successfully',
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Session extension error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to extend session']);
}
?>
