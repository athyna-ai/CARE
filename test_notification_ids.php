<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

echo "<h1>🔬 Notification ID Consistency Test</h1>";

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get the same notification data that would be used in get_notifications.php
    $securityAlerts = $pdo->query("
        SELECT 
            CONCAT(action, ' from IP ', ip_address, ' - ', description) as message,
            timestamp
        FROM activity_logs 
        WHERE (success = 0 AND user_type = 'system') 
            OR action IN ('login_failed', 'SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach', 'Unauthorized Admin Access', 'Unauthorized Medical Access')
            AND timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
        ORDER BY timestamp DESC
        LIMIT 5
    ")->fetchAll();
    
    echo "<h2>📋 Notification ID Generation Test</h2>";
    
    foreach ($securityAlerts as $i => $alert) {
        $notificationId = 'security_' . md5($alert['message'] . $alert['timestamp']);
        
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9;'>";
        echo "<h3>Notification " . ($i + 1) . "</h3>";
        echo "<p><strong>Raw Message:</strong> " . htmlspecialchars($alert['message']) . "</p>";
        echo "<p><strong>Raw Timestamp:</strong> " . htmlspecialchars($alert['timestamp']) . "</p>";
        echo "<p><strong>Concatenated String:</strong> " . htmlspecialchars($alert['message'] . $alert['timestamp']) . "</p>";
        echo "<p><strong>MD5 Hash:</strong> " . md5($alert['message'] . $alert['timestamp']) . "</p>";
        echo "<p><strong>Final Notification ID:</strong> " . htmlspecialchars($notificationId) . "</p>";
        
        // Check if this ID is in the session
        $isRead = isset($_SESSION['read_notifications']) && in_array($notificationId, $_SESSION['read_notifications']);
        echo "<p><strong>Is Read:</strong> " . ($isRead ? "✅ YES" : "❌ NO") . "</p>";
        
        // Test marking as read
        if (isset($_POST['mark_read_' . $i])) {
            if (!isset($_SESSION['read_notifications'])) {
                $_SESSION['read_notifications'] = [];
            }
            if (!in_array($notificationId, $_SESSION['read_notifications'])) {
                $_SESSION['read_notifications'][] = $notificationId;
                echo "<p style='color: green;'>✅ Marked as read!</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ Already marked as read</p>";
            }
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        } else {
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='mark_read_" . $i . "' value='1'>";
            echo "<button type='submit' style='padding: 5px 10px; background: #007cba; color: white; border: none; border-radius: 3px;'>Mark as Read</button>";
            echo "</form>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='debug_notifications.php'>🔄 Back to Debug Page</a>";
?>
