<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    
    if ($facultyId > 0) {
        try {
            // Get faculty data before archiving
            $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
            $stmt->execute([$facultyId]);
            $faculty = $stmt->fetch();
            
            if ($faculty) {
                // Create archive table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS faculty_archive (
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
                    gender ENUM("Male", "Female", "Other") NULL,
                    rfid VARCHAR(50) NULL,
                    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    archived_by INT NULL,
                    INDEX idx_original_id (original_id),
                    INDEX idx_name (name),
                    INDEX idx_department (department),
                    INDEX idx_archived_at (archived_at)
                )');
                
                // Insert into archive table
                $archiveStmt = $pdo->prepare('INSERT INTO faculty_archive (
                    original_id, name, department, address, age, sr, dob, allergies, 
                    religion, emergency_contact, gender, rfid, archived_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                
                $archiveStmt->execute([
                    $faculty['id'],
                    $faculty['name'],
                    $faculty['department'],
                    $faculty['address'],
                    $faculty['age'],
                    $faculty['sr'],
                    $faculty['dob'],
                    $faculty['allergies'],
                    $faculty['religion'],
                    $faculty['emergency_contact'],
                    $faculty['gender'],
                    $faculty['rfid'],
                    $_SESSION['user']['id']
                ]);
                
                // Delete from main table
                $deleteStmt = $pdo->prepare('DELETE FROM faculty WHERE id = ?');
                $deleteStmt->execute([$facultyId]);
                
                // Log activity
                log_activity($pdo, (int)$_SESSION['user']['id'], 'faculty_archived', "Archived faculty: {$faculty['name']} ({$faculty['department']})", 'archive_faculty');
                
                // Redirect back to the listing with success message
                header("Location: faculty_listing.php?archived=1");
                exit;
            }
        } catch (Exception $e) {
            error_log("Archive faculty error: " . $e->getMessage());
        }
    }
}

// If we get here, something went wrong
header("Location: faculty_listing.php?error=1");
exit;
?>
