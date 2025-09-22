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
    
    // Prepare the update data with proper field mapping
    $name = sanitize_string($_POST['name'] ?? '');
    $address = sanitize_string($_POST['address'] ?? '');
    $religion = sanitize_string($_POST['religion'] ?? '');
    $allergies = sanitize_string($_POST['allergies'] ?? '');
    
    // Set to N/A if allergies is empty
    if (empty(trim($allergies))) {
        $allergies = 'N/A';
    }
    
    // Hash the sensitive data
    $nameHash = password_hash($name, PASSWORD_DEFAULT);
    $addressHash = password_hash($address, PASSWORD_DEFAULT);
    
    // Create masked versions (show first 2 chars, mask the rest)
    $nameMasked = strlen($name) > 2 ? substr($name, 0, 2) . str_repeat('*', strlen($name) - 2) : $name;
    $addressMasked = strlen($address) > 2 ? substr($address, 0, 2) . str_repeat('*', strlen($address) - 2) : $address;
    
    // Prepare the update data - store both hashed and plain text for functionality
    $updateData = [
        'name_hash' => $nameHash,
        'name_masked' => $nameMasked,
        'name' => $name, // Store plain text for display
        'address_hash' => $addressHash,
        'address_masked' => $addressMasked,
        'address' => $address, // Store plain text for display
        'dob' => !empty($_POST['date_of_birth']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['date_of_birth']))) : null,
        'gender' => $_POST['gender'] ?? '',
        'religion' => $religion,
        'allergies' => $allergies,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add guardian field only for students
    if ($patientType === 'student') {
        $guardian = sanitize_string($_POST['guardian'] ?? '');
        $guardianHash = password_hash($guardian, PASSWORD_DEFAULT);
        $guardianMasked = strlen($guardian) > 2 ? substr($guardian, 0, 2) . str_repeat('*', strlen($guardian) - 2) : $guardian;
        
        $updateData['guardian_hash'] = $guardianHash;
        $updateData['guardian_masked'] = $guardianMasked;
        $updateData['guardian'] = $guardian; // Store plain text for display
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
    
    // Hash the contact numbers
    $hashedContacts = [];
    $maskedContacts = [];
    foreach ($contacts as $contact) {
        $hashedContacts[] = password_hash($contact, PASSWORD_DEFAULT);
        $maskedContacts[] = strlen($contact) > 4 ? substr($contact, 0, 4) . str_repeat('*', strlen($contact) - 4) : $contact;
    }
    
    // Update contacts
    $updateData['contacts'] = json_encode($contacts); // Store original for functionality
    $updateData['contacts_hash'] = json_encode($hashedContacts);
    $updateData['contacts_masked'] = json_encode($maskedContacts);
    
    // Add emergency contact for faculty (legacy field)
    if ($patientType === 'faculty') {
        $emergencyContact = $contacts[0];
        $emergencyContactHash = password_hash($emergencyContact, PASSWORD_DEFAULT);
        $emergencyContactMasked = strlen($emergencyContact) > 4 ? substr($emergencyContact, 0, 4) . str_repeat('*', strlen($emergencyContact) - 4) : $emergencyContact;
        
        $updateData['emergency_contact'] = $emergencyContact;
        $updateData['emergency_contact_hash'] = $emergencyContactHash;
        $updateData['emergency_contact_masked'] = $emergencyContactMasked;
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
