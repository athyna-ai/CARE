<?php
// Start output buffering to prevent any HTML output before JSON
ob_start();

// Suppress all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Session: ' . (isset($_SESSION['user']) ? 'User exists but not admin' : 'No user in session')]);
    exit;
}

$archiveId = $_POST['archive_id'] ?? null;
$patientId = $_POST['patient_id'] ?? null;
$patientType = $_POST['patient_type'] ?? null;

if (!$archiveId || !$patientId || !$patientType) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: archive_id=' . ($archiveId ?? 'null') . ', patient_id=' . ($patientId ?? 'null') . ', patient_type=' . ($patientType ?? 'null')]);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Test database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if visitation_logs table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'visitation_logs'");
    if ($tableCheck->rowCount() === 0) {
        throw new Exception('visitation_logs table does not exist. Please run the database update.');
    }
    
    // Check if archive table exists
    $archiveTable = $patientType . '_visitation_archive';
    $archiveTableCheck = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'");
    if ($archiveTableCheck->rowCount() === 0) {
        throw new Exception("Archive table '{$archiveTable}' does not exist. Please run the database update.");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get the archived visitation record
    $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE id = ? AND patient_id = ? AND patient_type = ?");
    $stmt->execute([$archiveId, $patientId, $patientType]);
    $archivedVisit = $stmt->fetch();
    
    if (!$archivedVisit) {
        throw new Exception('Archived visitation record not found');
    }
    
    // Insert back into active visitation_logs table
    $insertSQL = "
        INSERT INTO visitation_logs (
            patient_id, patient_type, reason, visit_date, symptoms, other_notes,
            heart_rate, blood_pressure, temperature, medication_given, medication_name,
            other_treatment, medication_notes, injury, first_aid_given, first_aid_type,
            nurse_name, created_by, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($insertSQL);
    $result = $stmt->execute([
        $archivedVisit['patient_id'],
        $archivedVisit['patient_type'],
        $archivedVisit['reason'],
        $archivedVisit['visit_date'],
        $archivedVisit['symptoms'],
        $archivedVisit['other_notes'],
        $archivedVisit['heart_rate'],
        $archivedVisit['blood_pressure'],
        $archivedVisit['temperature'],
        $archivedVisit['medication_given'],
        $archivedVisit['medication_name'],
        $archivedVisit['other_treatment'],
        $archivedVisit['medication_notes'],
        $archivedVisit['injury'],
        $archivedVisit['first_aid_given'],
        $archivedVisit['first_aid_type'],
        $archivedVisit['nurse_name'] ?? 'Unknown', // Handle NULL nurse_name
        $_SESSION['user']['id'],
        $archivedVisit['created_at'],
        date('Y-m-d H:i:s')
    ]);
    
    if (!$result) {
        throw new Exception('Failed to restore visitation record');
    }
    
    $newVisitId = $pdo->lastInsertId();
    
    // Delete from archive table
    $stmt = $pdo->prepare("DELETE FROM `{$archiveTable}` WHERE id = ?");
    $stmt->execute([$archiveId]);
    
    // Log the activity
    log_activity(
        $pdo,
        $_SESSION['user']['id'],
        'restore_visitation',
        "Restored archived visitation record #{$archiveId} (now #{$newVisitId}) for " . ucfirst($patientType) . " ID {$patientId}",
        'patient_archive.php'
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Clean any unwanted output and send success response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Visitation record restored successfully', 'new_id' => $newVisitId]);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error restoring visitation: ' . $e->getMessage());
    
    // Clean any unwanted output and send error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error restoring visitation: ' . $e->getMessage()]);
    exit;
}
?>
