<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>RFID Diagnostic Tool</h2>";
echo "<p>This tool will help diagnose RFID issues in your Hostinger database.</p>";

try {
    $pdo = get_pdo();
    
    // Check admin users and their RFID status
    echo "<h3>Admin Users RFID Status</h3>";
    $adminStmt = $pdo->prepare('SELECT id, name, email, rfid, LENGTH(rfid) as rfid_length FROM users WHERE is_admin = 1');
    $adminStmt->execute();
    $adminUsers = $adminStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>RFID Status</th><th>RFID Length</th><th>Is Hashed?</th><th>Action</th></tr>";
    
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
        echo "<td>" . ($isHashed ? 'Yes' : 'No') . "</td>";
        echo "<td>";
        
        if ($user['rfid'] && !$isHashed) {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='action' value='hash_rfid'>";
            echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
            echo "<button type='submit' style='background: #ff6b6b; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>Hash RFID</button>";
            echo "</form>";
        } elseif ($user['rfid'] && $isHashed) {
            echo "<span style='color: green;'>✓ Properly hashed</span>";
        } else {
            echo "<span style='color: gray;'>No RFID set</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Handle RFID hashing action
    if ($_POST['action'] === 'hash_rfid' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        // Get the current RFID value
        $getRfidStmt = $pdo->prepare('SELECT rfid FROM users WHERE id = ? AND is_admin = 1');
        $getRfidStmt->execute([$userId]);
        $currentRfid = $getRfidStmt->fetchColumn();
        
        if ($currentRfid && strpos($currentRfid, '$2y$') !== 0) {
            // It's plain text, hash it
            $hashedRfid = password_hash($currentRfid, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ?');
            $result = $updateStmt->execute([$hashedRfid, $userId]);
            
            if ($result) {
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "✅ RFID hashed successfully for user ID: $userId<br>";
                echo "Original: " . htmlspecialchars($currentRfid) . "<br>";
                echo "Hashed: " . substr($hashedRfid, 0, 20) . "...<br>";
                echo "Verification test: " . (password_verify($currentRfid, $hashedRfid) ? 'SUCCESS' : 'FAILED');
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "❌ Failed to hash RFID for user ID: $userId";
                echo "</div>";
            }
        }
    }
    
    // Test RFID verification for each admin user
    echo "<h3>RFID Verification Test</h3>";
    echo "<p>Enter an RFID to test verification against all admin users:</p>";
    
    if ($_POST['action'] === 'test_rfid' && isset($_POST['test_rfid'])) {
        $testRfid = sanitize_string($_POST['test_rfid']);
        echo "<h4>Testing RFID: " . htmlspecialchars($testRfid) . "</h4>";
        
        foreach ($adminUsers as $user) {
            if ($user['rfid']) {
                $matches = false;
                $method = '';
                
                if (strpos($user['rfid'], '$2y$') === 0) {
                    // It's hashed
                    $matches = password_verify($testRfid, $user['rfid']);
                    $method = 'password_verify()';
                } else {
                    // It's plain text
                    $matches = ($user['rfid'] === $testRfid);
                    $method = 'direct comparison';
                }
                
                $status = $matches ? '✅ MATCH' : '❌ No match';
                $color = $matches ? 'green' : 'red';
                
                echo "<div style='color: $color; margin: 5px 0;'>";
                echo "User: " . htmlspecialchars($user['name']) . " - $status ($method)";
                echo "</div>";
            }
        }
    }
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='test_rfid'>";
    echo "<input type='text' name='test_rfid' placeholder='Enter RFID to test' required style='padding: 8px; width: 200px;'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 3px; margin-left: 10px;'>Test RFID</button>";
    echo "</form>";
    
    // Database connection info
    echo "<h3>Database Connection Info</h3>";
    $config = include __DIR__ . '/../core/config.php';
    echo "<p><strong>Host:</strong> " . htmlspecialchars($config['db_host']) . "</p>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($config['db_name']) . "</p>";
    echo "<p><strong>Charset:</strong> " . htmlspecialchars($config['db_charset']) . "</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
