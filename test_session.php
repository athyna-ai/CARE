<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

// Test session persistence
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo json_encode([
    'success' => true,
    'session_id' => session_id(),
    'user' => $_SESSION['user']['name'],
    'test_counter' => $_SESSION['test_counter'],
    'read_notifications_count' => isset($_SESSION['read_notifications']) ? count($_SESSION['read_notifications']) : 0,
    'read_notifications' => $_SESSION['read_notifications'] ?? []
]);
?>
