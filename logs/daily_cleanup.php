<?php
/**
 * Daily Cleanup Script
 * This script should be run daily via cron job to automatically clean up old logs
 * 
 * Cron job example (run every day at 2 AM):
 * 0 2 * * * /usr/bin/php /path/to/your/care/daily_cleanup.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Only allow this script to run from command line or with a secret key
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'cleanup_secret_key_2024')) {
    http_response_code(403);
    die('Access denied. This script can only be run from command line or with proper authorization.');
}

try {
    $pdo = get_pdo();
    $today = date('Y-m-d');
    
    echo "Starting daily cleanup for {$today}...\n";
    
    // 1. Archive today's logs first
    echo "Archiving today's logs...\n";
    
    // Get today's activity logs
    $activityLogs = $pdo->prepare("SELECT * FROM activity_logs WHERE DATE(timestamp) = ?");
    $activityLogs->execute([$today]);
    $activityData = $activityLogs->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's visitation logs
    $visitationLogs = $pdo->prepare("SELECT * FROM visitation_logs WHERE DATE(visit_date) = ?");
    $visitationLogs->execute([$today]);
    $visitationData = $visitationLogs->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($activityData) || !empty($visitationData)) {
        // Create daily_logs table if it doesn't exist
        $pdo->exec('CREATE TABLE IF NOT EXISTS daily_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_date DATE NOT NULL,
            activity_logs_data JSON NOT NULL,
            visitation_logs_data JSON NOT NULL,
            total_activities INT NOT NULL DEFAULT 0,
            total_visitations INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_log_date (log_date)
        ) ENGINE=InnoDB');
        
        // Insert into daily_logs
        $insertDaily = $pdo->prepare("
            INSERT INTO daily_logs (log_date, activity_logs_data, visitation_logs_data, total_activities, total_visitations, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insertDaily->execute([
            $today,
            json_encode($activityData),
            json_encode($visitationData),
            count($activityData),
            count($visitationData)
        ]);
        
        echo "Archived " . count($activityData) . " activity logs and " . count($visitationData) . " visitation logs.\n";
    } else {
        echo "No logs found for today to archive.\n";
    }
    
    // 2. Clean up old logs (older than today)
    echo "Cleaning up old logs...\n";
    
    // Count logs before cleanup
    $activityCount = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(timestamp) < '$today'")->fetchColumn();
    $visitationCount = $pdo->query("SELECT COUNT(*) FROM visitation_logs WHERE DATE(visit_date) < '$today'")->fetchColumn();
    
    // Clear old logs
    $pdo->prepare("DELETE FROM activity_logs WHERE DATE(timestamp) < ?")->execute([$today]);
    $pdo->prepare("DELETE FROM visitation_logs WHERE DATE(visit_date) < ?")->execute([$today]);
    
    echo "Cleaned up {$activityCount} activity logs and {$visitationCount} visitation logs older than today.\n";
    
    // 3. Log this action (if we can get a user ID)
    try {
        // Try to get the first admin user for logging
        $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        if ($adminUser) {
            log_activity($pdo, (int)$adminUser['id'], 'daily_cleanup', "Daily cleanup completed: archived today's logs, cleaned {$activityCount} activity logs and {$visitationCount} visitation logs", 'system');
        }
    } catch (Exception $e) {
        echo "Warning: Could not log cleanup activity: " . $e->getMessage() . "\n";
    }
    
    // 4. Auto-unblock IPs after 24 hours
    echo "Auto-unblocking IPs after 24 hours...\n";
    $unblockIPs = $pdo->prepare('DELETE FROM activity_logs WHERE action = "ip_blocked" AND timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $unblockIPs->execute();
    $ipsUnblocked = $unblockIPs->rowCount();
    
    if ($ipsUnblocked > 0) {
        echo "Auto-unblocked {$ipsUnblocked} IPs after 24 hours\n";
        // Log this action
        try {
            $adminUser = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
            if ($adminUser) {
                log_activity($pdo, (int)$adminUser['id'], 'ip_auto_unblocked', "Auto-unblocked {$ipsUnblocked} IPs after 24 hours", 'daily_cleanup');
            }
        } catch (Exception $e) {
            echo "Warning: Could not log IP unblock activity: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No IPs to unblock.\n";
    }
    
    // 5. Clean up old daily_logs (keep only last 30 days)
    echo "Cleaning up old daily archives...\n";
    $oldArchiveDate = date('Y-m-d', strtotime('-30 days'));
    $deletedArchives = $pdo->prepare("DELETE FROM daily_logs WHERE log_date < ?");
    $deletedArchives->execute([$oldArchiveDate]);
    $deletedCount = $deletedArchives->rowCount();
    echo "Cleaned up {$deletedCount} old daily archive records.\n";
    
    echo "Daily cleanup completed successfully!\n";
    echo "Summary:\n";
    echo "- Archived today's logs: " . count($activityData) . " activity, " . count($visitationData) . " visitation\n";
    echo "- Cleaned old logs: {$activityCount} activity, {$visitationCount} visitation\n";
    echo "- Auto-unblocked IPs: {$ipsUnblocked} IPs\n";
    echo "- Cleaned old archives: {$deletedCount} daily archive records\n";
    
} catch (Throwable $e) {
    echo "Error during daily cleanup: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
