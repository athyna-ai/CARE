<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

echo "<h2>Database Setup</h2>";

try {
    $pdo = get_pdo();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Create medical_records table
    echo "<h3>Creating medical_records table...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        patient_type ENUM('student', 'faculty') NOT NULL,
        form_type VARCHAR(50) NOT NULL,
        form_data JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_patient (patient_id, patient_type),
        INDEX idx_form_type (form_type),
        INDEX idx_created_at (created_at)
    )");
    echo "<p style='color: green;'>✓ medical_records table created</p>";
    
    // Create visitation_logs table
    echo "<h3>Creating visitation_logs table...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        patient_type ENUM('student', 'faculty') NOT NULL,
        reason VARCHAR(100) NOT NULL,
        visit_date DATETIME NOT NULL,
        symptoms TEXT NULL,
        heart_rate INT NULL,
        blood_pressure VARCHAR(20) NULL,
        temperature DECIMAL(4,1) NULL,
        other_notes TEXT NULL,
        medication_given BOOLEAN DEFAULT FALSE,
        medication_name VARCHAR(100) NULL,
        other_treatment VARCHAR(100) NULL,
        medication_notes TEXT NULL,
        injury BOOLEAN DEFAULT FALSE,
        first_aid_given BOOLEAN DEFAULT FALSE,
        first_aid_type VARCHAR(100) NULL,
        nurse_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_patient (patient_id, patient_type),
        INDEX idx_visit_date (visit_date),
        INDEX idx_reason (reason)
    )");
    echo "<p style='color: green;'>✓ visitation_logs table created</p>";
    
    // Create archive tables
    echo "<h3>Creating archive tables...</h3>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS students_archive (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        level VARCHAR(50) NOT NULL,
        year_grade VARCHAR(20) NULL,
        section VARCHAR(100) NULL,
        strand VARCHAR(100) NULL,
        course VARCHAR(100) NULL,
        rfid VARCHAR(50) NULL,
        address TEXT NULL,
        guardian VARCHAR(255) NULL,
        emergency_contact VARCHAR(20) NULL,
        dob DATE NULL,
        age INT NULL,
        religion VARCHAR(100) NULL,
        allergies TEXT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT NULL,
        INDEX idx_original_id (original_id),
        INDEX idx_name (name),
        INDEX idx_level (level),
        INDEX idx_archived_at (archived_at)
    )");
    echo "<p style='color: green;'>✓ students_archive table created</p>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS faculty_archive (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        department VARCHAR(100) NULL,
        address TEXT NULL,
        age INT NULL,
        sr BOOLEAN DEFAULT FALSE,
        dob DATE NULL,
        allergies TEXT NULL,
        religion VARCHAR(100) NULL,
        emergency_contact VARCHAR(20) NULL,
        gender ENUM('Male', 'Female', 'Other') NULL,
        rfid VARCHAR(50) NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT NULL,
        INDEX idx_original_id (original_id),
        INDEX idx_name (name),
        INDEX idx_department (department),
        INDEX idx_archived_at (archived_at)
    )");
    echo "<p style='color: green;'>✓ faculty_archive table created</p>";
    
    // Check if we have any students or faculty
    echo "<h3>Checking existing data...</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $studentCount = $stmt->fetch()['count'];
    echo "<p>Students: " . $studentCount . "</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty");
    $facultyCount = $stmt->fetch()['count'];
    echo "<p>Faculty: " . $facultyCount . "</p>";
    
    if ($studentCount > 0) {
        echo "<h4>Sample Student:</h4>";
        $stmt = $pdo->query("SELECT id, name, level, rfid FROM students LIMIT 1");
        $student = $stmt->fetch();
        echo "<p>ID: " . $student['id'] . ", Name: " . htmlspecialchars($student['name']) . ", Level: " . htmlspecialchars($student['level']) . "</p>";
        echo "<p><a href='patient_view.php?id=" . $student['id'] . "&type=student' target='_blank'>Test Patient View</a></p>";
    }
    
    if ($facultyCount > 0) {
        echo "<h4>Sample Faculty:</h4>";
        $stmt = $pdo->query("SELECT id, name, department, rfid FROM faculty LIMIT 1");
        $faculty = $stmt->fetch();
        echo "<p>ID: " . $faculty['id'] . ", Name: " . htmlspecialchars($faculty['name']) . ", Department: " . htmlspecialchars($faculty['department']) . "</p>";
        echo "<p><a href='patient_view.php?id=" . $faculty['id'] . "&type=faculty' target='_blank'>Test Patient View</a></p>";
    }
    
    echo "<h3 style='color: green;'>✓ Database setup complete!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="dashboard.php">Go to Dashboard</a> | <a href="test_database.php">Test Database</a></p>
