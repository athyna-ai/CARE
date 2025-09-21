<?php
// Debug login issues
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Debug Login Issues</h2>";

try {
    $pdo = get_pdo();
    echo "<p>✓ Database connected</p>";
    
    // Check what's in the admin table
    $stmt = $pdo->query('SELECT id, username, email, password_hash, rfid_hash FROM admin');
    $users = $stmt->fetchAll();
    
    echo "<h3>Admin Users in Database:</h3>";
    foreach ($users as $user) {
        echo "<p>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Password Hash: " . substr($user['password_hash'], 0, 20) . "...<br>";
        echo "RFID Hash: " . (!empty($user['rfid_hash']) ? substr($user['rfid_hash'], 0, 20) . "..." : "NULL") . "<br>";
        echo "</p>";
    }
    
    // Test password verification
    if (!empty($users)) {
        $user = $users[0];
        echo "<h3>Testing Password Verification:</h3>";
        
        // Test with common passwords
        $testPasswords = ['admin', 'password', '123456', 'admin123', 'care123'];
        foreach ($testPasswords as $testPass) {
            $isValid = password_verify($testPass, $user['password_hash']);
            echo "<p>Password '$testPass': " . ($isValid ? "✓ VALID" : "✗ Invalid") . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}
?>
