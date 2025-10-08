<?php
/**
 * Comprehensive Security Framework
 * 
 * Prevents URL manipulation, unauthorized access, and handles security logging
 * for the Care CMS system.
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Prevent direct access
if (!defined('SECURITY_FRAMEWORK_INIT')) {
    define('SECURITY_FRAMEWORK_INIT', true);
    
    // Include logging functions
    require_once __DIR__ . '/log_functions.php';
}

/**
 * Security Configuration Constants
 */
class SecurityConfig {
    // Session Configuration
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const SESSION_REGENERATE_INTERVAL = 300; // 5 minutes
    
    // Security Limits
    const MAX_LOGIN_ATTEMPTS = 5;
    const BRUTE_FORCE_WINDOW = 900; // 15 minutes
    
    // URL Validation
    const ALLOWED_EXTENSIONS = ['php', 'html', 'htm'];
    const FORBIDDEN_PARAMS = ['../', './', '..\\', '.\\', '%00', '\x00'];
    
    // Logging Configuration
    const LOG_SECURITY_EVENTS = true;
    const LOG_NORMAL_ACTIVITY = false; // Set to true for detailed activity logging
}

/**
 * Core Security Class
 */
class SecurityFramework {
    private static $csrf_token = null;
    private static $init_time = null;
    
    /**
     * Initialize security framework
     */
    public static function init() {
        self::$init_time = microtime(true);
        
        // Configure session security
        self::configureSessionSecurity();
        
        // Start or resume session
        self::startSession();
        
        // Perform security checks
        self::performSecurityChecks();
        
        // Log initialization
        self::logSecurityEvent('SECURITY_INIT', 'Security framework initialized');
    }
    
    /**
     * Configure secure session settings
     */
    private static function configureSessionSecurity() {
        // Configure session parameters
        ini_set('session.cookie_httponly'  , '1');
        ini_set('session.cookie_secure'    , '1'); // Only for HTTPS production
        ini_set('session.use_strict_mode'  , '1');
        ini_set('session.cookie_samesite'  , 'Strict');
        
        // Prevent session fixation
        ini_set('session.regenerate_id', '1');
        
        // Set session garbage collection
        ini_set('session.gc_maxlifetime', SecurityConfig::SESSION_TIMEOUT);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }
    
