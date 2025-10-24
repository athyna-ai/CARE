<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
$newStatus = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
$statusNotes = isset($_POST['status_notes']) ? trim($_POST['status_notes']) : '';

if (!$studentId || !$newStatus) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: patient_view.php?id=' . $studentId . '&type=student');
    exit;
}

// Validate status
$validStatuses = ['Active', 'Graduated', 'Transferred', 'Inactive'];
if (!in_array($newStatus, $validStatuses)) {
    $_SESSION['error'] = 'Invalid status selected';
    header('Location: patient_view.php?id=' . $studentId . '&type=student');
    exit;
}

try {
    // Get current student data
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $_SESSION['error'] = 'Student not found';
        header('Location: ../admin/dashboard.php');
        exit;
    }
    
    $currentStatus = $student['status'] ?? 'Active';
    
    // If status is the same, no need to update
    if ($currentStatus === $newStatus) {
        $_SESSION['error'] = 'Status is already ' . $newStatus;
        header('Location: patient_view.php?id=' . $studentId . '&type=student');
        exit;
    }
    
    // Update student status
    $updateStmt = $pdo->prepare('UPDATE students SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->execute([$newStatus, $studentId]);
    
    // Record status change in enrollment history
    // Only create enrollment history for significant status changes (not for Active <-> Inactive)
    // Skip creating duplicate records if student is being graduated (re-enrollment will handle it)
    if (!($currentStatus === 'Active' && $newStatus === 'Graduated')) {
        $historyStmt = $pdo->prepare('
            INSERT INTO enrollment_history (
                student_id, 
                enrollment_type, 
                previous_status, 
                new_status, 
                previous_level, 
                new_level, 
                previous_year_grade, 
                new_year_grade, 
                previous_section, 
                new_section, 
                previous_strand, 
                new_strand, 
                previous_course, 
                new_course, 
                previous_block, 
                new_block, 
                enrollment_year, 
                notes, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $historyStmt->execute([
            $studentId,
            'status_change',
            $currentStatus,
            $newStatus,
            $student['level'],
            $student['level'],
            $student['year_grade'],
            $student['year_grade'],
            $student['section'],
            $student['section'],
            $student['strand'],
            $student['strand'],
            $student['course'],
            $student['course'],
            $student['block'],
            $student['block'],
            date('Y'),
            $statusNotes ?: "Status changed from {$currentStatus} to {$newStatus}",
            $_SESSION['user_id'] ?? 1
        ]);
    }
    
    $_SESSION['success'] = "Student status updated from {$currentStatus} to {$newStatus}";
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to update student status: ' . $e->getMessage();
}

header('Location: patient_view.php?id=' . $studentId . '&type=student');
exit;
?>
