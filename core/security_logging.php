<?php
/**
 * Security Logging Helper
 * 
 * Handles logging security events to both database and files
 * 
 * @author Security Framework Team
 * @version 1.0
 */

require_once __DIR__ . '/config.php';

/**
 * Log a security breach attempt to activity logs
 */
function logSecurityBreach($event_type, $description, $additional_data = []) {
    try {
        $pdo = get_pdo();
        
        // Get client information
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ip_address, ',') !== false) {
            $ip_address = trim(explode(',', $ip_address)[0]);
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Parse event type and severity
        $severity = 'MEDIUM';
        if (stripos($event_type, 'CRITICAL') !== false) {
            $severity = 'CRITICAL';
        } elseif (stripos($event_type, 'HIGH') !== false) {
            $severity = 'HIGH';
        }
        
        // Clean action for activity logs with better naming
        $action_mapping = [
            'SQL_INJECTION_ATTEMPT' => 'SQL Injection Blocked',
            'ADMIN_DIRECTORY_BREACH_ATTEMPT' => 'Admin Directory Breach',
            'INVALID_PATIENT_TYPE' => 'Invalid Patient Type',
            'INVALID_PATIENT_ID' => 'Invalid Patient ID',
            'INVALID_PATIENT_PARAMS' => 'Invalid Patient Parameters',
            'PATIENT_NOT_FOUND' => 'Patient Not Found',
            'INCOMPLETE_PATIENT_DATA' => 'Incomplete Patient Data',
            'UNAUTHORIZED_ADMIN_ACCESS' => 'Unauthorized Admin Access',
            'UNAUTHORIZED_MEDICAL_ACCESS' => 'Unauthorized Medical Access',
            'TEST_ATTEMPT' => 'Security Test',
            'XSS_ATTEMPT' => 'XSS Attack Blocked',
            'DIRECTORY_TRAVERSAL' => 'Directory Traversal Blocked',
            'SETTINGS_PAGE_ACCESS' => 'Settings Page Access',
            'SETTINGS_POST_ATTEMPT' => 'Settings Form Submission',
            'SETTINGS_RATE_LIMIT_EXCEEDED' => 'Settings Rate Limit Exceeded',
            'INVALID_SETTINGS_SECTION' => 'Invalid Settings Section',
            'INVALID_SETTINGS_SECTION_FORMAT' => 'Invalid Settings Section Format',
            'INVALID_SETTINGS_ACTION' => 'Invalid Settings Action',
            'CSRF_TOKEN_INVALID' => 'CSRF Token Invalid',
            'ADMIN_CREATION_ATTEMPT' => 'Admin Creation Attempt',
            'USER_DELETION_ATTEMPT' => 'User Deletion Attempt'
        ];
        
        $action = $action_mapping[$event_type] ?? str_replace(['_', '-'], ' ', $event_type);
        $action = ucwords(strtolower($action));
        
        // Enhanced description
        $full_description = $description;
        if (!empty($additional_data)) {
            $additional_info = [];
            foreach ($additional_data as $key => $value) {
                $additional_info[] = ucfirst($key) . ': ' . $value;
            }
            $full_description .= ' | ' . implode(', ', $additional_info);
        }
        
        // Insert into activity_logs table
        $sql = "INSERT INTO activity_logs (user_id, user_type, action, description, action_description, location, ip_address, user_agent, success, error_message, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            null, // user_id (null for anonymous attempts)
            'system', // user_type
            $action, // action (use mapped action, not event_type)
            $description, // description
            $full_description, // action_description
            $_SERVER['REQUEST_URI'] ?? 'Unknown', // location
            $ip_address, // ip_address
            $user_agent, // user_agent
            0, // success (false for security breaches)
            $severity . ' security breach attempt' // error_message
        ]);
        
        // Also log to file for emergency access
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'severity' => $severity,
            'ip_address' => $ip_address,
            'user_id' => 'anonymous',
            'description' => $full_description,
            'risk_score' => $severity === 'CRITICAL' ? 10 : ($severity === 'HIGH' ? 8 : 5),
            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'user_agent' => $user_agent,
            'additional_data' => $additional_data
        ];
        
        $log_file = __DIR__ . '/../logs/security.log';
        $log_line = json_encode($log_entry) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Also add to alerts log for critical/high events
        if ($severity === 'CRITICAL' || $severity === 'HIGH') {
            $alert_file = __DIR__ . '/../logs/alerts.log';
            $alert_line = date('Y-m-d H:i:s') . " - {$event_type} - {$description}\n";
            file_put_contents($alert_file, $alert_line, FILE_APPEND | LOCK_EX);
        }
        
        return true;
        
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_log("Security logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log successful security event
 */
function logSecurityEvent($event_type, $description, $user_id = null, $success = true) {
    try {
        $pdo = get_pdo();
        
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ip_address, ',') !== false) {
            $ip_address = trim(explode(',', $ip_address)[0]);
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO activity_logs (user_id, user_type, action, description, location, ip_address, user_agent, success, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $user_id ? 'admin' : 'system',
            $event_type,
            $description,
            $_SERVER['REQUEST_URI'] ?? 'Unknown',
            $ip_address,
            $user_agent,
            $success ? 1 : 0
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Security event logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent security events for monitoring
 */
function getRecentSecurityEvents($limit = 10) {
    try {
        $pdo = get_pdo();
        
        $sql = "SELECT * FROM activity_logs 
                WHERE success = 0 OR user_type = 'system'
                ORDER BY timestamp DESC 
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Failed to get security events: " . $e->getMessage());
        return [];
    }
}

/**
 * Check for suspicious activity patterns
 */
function checkSuspiciousActivity($ip_address, $time_window_minutes = 5) {
    try {
        $pdo = get_pdo();
        
        $sql = "SELECT COUNT(*) as attempts, MIN(timestamp) as first_attempt
                FROM activity_logs 
                WHERE ip_address = ? 
                AND success = 0 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ip_address, $time_window_minutes]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'attempts' => (int)$result['attempts'],
            'first_attempt' => $result['first_attempt'],
            'is_suspicious' => $result['attempts'] >= 3
        ];
        
    } catch (Exception $e) {
        error_log("Failed to check suspicious activity: " . $e->getMessage());
        return ['attempts' => 0, 'is_suspicious' => false];
    }
}
?>
