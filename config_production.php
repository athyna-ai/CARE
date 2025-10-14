<?php
// Production configuration for olshacare.com
// Replace the database credentials with your actual Hostinger database details

// Database Configuration for Hostinger Production
$DB_HOST = 'localhost';
$DB_NAME = 'u[YOUR_USER_ID]_care_cms';  // Replace with your actual database name
$DB_USER = 'u[YOUR_USER_ID]_care';      // Replace with your actual username
$DB_PASS = 'YOUR_ACTUAL_PASSWORD';      // Replace with your actual password
$DB_CHARSET = 'utf8mb4';

// Production Environment Settings
$ENVIRONMENT = 'production';
$DEBUG_MODE = false;
$LOG_LEVEL = 'error';

// Security Settings
$ENCRYPTION_KEY = 'your_32_character_encryption_key_here';
$JWT_SECRET = 'your_jwt_secret_key_here';

// Session Configuration
$SESSION_LIFETIME = 3600; // 1 hour
$SESSION_SECURE = true; // Use HTTPS
$SESSION_HTTPONLY = true;
$SESSION_SAMESITE = 'Strict';

// Domain Configuration
$BASE_URL = 'https://olshacare.com';
$ADMIN_EMAIL = 'admin@olshacare.com';

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Handling
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/u[YOUR_USER_ID]/domains/olshacare.com/public_html/logs/app.log');

// Memory and Execution Limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Session Configuration
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', $SESSION_HTTPONLY ? 1 : 0);
    ini_set('session.cookie_secure', $SESSION_SECURE ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', $SESSION_SAMESITE);
    ini_set('session.gc_maxlifetime', $SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', 0);
    
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
        session_regenerate_id(false);
        $_SESSION['last_regeneration'] = time();
    }
}

// Create a shared PDO instance
function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$DB_CHARSET}",
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    return $pdo;
}

// CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Security Headers
function set_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Initialize security headers
set_security_headers();

?>
