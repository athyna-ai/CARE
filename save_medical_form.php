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
    $patientId = (int)$_POST['patient_id'];
    $patientType = $_POST['patient_type'];
    $formType = $_POST['form_type'];
    
    // Validate patient type
    if (!in_array($patientType, ['student', 'faculty'])) {
        throw new Exception('Invalid patient type');
    }
    
    // Validate form type
    if (!in_array($formType, ['athlete', 'general', 'emergency'])) {
        throw new Exception('Invalid form type');
    }
    
    // Prepare form data based on type
    $formData = [];
    
    if ($formType === 'athlete') {
        $formData = [
            'sport' => $_POST['sport'] ?? '',
            'position' => $_POST['position'] ?? '',
            'height' => $_POST['height'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'medical_history' => $_POST['medical_history'] ?? '',
            'physical_exam' => $_POST['physical_exam'] ?? '',
            'recommendations' => $_POST['recommendations'] ?? ''
        ];
    } elseif ($formType === 'general') {
        $formData = [
            'chief_complaint' => $_POST['chief_complaint'] ?? '',
            'duration' => $_POST['duration'] ?? '',
            'history_present' => $_POST['history_present'] ?? '',
            'past_medical' => $_POST['past_medical'] ?? '',
            'physical_exam' => $_POST['physical_exam'] ?? '',
            'assessment_plan' => $_POST['assessment_plan'] ?? ''
        ];
    } elseif ($formType === 'emergency') {
        $formData = [
            'emergency_type' => $_POST['emergency_type'] ?? '',
            'severity' => $_POST['severity'] ?? '',
            'emergency_description' => $_POST['emergency_description'] ?? '',
            'immediate_actions' => $_POST['immediate_actions'] ?? '',
            'heart_rate' => $_POST['heart_rate'] ?? '',
            'blood_pressure' => $_POST['blood_pressure'] ?? '',
            'temperature' => $_POST['temperature'] ?? '',
            'follow_up' => $_POST['follow_up'] ?? ''
        ];
    }
    
    // Remove empty values
    $formData = array_filter($formData, function($value) {
        return $value !== '';
    });
    
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
        $formType,
        $formDataJson,
        $_SESSION['user']['id']
    ]);
    
    // Log the activity
    log_activity($pdo, $_SESSION['user']['id'], 'medical_form_created', 
        "Created {$formType} medical form for patient ID {$patientId}", 
        'medical/forms'
    );
    
    // Redirect back to patient view with success message
    $message = urlencode("Medical form saved successfully!");
    $type = 'success';
    header("Location: patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type={$type}");
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log("Medical form save error: " . $e->getMessage());
    
    // Redirect back with error message
    $message = urlencode("Error saving medical form: " . $e->getMessage());
    $type = 'error';
    header("Location: patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type={$type}");
    exit;
}
?>
