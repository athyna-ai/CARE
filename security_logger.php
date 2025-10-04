<?php
/**
 * Security Logger
 * 
 * Handles logging of security events that are blocked by .htaccess
 * This file is called when .htaccess redirects blocked requests
 */

require_once __DIR__ . '/core/security_logging.php';

// Get the security event details from URL parameters
$event_type = $_GET['type'] ?? 'UNKNOWN_ATTACK';
$url = $_GET['url'] ?? $_SERVER['REQUEST_URI'];
$query = $_GET['query'] ?? $_SERVER['QUERY_STRING'];

// Map event types to proper descriptions
$event_descriptions = [
    'sql_injection' => 'SQL Injection attempt blocked by .htaccess',
    'xss' => 'XSS attack attempt blocked by .htaccess',
    'directory_traversal' => 'Directory traversal attempt blocked by .htaccess'
];

$description = $event_descriptions[$event_type] ?? 'Security attack attempt blocked by .htaccess';

// Additional data
$additional_data = [
    'blocked_url' => $url,
    'malicious_query' => $query,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'blocked_by' => '.htaccess'
];

// Log the security breach
logSecurityBreach($event_type, $description, $additional_data);

// Show 403 Forbidden page
http_response_code(403);
?>
<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .error-container {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 50px auto;
        }
        .error-code {
            font-size: 72px;
            color: #dc3545;
            margin: 0;
            font-weight: bold;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin: 20px 0;
        }
        .error-message {
            color: #666;
            margin: 20px 0;
        }
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .back-link {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-link:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-title">Forbidden</div>
        <div class="error-message">
            You don't have permission to access this resource.
        </div>
        <div class="security-notice">
            <strong>Security Notice:</strong> Your request has been blocked due to suspicious activity. 
            This incident has been logged for security purposes.
        </div>
        <a href="/Care/" class="back-link">← Return to Safe Area</a>
    </div>
</body>
</html>