    /**
     * Start secure session
     */
    private static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            self::regenerateSessionIfNeeded();
        }
    }
    
    /**
     * Regenerate session ID if needed
     */
    private static function regenerateSessionIfNeeded() {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            session_regenerate_id(true);
            self::logSecurityEvent('SESSION_REGENERATED', 'New session started');
        } elseif ((time() - $_SESSION['last_regeneration']) > SecurityConfig::SESSION_REGENERATE_INTERVAL) {
            $_SESSION['last_regeneration'] = time();
            session_regenerate_id(true);
            self::logSecurityEvent('SESSION_REGENERATED', 'Session ID regenerated for security');
        }
    }
    
    /**
     * Perform comprehensive security checks
     */
    private static function performSecurityChecks() {
        // URL Manipulation Detection
        self::detectURLManipulation();
        
        // Parameter Validation
        self::validateInputParameters();
        
        // HTTP Method Validation
        self::validateHTTPMethod();
        
        // Rate Limiting Check
        self::checkRateLimiting();
        
        // Suspicious Activity Detection
        self::detectSuspiciousActivity();
    }
    
    /**
     * Detect URL manipulation attempts
     */
    private static function detectURLManipulation() {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        // Check for forbidden patterns
        foreach (SecurityConfig::FORBIDDEN_PARAMS as $pattern) {
            if (strpos($current_url, $pattern) !== false || strpos($query_string, $pattern) !== false) {
                self::logSecurityEvent('URL_MANIPULATION_DETECTED', "Forbidden pattern detected: $pattern in URL: $current_url");
                self::blockSuspiciousUser("URL manipulation attempt");
            }
        }
        
        // Check for directory traversal
        if (preg_match('/\.\.(\/|\\\)|\.(\/|\\\)(\.(\/|\\\))*/', $current_url)) {
            self::logSecurityEvent('DIRECTORY_TRAVERSAL', "Directory traversal attempt: $current_url");
            self::blockSuspiciousUser("Directory traversal attempt");
        }
        
        // Validate file extensions
        $path_info = pathinfo($current_url);
        if (isset($path_info['extension'])) {
            $extension = strtolower($path_info['extension']);
            if (!in_array($extension, SecurityConfig::ALLOWED_EXTENSIONS)) {
                self::logSecurityEvent('FORBIDDEN_FILE_ACCESS', "Attempted access to forbidden file type: $extension in $current_url");
                self::redirectToSafePage();
            }
        }
    }
    
    /**
     * Validate and sanitize input parameters
     */
    private static function validateInputParameters() {
        $suspicious_found = false;
        
        // Check GET parameters
        foreach ($_GET as $key => $value) {
            if (self::isParameterSuspicious($key, $value)) {
                self::logSecurityEvent('SUSPICIOUS_PARAMETER', "Suspicious GET parameter: $key=$value");
                $suspicious_found = true;
            }
        }
        
        // Check POST parameters
        foreach ($_POST as $key => $value) {
            if (self::isParameterSuspicious($key, $value)) {
                self::logShippingEvent('SUSPICIOUS_PARAMETER', "Suspicious POST parameter: $key=$value");
                $suspicious_found = true;
            }
        }
        
        if ($suspicious_found) {
            self::blockSuspiciousUser("Suspicious parameter values detected");
        }
    }
    
    /**
     * Check if parameter is suspicious
     */
    private static function isParameterSuspicious($key, $value) {
        // Check for SQL injection patterns
        $sql_patterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/script.*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<\s*script\b[^<]*(?:(?!<!\s*</script>)<[^<]*)*<\s*\/\s*script\s*>/is'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        // Check for extremely long values (potential buffer overflow)
        if (strlen($value) > 10000) {
            return true;
        }
        
        // Check for binary data
        if (strpos($value, "\0") !== false || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate HTTP method for intended actions
     */
    private static function validateHTTPMethod() {
        $current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Dangerous actions should only be via POST
        $dangerous_scripts = ['delete_', 'update_', 'create_', 'admin_', 'reset_'];
        foreach ($dangerous_scripts as $prefix) {
            if (strpos($current_script, $prefix) === 0 && $method !== 'POST') {
                self::logSecurityEvent('PROHIBITED_METHOD', "Dangerous action via GET: $current_script");
                self::redirectToSafePage();
            }
        }
    }
    
    /**
     * Check rate limiting (brute force protection)
     */
    private static function checkRateLimiting() {
        $ip = self::getClientIP();
        $key = "failed_attempts_" . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'timestamp' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset counter if outside time window
        if ((time() - $attempts['timestamp']) > SecurityConfig::BRUTE_FORCE_WINDOW) {
            $_SESSION[$key] = ['count' => 0, 'timestamp' => time()];
        }
        
        // Block if too many failed attempts
        if ($attempts['count'] >= SecurityConfig::MAX_LOGIN_ATTEMPTS) {
            self::logSecurityEvent('BRUTE_FORCE_BLOCKED', "Too many failed attempts from IP: $ip");
            self::redirectToSafePage();
        }
    }
    
    /**
     * Detect suspicious activity patterns
     */
    private static function detectSuspiciousActivity() {
        $request_time = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Check for suspicious user agents
        $suspicious_agents = ['bot', 'crawler', 'scanner', 'hack', 'exploit'];
        foreach ($suspicious_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                self::logSecurityEvent('SUSPICIOUS_USER_AGENT', "Suspicious user agent detected: " . substr($user_agent, 0, 100));
            }
        }
        
        // Check for rapid requests (potential DoS or scanning)
        if (!$referer && (microtime(true) - $request_time) < 0.1) {
            self::logSecurityEvent('RAPID_REQUEST', "Rapid request detected without referer");
        }
    }
    
    /**
     * Enforce authentication requirement
     */
    public static function requireLogin($required_role = null) {
        if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
            self::logSecurityEvent('AUTHENTICATION_REQUIRED', 'Unauthorized access attempt to protected page');
            self::redirectToLogin();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > SecurityConfig::SESSION_TIMEOUT) {
            self::logSecurityEvent('SESSION_EXPIRED', 'Session expired due to inactivity');
            self::destroySession();
            self::redirectToLogin();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check role requirement
        if ($required_role !== null) {
            $user_role = $_SESSION['role'] ?? 'guest';
            if ($user_role !== $required_role) {
                self::logSecurityEvent('INSUFFICIENT_PRIVILEGES', "Role mismatch: required '$required_role', user has '$user_role'");
                self::redirectToSafePage();
            }
        }
        
        return true;
    }
    
    /**
     * Validate user ownership of resource
     */
    public static function validateOwnership($resource_user_id, $current_action = '') {
        $current_user_id = $_SESSION['user_id'] ?? null;
        
        if (!$current_user_id) {
            self::logSecurityEvent('OWNERSHIP_VALIDATION_FAILED', "No user ID in session for ownership check");
            return false;
        }
        
        if ($current_user_id != $resource_user_id) {
            self::logSecurityEvent('UNAUTHORIZED_RESOURCE_ACCESS', "User $current_user_id attempted to access resource owned by $resource_user_id ($current_action)");
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (self::$csrf_token === null) {
            self::$csrf_token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = self::$csrf_token;
            self::logSecurityEvent('CSRF_TOKEN_GENERATED', 'New CSRF token generated');
        }
        
        return self::$csrf_token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? '';
        }
        
        $session_token = $_SESSION['csrf_token'] ?? '';
        
        if (!hash_equals($session_token, $token)) {
            self::logSecurityEvent('CSRF_TOKEN_INVALID', "CSRF token mismatch. Provided: '$token'");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate and sanitize input data
     */
    public static function validateInput($data, $type, $options = []) {
        switch ($type) {
            case 'integer':
                $validated = filter_var($data, FILTER_VALIDATE_INT, $options);
                if ($validated === false && $data !== '0') {
                    self::logSecurityEvent('INVALID_INTEGER', "Invalid integer input: $data");
                    return null;
                }
                break;
                
            case 'email':
                $validated = filter_var($data, FILTER_VALIDATE_EMAIL);
                if ($validated === false) {
                    self::logSecurityEvent('INVALID_EMAIL', "Invalid email input: $data");
                    return null;
                }
                break;
                
            case 'url':
                $validated = filter_var($data, FILTER_VALIDATE_URL);
                if ($validated === false) {
                    self::logSecurityEvent('INVALID_URL', "Invalid URL input: $data");
                    return null;
                }
                break;
                
            case 'string':
                $validated = trim($data);
                $max_length = $options['max_length'] ?? 1000;
                if (strlen($validated) > $max_length) {
                    self::logSecurityEvent('STRING_TOO_LONG', "String too long: " . strlen($validated) . " characters");
                    return null;
                }
                break;
                
            default:
                self::logSecurityEvent('UNKNOWN_VALIDATION_TYPE', "Unknown validation type: $type");
                return null;
        }
        
        return $validated;
    }
    
    /**
     * Enforce POST-only requests for sensitive actions
     */
    public static function requirePOST() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::logSecurityEvent('METHOD_NOT_ALLOWED', "Non-POST request to sensitive endpoint: " . $_SERVER['REQUEST_METHOD']);
            self::redirectToSafePage();
        }
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event_type, $description) {
        if (SecurityConfig::LOG_SECURITY_EVENTS) {
            $log_entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event_type' => $event_type,
                'description' => $description,
                'ip_address' => self::getClientIP(),
                'user_id' => $_SESSION['user_id'] ?? 'guest',
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
                'session_id' => session_id()
            ];
            
            SecurityLogger::logEvent($log_entry);
        }
    }
    
    /**
     * Log normal user activity
     */
    public static function logActivity($action, $details = '') {
        if (SecurityConfig::LOG_NORMAL_ACTIVITY) {
            self::logSecurityEvent('USER_ACTIVITY', "$action: $details");
        }
    }
    
    /**
     * Block if suspicious user behavior detected
     */
    public static function blockSuspiciousUser($reason) {
        self::logSecurityEvent('USER_BLOCKED', "Suspicious behavior: $reason");
        
        // Invalidate session
        self::destroySession();
        
        // Redirect to safe page
        self::redirectToSafePage();
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '127.0.0.1'; // Default for local development
    }
    
    /**
     * Destroy current session
     */
    public static function destroySession() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::logSecurityEvent('SESSION_DESTROYED', 'Session destroyed for security');
    }
    
    /**
     * Redirect to login page
     */
    public static function redirectToLogin() {
        $login_url = '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header("Location: $login_url");
        exit();
    }
    
    /**
     * Redirect to safe page
     */
    public static function redirectToSafePage() {
        $safe_url = '/index.php';
        header("Location: $safe_url");
        exit();
    }
    
    /**
     * Get security statistics
     */
    public static function getSecurityStats() {
        return [
            'init_time' => self::$init_time,
            'current_session_id' => session_id(),
            'user_ip' => self::getClientIP(),
            'php_version' => PHP_VERSION,
            'session_config' => [
                'httponly' => ini_get('session.cookie_httponly'),
                'secure' => ini_get('session.cookie_secure'),
                'samesite' => ini_get('session.cookie_samesite')
            ]
        ];
    }
    
    /**
     * Clean up on shutdown
     */
    public static function shutdown() {
        if (self::$init_time) {
            $execution_time = microtime(true) - self::$init_time;
            if ($execution_time > 10.0) { // Log slow execution
                self::logSecurityEvent('SLOW_EXECUTION', "Script execution took {$execution_time} seconds");
            }
        }
    }
}

// Initialize security framework automatically
SecurityFramework::init();

// Register shutdown function for cleanup
register_shutdown_function(['SecurityFramework', 'shutdown']);

// Make functions available globally for easy integration
function requireLogin($role = null) { return SecurityFramework::requireLogin($role); }
function validateOwnership($user_id) { return SecurityFramework::validateOwnership($user_id); }
function generateCSRFToken() { return SecurityFramework::generateCSRFToken(); }
function validateCSRFToken($token = null) { return SecurityFramework::validateCSRFToken($token); }
function validateInput($data, $type, $options = []) { return SecurityFramework::validateInput($data, $type, $options); }
function requirePOST() { SecurityFramework::requirePOST(); }
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent() { return SecurityFramework::logSecurityEvent(...func_get_args()); }
}
if (!function_exists('logActivity')) {
    function logActivity() { return SecurityFramework::logActivity(...func_get_args()); }
}
?>
