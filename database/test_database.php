<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

echo "<h2>Database Structure Test</h2>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check what tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Existing Tables:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Check if students table exists and has data
    if (in_array('students', $tables)) {
        echo "<h3>Students Table:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
        $result = $stmt->fetch();
        echo "<p>Records: " . $result['count'] . "</p>";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT id, name, level, rfid FROM students LIMIT 3");
            $students = $stmt->fetchAll();
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Level</th><th>RFID</th></tr>";
            foreach ($students as $student) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['id']) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['level']) . "</td>";
                echo "<td>" . htmlspecialchars($student['rfid']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Students table does not exist</p>";
    }
    
    // Check if faculty table exists and has data
    if (in_array('faculty', $tables)) {
        echo "<h3>Faculty Table:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty");
        $result = $stmt->fetch();
        echo "<p>Records: " . $result['count'] . "</p>";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT id, name, department, rfid FROM faculty LIMIT 3");
            $faculty = $stmt->fetchAll();
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Department</th><th>RFID</th></tr>";
            foreach ($faculty as $fac) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($fac['id']) . "</td>";
                echo "<td>" . htmlspecialchars($fac['name']) . "</td>";
                echo "<td>" . htmlspecialchars($fac['department']) . "</td>";
                echo "<td>" . htmlspecialchars($fac['rfid']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Faculty table does not exist</p>";
    }
    
    // Check if medical_records table exists
    if (in_array('medical_records', $tables)) {
        echo "<p style='color: green;'>✓ medical_records table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ medical_records table does not exist - run create_medical_tables.sql</p>";
    }
    
    // Check if visitation_logs table exists
    if (in_array('visitation_logs', $tables)) {
        echo "<p style='color: green;'>✓ visitation_logs table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ visitation_logs table does not exist - run create_medical_tables.sql</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="../admin/dashboard.php">Go to Dashboard</a></p>
