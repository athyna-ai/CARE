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

$facultyId = isset($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
$newStatus = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
$statusNotes = isset($_POST['status_notes']) ? trim($_POST['status_notes']) : '';

if (!$facultyId || !$newStatus) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: patient_view.php?id=' . $facultyId . '&type=faculty');
    exit;
}

// Validate status
$validStatuses = ['Active', 'Retired', 'On Leave', 'Resigned', 'Inactive'];
if (!in_array($newStatus, $validStatuses)) {
    $_SESSION['error'] = 'Invalid status selected';
    header('Location: patient_view.php?id=' . $facultyId . '&type=faculty');
    exit;
}

try {
    // Get current faculty data
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([$facultyId]);
    $faculty = $stmt->fetch();
    
    if (!$faculty) {
        $_SESSION['error'] = 'Faculty not found';
        header('Location: ../admin/dashboard.php');
        exit;
    }
    
    $currentStatus = $faculty['status'] ?? 'Active';
    
    // If status is the same, no need to update
    if ($currentStatus === $newStatus) {
        $_SESSION['error'] = 'Status is already ' . $newStatus;
        header('Location: patient_view.php?id=' . $facultyId . '&type=faculty');
        exit;
    }
    
    // Update faculty status
    $updateStmt = $pdo->prepare('UPDATE faculty SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->execute([$newStatus, $facultyId]);
    
    // Log the status change activity
    log_activity($pdo, $_SESSION['user_id'] ?? 1, 'faculty_status_change', 
        "Faculty status changed from {$currentStatus} to {$newStatus} for {$faculty['name']} ({$faculty['department']})", 
        'patient_view');
    
    $_SESSION['success'] = "Faculty status updated from {$currentStatus} to {$newStatus}";
    
} catch (Exception $e) {
    error_log("Faculty status update error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to update faculty status: ' . $e->getMessage();
}

header('Location: patient_view.php?id=' . $facultyId . '&type=faculty');
exit;
?>
