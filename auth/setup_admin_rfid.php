<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Admin RFID Setup Tool</h2>";
echo "<p>This tool helps you set up RFID for your admin account on Hostinger.</p>";

try {
    $pdo = get_pdo();
    
    // Get all admin users
    $adminStmt = $pdo->prepare('SELECT id, name, email, rfid FROM users WHERE is_admin = 1');
    $adminStmt->execute();
    $adminUsers = $adminStmt->fetchAll();
    
    if (empty($adminUsers)) {
        echo "<p style='color: red;'>No admin users found!</p>";
        exit;
    }
    
    // Handle RFID setup
    if ($_POST['action'] === 'set_rfid' && isset($_POST['user_id']) && isset($_POST['rfid'])) {
        $userId = (int)$_POST['user_id'];
        $rfid = sanitize_string($_POST['rfid']);
        
        if ($rfid) {
            // Hash the RFID before storing
            $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ? AND is_admin = 1');
            $result = $updateStmt->execute([$rfidHash, $userId]);
            
            if ($result) {
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                echo "<h3>✅ RFID Set Successfully!</h3>";
                echo "<p><strong>User ID:</strong> $userId</p>";
                echo "<p><strong>RFID:</strong> " . htmlspecialchars($rfid) . "</p>";
                echo "<p><strong>Status:</strong> Properly hashed and stored</p>";
                echo "<p><strong>Verification Test:</strong> " . (password_verify($rfid, $rfidHash) ? 'SUCCESS' : 'FAILED') . "</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                echo "<h3>❌ Failed to set RFID</h3>";
                echo "<p>Please try again or check your database connection.</p>";
                echo "</div>";
            }
        }
    }
    
    // Display admin users
    echo "<h3>Admin Users</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Current RFID</th><th>Action</th></tr>";
    
    foreach ($adminUsers as $user) {
        $rfidStatus = 'Not Set';
        if ($user['rfid']) {
            if (strpos($user['rfid'], '$2y$') === 0) {
                $rfidStatus = 'Hashed (Secure)';
            } else {
                $rfidStatus = 'Plain Text (Needs Hashing)';
            }
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $rfidStatus . "</td>";
        echo "<td>";
        
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='action' value='set_rfid'>";
        echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
        echo "<input type='text' name='rfid' placeholder='Enter RFID' required style='padding: 5px; width: 120px;'>";
        echo "<button type='submit' style='background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; margin-left: 5px;'>Set RFID</button>";
        echo "</form>";
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test login instructions
    echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>📋 How to Test RFID Login</h3>";
    echo "<ol>";
    echo "<li>Set your RFID using the form above</li>";
    echo "<li>Go to the <a href='login.php' target='_blank'>login page</a></li>";
    echo "<li>Enter your username/email and password</li>";
    echo "<li>When prompted for RFID, enter the same RFID you just set</li>";
    echo "<li>You should be logged in successfully!</li>";
    echo "</ol>";
    echo "</div>";
    
    // Troubleshooting section
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>🔧 Troubleshooting</h3>";
    echo "<p>If RFID login still doesn't work:</p>";
    echo "<ul>";
    echo "<li>Check the <a href='rfid_diagnostic.php' target='_blank'>RFID Diagnostic Tool</a> for detailed analysis</li>";
    echo "<li>Make sure your database connection is working properly</li>";
    echo "<li>Check the error logs in your hosting panel</li>";
    echo "<li>Verify that the RFID is being hashed correctly (should start with \$2y\$)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; font-weight: bold; }
tr:nth-child(even) { background-color: #f8f9fa; }
button:hover { opacity: 0.8; cursor: pointer; }
</style>
