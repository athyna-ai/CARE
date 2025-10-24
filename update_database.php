<?php
// Simple database update script - run this in your browser
// Go to: http://localhost/Care/update_database.php

echo "<h2>Updating Database - Creating Enrollment History Table</h2>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'care_cms';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Create enrollment history table
    $createTableSQL = '
    CREATE TABLE IF NOT EXISTS enrollment_history (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id INT UNSIGNED NOT NULL,
        enrollment_type ENUM("initial", "re_enrollment", "level_change", "status_change") NOT NULL,
        previous_level VARCHAR(50) NULL,
        new_level VARCHAR(50) NOT NULL,
        previous_status VARCHAR(20) NULL,
        new_status VARCHAR(20) NOT NULL,
        previous_year_grade VARCHAR(40) NULL,
        new_year_grade VARCHAR(40) NULL,
        previous_section VARCHAR(50) NULL,
        new_section VARCHAR(50) NULL,
        previous_strand VARCHAR(50) NULL,
        new_strand VARCHAR(50) NULL,
        previous_course VARCHAR(120) NULL,
        new_course VARCHAR(120) NULL,
        previous_block VARCHAR(10) NULL,
        new_block VARCHAR(10) NULL,
        previous_rfid VARCHAR(50) NULL,
        new_rfid VARCHAR(50) NULL,
        previous_name VARCHAR(200) NULL,
        previous_gender VARCHAR(10) NULL,
        previous_dob DATE NULL,
        previous_phone VARCHAR(20) NULL,
        previous_address TEXT NULL,
        previous_guardian_name VARCHAR(200) NULL,
        previous_guardian_phone VARCHAR(20) NULL,
        previous_guardian_relationship VARCHAR(50) NULL,
        enrollment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        enrollment_year VARCHAR(10) NULL,
        semester VARCHAR(20) NULL,
        notes TEXT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student_id (student_id),
        INDEX idx_enrollment_type (enrollment_type),
        INDEX idx_enrollment_date (enrollment_date),
        INDEX idx_enrollment_year (enrollment_year),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    
    $pdo->exec($createTableSQL);
    echo "<p style='color: green;'>✓ Enrollment history table created successfully</p>";
    
    // Add new columns to existing table if they don't exist
    $alterColumns = [
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_rfid VARCHAR(50) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS new_rfid VARCHAR(50) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_name VARCHAR(200) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_gender VARCHAR(10) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_dob DATE NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_age INT NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_religion VARCHAR(80) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_barangay VARCHAR(100) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_municipality VARCHAR(100) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_province VARCHAR(100) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_guardian_name VARCHAR(200) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_emergency_contact VARCHAR(120) NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_contacts JSON NULL",
        "ALTER TABLE enrollment_history ADD COLUMN IF NOT EXISTS previous_allergies TEXT NULL"
    ];
    
    foreach ($alterColumns as $alterSQL) {
        try {
            $pdo->exec($alterSQL);
            echo "<p style='color: green;'>✓ Added column successfully</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists, skipping</p>";
            } else {
                echo "<p style='color: red;'>✗ Error adding column: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Verify table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_history'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table verification successful</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE enrollment_history");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3 style='color: green;'>✅ Database Update Complete!</h3>";
        echo "<p>The enrollment history system is now ready to use.</p>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li><a href='test_enrollment_history.php'>Test the enrollment history system</a></li>";
        echo "<li><a href='patients/student_form.php'>Try re-enrolling a student</a></li>";
        echo "<li><a href='admin/dashboard.php'>Go to dashboard</a></li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: red;'>❌ Table creation failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure your database connection settings are correct.</p>";
}
?>
