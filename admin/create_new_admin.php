<?php
// Create new admin with your email - SECURITY PROTECTED
require_once __DIR__ . '/../security_breach_detector.php';

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

$username = 'admin2';
$email = 'er@gmail.com';
$password = 'admin123';
$rfid = 'ADMIN0002';

try {
    $pdo = get_pdo();
    
    // Hash password only (RFID is stored as plain text in users table)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, rfid, is_admin) VALUES (?, ?, ?, ?, 1)');
    $result = $stmt->execute([$username, $email, $hashedPassword, $rfid]);
    
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
    echo "<p>An error occurred. Please try again.</p>";
}
?>
