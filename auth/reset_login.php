<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Clear any existing session
session_start();
session_destroy();
session_start();

echo "<h2>Login Reset</h2>";
echo "<p>Session cleared. Please try logging in again.</p>";

try {
    $pdo = get_pdo();
    
    // Check if we have any admin users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "<p style='color: orange;'>⚠️ No admin users found. Creating default admin account...</p>";
        
        $adminPassword = 'admin123';
        $adminRfid = '1234567890';
        $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        // Store RFID as hashed to match existing database structure
        $rfidHash = password_hash($adminRfid, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, rfid, is_admin, verified) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute(['Admin', 'admin@care.com', $passwordHash, $rfidHash, 1, 1]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Default admin account created successfully!</p>";
            echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #007acc; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>Login Credentials:</h3>";
            echo "<ul>";
            echo "<li><strong>Name/Email:</strong> admin@care.com or Admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "<li><strong>RFID:</strong> 1234567890</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin account</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Admin users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="../auth/login.php" style="background: #007acc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Login Page</a></p>
