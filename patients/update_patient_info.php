<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get form data
$patientId = (int)($_POST['patient_id'] ?? 0);
$patientType = sanitize_string($_POST['patient_type'] ?? 'student');

if ($patientId <= 0) {
    header('Location: ../admin/dashboard.php?error=invalid_patient');
    exit;
}

try {
    // Validate required fields
    $requiredFields = ['name', 'rfid', 'gender'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    // Validate contacts
    $contacts = $_POST['contacts'] ?? [];
    $contacts = array_filter($contacts, function($contact) {
        return !empty(trim($contact));
    });
    
    if (empty($contacts)) {
        $missingFields[] = 'contacts';
    }
    
    if (!empty($missingFields)) {
        $message = urlencode('Please fill in all required fields: ' . implode(', ', $missingFields));
        header("Location: patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type=error");
        exit;
    }
    
    // Prepare the update query based on patient type
    if ($patientType === 'student') {
        $sql = 'UPDATE students SET 
                name = ?, 
                rfid = ?, 
                level = ?, 
                year_grade = ?, 
                course = ?, 
                block = ?, 
                strand = ?, 
                section = ?, 
                dob = ?, 
                gender = ?, 
                religion = ?, 
                address = ?, 
                guardian = ?, 
                contacts = ?, 
                allergies = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?';
        
        $stmt = $pdo->prepare($sql);
        
        // Process contacts array
        $contactsJson = json_encode($contacts);
        
        // Handle date of birth conversion
        $dob = null;
        if (!empty($_POST['date_of_birth'])) {
            $dob = date('Y-m-d', strtotime($_POST['date_of_birth']));
        }
        
        $stmt->execute([
            sanitize_string($_POST['name'] ?? ''),
            sanitize_string($_POST['rfid'] ?? ''),
            sanitize_string($_POST['level'] ?? ''),
            sanitize_string($_POST['year_grade'] ?? ''),
            sanitize_string($_POST['course'] ?? ''),
            sanitize_string($_POST['block'] ?? ''),
            sanitize_string($_POST['strand'] ?? ''),
            sanitize_string($_POST['section'] ?? ''),
            $dob,
            sanitize_string($_POST['gender'] ?? ''),
            sanitize_string($_POST['religion'] ?? ''),
            sanitize_string($_POST['address'] ?? ''),
            sanitize_string($_POST['guardian'] ?? ''),
            $contactsJson,
            sanitize_string($_POST['allergies'] ?? ''),
            $patientId
        ]);
    } else {
        // Faculty update
        $sql = 'UPDATE faculty SET 
                name = ?, 
                rfid = ?, 
                department = ?, 
                dob = ?, 
                gender = ?, 
                religion = ?, 
                address = ?, 
                contacts = ?, 
                allergies = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?';
        
        $stmt = $pdo->prepare($sql);
        
        // Process contacts array
        $contactsJson = json_encode($contacts);
        
        // Handle date of birth conversion
        $dob = null;
        if (!empty($_POST['date_of_birth'])) {
            $dob = date('Y-m-d', strtotime($_POST['date_of_birth']));
        }
        
        $stmt->execute([
            sanitize_string($_POST['name'] ?? ''),
            sanitize_string($_POST['rfid'] ?? ''),
            sanitize_string($_POST['department'] ?? ''),
            $dob,
            sanitize_string($_POST['gender'] ?? ''),
            sanitize_string($_POST['religion'] ?? ''),
            sanitize_string($_POST['address'] ?? ''),
            $contactsJson,
            sanitize_string($_POST['allergies'] ?? ''),
            $patientId
        ]);
    }
    
    // Log the activity
    $logSql = "INSERT INTO activity_logs (user_id, action, description, location, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $_SESSION['user']['id'],
        'update_patient',
        "Updated $patientType information for patient ID: $patientId",
        'patient_view',
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Redirect back to patient view with success message
    $message = urlencode('Patient information updated successfully!');
    header("Location: patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type=success");
    exit;
    
} catch (Exception $e) {
    error_log("Error updating patient info: " . $e->getMessage());
    
    // Redirect back with error message
    $message = urlencode('Error updating patient information. Please try again.');
    header("Location: patient_view.php?id={$patientId}&type={$patientType}&message={$message}&message_type=error");
    exit;
}
?>
