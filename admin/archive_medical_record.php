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
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Session: ' . (isset($_SESSION['user']) ? 'User exists but not admin' : 'No user in session')]);
    exit;
}

$recordId = $_POST['id'] ?? null;
$patientId = $_POST['patient_id'] ?? null;
$patientType = $_POST['patient_type'] ?? null;

if (!$recordId || !$patientId || !$patientType) {
    error_log('Archive medical record - Missing parameters: recordId=' . ($recordId ?? 'null') . ', patientId=' . ($patientId ?? 'null') . ', patientType=' . ($patientType ?? 'null'));
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: recordId=' . ($recordId ?? 'null') . ', patientId=' . ($patientId ?? 'null') . ', patientType=' . ($patientType ?? 'null')]);
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
    
    // Check if medical_records table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'medical_records'");
    if ($tableCheck->rowCount() === 0) {
        throw new Exception('medical_records table does not exist. Please run the database update.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get the medical record
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ? AND patient_id = ? AND patient_type = ?");
    $stmt->execute([$recordId, $patientId, $patientType]);
    $record = $stmt->fetch();
    
    if (!$record) {
        throw new Exception('Medical record not found');
    }
    
    // Create patient-specific medical archive table if it doesn't exist
    $archiveTable = $patientType . '_medical_archive';
    
    // Check if table exists first
    $tableExists = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'")->rowCount() > 0;
    
    if (!$tableExists) {
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `{$archiveTable}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `original_id` int(11) NOT NULL,
            `patient_id` int(11) NOT NULL,
            `patient_type` enum('student','faculty') NOT NULL,
            `form_type` varchar(50) NOT NULL,
            `form_data` longtext NOT NULL,
            `created_at` timestamp NOT NULL,
            `archived_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `archived_by` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `patient_id` (`patient_id`),
            KEY `archived_at` (`archived_at`),
            KEY `original_id` (`original_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($createTableSQL);
    } else {
        // Check if created_at column exists and add it if missing
        $columnCheck = $pdo->query("SHOW COLUMNS FROM `{$archiveTable}` LIKE 'created_at'");
        if ($columnCheck->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `{$archiveTable}` ADD COLUMN `created_at` timestamp NOT NULL AFTER `form_data`");
        }
    }
    
    // Get a valid admin user ID for archiving
    $adminStmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
    $adminUser = $adminStmt->fetch();
    $archivedBy = $adminUser ? $adminUser['id'] : 1; // Fallback to 1 if no admin found
    
    // Check if created_at column exists in archive table
    $columnCheck = $pdo->query("SHOW COLUMNS FROM `{$archiveTable}` LIKE 'created_at'");
    $hasCreatedAt = $columnCheck->rowCount() > 0;
    
    if ($hasCreatedAt) {
        // Insert with created_at column
        $insertSQL = "
            INSERT INTO `{$archiveTable}` (
                `original_id`, `patient_id`, `patient_type`, `form_type`, `form_data`, 
                `created_at`, `archived_by`
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($insertSQL);
        $result = $stmt->execute([
            $record['id'],
            $record['patient_id'],
            $record['patient_type'],
            $record['form_type'],
            $record['form_data'],
            $record['created_at'],
            $archivedBy
        ]);
    } else {
        // Insert without created_at column
        $insertSQL = "
            INSERT INTO `{$archiveTable}` (
                `original_id`, `patient_id`, `patient_type`, `form_type`, `form_data`, 
                `archived_by`
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($insertSQL);
        $result = $stmt->execute([
            $record['id'],
            $record['patient_id'],
            $record['patient_type'],
            $record['form_type'],
            $record['form_data'],
            $archivedBy
        ]);
    }
    
    // Delete from original table
    $stmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ?");
    $stmt->execute([$recordId]);
    
    // Log the activity
    log_activity(
        $pdo,
        $archivedBy,
        'archive_medical_record',
        "Archived medical record #{$recordId} for " . ucfirst($patientType) . " ID {$patientId}",
        'medical_forms_management.php'
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Clean any unwanted output and send success response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Medical record archived successfully']);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error archiving medical record: ' . $e->getMessage());
    
    // Clean any unwanted output and send error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred while archiving the medical record. Please try again.']);
    exit;
}
?>
