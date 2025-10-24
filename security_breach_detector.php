<?php
/**
 * Security Breach Detector
 * 
 * Automatically detects and logs security breach attempts
 * Include this in admin files to log unauthorized access
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security logging
require_once __DIR__ . '/core/security_logging.php';

// Check if this is a breach attempt
function detectBreachAttempt() {
    $breach_detected = false;
    $breach_type = '';
    $breach_description = '';
    $additional_data = [];
    
    // Check for admin directory breach (only for sensitive files)
    $sensitive_admin_files = ['run_database_update.php', 'clear_failed_attempts.php', 'export_security_report.php'];
    
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        $requested_file = basename($_SERVER['REQUEST_URI']);
        
        // Only treat as breach if it's a sensitive admin file
        if (in_array($requested_file, $sensitive_admin_files)) {
            $breach_detected = true;
            $breach_type = 'ADMIN_DIRECTORY_BREACH_ATTEMPT';
            $breach_description = 'Direct unauthorized access attempt to admin directory';
            $additional_data = [
                'admin_file' => $requested_file,
                'full_url' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD']
            ];
        }
    }
    
    // Check for SQL injection patterns in URL
    $query_string = $_SERVER['QUERY_STRING'] ?? '';
    $sql_patterns = [
        "' OR '1'='1",
        "'; DROP TABLE",
        "UNION SELECT",
        "INSERT INTO",
        "DELETE FROM",
        "' OR 1=1--",
        "%27%20OR%201%3D1"
    ];
    
    foreach ($sql_patterns as $pattern) {
        if (stripos($query_string, $pattern) !== false) {
            $breach_detected = true;
            $breach_type = 'SQL_INJECTION_ATTEMPT';
            $breach_description = 'SQL injection attack pattern detected in URL parameters';
            $additional_data = [
                'malicious_pattern' => $pattern,
                'query_string' => $query_string,
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'Direct access'
            ];
            break;
        }
    }
    
    // Check for directory traversal
    if (strpos($query_string, '../') !== false || strpos($query_string, '..\\') !== false) {
        $breach_detected = true;
        $breach_type = 'DIRECTORY_TRAVERSAL_ATTEMPT';
        $breach_description = 'Directory traversal attack attempt detected';
        $additional_data = [
            'attempted_path' => $query_string,
            'file' => $_GET['file'] ?? $_GET['path'] ?? 'Unknown'
        ];
    }
    
    // Check for XSS attempts
    $xss_patterns = ['<script>', 'javascript:', 'onload=', 'onerror=', 'onclick='];
    foreach ($xss_patterns as $pattern) {
        if (stripos($query_string, $pattern) !== false) {
            $breach_detected = true;
            $breach_type = 'XSS_ATTEMPT';
            $breach_description = 'Cross-site scripting attack attempt detected';
            $additional_data = [
                'malicious_pattern' => $pattern,
                'query_string' => $query_string
            ];
            break;
        }
    }
    
    // Check for unauthorized file access patterns
    $protected_extensions = ['.sql', '.config', '.env', '.htaccess', '.bak', '.backup'];
    $current_file = $_SERVER['REQUEST_URI'];
    foreach ($protected_extensions as $ext) {
        if (strpos($current_file, $ext) !== false) {
            $breach_detected = true;
            $breach_type = 'UNAUTHORIZED_FILE_ACCESS_ATTEMPT';
            $breach_description = 'Attempted access to protected file type';
            $additional_data = [
                'protected_extension' => $ext,
                'file_path' => $current_file,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ];
            break;
        }
    }
    
    // Log the breach if detected
    if ($breach_detected) {
        logSecurityBreach($breach_type, $breach_description, $additional_data);
        
        // Return HTTP 403 and show error page
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>403 Forbidden - Security Breach Detected</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background: linear-gradient(135deg, #e74c3c, #c0392b);
                    color: white; 
                    text-align: center; 
                    padding: 50px; 
                }
                .alert-box {
                    background: rgba(255,255,255,0.1);
                    border-radius: 15px;
                    padding: 30px;
                    max-width: 600px;
                    margin: 0 auto;
                    backdrop-filter: blur(10px);
                }
            </style>
        </head>
        <body>
            <div class="alert-box">
                <h1>🚨 Security Breach Detected</h1>
                <p><strong>Unauthorized access attempt has been detected and logged.</strong></p>
                <p>Your IP address and activity have been recorded for security purposes.</p>
                <p><small>Timestamp: <?= date('Y-m-d H:i:s') ?></small></p>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid rgba(255,255,255,0.3);">
                <p><a href="/Care/index.php" style="color: white; text-decoration: underline;">← Return to Safe Area</a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    return $breach_detected;
}

// Run breach detection on page load (only for unauthenticated users)
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    detectBreachAttempt();
}
?>
