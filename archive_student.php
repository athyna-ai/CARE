<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $level = sanitize_string($_POST['level'] ?? '');
    
    if ($studentId > 0) {
        try {
            // Get student data before archiving
            $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();
            
            if ($student) {
                // Create archive table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS students_archive (
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
                )');
                
                // Insert into archive table
                $archiveStmt = $pdo->prepare('INSERT INTO students_archive (
                    original_id, name, level, year_grade, section, strand, course, 
                    rfid, address, guardian, emergency_contact, dob, age, religion, 
                    allergies, archived_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                
                $archiveStmt->execute([
                    $student['id'],
                    $student['name'],
                    $student['level'],
                    $student['year_grade'],
                    $student['section'],
                    $student['strand'],
                    $student['course'],
                    $student['rfid'],
                    $student['address'],
                    $student['guardian'],
                    $student['emergency_contact'],
                    $student['dob'],
                    $student['age'],
                    $student['religion'],
                    $student['allergies'],
                    $_SESSION['user']['id']
                ]);
                
                // Delete from main table
                $deleteStmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
                $deleteStmt->execute([$studentId]);
                
                // Log activity
                log_activity($pdo, (int)$_SESSION['user']['id'], 'student_archived', "Archived student: {$student['name']} ({$student['level']})", 'archive_student');
                
                // Redirect back to the listing with success message
                header("Location: school_listing.php?level=" . urlencode($level) . "&archived=1");
                exit;
            }
        } catch (Exception $e) {
            error_log("Archive student error: " . $e->getMessage());
        }
    }
}

// If we get here, something went wrong
header("Location: school_listing.php?level=" . urlencode($level) . "&error=1");
exit;
?>
