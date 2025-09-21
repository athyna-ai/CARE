<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log the POST data
    error_log('Save visitation POST data: ' . print_r($_POST, true));
    error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('POST data count: ' . count($_POST));
    
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $patientType = sanitize_string($_POST['patient_type'] ?? '');
    $reason = sanitize_string($_POST['reason'] ?? '');
    $otherReason = sanitize_string($_POST['other_reason'] ?? '');
    
    // Use other_reason if reason is 'other'
    if ($reason === 'other' && !empty($otherReason)) {
        $reason = $otherReason;
    }
    
    $visitDate = sanitize_string($_POST['visit_date'] ?? '');
    $symptoms = sanitize_string($_POST['symptoms'] ?? '');
    $heartRate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    $bloodPressure = sanitize_string($_POST['blood_pressure'] ?? '');
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $otherNotes = sanitize_string($_POST['other_notes'] ?? '');
    $medicationGiven = isset($_POST['medication_given']);
    $medicationName = sanitize_string($_POST['medication_name'] ?? '');
    $otherMedication = sanitize_string($_POST['other_medication'] ?? '');
    
    // Use other_medication if medication_name is 'other'
    if ($medicationName === 'other' && !empty($otherMedication)) {
        $medicationName = $otherMedication;
    }
    
    $otherTreatment = sanitize_string($_POST['other_treatment'] ?? '');
    $medicationNotes = sanitize_string($_POST['medication_notes'] ?? '');
    $injury = isset($_POST['injury']);
    $firstAidGiven = isset($_POST['first_aid_given']);
    $firstAidType = sanitize_string($_POST['first_aid_type'] ?? '');
    $otherFirstAid = sanitize_string($_POST['other_first_aid'] ?? '');
    
    // Use other_first_aid if first_aid_type is 'other'
    if ($firstAidType === 'other' && !empty($otherFirstAid)) {
        $firstAidType = $otherFirstAid;
    }
    
    // Get nurse name from current user
    $nurseName = $_SESSION['user']['name'] ?? 'Unknown Nurse';
    
    // Debug: Log the processed values
    error_log("Processed values - Patient ID: {$patientId}, Type: {$patientType}, Reason: {$reason}, Visit Date: {$visitDate}");
    error_log("Other fields - Other Reason: {$otherReason}, Medication Name: {$medicationName}, Other Medication: {$otherMedication}, First Aid Type: {$firstAidType}, Other First Aid: {$otherFirstAid}");
    
    // Debug: Check validation
    error_log("Validation check - Patient ID: {$patientId} (>0: " . ($patientId > 0 ? 'true' : 'false') . "), Type: '{$patientType}' (empty: " . (empty($patientType) ? 'true' : 'false') . "), Reason: '{$reason}' (empty: " . (empty($reason) ? 'true' : 'false') . "), Visit Date: '{$visitDate}' (empty: " . (empty($visitDate) ? 'true' : 'false') . ")");
    
    if ($patientId > 0 && $patientType && $reason && $visitDate) {
        try {
            // Check if visitation_logs table exists, if not create it
            $tableExists = false;
            try {
                $pdo->query("SELECT 1 FROM visitation_logs LIMIT 1");
                $tableExists = true;
                error_log("Visitation_logs table exists");
            } catch (Exception $e) {
                $tableExists = false;
                error_log("Visitation_logs table does not exist, will create it");
            }
            
            if (!$tableExists) {
                // Create visitation_logs table
                $createTableSQL = 'CREATE TABLE visitation_logs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    patient_id INT UNSIGNED NOT NULL,
                    patient_type ENUM("student", "faculty") NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    visit_date DATETIME NOT NULL,
                    symptoms TEXT NULL,
                    heart_rate INT NULL,
                    blood_pressure VARCHAR(50) NULL,
                    temperature DECIMAL(4,2) NULL,
                    other_notes TEXT NULL,
                    medication_given TINYINT(1) DEFAULT 0,
                    medication_name VARCHAR(255) NULL,
                    other_treatment VARCHAR(255) NULL,
                    medication_notes TEXT NULL,
                    injury TINYINT(1) DEFAULT 0,
                    first_aid_given TINYINT(1) DEFAULT 0,
                    first_aid_type VARCHAR(255) NULL,
                    nurse_name VARCHAR(100) NULL,
                    created_by INT UNSIGNED NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_patient_id (patient_id),
                    INDEX idx_patient_type (patient_type),
                    INDEX idx_visit_date (visit_date),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
                
                $pdo->exec($createTableSQL);
                error_log("Visitation_logs table created successfully");
            }
            
            // Insert visitation record
            $stmt = $pdo->prepare('INSERT INTO visitation_logs (
                patient_id, patient_type, reason, visit_date, symptoms, heart_rate, 
                blood_pressure, temperature, other_notes, medication_given, 
                medication_name, other_treatment, medication_notes, injury, 
                first_aid_given, first_aid_type, nurse_name, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            
            $result = $stmt->execute([
                $patientId,
                $patientType,
                $reason,
                $visitDate,
                $symptoms ?: null,
                $heartRate,
                $bloodPressure ?: null,
                $temperature,
                $otherNotes ?: null,
                $medicationGiven,
                $medicationName ?: null,
                $otherTreatment ?: null,
                $medicationNotes ?: null,
                $injury,
                $firstAidGiven,
                $firstAidType ?: null,
                $nurseName,
                $_SESSION['user']['id']
            ]);
            
            if ($result) {
                error_log("Visitation record inserted successfully - ID: " . $pdo->lastInsertId());
            } else {
                error_log("Failed to insert visitation record");
            }
            
            // Log activity
            log_activity($pdo, (int)$_SESSION['user']['id'], 'visitation_logged', "Added visitation record for patient ID {$patientId} - Reason: {$reason}", 'save_visitation');
            
            // Debug: Log the redirect URL
            $redirectUrl = "patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode("Visitation record saved successfully") . "&message_type=success";
            error_log("Redirecting to: " . $redirectUrl);
            
            // Redirect back with success message
            header("Location: " . $redirectUrl);
            exit;
            
        } catch (Exception $e) {
            error_log("Save visitation error: " . $e->getMessage());
            error_log("Save visitation error trace: " . $e->getTraceAsString());
            // Always redirect back to patient view, never to dashboard
            $errorMessage = "Error saving visitation record: " . $e->getMessage();
            header("Location: patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode($errorMessage) . "&message_type=error");
            exit;
        }
    } else {
        error_log("Missing required fields - Patient ID: {$patientId}, Type: {$patientType}, Reason: {$reason}, Visit Date: {$visitDate}");
        error_log("Form validation failed - POST data: " . print_r($_POST, true));
        header("Location: patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode("Missing required fields: Patient ID={$patientId}, Type={$patientType}, Reason={$reason}, Visit Date={$visitDate}") . "&message_type=error");
        exit;
    }
}

// If not POST request, redirect to dashboard
error_log("Not a POST request, redirecting to dashboard");
error_log("Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));
header('Location: ../admin/dashboard.php');
exit;
?>
