<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $patientType = sanitize_string($_POST['patient_type'] ?? '');
    $reason = sanitize_string($_POST['reason'] ?? '');
    $visitDate = sanitize_string($_POST['visit_date'] ?? '');
    $symptoms = sanitize_string($_POST['symptoms'] ?? '');
    $heartRate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
    $bloodPressure = sanitize_string($_POST['blood_pressure'] ?? '');
    $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
    $otherNotes = sanitize_string($_POST['other_notes'] ?? '');
    $medicationGiven = isset($_POST['medication_given']);
    $medicationName = sanitize_string($_POST['medication_name'] ?? '');
    $otherTreatment = sanitize_string($_POST['other_treatment'] ?? '');
    $medicationNotes = sanitize_string($_POST['medication_notes'] ?? '');
    $injury = isset($_POST['injury']);
    $firstAidGiven = isset($_POST['first_aid_given']);
    $firstAidType = sanitize_string($_POST['first_aid_type'] ?? '');
    
    // Get nurse name from current user
    $nurseName = $_SESSION['user']['name'] ?? 'Unknown Nurse';
    
    if ($patientId > 0 && $patientType && $reason && $visitDate) {
        try {
            // Create visitation_logs table if it doesn't exist
            $pdo->exec('CREATE TABLE IF NOT EXISTS visitation_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                patient_type ENUM("student", "faculty") NOT NULL,
                reason VARCHAR(100) NOT NULL,
                visit_date DATETIME NOT NULL,
                symptoms TEXT NULL,
                heart_rate INT NULL,
                blood_pressure VARCHAR(20) NULL,
                temperature DECIMAL(4,1) NULL,
                other_notes TEXT NULL,
                medication_given BOOLEAN DEFAULT FALSE,
                medication_name VARCHAR(100) NULL,
                other_treatment VARCHAR(100) NULL,
                medication_notes TEXT NULL,
                injury BOOLEAN DEFAULT FALSE,
                first_aid_given BOOLEAN DEFAULT FALSE,
                first_aid_type VARCHAR(100) NULL,
                nurse_name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_patient (patient_id, patient_type),
                INDEX idx_visit_date (visit_date),
                INDEX idx_reason (reason)
            )');
            
            // Insert visitation record
            $stmt = $pdo->prepare('INSERT INTO visitation_logs (
                patient_id, patient_type, reason, visit_date, symptoms, heart_rate, 
                blood_pressure, temperature, other_notes, medication_given, 
                medication_name, other_treatment, medication_notes, injury, 
                first_aid_given, first_aid_type, nurse_name, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            
            $stmt->execute([
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
            
            // Log activity
            log_activity($pdo, (int)$_SESSION['user']['id'], 'visitation_logged', "Added visitation record for patient ID {$patientId} - Reason: {$reason}", 'save_visitation');
            
            // Redirect back with success message
            header("Location: patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode("Visitation record saved successfully") . "&type=success");
            exit;
            
        } catch (Exception $e) {
            error_log("Save visitation error: " . $e->getMessage());
            header("Location: patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode("Error saving visitation record") . "&type=error");
            exit;
        }
    } else {
        header("Location: patient_view.php?id={$patientId}&type={$patientType}&message=" . urlencode("Missing required fields") . "&type=error");
        exit;
    }
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit;
?>
