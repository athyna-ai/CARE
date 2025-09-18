<?php
// Create new admin with your email
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$username = 'admin2';
$email = 'er@gmail.com';
$password = 'admin123';
$rfid = 'ADMIN0002';

try {
    $pdo = get_pdo();
    
    // Hash password and RFID
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $hashedRfid = password_hash($rfid, PASSWORD_DEFAULT);
    
    // Insert new admin
    $stmt = $pdo->prepare('INSERT INTO admin (username, email, password_hash, rfid_hash) VALUES (?, ?, ?, ?)');
    $result = $stmt->execute([$username, $email, $hashedPassword, $hashedRfid]);
    
    if ($result) {
        echo "<h2>New Admin Created Successfully!</h2>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li>Username: $username</li>";
        echo "<li>Email: $email</li>";
        echo "<li>Password: $password</li>";
        echo "<li>RFID: $rfid</li>";
        echo "</ul>";
    } else {
        echo "<p>Failed to create admin</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
