<?php
/**
 * Database Update Script - SECURITY PROTECTED
 * Run this to update your existing database with the new archive tables
 */

require_once __DIR__ . '/../security_breach_detector.php';

require_once __DIR__ . '/../core/config.php';

try {
    $pdo = get_pdo();
    
    echo "<h2>Updating CARE CMS Database...</h2>\n";
    
    // Read and execute the update SQL file
    $sql = file_get_contents('update_database_archive_tables.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read update_database_archive_tables.sql file");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>\n";
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
            echo "<p style='color: gray;'>Statement: " . substr($statement, 0, 100) . "...</p>\n";
        }
    }
    
    echo "<h3>Update Complete!</h3>\n";
    echo "<p><strong>Successful operations:</strong> $successCount</p>\n";
    echo "<p><strong>Errors:</strong> $errorCount</p>\n";
    
    if ($errorCount === 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Database updated successfully! All archive tables are now available.</p>\n";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Some operations had errors. Check the messages above.</p>\n";
    }
    
    // Show current tables
    echo "<h3>Current Database Tables:</h3>\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>\n";
    foreach ($tables as $table) {
        echo "<li>$table</li>\n";
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error updating database:</h2>\n";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h2, h3 {
    color: #333;
}
p {
    margin: 5px 0;
}
</style>
