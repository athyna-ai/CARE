<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Login Debug Information</h2>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Check users in the table
        $stmt = $pdo->query("SELECT id, name, email, is_admin, rfid FROM users");
        $users = $stmt->fetchAll();
        
        echo "<h3>Users in database:</h3>";
        if (empty($users)) {
            echo "<p style='color: red;'>❌ No users found in database</p>";
            
            // Create a default admin account
            echo "<h3>Creating default admin account...</h3>";
            $adminPassword = 'admin123';
            $adminRfid = '1234567890';
            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            // Store RFID as hashed to match existing database structure
            $rfidHash = password_hash($adminRfid, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, rfid, is_admin, verified) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute(['Admin', 'admin@care.com', $passwordHash, $rfidHash, 1, 1]);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Default admin account created</p>";
                echo "<p><strong>Login credentials:</strong></p>";
                echo "<ul>";
                echo "<li>Name/Email: <code>admin@care.com</code> or <code>Admin</code></li>";
                echo "<li>Password: <code>admin123</code></li>";
                echo "<li>RFID: <code>1234567890</code></li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create admin account</p>";
            }
        } else {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Admin</th><th>RFID</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . (empty($user['rfid']) ? 'None' : 'Set') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist</p>";
        echo "<p>Creating users table...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            rfid VARCHAR(255) NULL,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            verify_token VARCHAR(100) NULL,
            reset_token VARCHAR(100) NULL,
            reset_expiry DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "<p style='color: green;'>✓ Users table created</p>";
        echo "<p>Please refresh this page to create an admin account.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="../auth/login.php">Go to Login Page</a></p>
