<?php
// Reset admin password
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

$newPassword = 'admin123'; // Change this to your desired password

try {
    $pdo = get_pdo();
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the admin password
    $stmt = $pdo->prepare('UPDATE admin SET password_hash = ? WHERE username = ?');
    $result = $stmt->execute([$hashedPassword, 'admin']);
    
    if ($result) {
        echo "<h2>Password Reset Successful!</h2>";
        echo "<p>New password: <strong>$newPassword</strong></p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li>Username: admin</li>";
        echo "<li>Password: $newPassword</li>";
        echo "<li>RFID: ADMIN0001</li>";
        echo "</ul>";
    } else {
        echo "<p>Failed to reset password</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
