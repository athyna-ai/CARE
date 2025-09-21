<?php
// Start output buffering to prevent any HTML output before JSON
ob_start();

// Suppress all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Debug session information (commented out for production)
// error_log('Archive visitation - Session data: ' . print_r($_SESSION, true));
// error_log('Archive visitation - User agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));
// error_log('Archive visitation - Session ID: ' . session_id());

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Session: ' . (isset($_SESSION['user']) ? 'User exists but not admin' : 'No user in session')]);
    exit;
}

$visitId = $_POST['id'] ?? null;
$patientId = $_POST['patient_id'] ?? null;
$patientType = $_POST['patient_type'] ?? null;

// Debug: Log the received parameters (commented out for production)
// error_log('Archive visitation - Received parameters: ' . print_r($_POST, true));

// Add debug response for testing
if (isset($_POST['debug']) && $_POST['debug'] === 'test') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Debug test successful', 'received_data' => $_POST]);
    exit;
}

if (!$visitId || !$patientId || !$patientType) {
    error_log('Archive visitation - Missing parameters: visitId=' . ($visitId ?? 'null') . ', patientId=' . ($patientId ?? 'null') . ', patientType=' . ($patientType ?? 'null'));
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: visitId=' . ($visitId ?? 'null') . ', patientId=' . ($patientId ?? 'null') . ', patientType=' . ($patientType ?? 'null')]);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Test database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Test database connection with a simple query
    $pdo->query("SELECT 1");
    
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
    
    // Get the visitation record
    $stmt = $pdo->prepare("SELECT * FROM visitation_logs WHERE id = ? AND patient_id = ? AND patient_type = ?");
    $stmt->execute([$visitId, $patientId, $patientType]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        throw new Exception('Visitation record not found');
    }
    
    // Create patient archive table if it doesn't exist
    $archiveTable = $patientType . '_visitation_archive';
    
    // Check if table exists first
    $tableExists = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'")->rowCount() > 0;
    
    if (!$tableExists) {
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `{$archiveTable}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `original_id` int(11) NOT NULL,
            `patient_id` int(11) NOT NULL,
            `patient_type` enum('student','faculty') NOT NULL,
            `reason` varchar(255) NOT NULL,
            `visit_date` datetime NOT NULL,
            `symptoms` text,
            `other_notes` text,
            `heart_rate` int(11) DEFAULT NULL,
            `blood_pressure` varchar(50) DEFAULT NULL,
            `temperature` decimal(4,2) DEFAULT NULL,
            `medication_given` tinyint(1) DEFAULT 0,
            `medication_name` varchar(255) DEFAULT NULL,
            `other_treatment` varchar(255) DEFAULT NULL,
            `medication_notes` text,
            `injury` tinyint(1) DEFAULT 0,
            `first_aid_given` tinyint(1) DEFAULT 0,
            `first_aid_type` varchar(255) DEFAULT NULL,
            `archived_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `archived_by` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `patient_id` (`patient_id`),
            KEY `archived_at` (`archived_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($createTableSQL);
    }
    
    // Insert into archive table
    $insertSQL = "
        INSERT INTO `{$archiveTable}` (
            `original_id`, `patient_id`, `patient_type`, `reason`, `visit_date`,
            `symptoms`, `other_notes`, `heart_rate`, `blood_pressure`, `temperature`,
            `medication_given`, `medication_name`, `other_treatment`, `medication_notes`,
            `injury`, `first_aid_given`, `first_aid_type`, `archived_by`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($insertSQL);
    $result = $stmt->execute([
        $visit['id'],
        $visit['patient_id'],
        $visit['patient_type'],
        $visit['reason'],
        $visit['visit_date'],
        $visit['symptoms'],
        $visit['other_notes'],
        $visit['heart_rate'],
        $visit['blood_pressure'],
        $visit['temperature'],
        $visit['medication_given'],
        $visit['medication_name'],
        $visit['other_treatment'],
        $visit['medication_notes'],
        $visit['injury'],
        $visit['first_aid_given'],
        $visit['first_aid_type'],
        $_SESSION['user']['id']
    ]);
    
    // Delete from original table
    $stmt = $pdo->prepare("DELETE FROM visitation_logs WHERE id = ?");
    $stmt->execute([$visitId]);
    
    // Log the activity
    log_activity(
        $pdo,
        $_SESSION['user']['id'],
        'archive_visitation',
        "Archived visitation record #{$visitId} for " . ucfirst($patientType) . " ID {$patientId}",
        'patient_view.php'
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Clean any unwanted output and send success response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Visitation record archived successfully']);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error archiving visitation: ' . $e->getMessage());
    
    // Clean any unwanted output and send error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error archiving visitation: ' . $e->getMessage()]);
    exit;
}
?>
