<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get form data
    $recordId = (int)$_POST['record_id'];
    $patientId = (int)$_POST['patient_id'];
    $patientType = $_POST['patient_type'];
    
    // Validate patient type
    if (!in_array($patientType, ['student', 'faculty'])) {
        throw new Exception('Invalid patient type');
    }
    
    // Get the existing record to determine form type
    $stmt = $pdo->prepare('SELECT * FROM medical_records WHERE id = ?');
    $stmt->execute([$recordId]);
    $record = $stmt->fetch();
    
    if (!$record) {
        throw new Exception('Medical record not found');
    }
    
    // Prepare updated medical history data based on form type
    if ($record['form_type'] === 'medical_history') {
        $medicalHistoryData = [
            'ongoing_conditions' => $_POST['ongoing_conditions'] ?? [],
            'ongoing_conditions_other' => $_POST['ongoing_conditions_other'] ?? '',
            'surgery_status' => $_POST['surgery_status'] ?? 'no',
            'surgery_details' => $_POST['surgery_details'] ?? '',
            'family_conditions' => $_POST['family_conditions'] ?? [],
            'family_conditions_other' => $_POST['family_conditions_other'] ?? '',
            'smoke_exposure' => $_POST['smoke_exposure'] ?? 'no',
            'immunization' => $_POST['immunization'] ?? [],
            'covid_vaccine' => $_POST['covid_vaccine'] ?? [],
            'covid_positive' => $_POST['covid_positive'] ?? 'no',
            'covid_details' => $_POST['covid_details'] ?? ''
        ];
        
        // Convert to JSON
        $formDataJson = json_encode($medicalHistoryData, JSON_PRETTY_PRINT);
    } else {
        // For other form types, update the form_data directly
        $formDataJson = $_POST['form_data'] ?? '';
        
        // Validate JSON
        $decoded = json_decode($formDataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
        }
    }
    
    // Update the medical record
    $stmt = $pdo->prepare('
        UPDATE medical_records 
        SET form_data = ?, updated_at = NOW() 
        WHERE id = ?
    ');
    
    $result = $stmt->execute([$formDataJson, $recordId]);
    
    if (!$result) {
        throw new Exception('Failed to update medical record');
    }
    
    // Log the activity
    log_activity($pdo, $_SESSION['user']['id'], 'medical_record_updated', 
        "Updated medical record ID: $recordId for patient ID: $patientId ($patientType)");
    
    // Redirect back to medical record view with success message
    header("Location: medical_record_view.php?id=$recordId&message=" . urlencode('Medical record updated successfully!') . '&type=success');
    exit;
    
} catch (Exception $e) {
    error_log("Error in update_medical_record.php: " . $e->getMessage());
    header("Location: medical_record_view.php?id=" . ($_POST['record_id'] ?? '') . "&message=" . urlencode('Error updating medical record: ' . $e->getMessage()) . '&type=error');
    exit;
}
?>
