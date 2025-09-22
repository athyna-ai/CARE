<?php
// Simple test to verify RFID hashing
$testRfid = '3546657229';

echo "<h2>RFID Hash Test</h2>";
echo "<p>Original RFID: " . $testRfid . "</p>";

// Create a hash
$hash = password_hash($testRfid, PASSWORD_DEFAULT);
echo "<p>Hashed RFID: " . $hash . "</p>";

// Test verification
$verifyResult = password_verify($testRfid, $hash);
echo "<p>Verification result: " . ($verifyResult ? 'SUCCESS' : 'FAILED') . "</p>";

// Test with wrong RFID
$wrongRfid = '1234567890';
$wrongResult = password_verify($wrongRfid, $hash);
echo "<p>Wrong RFID verification: " . ($wrongResult ? 'SUCCESS' : 'FAILED') . "</p>";

echo "<h3>Test Complete</h3>";
?>
