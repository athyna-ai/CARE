<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>RFID Debug Information</h2>";

try {
    $pdo = get_pdo();
    
    // Get user info
    $stmt = $pdo->prepare('SELECT id, name, email, rfid FROM users WHERE id = 4');
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h3>User Information:</h3>";
        echo "<p>ID: " . $user['id'] . "</p>";
        echo "<p>Name: " . $user['name'] . "</p>";
        echo "<p>Email: " . $user['email'] . "</p>";
        echo "<p>RFID: " . ($user['rfid'] ?: 'NULL') . "</p>";
        echo "<p>RFID Length: " . strlen($user['rfid'] ?: '') . "</p>";
        echo "<p>Is Hashed: " . (strpos($user['rfid'], '$2y$') === 0 ? 'YES' : 'NO') . "</p>";
        
        // Test with 3546657229
        $testRfid = '3546657229';
        echo "<h3>Verification Test:</h3>";
        echo "<p>Testing RFID: $testRfid</p>";
        
        if ($user['rfid']) {
            $verifyResult = password_verify($testRfid, $user['rfid']);
            echo "<p>Password Verify Result: " . ($verifyResult ? 'SUCCESS' : 'FAILED') . "</p>";
            
            // Create fresh hash and test
            $freshHash = password_hash($testRfid, PASSWORD_DEFAULT);
            $freshVerify = password_verify($testRfid, $freshHash);
            echo "<p>Fresh Hash Test: " . ($freshVerify ? 'SUCCESS' : 'FAILED') . "</p>";
            echo "<p>Fresh Hash: " . $freshHash . "</p>";
        }
        
        // Update RFID if needed
        echo "<h3>Update RFID:</h3>";
        $newHash = password_hash($testRfid, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ?');
        $updateResult = $updateStmt->execute([$newHash, $user['id']]);
        
        if ($updateResult) {
            echo "<p style='color: green;'>✅ RFID updated successfully!</p>";
            
            // Test the updated RFID
            $testVerify = password_verify($testRfid, $newHash);
            echo "<p>Updated RFID Verification: " . ($testVerify ? 'SUCCESS' : 'FAILED') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update RFID</p>";
        }
        
    } else {
        echo "<p style='color: red;'>User not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
