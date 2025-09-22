<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

$rfid = '3546657229';
$user_id = 4; // Tunapay's ID

try {
    $pdo = get_pdo();
    
    // Create new hash for the RFID
    $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
    
    echo "<h2>Direct RFID Update</h2>";
    echo "<p>Updating RFID for User ID: $user_id</p>";
    echo "<p>New RFID: $rfid</p>";
    echo "<p>New Hash: $rfidHash</p>";
    
    // Update the RFID
    $stmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ? AND is_admin = 1');
    $result = $stmt->execute([$rfidHash, $user_id]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ RFID updated successfully!</p>";
        
        // Test verification
        $testVerify = password_verify($rfid, $rfidHash);
        echo "<p>Verification test: " . ($testVerify ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        // Verify in database
        $checkStmt = $pdo->prepare('SELECT rfid FROM users WHERE id = ?');
        $checkStmt->execute([$user_id]);
        $storedRfid = $checkStmt->fetchColumn();
        
        echo "<p>Stored in DB: " . substr($storedRfid, 0, 20) . "...</p>";
        
        $dbVerify = password_verify($rfid, $storedRfid);
        echo "<p>Database verification: " . ($dbVerify ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to update RFID</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
