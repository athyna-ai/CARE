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
    
    // Get form data
    $patientId = (int)$_POST['patient_id'];
    $patientType = $_POST['patient_type'];
    $formType = $_POST['form_type'] ?? 'medical_history';
    
    // Validate patient type
    if (!in_array($patientType, ['student', 'faculty'])) {
        throw new Exception('Invalid patient type');
    }
    
    // Prepare form data based on type
    if ($formType === 'general_checkup') {
        // General CheckUp form data
        $formData = [
            'assessment_plan' => !empty($_POST['assessment_plan']) ? $_POST['assessment_plan'] : 'N/A',
            // Physical measurements
            'height' => $_POST['height'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'bmi' => $_POST['bmi'] ?? '',
            'bmi_status' => $_POST['bmi_status'] ?? '',
            // Vital signs
            'heart_rate' => $_POST['heart_rate'] ?? '',
            'heart_rate_status' => $_POST['heart_rate_status'] ?? '',
            'temperature' => $_POST['temperature'] ?? '',
            'temperature_status' => $_POST['temperature_status'] ?? '',
            'blood_pressure' => $_POST['blood_pressure'] ?? '',
            'blood_pressure_status' => $_POST['blood_pressure_status'] ?? ''
        ];
        $formTypeValue = 'general_checkup';
        $successMessage = "General CheckUp saved successfully!";
    } else {
        // Complex medical history form data
        $formData = [
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
            'covid_details' => $_POST['covid_details'] ?? '',
            'allergies' => $_POST['allergies'] ?? ''
        ];
        $formTypeValue = 'medical_history';
        $successMessage = "Medical history saved successfully!";
    }
    
    // Convert to JSON
    $formDataJson = json_encode($formData, JSON_PRETTY_PRINT);
    
    // Insert into medical_records table
    $stmt = $pdo->prepare('
        INSERT INTO medical_records (patient_id, patient_type, form_type, form_data, created_by) 
        VALUES (?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $patientId,
        $patientType,
        $formTypeValue,
        $formDataJson,
        $_SESSION['user']['id']
    ]);
    
    // Log the activity
    log_activity($pdo, $_SESSION['user']['id'], $formTypeValue . '_created', 
        "Created {$formTypeValue} form for patient ID {$patientId}", 
        'medical/history'
    );
    
    // Redirect back to patient view with success message
    $message = urlencode($successMessage);
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
