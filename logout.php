<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Handle both GET and POST requests
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$validCsrf = $isPost ? verify_csrf($_POST['csrf_token'] ?? null) : true;

if ($validCsrf) {
	// Log the logout activity
	try {
		$pdo = get_pdo();
		$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
		if ($userId) { 
			log_activity($pdo, $userId, 'logout', 'Admin logout', 'auth/logout'); 
		}
	} catch (Throwable $e) {
		// ignore logging errors
	}
}

// Clear session data
$_SESSION = [];

// Destroy session cookie
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;


