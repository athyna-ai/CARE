<?php
// Simple test to verify login works without RFID
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h1>Login Test (No RFID)</h1>";

// Test credentials
$testEmail = 'admin@care.com';
$testPassword = 'password'; // This should be the default password

try {
    $pdo = get_pdo();
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_admin FROM users WHERE email = ? AND is_admin = 1 LIMIT 1');
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>❌ User not found: $testEmail</p>";
    } else {
        echo "<p style='color: green;'>✅ User found: " . htmlspecialchars($user['name']) . "</p>";
        
        // Test password verification
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "<p style='color: green;'>✅ Password verification successful</p>";
            
            // Simulate login
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => (int)$user['is_admin'],
            ];
            $_SESSION['last_activity'] = time();
            
            echo "<p style='color: green;'>✅ Session created successfully</p>";
            echo "<p><a href='../admin/dashboard.php'>Go to Dashboard</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed</p>";
            echo "<p>Try with password: 'password'</p>";
        }
    }
    
    // Show all admin users
    echo "<h2>All Admin Users:</h2>";
    $stmt = $pdo->prepare('SELECT id, name, email, is_admin FROM users WHERE is_admin = 1');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Is Admin</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($u['id']) . "</td>";
        echo "<td>" . htmlspecialchars($u['name']) . "</td>";
        echo "<td>" . htmlspecialchars($u['email']) . "</td>";
        echo "<td>" . ($u['is_admin'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
