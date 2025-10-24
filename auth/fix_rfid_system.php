<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>RFID System Fix & Test Tool</h2>";
echo "<p>This tool will fix and test your RFID system from registration to login.</p>";

try {
    $pdo = get_pdo();
    
    // Handle actions
    if ($_POST['action'] === 'fix_user_rfid' && isset($_POST['user_id']) && isset($_POST['rfid'])) {
        $userId = (int)$_POST['user_id'];
        $rfid = sanitize_string($_POST['rfid']);
        
        // Hash the RFID
        $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
        
        // Update the user's RFID
        $updateStmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ? AND is_admin = 1');
        $result = $updateStmt->execute([$rfidHash, $userId]);
        
        if ($result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h3>✅ RFID Fixed Successfully!</h3>";
            echo "<p><strong>User ID:</strong> $userId</p>";
            echo "<p><strong>RFID:</strong> " . htmlspecialchars($rfid) . "</p>";
            echo "<p><strong>Hash:</strong> " . substr($rfidHash, 0, 30) . "...</p>";
            echo "<p><strong>Verification Test:</strong> " . (password_verify($rfid, $rfidHash) ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h3>❌ Failed to fix RFID</h3>";
            echo "</div>";
        }
    }
    
    if ($_POST['action'] === 'test_rfid_login' && isset($_POST['test_rfid'])) {
        $testRfid = sanitize_string($_POST['test_rfid']);
        
        echo "<h3>RFID Login Test Results</h3>";
        echo "<p><strong>Testing RFID:</strong> " . htmlspecialchars($testRfid) . "</p>";
        
        // Get all admin users
        $adminStmt = $pdo->prepare('SELECT id, name, email, rfid FROM users WHERE is_admin = 1');
        $adminStmt->execute();
        $adminUsers = $adminStmt->fetchAll();
        
        $foundMatch = false;
        foreach ($adminUsers as $user) {
            if ($user['rfid']) {
                $matches = false;
                $method = '';
                
                if (strpos($user['rfid'], '$2y$') === 0) {
                    // It's hashed
                    $matches = password_verify($testRfid, $user['rfid']);
                    $method = 'password_verify() (hashed)';
                } else {
                    // It's plain text
                    $matches = ($user['rfid'] === $testRfid);
                    $method = 'direct comparison (plain text)';
                }
                
                $status = $matches ? '✅ MATCH' : '❌ No match';
                $color = $matches ? 'green' : 'red';
                
                echo "<div style='color: $color; margin: 10px 0; padding: 10px; border: 1px solid $color; border-radius: 5px;'>";
                echo "<strong>User:</strong> " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";
                echo "<strong>Status:</strong> $status<br>";
                echo "<strong>Method:</strong> $method<br>";
                echo "<strong>Stored RFID:</strong> " . (strpos($user['rfid'], '$2y$') === 0 ? 'Hashed (' . substr($user['rfid'], 0, 20) . '...)' : htmlspecialchars($user['rfid']));
                echo "</div>";
                
                if ($matches) {
                    $foundMatch = true;
                }
            }
        }
        
        if (!$foundMatch) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h3>❌ No Matching RFID Found</h3>";
            echo "<p>The RFID '" . htmlspecialchars($testRfid) . "' does not match any admin user.</p>";
            echo "</div>";
        }
    }
    
    // Display all admin users
    echo "<h3>Current Admin Users</h3>";
    $adminStmt = $pdo->prepare('SELECT id, name, email, rfid, LENGTH(rfid) as rfid_length FROM users WHERE is_admin = 1');
    $adminStmt->execute();
    $adminUsers = $adminStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>RFID Status</th><th>Length</th><th>Action</th></tr>";
    
    foreach ($adminUsers as $user) {
        $isHashed = false;
        $rfidStatus = 'Not Set';
        
        if ($user['rfid']) {
            if (strpos($user['rfid'], '$2y$') === 0) {
                $isHashed = true;
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
        echo "<td>" . $user['rfid_length'] . "</td>";
        echo "<td>";
        
        if ($user['rfid'] && !$isHashed) {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='action' value='fix_user_rfid'>";
            echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
            echo "<input type='text' name='rfid' placeholder='Enter RFID' required style='padding: 5px; width: 120px;'>";
            echo "<button type='submit' style='background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; margin-left: 5px;'>Fix RFID</button>";
            echo "</form>";
        } elseif ($user['rfid'] && $isHashed) {
            echo "<span style='color: green;'>✓ Properly hashed</span>";
        } else {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='action' value='fix_user_rfid'>";
            echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
            echo "<input type='text' name='rfid' placeholder='Enter RFID' required style='padding: 5px; width: 120px;'>";
            echo "<button type='submit' style='background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; margin-left: 5px;'>Set RFID</button>";
            echo "</form>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // RFID Test Form
    echo "<h3>Test RFID Login</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='test_rfid_login'>";
    echo "<input type='text' name='test_rfid' placeholder='Enter RFID to test' required style='padding: 8px; width: 200px;'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 3px; margin-left: 10px;'>Test RFID</button>";
    echo "</form>";
    
    // Instructions
    echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>📋 Instructions</h3>";
    echo "<ol>";
    echo "<li><strong>Fix RFID:</strong> If any user shows 'Plain Text (Needs Hashing)', enter their RFID and click 'Fix RFID'</li>";
    echo "<li><strong>Set RFID:</strong> If any user shows 'Not Set', enter their RFID and click 'Set RFID'</li>";
    echo "<li><strong>Test Login:</strong> Use the test form above to verify RFID works</li>";
    echo "<li><strong>Login:</strong> Go to <a href='login.php' target='_blank'>login.php</a> and test with your credentials + RFID</li>";
    echo "</ol>";
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
