<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$patientId = $_POST['patient_id'] ?? '';
$patientType = $_POST['patient_type'] ?? '';

if (empty($patientId) || empty($patientType)) {
    header('Location: dashboard.php?message=' . urlencode('Invalid patient information'));
    exit;
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Debug: Log the received data
    error_log("Update Patient Debug - Patient ID: $patientId, Type: $patientType");
    error_log("Update Patient Debug - POST data: " . print_r($_POST, true));
    
    // Get the table name based on patient type
    $tableName = ($patientType === 'student') ? 'students' : 'faculty';
    
    // Prepare the update data
    $updateData = [
        'name' => $_POST['name'] ?? '',
        'dob' => $_POST['date_of_birth'] ?? '', // Use 'dob' to match database column
        'gender' => $_POST['gender'] ?? '',
        'religion' => $_POST['religion'] ?? '',
        'address' => $_POST['address'] ?? '',
        'allergies' => !empty($_POST['allergies']) ? $_POST['allergies'] : 'N/A',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add guardian field only for students
    if ($patientType === 'student') {
        $updateData['guardian'] = $_POST['guardian'] ?? '';
    }
    
    // Handle contacts array for both students and faculty
    $contacts = $_POST['contacts'] ?? [];
    $contacts = array_filter($contacts, function($contact) {
        return !empty(trim($contact));
    });
    
    // Only update contacts if there are actual contacts, otherwise keep existing
    if (!empty($contacts)) {
        $updateData['contacts'] = json_encode($contacts);
    }
    
    // Add emergency contact only for faculty (legacy field)
    if ($patientType === 'faculty') {
        $updateData['emergency_contact'] = !empty($contacts) ? $contacts[0] : '';
    }
    
    // Add type-specific fields
    if ($patientType === 'student') {
        $updateData['level'] = $_POST['level'] ?? '';
        
        // Handle dynamic fields based on level
        $level = $_POST['level'] ?? '';
        if (in_array($level, ['Elementary', 'High School', 'Senior High School', 'College'])) {
            $updateData['year_grade'] = $_POST['year_grade'] ?? 'N/A';
        } else {
            $updateData['year_grade'] = 'N/A';
        }
        
        if ($level === 'College') {
            $updateData['course'] = $_POST['course'] ?? 'N/A';
            $updateData['strand'] = 'N/A';
            $updateData['section'] = 'N/A';
        } elseif ($level === 'Senior High School') {
            $updateData['strand'] = $_POST['strand'] ?? 'N/A';
            $updateData['course'] = 'N/A';
            $updateData['section'] = 'N/A';
        } elseif (in_array($level, ['Elementary', 'High School'])) {
            $updateData['section'] = $_POST['section'] ?? 'N/A';
            $updateData['course'] = 'N/A';
            $updateData['strand'] = 'N/A';
        } else {
            // Pre-school - no additional fields needed
            $updateData['course'] = 'N/A';
            $updateData['strand'] = 'N/A';
            $updateData['section'] = 'N/A';
        }
    } else {
        $updateData['department'] = $_POST['department'] ?? '';
    }
    
    // Calculate age from date of birth
    if (!empty($updateData['date_of_birth'])) {
        $dob = new DateTime($updateData['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        $updateData['age'] = $age;
    }
    
    // Build the SQL query
    $setClause = [];
    $values = [];
    
    foreach ($updateData as $field => $value) {
        if ($field === 'dob') {
            // Special handling for date of birth with STR_TO_DATE
            $setClause[] = "`$field` = STR_TO_DATE(:$field, '%d/%m/%Y')";
        } else {
            $setClause[] = "`$field` = :$field";
        }
        $values[$field] = $value;
    }
    
    $sql = "UPDATE `$tableName` SET " . implode(', ', $setClause) . " WHERE id = :id";
    $values['id'] = $patientId;
    
    // Debug: Log the SQL and values
    error_log("Update Patient Debug - SQL: $sql");
    error_log("Update Patient Debug - Values: " . print_r($values, true));
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);
    
    if (!$result) {
        throw new Exception('Failed to update patient information');
    }
    
    // Log the activity
    $logSql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        $_SESSION['user']['id'],
        'update_patient',
        "Updated $patientType information for patient ID: $patientId",
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    // Redirect back to patient view with success message
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Patient information updated successfully!') . '&type=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Database error in update_patient.php: " . $e->getMessage());
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Error updating patient information. Please try again.') . '&type=error');
    exit;
} catch (Exception $e) {
    error_log("General error in update_patient.php: " . $e->getMessage());
    header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('Error updating patient information. Please try again.') . '&type=error');
    exit;
}
?>
