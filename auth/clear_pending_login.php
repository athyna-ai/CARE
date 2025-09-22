<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Clear pending login session
if (isset($_SESSION['pending_login'])) {
    unset($_SESSION['pending_login']);
}

// Return success response
http_response_code(200);
echo json_encode(['success' => true]);
?>
