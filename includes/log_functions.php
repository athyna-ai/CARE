<?php
/**
 * Security Logging System
 * 
 * Comprehensive logging for security events, user activities, and intrusion attempts
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Prevent direct access
if (!defined('SECURITY_FRAMEWORK_INIT')) {
    die('Direct access to this file is not allowed');
}

/**
 * Security Logger Class
 */
class SecurityLogger {
    private static $log_directory = __DIR__ . '/../logs/';
    private static $log_file = 'security.log';
    private static $max_log_size = 10 * 1024 * 1024; // 10MB
    private static $daily_rotation = true;
    
    /**
     * Initialize logging system
     */
    public static function init() {
        // Ensure log directory exists
        if (!is_dir(self::$log_directory)) {
            mkdir(self::$log_directory, 0755, true);
        }
        
        // Set log file permissions
        $log_path = self::$log_directory . self::$log_file;
        if (file_exists($log_path)) {
            chmod($log_path, 0644);
        }
    }
    
    /**
     * Log security event
     */
    public static function logEvent($event_data) {
        try {
            $log_entry = self::formatLogEntry($event_data);
            self::writeToLog($log_entry);
            
            // Check for critical events that require immediate attention
            self::checkCriticalEvents($event_data);
            
            // Maintain log file size
            self::maintainLogSize();
            
            return true;
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format log entry with structured data
     */
    private static function formatLogEntry($event_data) {
        $timestamp = $event_data['timestamp'] ?? date('Y-m-d H:i:s');
        $event_type = $event_data['event_type'] ?? 'UNKNOWN';
        $description = $event_data['description'] ?? '';
        $ip_address = $event_data['ip_address'] ?? 'unknown';
        $user_id = $event_data['user_id'] ?? 'guest';
        $url = $event_data['url'] ?? '';
        $user_agent = $event_data['user_agent'] ?? '';
        $session_id = $event_data['session_id'] ?? '';
        
        // Create structured log entry
        $log_entry = [
            'timestamp' => $timestamp,
            'event_type' => $event_type,
            'severity' => self::getSeverityLevel($event_type),
            'ip_address' => self::maskIP($ip_address), // Mask IP for privacy
            'user_id' => $user_id,
            'session_id' => self::maskSessionId($session_id),
            'url' => self::sanitizeURL($url),
            'user_agent' => self::sanitizeUserAgent($user_agent),
            'description' => $description,
            'risk_score' => self::calculateRiskScore($event_type, $description),
            'stack_trace' => self::getStackTrace($event_type)
        ];
        
        return self::formatAsJSON($log_entry) . PHP_EOL;
    }
    
    /**
     * Determine severity level
     */
    private static function getSeverityLevel($event_type) {
        $critical_events = [
            'SECURITY_INIT', 'SESSION_DESTROYED', 'URL_MANIPULATION_DETECTED',
            'DIRECTORY_TRAVERSAL', 'CSRF_TOKEN_INVALID', 'BRUTE_FORCE_BLOCKED',
            'SQL_INJECTION_ATTEMPT', 'XSS_ATTEMPT', 'UNAUTHORIZED_RESOURCE_ACCESS'
        ];
        
        $high_events = [
            'AUTHENTICATION_REQUIRED', 'INSUFFICIENT_PRIVILEGES', 'SUSPICIOUS_PARAMETER',
            'PROHIBITED_METHOD', 'SUSPICIOUS_USER_AGENT', 'RAPID_REQUEST'
        ];
        
        $medium_events = [
            'INVALID_INTEGER', 'INVALID_EMAIL', 'INVALID_URL', 'STRING_TOO_LONG',
            'SESSION_EXPIRED', 'OWNERSHIP_VALIDATION_FAILED'
        ];
        
        if (in_array($event_type, $critical_events)) {
            return 'CRITICAL';
        } elseif (in_array($event_type, $high_events)) {
            return 'HIGH';
        } elseif (in_array($event_type, $medium_events)) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }
    
    /**
     * Calculate risk score for the event
     */
    private static function calculateRiskScore($event_type, $description) {
        $base_scores = [
            'CRITICAL' => 9,
            'HIGH' => 7,
            'MEDIUM' => 5,
            'LOW' => 2
        ];
        
        $severity = self::getSeverityLevel($event_type);
        $score = $base_scores[$severity];
        
        // Adjust score based on description content
        if (strpos($description, 'SQL') !== false || strpos($description, 'injection') !== false) {
            $score += 2;
        }
        if (strpos($description, 'script') !== false || strpos($description, 'javascript') !== false) {
            $score += 2;
        }
        if (strpos($description, 'admin') !== false || strpos($description, 'delete') !== false) {
            $score += 1;
        }
        
        return min($score, 10); // Cap at 10
    }
    
    /**
     * Mask IP address for privacy (show first 3 octets only for local networks)
     */
    private static function maskIP($ip) {
        // For local development (127.0.0.1 or private ranges)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'local';
        }
        
        // For public IPs, mask last octet
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }
        
        return 'unknown';
    }
    
