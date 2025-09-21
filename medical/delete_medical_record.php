<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

$recordId = (int)($_GET['id'] ?? 0);
$patientId = (int)($_GET['patient_id'] ?? 0);
$patientType = $_GET['patient_type'] ?? '';

if (!$recordId || !$patientId || !in_array($patientType, ['student', 'faculty'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

try {
    // Delete the medical record
    $stmt = $pdo->prepare('DELETE FROM medical_records WHERE id = ? AND patient_id = ? AND patient_type = ?');
    $stmt->execute([$recordId, $patientId, $patientType]);
    
    if ($stmt->rowCount() > 0) {
        // Log the deletion
        log_activity($pdo, (int)$_SESSION['user']['id'], 'medical_record_deleted', "Deleted medical record #{$recordId} for {$patientType} #{$patientId}", 'medical_forms');
        
        // Redirect back with success message
        header("Location: ../patients/medical_forms_management.php?patient_id={$patientId}&patient_type={$patientType}&message=Medical record deleted successfully&message_type=success");
    } else {
        // Record not found
        header("Location: ../patients/medical_forms_management.php?patient_id={$patientId}&patient_type={$patientType}&message=Medical record not found&message_type=error");
    }
} catch (Throwable $e) {
    error_log("Error deleting medical record: " . $e->getMessage());
    header("Location: ../patients/medical_forms_management.php?patient_id={$patientId}&patient_type={$patientType}&message=Error deleting medical record&message_type=error");
}
exit;
?>
