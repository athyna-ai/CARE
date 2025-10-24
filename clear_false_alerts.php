<?php
/**
 * Clear False Security Alerts
 * 
 * This script removes false security breach notifications for legitimate admin actions
 * that were incorrectly logged as security breaches.
 */

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

// Check if user is admin
if (!isset($_SESSION['user']) || empty($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    die('Access denied. Admin privileges required.');
}

$pdo = get_pdo();

try {
    // Clear false security breach logs for legitimate admin actions
    $stmt = $pdo->prepare("
        DELETE FROM activity_logs 
        WHERE action IN ('ADMIN_CREATION_ATTEMPT', 'USER_DELETION_ATTEMPT') 
        AND user_type = 'system' 
        AND success = 0
        AND description LIKE '%Admin creation attempt in settings%'
        OR description LIKE '%User deletion attempt in settings%'
    ");
    
    $result = $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo "<h2>False Security Alerts Cleared</h2>";
    echo "<p>Successfully removed {$deletedCount} false security breach notifications for legitimate admin actions.</p>";
    echo "<p>These were admin creation and user deletion attempts that were incorrectly flagged as security breaches.</p>";
    echo "<p><a href='admin/settings.php'>← Back to Admin Settings</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Failed to clear false alerts: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>