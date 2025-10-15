<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>COMPLETE Database AUTO_INCREMENT Fix - ALL TABLES</h2>";
echo "<p>This will fix EVERY table in your database that has ID = 0 issues.</p>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Get ALL tables from the database
    echo "<h3>Step 1: Discovering all tables...</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = [];
    while ($row = $stmt->fetch()) {
        $allTables[] = $row[0];
    }
    
    echo "<p>Found " . count($allTables) . " tables: " . implode(', ', $allTables) . "</p>";
    
    $totalDeleted = 0;
    $totalFixed = 0;
    $tablesWithIssues = [];
    
    echo "<h3>Step 2: Checking ALL tables for ID = 0 records...</h3>";
    
    // Check every table for ID = 0 records
    foreach ($allTables as $table) {
        try {
            // Check if table has an 'id' column
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
            if (!$stmt->fetch()) {
                echo "<p style='color: gray;'>⚠ Table {$table} has no 'id' column, skipping...</p>";
                continue;
            }
            
            // Count ID = 0 records
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}` WHERE id = 0");
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                echo "<p style='color: red;'>❌ Found {$count} ID = 0 records in {$table}</p>";
                $tablesWithIssues[] = $table;
            } else {
                echo "<p style='color: green;'>✓ No ID = 0 records in {$table}</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Error checking {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>Step 3: Deleting ALL ID = 0 records...</h3>";
    
    // Delete ID = 0 records from all tables
    foreach ($allTables as $table) {
        try {
            // Check if table has an 'id' column
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
            if (!$stmt->fetch()) {
                continue;
            }
            
            // Delete ID = 0 records
            $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE id = 0");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
            if ($deleted > 0) {
                echo "<p style='color: red;'>❌ Deleted {$deleted} ID = 0 records from {$table}</p>";
                $totalDeleted += $deleted;
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Error deleting from {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>Step 4: Fixing AUTO_INCREMENT for ALL tables...</h3>";
    
    // Fix AUTO_INCREMENT for all tables
    foreach ($allTables as $table) {
        try {
            // Check if table has an 'id' column
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
            $idColumn = $stmt->fetch();
            if (!$idColumn) {
                continue;
            }
            
            // Check current column definition
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createTable = $stmt->fetch()[1];
            
            // Determine if it needs fixing
            $needsFix = false;
            if (strpos($createTable, '`id` int(') !== false && strpos($createTable, 'unsigned') === false) {
                $needsFix = true;
            }
            
            if ($needsFix) {
                // Check if it's BIGINT or INT
                if (strpos($createTable, 'bigint') !== false) {
                    $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT");
                } else {
                    $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT");
                }
                
                echo "<p style='color: green;'>✓ Fixed AUTO_INCREMENT for {$table}</p>";
                $totalFixed++;
            } else {
                echo "<p style='color: blue;'>ℹ {$table} already has correct AUTO_INCREMENT</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Error fixing {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>Step 5: Resetting AUTO_INCREMENT counters for ALL tables...</h3>";
    
    // Reset AUTO_INCREMENT for all tables
    foreach ($allTables as $table) {
        try {
            // Check if table has an 'id' column
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
            if (!$stmt->fetch()) {
                continue;
            }
            
            // Get the next available ID
            $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM `{$table}`");
            $nextId = $stmt->fetch()['next_id'];
            
            // Reset AUTO_INCREMENT
            $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId}");
            
            echo "<p style='color: blue;'>ℹ Reset {$table} AUTO_INCREMENT to {$nextId}</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Error resetting AUTO_INCREMENT for {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>Step 6: Final verification...</h3>";
    
    // Final check for any remaining ID = 0 records
    $remainingIssues = 0;
    foreach ($allTables as $table) {
        try {
            // Check if table has an 'id' column
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'");
            if (!$stmt->fetch()) {
                continue;
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}` WHERE id = 0");
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                echo "<p style='color: red;'>❌ STILL {$count} ID = 0 records in {$table}</p>";
                $remainingIssues++;
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Error verifying {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Show current AUTO_INCREMENT values
    echo "<h4>Current AUTO_INCREMENT Values:</h4>";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            AUTO_INCREMENT
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
            AND AUTO_INCREMENT IS NOT NULL
        ORDER BY TABLE_NAME
    ");
    
    while ($row = $stmt->fetch()) {
        echo "<p style='color: blue;'>ℹ {$row['TABLE_NAME']}: AUTO_INCREMENT = {$row['AUTO_INCREMENT']}</p>";
    }
    
    echo "<h3 style='color: green;'>✓ COMPLETE Database fix finished!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Total tables checked: <strong>" . count($allTables) . "</strong></li>";
    echo "<li>Tables with ID = 0 issues: <strong>" . count($tablesWithIssues) . "</strong></li>";
    echo "<li>Records with ID = 0 deleted: <strong>{$totalDeleted}</strong></li>";
    echo "<li>Tables fixed: <strong>{$totalFixed}</strong></li>";
    echo "<li>Remaining issues: <strong>{$remainingIssues}</strong></li>";
    echo "</ul>";
    
    if ($remainingIssues === 0) {
        echo "<p style='color: green; font-size: 20px; font-weight: bold;'>🎉 SUCCESS! ALL ID = 0 issues have been resolved!</p>";
        echo "<p style='color: blue; font-size: 16px;'>ℹ New records will now get proper sequential IDs (1, 2, 3, etc.)</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>⚠ Some issues remain. Check the error messages above.</p>";
    }
    
    if (count($tablesWithIssues) > 0) {
        echo "<h4>Tables that had ID = 0 issues:</h4>";
        echo "<ul>";
        foreach ($tablesWithIssues as $table) {
            echo "<li style='color: red;'>{$table}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div style="margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 10px;">
    <h4>What to do next:</h4>
    <ol>
        <li><strong>Test new data entry:</strong> Try adding a new student, faculty member, or visitation log</li>
        <li><strong>Verify IDs:</strong> Check that new records get proper sequential IDs (not 0)</li>
        <li><strong>Delete this file:</strong> For security, delete this file after running it</li>
    </ol>
</div>

<p><a href="../admin/dashboard.php">← Go to Dashboard</a> | <a href="setup_database.php">Run Database Setup</a></p>