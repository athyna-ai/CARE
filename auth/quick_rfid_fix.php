<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Quick RFID Fix</h2>";

try {
    $pdo = get_pdo();
    
    // Fix RFID for Tester user
    $rfid = 'samplerfid';
    $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
    
    $updateStmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE email = ? AND is_admin = 1');
    $result = $updateStmt->execute([$rfidHash, 'sampleadmin@care.com']);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>✅ RFID Fixed Successfully!</h3>";
        echo "<p><strong>User:</strong> Tester (sampleadmin@care.com)</p>";
        echo "<p><strong>RFID:</strong> $rfid</p>";
        echo "<p><strong>Status:</strong> Properly hashed and stored</p>";
        echo "<p><strong>Verification Test:</strong> " . (password_verify($rfid, $rfidHash) ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        echo "</div>";
        
        // Test the verification
        $testStmt = $pdo->prepare('SELECT rfid FROM users WHERE email = ? AND is_admin = 1');
        $testStmt->execute(['sampleadmin@care.com']);
        $storedRfid = $testStmt->fetchColumn();
        
        if ($storedRfid) {
            $testVerify = password_verify($rfid, $storedRfid);
            echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h3>🔍 Database Verification Test</h3>";
            echo "<p><strong>Stored Hash:</strong> " . substr($storedRfid, 0, 30) . "...</p>";
            echo "<p><strong>Test Result:</strong> " . ($testVerify ? '✅ RFID VERIFIES CORRECTLY' : '❌ STILL FAILING') . "</p>";
            echo "</div>";
        }
        
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>📋 Next Steps</h3>";
        echo "<ol>";
        echo "<li>Go to your <a href='login.php' target='_blank'>login page</a></li>";
        echo "<li>Enter username: <strong>Tester</strong> or email: <strong>sampleadmin@care.com</strong></li>";
        echo "<li>Enter your password</li>";
        echo "<li>When prompted for RFID, enter: <strong>$rfid</strong></li>";
        echo "<li>You should now be logged in successfully!</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>❌ Failed to fix RFID</h3>";
        echo "<p>Please check your database connection.</p>";
        echo "</div>";
    }
    
    // Show current user data
    echo "<h3>Current User Data</h3>";
    $userStmt = $pdo->prepare('SELECT id, name, email, rfid FROM users WHERE email = ? AND is_admin = 1');
    $userStmt->execute(['sampleadmin@care.com']);
    $user = $userStmt->fetch();
    
    if ($user) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>RFID Status</th></tr>";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . ($user['rfid'] ? 'Set (' . (strpos($user['rfid'], '$2y$') === 0 ? 'Hashed' : 'Plain Text') . ')' : 'Not Set') . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>User not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
