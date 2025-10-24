<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check CSRF token
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'enrollment_history.php');
    exit;
}

try {
    $pdo = get_pdo();
    
    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    
    if (!$studentId) {
        throw new Exception('Invalid student ID');
    }
    
    // Get student details
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception('Student not found');
    }
    
    // Create archived_students table if it doesn't exist
    $pdo->exec('CREATE TABLE IF NOT EXISTS archived_students (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        original_id INT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        gender ENUM("Male", "Female") NOT NULL,
        level VARCHAR(100) NOT NULL,
        course VARCHAR(255),
        block VARCHAR(50),
        section VARCHAR(100),
        strand VARCHAR(100),
        year_grade VARCHAR(100),
        rfid VARCHAR(50) UNIQUE,
        address TEXT,
        age INT,
        dob DATE,
        religion VARCHAR(100),
        guardian VARCHAR(255),
        allergies TEXT,
        contacts JSON,
        status ENUM("Active", "Graduated", "Transferred", "Inactive", "Archived") DEFAULT "Archived",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        INDEX idx_original_id (original_id),
        INDEX idx_rfid (rfid),
        INDEX idx_name (name),
        INDEX idx_status (status)
    ) ENGINE=InnoDB');
    
    // Move student to archived table
    $archiveStmt = $pdo->prepare('
        INSERT INTO archived_students (
            original_id, name, gender, level, course, block, section, strand, year_grade,
            rfid, address, age, dob, religion, guardian, allergies, contacts, status,
            archived_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $archiveStmt->execute([
        $student['id'],
        $student['name'],
        $student['gender'],
        $student['level'],
        $student['course'],
        $student['block'],
        $student['section'],
        $student['strand'],
        $student['year_grade'],
        $student['rfid'],
        $student['address'],
        $student['age'],
        $student['dob'],
        $student['religion'],
        $student['guardian'],
        $student['allergies'],
        $student['contacts'],
        'Archived',
        $_SESSION['user']['id']
    ]);
    
    // Delete from main students table
    $deleteStmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $deleteStmt->execute([$studentId]);
    
    // Log activity
    log_activity($pdo, (int)$_SESSION['user']['id'], 'student_archive', 
        "Archived student: {$student['name']} (ID: {$studentId})", 'enrollment_history');
    
    $_SESSION['success'] = "Student '{$student['name']}' has been successfully archived.";
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Redirect back to enrollment history or dashboard
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../admin/dashboard.php';
header("Location: {$redirectUrl}");
exit;
?>
