<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Simple tool to set RFID for admin users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $rfid = sanitize_string($_POST['rfid'] ?? '');
    
    if ($user_id && $rfid) {
        try {
            $pdo = get_pdo();
            // Store RFID as hashed (to match system behavior)
            $rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET rfid = ? WHERE id = ? AND is_admin = 1');
            $result = $stmt->execute([$rfidHash, $user_id]);
            
            if ($result) {
                echo "RFID set successfully for user ID: $user_id<br>";
                echo "New hash: " . $rfidHash . "<br>";
                echo "Test verification: " . (password_verify($rfid, $rfidHash) ? 'SUCCESS' : 'FAILED');
            } else {
                echo "Failed to set RFID";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Please provide user ID and RFID";
    }
    exit;
}

// Show current admin users
try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, name, email, rfid FROM users WHERE is_admin = 1');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Admin Users and their RFID</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>RFID</th><th>Action</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        $rfidDisplay = $user['rfid'] ? (strpos($user['rfid'], '$2y$') === 0 ? 'Hashed value' : $user['rfid']) : 'Not set';
        echo "<td>" . htmlspecialchars($rfidDisplay) . "</td>";
        echo "<td>";
        echo "<form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='user_id' value='" . $user['id'] . "'>";
        echo "<input type='text' name='rfid' placeholder='Enter RFID' required>";
        echo "<button type='submit'>Set RFID</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
