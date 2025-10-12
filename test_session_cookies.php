<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

echo "<h1>🍪 Session Cookie Test</h1>";

// Check session cookie settings
echo "<h2>📊 Session Cookie Information</h2>";
$cookieParams = session_get_cookie_params();
echo "<p><strong>Cookie Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Cookie Lifetime:</strong> " . $cookieParams['lifetime'] . " seconds</p>";
echo "<p><strong>Cookie Path:</strong> " . $cookieParams['path'] . "</p>";
echo "<p><strong>Cookie Domain:</strong> " . $cookieParams['domain'] . "</p>";
echo "<p><strong>Cookie Secure:</strong> " . ($cookieParams['secure'] ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Cookie HttpOnly:</strong> " . ($cookieParams['httponly'] ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Cookie SameSite:</strong> " . $cookieParams['samesite'] . "</p>";

// Check if session cookie exists
echo "<h2>🍪 Browser Cookie Check</h2>";
if (isset($_COOKIE[session_name()])) {
    echo "<p style='color: green;'>✅ Session cookie exists: " . htmlspecialchars($_COOKIE[session_name()]) . "</p>";
} else {
    echo "<p style='color: red;'>❌ No session cookie found</p>";
}

// Test session persistence
echo "<h2>🔄 Session Persistence Test</h2>";
if (!isset($_SESSION['persistence_test'])) {
    $_SESSION['persistence_test'] = [
        'created' => time(),
        'counter' => 0,
        'random' => rand(1000, 9999)
    ];
}

$_SESSION['persistence_test']['counter']++;
$_SESSION['persistence_test']['last_access'] = time();

echo "<p><strong>Session Data:</strong></p>";
echo "<pre>" . print_r($_SESSION['persistence_test'], true) . "</pre>";

echo "<p><em>Refresh this page to see if the counter increments and data persists</em></p>";

// Test notification read status persistence
echo "<h2>🔔 Notification Read Status Test</h2>";
if (!isset($_SESSION['test_notification_read'])) {
    $_SESSION['test_notification_read'] = false;
    echo "<p style='color: orange;'>⚠️ Test notification not marked as read yet</p>";
} else {
    echo "<p style='color: green;'>✅ Test notification marked as read</p>";
}

if (isset($_POST['mark_test_read'])) {
    $_SESSION['test_notification_read'] = true;
    echo "<p style='color: green;'>✅ Test notification marked as read!</p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
} else {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='mark_test_read' value='1'>";
    echo "<button type='submit' style='padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px;'>Mark Test Notification as Read</button>";
    echo "</form>";
}

echo "<br><br><a href='debug_notifications.php' style='padding: 10px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;'>🔄 Back to Debug Page</a>";
?>
