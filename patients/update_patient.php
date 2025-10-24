<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';


// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$patientId = $_POST['patient_id'] ?? '';
$patientType = $_POST['patient_type'] ?? '';

if (empty($patientId) || empty($patientType)) {
    header('Location: ../admin/dashboard.php?message=' . urlencode('Invalid patient information'));
    exit;
}

try {
    $pdo = get_pdo();
    
    // Debug logging
    error_log("Update Patient Debug - Patient ID: $patientId, Type: $patientType");
    error_log("POST Data: " . print_r($_POST, true));
    
    // Validate required fields
    $requiredFields = ['name', 'date_of_birth', 'gender', 'address'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Please fill in all required fields: ' . implode(', ', $missingFields)) . '&message_type=error');
        exit;
    }
    
    // Get the table name based on patient type
    $tableName = ($patientType === 'student') ? 'students' : 'faculty';
    
    // Prepare the update data with proper field mapping
    $name = sanitize_string($_POST['name'] ?? '');
    $address = sanitize_string($_POST['address'] ?? '');
    $religion = sanitize_string($_POST['religion'] ?? '');
    $allergies = sanitize_string($_POST['allergies'] ?? '');
    
    // Set to N/A if allergies is empty
    if (empty(trim($allergies))) {
        $allergies = 'N/A';
    }
    
    // Prepare the update data - using original schema fields
    $updateData = [
        'name' => $name,
        'address' => $address,
        'dob' => !empty($_POST['date_of_birth']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['date_of_birth']))) : null,
        'religion' => $religion,
        'allergies' => $allergies,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add gender field for faculty
    if ($patientType === 'faculty') {
        $updateData['gender'] = $_POST['gender'] ?? '';
    }
    
    // Add guardian field only for students
    if ($patientType === 'student') {
        $guardian = sanitize_string($_POST['guardian'] ?? '');
        $updateData['guardian'] = $guardian;
    }
    
    // Handle contacts array for both students and faculty
    $contacts = $_POST['contacts'] ?? [];
    $contacts = array_filter($contacts, function($contact) {
        return !empty(trim($contact));
    });
    
    // Validate that at least one contact is provided
    if (empty($contacts)) {
        header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('At least one contact number is required') . '&message_type=error');
        exit;
    }
    
    // Update contacts
    $updateData['contacts'] = json_encode($contacts);
    
    // Add emergency contact for faculty (legacy field)
    if ($patientType === 'faculty') {
        $emergencyContact = $contacts[0] ?? '';
        $updateData['emergency_contact'] = $emergencyContact;
    }
    
    // Add type-specific fields
    if ($patientType === 'student') {
        $updateData['level'] = $_POST['level'] ?? '';
        $updateData['gender'] = $_POST['gender'] ?? '';
        
        // Handle dynamic fields based on level
        $level = $_POST['level'] ?? '';
        if (in_array($level, ['Elementary', 'High School', 'Senior High School', 'College'])) {
            $updateData['year_grade'] = $_POST['year_grade'] ?? 'N/A';
        } else {
            $updateData['year_grade'] = 'N/A';
        }
        
        if ($level === 'College') {
            $updateData['course'] = $_POST['course'] ?? 'N/A';
            $updateData['block'] = $_POST['block'] ?? 'N/A';
            $updateData['strand'] = 'N/A';
            $updateData['section'] = 'N/A';
        } elseif ($level === 'Senior High School') {
            $updateData['strand'] = $_POST['strand'] ?? 'N/A';
            $updateData['course'] = 'N/A';
            $updateData['block'] = 'N/A';
            $updateData['section'] = 'N/A';
        } elseif (in_array($level, ['Elementary', 'High School'])) {
            $updateData['section'] = $_POST['section'] ?? 'N/A';
            $updateData['course'] = 'N/A';
            $updateData['block'] = 'N/A';
            $updateData['strand'] = 'N/A';
        } else {
            // Pre-school - no additional fields needed
            $updateData['course'] = 'N/A';
            $updateData['block'] = 'N/A';
            $updateData['strand'] = 'N/A';
            $updateData['section'] = 'N/A';
        }
    } else {
        $updateData['department'] = $_POST['department'] ?? '';
    }
    
    // Calculate age from date of birth
    if (!empty($updateData['dob'])) {
        $dob = new DateTime($updateData['dob']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        $updateData['age'] = $age;
    }
    
    // Build the SQL query
    $setClause = [];
    $values = [];
    
    foreach ($updateData as $field => $value) {
        $setClause[] = "`$field` = :$field";
        $values[$field] = $value;
    }
    
    $sql = "UPDATE `$tableName` SET " . implode(', ', $setClause) . " WHERE id = :id";
    $values['id'] = $patientId;
    
    // Debug logging
    error_log("Final SQL Query: " . $sql);
    error_log("Final Values: " . print_r($values, true));
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL Error in update_patient.php: " . print_r($errorInfo, true));
        error_log("SQL Query: " . $sql);
        error_log("Values: " . print_r($values, true));
        throw new Exception('Failed to update patient information: ' . $errorInfo[2]);
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
    $patientId = (int)$patientId; // Ensure it's an integer
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Patient information updated successfully!') . '&message_type=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Database error in update_patient.php: " . $e->getMessage());
    $patientId = (int)$patientId; // Ensure it's an integer
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Error updating patient information. Please try again.') . '&message_type=error');
    exit;
} catch (Exception $e) {
    error_log("General error in update_patient.php: " . $e->getMessage());
    $patientId = (int)$patientId; // Ensure it's an integer
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Error updating patient information. Please try again.') . '&message_type=error');
    exit;
}
?>
