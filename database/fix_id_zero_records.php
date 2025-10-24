<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Database ID Validation and Fix</h2>";
echo "<p>This script will check for and fix any records with ID = 0</p>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // List of tables to check
    $tables = [
        'users',
        'students', 
        'faculty',
        'medical_records',
        'visitation_logs',
        'students_archive',
        'faculty_archive',
        'notification_reads',
        'enrollment_history',
        'archived_students',
        'activity_logs'
    ];
    
    $totalFixed = 0;
    
    foreach ($tables as $table) {
        echo "<h3>Checking table: {$table}</h3>";
        
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if (!$stmt->fetch()) {
            echo "<p style='color: orange;'>⚠ Table {$table} does not exist, skipping...</p>";
            continue;
        }
        
        // Check for ID = 0 records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table} WHERE id = 0");
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            echo "<p style='color: red;'>❌ Found {$count} records with ID = 0 in {$table}</p>";
            
            // Get the next available ID
            $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM {$table}");
            $nextId = $stmt->fetch()['next_id'];
            
            echo "<p>Next available ID: {$nextId}</p>";
            
            // Fix the records by updating their IDs
            $stmt = $pdo->prepare("UPDATE {$table} SET id = ? WHERE id = 0 LIMIT 1");
            $fixed = 0;
            
            while ($count > 0) {
                $result = $stmt->execute([$nextId]);
                if ($result) {
                    $fixed++;
                    $nextId++;
                    $count--;
                    echo "<p style='color: green;'>✓ Fixed record ID: " . ($nextId - 1) . "</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to fix record</p>";
                    break;
                }
            }
            
            $totalFixed += $fixed;
            echo "<p style='color: green;'>✓ Fixed {$fixed} records in {$table}</p>";
            
            // Reset AUTO_INCREMENT to the correct value
            $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM {$table}");
            $nextId = $stmt->fetch()['next_id'];
            
            $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = {$nextId}");
            echo "<p style='color: blue;'>ℹ Reset AUTO_INCREMENT to {$nextId}</p>";
            
        } else {
            echo "<p style='color: green;'>✓ No ID = 0 records found in {$table}</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ Database validation complete!</h3>";
    echo "<p><strong>Total records fixed: {$totalFixed}</strong></p>";
    
    if ($totalFixed > 0) {
        echo "<p style='color: blue;'>ℹ All AUTO_INCREMENT values have been reset to prevent future ID = 0 issues</p>";
    } else {
        echo "<p style='color: green;'>✓ No ID = 0 records found in any table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="../admin/dashboard.php">Go to Dashboard</a> | <a href="setup_database.php">Run Database Setup</a></p>
