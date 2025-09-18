<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int)($_POST['record_id'] ?? 0);
    $recordType = sanitize_string($_POST['record_type'] ?? '');
    
    if ($recordId > 0 && in_array($recordType, ['students', 'faculty'])) {
        try {
            if ($recordType === 'students') {
                // Get archived student data
                $stmt = $pdo->prepare('SELECT * FROM students_archive WHERE id = ?');
                $stmt->execute([$recordId]);
                $archivedStudent = $stmt->fetch();
                
                if ($archivedStudent) {
                    // Insert back into main students table
                    $restoreStmt = $pdo->prepare('INSERT INTO students (
                        name, level, year_grade, section, strand, course, 
                        rfid, address, guardian, emergency_contact, dob, age, 
                        religion, allergies
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    
                    $restoreStmt->execute([
                        $archivedStudent['name'],
                        $archivedStudent['level'],
                        $archivedStudent['year_grade'],
                        $archivedStudent['section'],
                        $archivedStudent['strand'],
                        $archivedStudent['course'],
                        $archivedStudent['rfid'],
                        $archivedStudent['address'],
                        $archivedStudent['guardian'],
                        $archivedStudent['emergency_contact'],
                        $archivedStudent['dob'],
                        $archivedStudent['age'],
                        $archivedStudent['religion'],
                        $archivedStudent['allergies']
                    ]);
                    
                    // Delete from archive
                    $deleteStmt = $pdo->prepare('DELETE FROM students_archive WHERE id = ?');
                    $deleteStmt->execute([$recordId]);
                    
                    // Log activity
                    log_activity($pdo, (int)$_SESSION['user']['id'], 'student_restored', "Restored student: {$archivedStudent['name']} ({$archivedStudent['level']})", 'restore_record');
                    
                    // Redirect back to archive management
                    header("Location: archive_management.php?type=students&restored=1");
                    exit;
                }
            } else {
                // Get archived faculty data
                $stmt = $pdo->prepare('SELECT * FROM faculty_archive WHERE id = ?');
                $stmt->execute([$recordId]);
                $archivedFaculty = $stmt->fetch();
                
                if ($archivedFaculty) {
                    // Insert back into main faculty table
                    $restoreStmt = $pdo->prepare('INSERT INTO faculty (
                        name, department, address, age, sr, dob, allergies, 
                        religion, emergency_contact, gender, rfid
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    
                    $restoreStmt->execute([
                        $archivedFaculty['name'],
                        $archivedFaculty['department'],
                        $archivedFaculty['address'],
                        $archivedFaculty['age'],
                        $archivedFaculty['sr'],
                        $archivedFaculty['dob'],
                        $archivedFaculty['allergies'],
                        $archivedFaculty['religion'],
                        $archivedFaculty['emergency_contact'],
                        $archivedFaculty['gender'],
                        $archivedFaculty['rfid']
                    ]);
                    
                    // Delete from archive
                    $deleteStmt = $pdo->prepare('DELETE FROM faculty_archive WHERE id = ?');
                    $deleteStmt->execute([$recordId]);
                    
                    // Log activity
                    log_activity($pdo, (int)$_SESSION['user']['id'], 'faculty_restored', "Restored faculty: {$archivedFaculty['name']} ({$archivedFaculty['department']})", 'restore_record');
                    
                    // Redirect back to archive management
                    header("Location: archive_management.php?type=faculty&restored=1");
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Restore record error: " . $e->getMessage());
        }
    }
}

// If we get here, something went wrong
header("Location: archive_management.php?error=1");
exit;
?>
