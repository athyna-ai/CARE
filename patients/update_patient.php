<?php
session_start();
require_once __DIR__ . '/../core/config.php';


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
    
    // Prepare the update data
    $updateData = [
        'name' => $_POST['name'] ?? '',
        'dob' => $_POST['date_of_birth'] ?? '', // Map date_of_birth to dob
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
    
    // Validate that at least one contact is provided
    if (empty($contacts)) {
        header("Location: patient_view.php?id=$patientId&type=$patientType&message=" . urlencode('At least one contact number is required') . '&message_type=error');
        exit;
    }
    
    // Update contacts
    $updateData['contacts'] = json_encode($contacts);
    
    // Add emergency contact only for faculty (legacy field)
    if ($patientType === 'faculty') {
        $updateData['emergency_contact'] = $contacts[0];
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
        // Handle different date formats
        $dobString = $updateData['dob'];
        if (strpos($dobString, '/') !== false) {
            // Convert from DD/MM/YYYY to YYYY-MM-DD for age calculation
            $date = DateTime::createFromFormat('d/m/Y', $dobString);
            if ($date) {
                $dob = $date;
            } else {
                $dob = new DateTime($dobString);
            }
        } else {
            $dob = new DateTime($dobString);
        }
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        $updateData['age'] = $age;
    }
    
    // Build the SQL query
    $setClause = [];
    $values = [];
    
    foreach ($updateData as $field => $value) {
        if ($field === 'dob' && !empty($value)) {
            // Convert date format if needed
            if (strpos($value, '/') !== false) {
                // Convert from DD/MM/YYYY to YYYY-MM-DD
                $date = DateTime::createFromFormat('d/m/Y', $value);
                if ($date) {
                    $value = $date->format('Y-m-d');
                }
            }
            $setClause[] = "`$field` = :$field";
        } else {
            $setClause[] = "`$field` = :$field";
        }
        $values[$field] = $value;
    }
    
    $sql = "UPDATE `$tableName` SET " . implode(', ', $setClause) . " WHERE id = :id";
    $values['id'] = $patientId;
    
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);
    
    if (!$result) {
        throw new Exception('Failed to update patient information');
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
