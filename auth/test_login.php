<?php
// Simple test script to check login functionality
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "Testing database connection...\n";

try {
    $pdo = get_pdo();
    echo "✓ Database connection successful\n";
    
    // Test admin table query
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, rfid FROM users WHERE (email = ? OR name = ?) LIMIT 1');
    $stmt->execute(['admin', 'admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Admin user found: " . $user['name'] . "\n";
        echo "✓ Email: " . $user['email'] . "\n";
        echo "✓ Has RFID: " . (!empty($user['rfid']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ No admin user found\n";
    }
    
    // Test activity logs table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM activity_logs');
    $count = $stmt->fetch()['count'];
    echo "✓ Activity logs table accessible, " . $count . " records\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
