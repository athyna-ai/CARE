<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

echo "<h1>🔍 Comprehensive Notification Debug</h1>";

// Check session status
echo "<h2>📊 Session Information</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    exit;
}

echo "<p style='color: green;'>✅ User logged in: " . htmlspecialchars($_SESSION['user']['name']) . " (ID: " . $_SESSION['user']['id'] . ")</p>";

// Check all session data
echo "<h2>📋 All Session Data</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Check read notifications specifically
echo "<h2>🔔 Read Notifications Status</h2>";
if (isset($_SESSION['read_notifications'])) {
    echo "<p style='color: green;'>✅ Read notifications array exists</p>";
    echo "<p><strong>Count:</strong> " . count($_SESSION['read_notifications']) . "</p>";
    echo "<p><strong>Contents:</strong></p>";
    echo "<ul>";
    foreach ($_SESSION['read_notifications'] as $i => $readId) {
        echo "<li>" . ($i + 1) . ". " . htmlspecialchars($readId) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ No read notifications array in session</p>";
}

// Test notification ID generation and matching
echo "<h2>🧪 Notification ID Test</h2>";
try {
    $pdo = get_pdo();
    
    // Get a sample notification
    $sampleAlert = $pdo->query("
        SELECT 
            CONCAT(action, ' from IP ', ip_address, ' - ', description) as message,
            timestamp
        FROM activity_logs 
        WHERE (success = 0 AND user_type = 'system') 
            OR action IN ('login_failed', 'SQL Injection Blocked', 'XSS Attack Blocked', 'Directory Traversal Blocked', 'Admin Directory Breach', 'Unauthorized Admin Access', 'Unauthorized Medical Access')
            AND timestamp > DATE_SUB(NOW(), INTERVAL 90 DAY)
        ORDER BY timestamp DESC
        LIMIT 3
    ")->fetchAll();
    
    if ($sampleAlert) {
        echo "<p><strong>Sample Notifications:</strong></p>";
        foreach ($sampleAlert as $i => $alert) {
            $testId = 'security_' . md5($alert['message'] . $alert['timestamp']);
            $isRead = isset($_SESSION['read_notifications']) && in_array($testId, $_SESSION['read_notifications']);
            
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Notification " . ($i + 1) . ":</strong></p>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars(substr($alert['message'], 0, 80)) . "...</p>";
            echo "<p><strong>Timestamp:</strong> " . htmlspecialchars($alert['timestamp']) . "</p>";
            echo "<p><strong>Generated ID:</strong> " . htmlspecialchars($testId) . "</p>";
            echo "<p><strong>Is Read:</strong> " . ($isRead ? "✅ YES" : "❌ NO") . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No sample notifications found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test session persistence
echo "<h2>🔄 Session Persistence Test</h2>";
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo "<p><strong>Test Counter:</strong> " . $_SESSION['test_counter'] . "</p>";
echo "<p><em>This should increment each time you refresh the page</em></p>";

// Test API endpoints
echo "<h2>🌐 API Endpoint Tests</h2>";
echo "<button onclick='testGetNotifications()' style='margin: 5px; padding: 10px;'>Test Get Notifications API</button>";
echo "<button onclick='testMarkRead()' style='margin: 5px; padding: 10px;'>Test Mark as Read API</button>";
echo "<button onclick='testMarkAllRead()' style='margin: 5px; padding: 10px;'>Test Mark All as Read API</button>";
echo "<div id='api-results' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;'></div>";

echo "<script>
async function testGetNotifications() {
    try {
        const response = await fetch('reports/get_notifications.php');
        const data = await response.json();
        document.getElementById('api-results').innerHTML = '<h3>📥 Get Notifications Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = '<p style=\"color: red;\">❌ Error: ' + error.message + '</p>';
    }
}

async function testMarkRead() {
    try {
        const response = await fetch('reports/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: 'test_notification_' + Date.now()
            })
        });
        const data = await response.json();
        document.getElementById('api-results').innerHTML = '<h3>✅ Mark Read Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = '<p style=\"color: red;\">❌ Error: ' + error.message + '</p>';
    }
}

async function testMarkAllRead() {
    try {
        const response = await fetch('reports/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        });
        const data = await response.json();
        document.getElementById('api-results').innerHTML = '<h3>✅ Mark All Read Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = '<p style=\"color: red;\">❌ Error: ' + error.message + '</p>';
    }
}
</script>";

echo "<br><br><a href='debug_notifications.php' style='padding: 10px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;'>🔄 Refresh Debug Page</a>";
?>