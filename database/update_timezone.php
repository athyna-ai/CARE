<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Database Timezone Update</h2>";
echo "<p>Updating Hostinger database timezone to Asia/Manila...</p>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check current timezone settings
    echo "<h3>Current Timezone Settings:</h3>";
    
    $stmt = $pdo->query("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz");
    $timezoneInfo = $stmt->fetch();
    
    echo "<p><strong>Global Timezone:</strong> " . htmlspecialchars($timezoneInfo['global_tz']) . "</p>";
    echo "<p><strong>Session Timezone:</strong> " . htmlspecialchars($timezoneInfo['session_tz']) . "</p>";
    
    // Update session timezone to Asia/Manila
    echo "<h3>Updating Session Timezone...</h3>";
    $pdo->exec("SET time_zone = '+08:00'");
    echo "<p style='color: green;'>✓ Session timezone updated to +08:00 (Asia/Manila)</p>";
    
    // Verify the change
    $stmt = $pdo->query("SELECT @@session.time_zone as session_tz, NOW() as current_time");
    $verifyInfo = $stmt->fetch();
    
    echo "<h3>Verification:</h3>";
    echo "<p><strong>New Session Timezone:</strong> " . htmlspecialchars($verifyInfo['session_tz']) . "</p>";
    echo "<p><strong>Current Database Time:</strong> " . htmlspecialchars($verifyInfo['current_time']) . "</p>";
    
    // Test with a sample timestamp
    echo "<h3>Testing Timestamp Storage:</h3>";
    $testTime = date('Y-m-d H:i:s');
    echo "<p><strong>PHP Current Time (Asia/Manila):</strong> " . htmlspecialchars($testTime) . "</p>";
    
    $stmt = $pdo->query("SELECT NOW() as db_time, UTC_TIMESTAMP() as utc_time");
    $timeTest = $stmt->fetch();
    echo "<p><strong>Database Current Time:</strong> " . htmlspecialchars($timeTest['db_time']) . "</p>";
    echo "<p><strong>Database UTC Time:</strong> " . htmlspecialchars($timeTest['utc_time']) . "</p>";
    
    // Update the PDO connection to always use Asia/Manila timezone
    echo "<h3>Updating PDO Connection Settings...</h3>";
    
    // Add timezone setting to the PDO connection
    $pdo->exec("SET time_zone = '+08:00'");
    
    echo "<p style='color: green;'>✓ PDO connection timezone updated</p>";
    
    // Create a function to ensure timezone is set on every connection
    echo "<h3>Creating Timezone Helper Function...</h3>";
    
    $timezoneHelper = "
// Add this to your core/config.php file in the get_pdo() function:
// After creating the PDO connection, add:
// \$pdo->exec(\"SET time_zone = '+08:00'\");
";
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<h4>Recommended Code Addition:</h4>";
    echo "<pre>" . htmlspecialchars($timezoneHelper) . "</pre>";
    echo "</div>";
    
    echo "<h3 style='color: green;'>✓ Timezone update complete!</h3>";
    echo "<p><strong>Note:</strong> The session timezone has been updated. For permanent changes, you may need to:</p>";
    echo "<ul>";
    echo "<li>Contact Hostinger support to change the global MySQL timezone</li>";
    echo "<li>Or ensure your application sets the timezone on every database connection</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border-left: 4px solid #2196F3;">
    <h4>Next Steps:</h4>
    <ol>
        <li><strong>Update core/config.php:</strong> Modify the get_pdo() function to always set timezone</li>
        <li><strong>Test your application:</strong> Check if timestamps are now showing in Manila time</li>
        <li><strong>Contact Hostinger:</strong> If you need global timezone changes, contact their support</li>
    </ol>
</div>

<p><a href="../admin/dashboard.php">Go to Dashboard</a> | <a href="setup_database.php">Database Setup</a></p>
