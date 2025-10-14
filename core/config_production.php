# Care CMS - Production Configuration

# Database Configuration for Hostinger Production
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'u258651435_CareOlsh_cms';
$DB_USER = getenv('DB_USER') ?: 'u258651435_athena';
$DB_PASS = getenv('DB_PASS') ?: '015hc@r3_Care';
$DB_CHARSET = 'utf8mb4';

# Production Environment Settings
$ENVIRONMENT = 'production';
$DEBUG_MODE = false;
$LOG_LEVEL = 'error';

# Security Settings
$ENCRYPTION_KEY = getenv('ENCRYPTION_KEY') ?: 'your_32_character_encryption_key_here';
$JWT_SECRET = getenv('JWT_SECRET') ?: 'your_jwt_secret_key_here';

# Session Configuration
$SESSION_LIFETIME = 3600; // 1 hour
$SESSION_SECURE = true; // Use HTTPS
$SESSION_HTTPONLY = true;
$SESSION_SAMESITE = 'Strict';

# File Upload Settings
$MAX_UPLOAD_SIZE = '10M';
$ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

# Email Configuration (if needed)
$SMTP_HOST = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
$SMTP_PORT = getenv('SMTP_PORT') ?: 587;
$SMTP_USER = getenv('SMTP_USER') ?: '';
$SMTP_PASS = getenv('SMTP_PASS') ?: '';

# Rate Limiting
$RATE_LIMIT_REQUESTS = 100; // requests per minute
$RATE_LIMIT_WINDOW = 60; // seconds

# Backup Settings
$BACKUP_ENABLED = true;
$BACKUP_RETENTION_DAYS = 30;

# Monitoring
$MONITORING_ENABLED = true;
$ERROR_REPORTING = false; // Set to false in production

# Domain Configuration
$BASE_URL = 'https://olshacare.com';
$ADMIN_EMAIL = 'admin@olshacare.com';

# RFID Configuration
$RFID_ENABLED = true;
$RFID_TIMEOUT = 30; // seconds

# Archive Settings
$AUTO_ARCHIVE_ENABLED = true;
$ARCHIVE_AFTER_DAYS = 365; // Archive records older than 1 year

# Security Headers
$SECURITY_HEADERS = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'"
];

# Performance Settings
$CACHE_ENABLED = true;
$CACHE_LIFETIME = 3600; // 1 hour
$COMPRESSION_ENABLED = true;

# Logging Configuration
$LOG_FILE = '/home/u258651435/domains/olshacare.com/public_html/logs/app.log';
$LOG_MAX_SIZE = '10MB';
$LOG_ROTATION = true;

# Database Connection Pooling
$DB_POOL_SIZE = 10;
$DB_TIMEOUT = 30;

# File Permissions
$FILE_PERMISSIONS = 0644;
$DIRECTORY_PERMISSIONS = 0755;

# Timezone
date_default_timezone_set('Asia/Manila');

# Error Handling
if ($ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $LOG_FILE);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

# Memory and Execution Limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

# Session Configuration
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

# Create a shared PDO instance with connection pooling
function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET, $DB_TIMEOUT;
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => $DB_TIMEOUT,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$DB_CHARSET}",
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    return $pdo;
}

# CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

# Security Headers Function
function set_security_headers() {
    global $SECURITY_HEADERS;
    foreach ($SECURITY_HEADERS as $header => $value) {
        header("{$header}: {$value}");
    }
}

# Initialize security headers
set_security_headers();

# Compression
if ($COMPRESSION_ENABLED && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

# Cache Headers
if ($CACHE_ENABLED) {
    header('Cache-Control: public, max-age=' . $CACHE_LIFETIME);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $CACHE_LIFETIME) . ' GMT');
}

# Database Health Check
function check_database_connection(): bool {
    try {
        $pdo = get_pdo();
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

# Performance Monitoring
function log_performance(string $operation, float $start_time): void {
    $execution_time = microtime(true) - $start_time;
    if ($execution_time > 1.0) { // Log slow operations (>1 second)
        error_log("Slow operation: {$operation} took {$execution_time} seconds");
    }
}

# Backup Function
function create_backup(): bool {
    global $BACKUP_ENABLED, $BACKUP_RETENTION_DAYS;
    if (!$BACKUP_ENABLED) return false;
    
    try {
        $backup_dir = '/home/u258651435/domains/olshacare.com/backups/';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . 'care_cms_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump -h {$DB_HOST} -u {$DB_USER} -p{$DB_PASS} {$DB_NAME} > {$backup_file}";
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0) {
            // Clean old backups
            $files = glob($backup_dir . 'care_cms_backup_*.sql');
            if (count($files) > $BACKUP_RETENTION_DAYS) {
                usort($files, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                $files_to_delete = array_slice($files, 0, count($files) - $BACKUP_RETENTION_DAYS);
                foreach ($files_to_delete as $file) {
                    unlink($file);
                }
            }
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Backup failed: " . $e->getMessage());
        return false;
    }
}

# Auto-backup on critical operations
register_shutdown_function(function() {
    if (isset($_SESSION['backup_needed']) && $_SESSION['backup_needed']) {
        create_backup();
        unset($_SESSION['backup_needed']);
    }
});

?>
