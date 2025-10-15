<?php
/**
 * Webhook endpoint for automatic deployment to olshacare.com
 * This file handles webhook requests from GitHub for automatic updates
 */

// Security: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Security: Verify webhook signature
function verifyWebhookSignature($payload, $signature, $secret) {
    if (empty($signature) || empty($secret)) {
        return false;
    }
    
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Configuration
$config = [
    'webhook_secret' => 'olsh_care_webhook_secret_2024_secure', // CHANGE THIS!
    'allowed_branches' => ['main', 'master', 'production'],
    'domain' => 'olshacare.com',
    'log_file' => __DIR__ . '/logs/webhook_deployment.log',
    'notifications' => [
        'email' => 'admin@olshacare.com',
        'enabled' => true
    ]
];

// Ensure log directory exists
$logDir = dirname($config['log_file']);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Logging function
function logMessage($message, $level = 'INFO') {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
    echo $logMessage;
}

// Send email notification
function sendNotification($subject, $message, $type = 'info') {
    global $config;
    
    if (!$config['notifications']['enabled']) {
        return;
    }
    
    $to = $config['notifications']['email'];
    $headers = "From: noreply@olshacare.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $fullMessage = "CARE System Deployment Notification\n\n";
    $fullMessage .= "Status: " . strtoupper($type) . "\n";
    $fullMessage .= "Domain: olshacare.com\n";
    $fullMessage .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
    $fullMessage .= "Details:\n" . $message;
    
    mail($to, $subject, $fullMessage, $headers);
}

try {
    logMessage("Webhook received for olshacare.com");
    
    // Get the raw payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    // Verify webhook signature
    if (!verifyWebhookSignature($payload, $signature, $config['webhook_secret'])) {
        logMessage("Invalid webhook signature", 'ERROR');
        http_response_code(401);
        die('Unauthorized');
    }
    
    // Parse the payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        logMessage("Invalid JSON payload", 'ERROR');
        http_response_code(400);
        die('Invalid payload');
    }
    
    // Check if it's a push event
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    if ($event !== 'push') {
        logMessage("Not a push event: $event", 'INFO');
        echo json_encode(['status' => 'skipped', 'reason' => 'Not a push event']);
        exit;
    }
    
    // Check branch
    $branch = $data['ref'] ?? '';
    $branch = str_replace('refs/heads/', '', $branch);
    
    if (!in_array($branch, $config['allowed_branches'])) {
        logMessage("Branch '$branch' not in allowed branches", 'INFO');
        echo json_encode(['status' => 'skipped', 'reason' => 'Branch not allowed']);
        exit;
    }
    
    // Check if there are commits
    $commits = $data['commits'] ?? [];
    if (empty($commits)) {
        logMessage("No commits found", 'INFO');
        echo json_encode(['status' => 'skipped', 'reason' => 'No commits']);
        exit;
    }
    
    // Check if there are relevant changes
    $hasRelevantChanges = false;
    foreach ($commits as $commit) {
        $files = array_merge(
            $commit['added'] ?? [],
            $commit['modified'] ?? [],
            $commit['removed'] ?? []
        );
        
        foreach ($files as $file) {
            // Skip deployment files and logs
            if (strpos($file, 'deploy') !== false || 
                strpos($file, 'log') !== false ||
                strpos($file, '.git') !== false ||
                strpos($file, 'README') !== false) {
                continue;
            }
            
            $hasRelevantChanges = true;
            break 2;
        }
    }
    
    if (!$hasRelevantChanges) {
        logMessage("No relevant changes found", 'INFO');
        echo json_encode(['status' => 'skipped', 'reason' => 'No relevant changes']);
        exit;
    }
    
    // Log deployment details
    $commitCount = count($commits);
    $lastCommit = $commits[0] ?? [];
    $commitMessage = $lastCommit['message'] ?? 'No message';
    
    logMessage("Starting deployment for branch: $branch");
    logMessage("Commits: $commitCount");
    logMessage("Last commit: $commitMessage");
    
    // Since GitHub Actions handles the actual deployment,
    // we just log and notify here
    logMessage("Deployment triggered successfully");
    
    // Send success notification
    sendNotification(
        "CARE System Deployment Triggered",
        "Deployment has been triggered for olshacare.com\n\nBranch: $branch\nCommits: $commitCount\nLast commit: $commitMessage",
        'success'
    );
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment triggered',
        'branch' => $branch,
        'commits' => $commitCount,
        'domain' => $config['domain']
    ]);
    
} catch (Exception $e) {
    logMessage("Webhook processing failed: " . $e->getMessage(), 'ERROR');
    sendNotification(
        "CARE System Deployment Error",
        "Webhook processing failed: " . $e->getMessage(),
        'error'
    );
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
