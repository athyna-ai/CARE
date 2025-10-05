<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/config.php';

function sanitize_string(?string $value): string {
	return trim((string)($value ?? ''));
}

function is_valid_email(string $email): bool {
	return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_strong_password(string $password): bool {
	// At least 8 chars, one uppercase, one lowercase, one digit, one special
	$lengthOk = strlen($password) >= 8;
	$hasUpper = preg_match('/[A-Z]/', $password) === 1;
	$hasLower = preg_match('/[a-z]/', $password) === 1;
	$hasDigit = preg_match('/\d/', $password) === 1;
	$hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password) === 1;
	return $lengthOk && $hasUpper && $hasLower && $hasDigit && $hasSpecial;
}

function log_activity(PDO $pdo, ?int $userId, string $action, string $details = '', string $location = ''): void {
	// Check if user exists before logging
	if ($userId !== null) {
		$checkUser = $pdo->prepare('SELECT id FROM users WHERE id = ?');
		$checkUser->execute([$userId]);
		if (!$checkUser->fetch()) {
			$userId = null; // Set to null if user doesn't exist
		}
	}
	
	$stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, location, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$stmt->execute([$userId, $action, $details, $location, $ip, $ua]);
}

function require_admin_auth(): void {
	$now = time();
	$timeoutSeconds = 600; // 10 minutes
	if (!empty($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > $timeoutSeconds) {
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
		session_destroy();
		header('Location: ../auth/login.php?timeout=1');
		exit;
	}
	if (empty($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
		// Log unauthorized access attempt
		try {
			$pdo = get_pdo();
			log_activity($pdo, null, 'unauthorized_access', 'Unauthorized access attempt to: ' . $_SERVER['REQUEST_URI'], 'auth/unauthorized');
		} catch (Throwable $e) {
			// Ignore logging errors for unauthorized access
		}
		header('Location: ../auth/login.php');
		exit;
	}
	$_SESSION['last_activity'] = $now;
}

// Masking utilities for safe display

function is_logged_in_admin(): bool {
	return !empty($_SESSION['user']) && (int)($_SESSION['user']['is_admin'] ?? 0) === 1;
}

function admin_exists(PDO $pdo): bool {
	$cnt = (int)$pdo->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 1')->fetchColumn();
	return $cnt > 0;
}

function mask_email(string $email): string {
	$parts = explode('@', $email);
	if (count($parts) !== 2) { return mask_name($email); }
	[$local, $domain] = $parts;
	$localLen = strlen($local);
	if ($localLen <= 2) {
		$localMasked = str_repeat('*', $localLen);
	} else {
		$localMasked = $local[0] . str_repeat('*', $localLen - 2) . $local[$localLen - 1];
	}
	// Mask domain except TLD
	$domainParts = explode('.', $domain);
	if (count($domainParts) >= 2) {
		$tld = array_pop($domainParts);
		$main = implode('.', $domainParts);
		$mainLen = strlen($main);
		$mainMasked = $mainLen <= 2 ? str_repeat('*', $mainLen) : $main[0] . str_repeat('*', $mainLen - 2) . $main[$mainLen - 1];
		return $localMasked . '@' . $mainMasked . '.' . $tld;
	}
	return $localMasked . '@' . str_repeat('*', max(1, strlen($domain)));
}

function mask_token_tail(string $value, int $keep = 6): string {
	$value = (string)$value;
	$len = strlen($value);
	if ($len <= $keep) { return str_repeat('*', max(0, $len - 1)) . substr($value, -1); }
	return str_repeat('*', $len - $keep) . substr($value, -$keep);
}

function mask_name(string $name): string {
	$name = trim($name);
	$words = explode(' ', $name);
	$masked = [];
	
	foreach ($words as $word) {
		$len = strlen($word);
		if ($len <= 2) {
			$masked[] = str_repeat('*', $len);
		} else {
			$masked[] = $word[0] . str_repeat('*', $len - 2) . $word[$len - 1];
		}
	}
	
	return implode(' ', $masked);
}

function mask_address(string $address): string {
	$address = trim($address);
	$words = explode(' ', $address);
	$masked = [];
	
	foreach ($words as $word) {
		$len = strlen($word);
		if ($len <= 3) {
			$masked[] = str_repeat('*', $len);
		} else {
			$masked[] = $word[0] . str_repeat('*', $len - 2) . $word[$len - 1];
		}
	}
	
	return implode(' ', $masked);
}

// CSRF functions already exist in config.php

// Enhanced input validation
function validate_patient_id($id): int {
	$id = (int)$id;
	if ($id <= 0 || $id > 999999) {
		throw new InvalidArgumentException('Invalid patient ID');
	}
	return $id;
}

function validate_patient_type($type): string {
	$type = sanitize_string($type);
	if (!in_array($type, ['student', 'faculty'])) {
		throw new InvalidArgumentException('Invalid patient type');
	}
	return $type;
}

// Output encoding for XSS prevention
function safe_output($data): string {
	return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// Enhanced security logging for patient data access
function log_patient_access(PDO $pdo, int $patientId, string $patientType, string $action): void {
	try {
		$userId = $_SESSION['user']['id'] ?? null;
		log_activity($pdo, $userId, "patient_{$action}", "Accessed patient ID: {$patientId} ({$patientType})", 'patient_access');
	} catch (Throwable $e) {
		// Ignore logging errors
	}
}

?>

