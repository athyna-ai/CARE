<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Complete Database Analysis</h2>";
echo "<p>This will analyze your Hostinger database to find the RFID issue.</p>";

try {
    $pdo = get_pdo();
    
    // 1. Check database connection and basic info
    echo "<h3>1. Database Connection Info</h3>";
    $config = include __DIR__ . '/../core/config.php';
    echo "<p><strong>Host:</strong> " . htmlspecialchars($config['db_host']) . "</p>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($config['db_name']) . "</p>";
    echo "<p><strong>Charset:</strong> " . htmlspecialchars($config['db_charset']) . "</p>";
    
    // 2. Check if users table exists and its structure
    echo "<h3>2. Users Table Structure</h3>";
    $tableInfo = $pdo->query("DESCRIBE users")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($tableInfo as $field) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Check all admin users with detailed RFID info
    echo "<h3>3. All Admin Users (Raw Data)</h3>";
    $adminStmt = $pdo->prepare('SELECT id, name, email, rfid, LENGTH(rfid) as rfid_length, CHAR_LENGTH(rfid) as char_length FROM users WHERE is_admin = 1');
    $adminStmt->execute();
    $adminUsers = $adminStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>RFID (Raw)</th><th>Byte Length</th><th>Char Length</th><th>Is Hashed?</th><th>First 20 Chars</th></tr>";
    
    foreach ($adminUsers as $user) {
        $isHashed = strpos($user['rfid'], '$2y$') === 0;
        $first20 = $user['rfid'] ? substr($user['rfid'], 0, 20) : 'NULL';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td style='word-break: break-all; max-width: 200px;'>" . htmlspecialchars($user['rfid']) . "</td>";
        echo "<td>" . $user['rfid_length'] . "</td>";
        echo "<td>" . $user['char_length'] . "</td>";
        echo "<td>" . ($isHashed ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($first20) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Test RFID verification for each user
    echo "<h3>4. RFID Verification Tests</h3>";
    $testRfid = 'samplerfid';
    echo "<p><strong>Testing RFID:</strong> " . htmlspecialchars($testRfid) . "</p>";
    
    foreach ($adminUsers as $user) {
        if ($user['rfid']) {
            $matches = false;
            $method = '';
            
            if (strpos($user['rfid'], '$2y$') === 0) {
                $matches = password_verify($testRfid, $user['rfid']);
                $method = 'password_verify()';
            } else {
                $matches = ($user['rfid'] === $testRfid);
                $method = 'direct comparison';
            }
            
            $color = $matches ? 'green' : 'red';
            echo "<div style='color: $color; margin: 10px 0; padding: 10px; border: 1px solid $color; border-radius: 5px;'>";
            echo "<strong>User:</strong> " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";
            echo "<strong>Result:</strong> " . ($matches ? '✅ MATCH' : '❌ NO MATCH') . "<br>";
            echo "<strong>Method:</strong> $method<br>";
            echo "<strong>Stored RFID:</strong> " . htmlspecialchars($user['rfid']) . "<br>";
            echo "<strong>Provided RFID:</strong> " . htmlspecialchars($testRfid);
            echo "</div>";
        }
    }
    
    // 5. Check for any encoding issues
    echo "<h3>5. Encoding Analysis</h3>";
    foreach ($adminUsers as $user) {
        if ($user['rfid']) {
            echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
            echo "<strong>User:</strong> " . htmlspecialchars($user['name']) . "<br>";
            echo "<strong>Raw RFID:</strong> " . bin2hex($user['rfid']) . "<br>";
            echo "<strong>UTF-8 Check:</strong> " . (mb_check_encoding($user['rfid'], 'UTF-8') ? 'Valid' : 'Invalid') . "<br>";
            echo "<strong>Contains NULL bytes:</strong> " . (strpos($user['rfid'], "\0") !== false ? 'YES' : 'NO') . "<br>";
            echo "</div>";
        }
    }
    
    // 6. Generate SQL fix commands
    echo "<h3>6. SQL Fix Commands</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<h4>For Tester user (sampleadmin@care.com):</h4>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
    echo "-- Clear existing RFID\n";
    echo "UPDATE users SET rfid = NULL WHERE email = 'sampleadmin@care.com' AND is_admin = 1;\n\n";
    echo "-- Set new hashed RFID\n";
    echo "UPDATE users SET rfid = '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'sampleadmin@care.com' AND is_admin = 1;\n\n";
    echo "-- Verify the update\n";
    echo "SELECT id, name, email, rfid FROM users WHERE email = 'sampleadmin@care.com' AND is_admin = 1;";
    echo "</pre>";
    echo "</div>";
    
    // 7. Check session data
    echo "<h3>7. Current Session Data</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Data:\n";
    print_r($_SESSION);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
pre { font-family: monospace; font-size: 12px; }
</style>