    /**
     * Mask session ID for privacy
     */
    private static function maskSessionId($session_id) {
        if (empty($session_id)) {
            return 'none';
        }
        
        return substr($session_id, 0, 8) . '...' . substr($session_id, -4);
    }
    
    /**
     * Sanitize URL for logging
     */
    private static function sanitizeURL($url) {
        // Remove sensitive parameters
        $sensitive_params = ['password', 'token', 'key', 'secret', 'auth'];
        $parsed_url = parse_url($url);
        
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $params);
            
            foreach ($sensitive_params as $param) {
                if (isset($params[$param])) {
                    $params[$param] = '***';
                }
            }
            
            $parsed_url['query'] = http_build_query($params);
            $url = http_build_url($parsed_url);
        }
        
        // Limit URL length
        return substr($url, 0, 200);
    }
    
    /**
     * Sanitize user agent string
     */
    private static function sanitizeUserAgent($user_agent) {
        // Remove potentially dangerous characters
        $sanitized = preg_replace('/[^\x20-\x7E]/', '', $user_agent);
        
        // Limit length
        return substr($sanitized, 0, 100);
    }
    
    /**
     * Get stack trace for critical events
     */
    private static function getStackTrace($event_type) {
        $critical_events = [
            'URL_MANIPULATION_DETECTED', 'DIRECTORY_TRAVERSAL', 'SQL_INJECTION_ATTEMPT',
            'XSS_ATTEMPT', 'UNAUTHORIZED_RESOURCE_ACCESS'
        ];
        
        if (in_array($event_type, $critical_events)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $trace_str = '';
            
            foreach ($trace as $frame) {
                if (isset($frame['file']) && isset($frame['line'])) {
                    $trace_str .= basename($frame['file']) . ':' . $frame['line'] . ' ';
                }
            }
            
            return trim($trace_str);
        }
        
        return '';
    }
    
    /**
     * Format log entry as JSON
     */
    private static function formatAsJSON($data) {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Write to log file
     */
    private static function writeToLog($log_entry) {
        $log_path = self::$log_directory . self::$log_file;
        
        // Use file locking to prevent corruption
        $file_handle = fopen($log_path, 'a');
        if ($file_handle && flock($file_handle, LOCK_EX)) {
            fwrite($file_handle, $log_entry);
            flock($file_handle, LOCK_UN);
            fclose($file_handle);
        } else {
            error_log("Failed to write to security log: $log_path");
        }
    }
    
    /**
     * Check for critical events requiring immediate attention
     */
    private static function checkCriticalEvents($event_data) {
        $critical_types = [
            'URL_MANIPULATION_DETECTED', 'DIRECTORY_TRAVERSAL', 'SQL_INJECTION_ATTEMPT',
            'XSS_ATTEMPT', 'BRUTE_FORCE_BLOCKED'
        ];
        
        if (in_array($event_data['event_type'], $critical_types)) {
            // Could trigger alerts/notifications here
            self::triggerAlert($event_data);
        }
    }
    
    /**
     * Trigger security alert (email, SMS, etc.)
     */
    private static function triggerAlert($event_data) {
        // This could be expanded to send emails, notifications, etc.
        $alert_file = self::$log_directory . 'alerts.log';
        $alert_entry = date('Y-m-d H:i:s') . " - CRITICAL: " . $event_data['event_type'] . 
                      " - " . $event_data['description'] . PHP_EOL;
        
        file_put_contents($alert_file, $alert_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Maintain log file size by rotating
     */
    private static function maintainLogSize() {
        $log_path = self::$log_directory . self::$log_file;
        
        if (file_exists($log_path) && filesize($log_path) > self::$max_log_size) {
            self::rotateLogFile();
        }
    }
    
    /**
     * Rotate log file
     */
    private static function rotateLogFile() {
        $log_path = self::$log_directory . self::$log_file;
        $timestamp = date('Y-m-d_H-i-s');
        $archive_path = self::$log_directory . "security_$timestamp.log";
        
        // Rename current log
        rename($log_path, $archive_path);
        
        // Compress archived log
        if (function_exists('gzopen')) {
            $gz_path = $archive_path . '.gz';
            $source = fopen($archive_path, 'rb');
            $dest = gzopen($gz_path, 'wb9');
            
            while (!feof($source)) {
                gzwrite($dest, fread($source, 1024));
            }
            
            fclose($source);
            gzclose($dest);
            unlink($archive_path); // Remove uncompressed file
        }
        
        self::logSecurityEvent('LOG_ROTATED', "Log file rotated: $log_path");
    }
    
    /**
     * Perform daily log cleanup
     */
    public static function dailyCleanup() {
        if (!self::$daily_rotation) {
            return;
        }
        
        $log_path = self::$log_directory . self::$log_file;
        $last_modified = filemtime($log_path);
        
        // If log is older than 1 day, rotate it
        if ((time() - $last_modified) > 86400) { // 24 hours
            self::rotateLogFile();
        }
        
        // Clean old archived logs (keep last 7 days)
        self::cleanupOldLogs();
    }
    
    /**
     * Clean up old archived log files
     */
    private static function cleanupOldLogs() {
        $log_dir = self::$log_directory;
        $files = glob($log_dir . 'security_*.log*');
        
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > (7 * 86400)) { // 7 days
                unlink($file);
                self::logSecurityEvent('LOG_CLEANUP', "Deleted old log file: " . basename($file));
            }
        }
    }
    
    /**
     * Generate security report
     */
    public static function generateSecurityReport($days = 7) {
        $log_path = self::$log_directory . self::$log_file;
        
        if (!file_exists($log_path)) {
            return ['error' => 'No log file found'];
        }
        
        $report = [
            'period_days' => $days,
            'total_events' => 0,
            'events_by_type' => [],
            'events_by_severity' => [],
            'top_ips' => [],
            'critical_events' => [],
            'summary' => []
        ];
        
        // Read log file
        $lines = file($log_path, FILE_IGNORE_NEW_LINES);
        $cutoff_time = time() - ($days * 86400);
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!$data) continue;
            
            $event_time = strtotime($data['timestamp']);
            if ($event_time < $cutoff_time) continue;
            
            $report['total_events']++;
            
            // Count by type
            $type = $data['event_type'];
            $report['events_by_type'][$type] = ($report['events_by_type'][$type] ?? 0) + 1;
            
            // Count by severity
            $severity = $data['severity'];
            $report['events_by_severity'][$severity] = ($report['events_by_severity'][$severity] ?? 0) + 1;
            
            // Track IPs
            $ip = $data['ip_address'];
            $report['top_ips'][$ip] = ($report['top_ips'][$ip] ?? 0) + 1;
            
            // Collect critical events
            if ($severity === 'CRITICAL' || $severity === 'HIGH') {
                $report['critical_events'][] = [
                    'timestamp' => $data['timestamp'],
                    'type' => $type,
                    'description' => $data['description'],
                    'risk_score' => $data['risk_score']
                ];
            }
        }
        
        // Generate summary
        $report['summary'] = [
            'average_risk_score' => array_sum(array_column($report['critical_events'], 'risk_score')) / max(count($report['critical_events']), 1),
            'most_active_ip' => max($report['top_ips']) ?: 'unknown',
            'most_common_event' => max($report['events_by_type']) ?: 'none',
            'security_threat_level' => count($report['critical_events']) > 10 ? 'HIGH' : 
                                     (count($report['critical_events']) > 5 ? 'MEDIUM' : 'LOW')
        ];
        
        return $report;
    }
    
    /**
     * Log security event (public method for easy access)
     */
    public static function logSecurityEvent($event_type, $description, $additional_data = []) {
        $base_data = array_merge([
            'event_type' => $event_type,
            'description' => $description,
            'timestamp' => date('Y-m-d H:i:s')
        ], $additional_data);
        
        return self::logEvent($base_data);
    }
    
    /**
     * Emergency log rotation (manual trigger)
     */
    public static function emergencyRotate() {
        self::rotateLogFile();
        return "Log rotation completed";
    }
    
    /**
     * Get log statistics
     */
    public static function getLogStats() {
        $log_path = self::$log_directory . self::$log_file;
        
        $stats = [
            'log_file_exists' => file_exists($log_path),
            'log_size_bytes' => file_exists($log_path) ? filesize($log_path) : 0,
            'log_size_mb' => file_exists($log_path) ? round(filesize($log_path) / (1024 * 1024), 2) : 0,
            'last_modified' => file_exists($log_path) ? date('Y-m-d H:i:s', filemtime($log_path)) : 'never',
            'write_permissions' => is_writable(self::$log_directory),
            'archived_files_count' => count(glob(self::$log_directory . 'security_*.log*'))
        ];
        
        return $stats;
    }
}

// Initialize logging system
SecurityLogger::init();

// Schedule daily cleanup (could be called via cron job)
if (!headers_sent()) {
    SecurityLogger::dailyCleanup();
}

// Helper functions for easy access
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($event_type, $description, $additional_data = []) {
        return SecurityLogger::logSecurityEvent($event_type, $description, $additional_data);
    }
}

function generateSecurityReport($days = 7) {
    return SecurityLogger::generateSecurityReport($days);
}

function getLogStats() {
    return SecurityLogger::getLogStats();
}
?>
