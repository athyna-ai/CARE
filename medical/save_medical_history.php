<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/dashboard.php');
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get form data
    $patientId = (int)$_POST['patient_id'];
    $patientType = $_POST['patient_type'];
    
    // Validate patient type
    if (!in_array($patientType, ['student', 'faculty'])) {
        throw new Exception('Invalid patient type');
    }
    
    // Prepare medical history data
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
    
    // Insert into medical_records table
    $stmt = $pdo->prepare('
        INSERT INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
        VALUES (?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $patientId,
        $patientType,
        'medical_history',
        $formDataJson,
        $_SESSION['user']['id']
    ]);
    
    // Log the activity
    log_activity($pdo, $_SESSION['user']['id'], 'medical_history_created', 
        "Created medical history form for patient ID {$patientId}", 
        'medical/history'
    );
    
    // Redirect back to patient view with success message
    $message = urlencode("Medical history saved successfully!");
    $type = 'success';
    header("Location: ../patients/patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type={$type}");
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log("Medical history save error: " . $e->getMessage());
    
    // Redirect back with error message
    $message = urlencode("Error saving medical history: " . $e->getMessage());
    $type = 'error';
    header("Location: ../patients/patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type={$type}");
    exit;
}
?>
