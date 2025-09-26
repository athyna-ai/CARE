<?php
// Start output buffering to prevent any HTML output before JSON
ob_start();

// Suppress all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$archiveId = $_POST['archive_id'] ?? null;
$patientId = $_POST['patient_id'] ?? null;
$patientType = $_POST['patient_type'] ?? null;

if (!$archiveId || !$patientId || !$patientType) {
    error_log('Restore medical record - Missing parameters: archiveId=' . ($archiveId ?? 'null') . ', patientId=' . ($patientId ?? 'null') . ', patientType=' . ($patientType ?? 'null'));
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Test database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get the archived medical record
    $archiveTable = $patientType . '_medical_archive';
    $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE id = ? AND patient_id = ?");
    $stmt->execute([$archiveId, $patientId]);
    $archivedRecord = $stmt->fetch();
    
    if (!$archivedRecord) {
        throw new Exception('Archived medical record not found');
    }
    
    // Insert back into medical_records table
    $insertSQL = "
        INSERT INTO medical_records (
            `patient_id`, `patient_type`, `form_type`, `form_data`, `created_at`, `created_by`
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($insertSQL);
    $result = $stmt->execute([
        $archivedRecord['patient_id'],
        $archivedRecord['patient_type'],
        $archivedRecord['form_type'],
        $archivedRecord['form_data'],
        $archivedRecord['created_at'],
        $_SESSION['user']['id']
    ]);
    
    if (!$result) {
        throw new Exception('Failed to restore medical record');
    }
    
    // Delete from archive table
    $stmt = $pdo->prepare("DELETE FROM `{$archiveTable}` WHERE id = ?");
    $stmt->execute([$archiveId]);
    
    // Log the activity
    log_activity(
        $pdo,
        $_SESSION['user']['id'],
        'restore_medical_record',
        "Restored medical record #{$archivedRecord['original_id']} for " . ucfirst($patientType) . " ID {$patientId}",
        'patient_archive.php'
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Clean any unwanted output and send success response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Medical record restored successfully']);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error restoring medical record: ' . $e->getMessage());
    
    // Clean any unwanted output and send error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error restoring medical record: ' . $e->getMessage()]);
    exit;
}
?>
