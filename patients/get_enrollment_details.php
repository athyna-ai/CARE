<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, log them instead

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $pdo = get_pdo();
    
    // Get enrollment ID
    $enrollmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$enrollmentId) {
        throw new Exception('Invalid enrollment ID');
    }
    
    // Fetch enrollment details
    $stmt = $pdo->prepare('
        SELECT eh.*, u.name as created_by_name
        FROM enrollment_history eh
        LEFT JOIN users u ON eh.created_by = u.id
        WHERE eh.id = ?
    ');
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        throw new Exception('Enrollment record not found');
    }
    
    // Create student object from enrollment history data (preserves old data)
    // Check if new columns exist, otherwise fallback to current student data
    $student = [
        'id' => $enrollment['student_id'],
        'first_name' => isset($enrollment['previous_name']) && $enrollment['previous_name'] ? explode(' ', $enrollment['previous_name'])[0] : null,
        'last_name' => isset($enrollment['previous_name']) && $enrollment['previous_name'] ? substr($enrollment['previous_name'], strpos($enrollment['previous_name'], ' ') + 1) : null,
        'name' => isset($enrollment['previous_name']) ? $enrollment['previous_name'] : null,
        'gender' => isset($enrollment['previous_gender']) ? $enrollment['previous_gender'] : null,
        'date_of_birth' => isset($enrollment['previous_dob']) ? $enrollment['previous_dob'] : null,
        'dob' => isset($enrollment['previous_dob']) ? $enrollment['previous_dob'] : null,
        'phone' => isset($enrollment['previous_phone']) ? $enrollment['previous_phone'] : null,
        'address' => null, // Will be constructed from separate address fields
        'guardian_name' => isset($enrollment['previous_guardian_name']) ? $enrollment['previous_guardian_name'] : null,
        'rfid' => isset($enrollment['previous_rfid']) ? $enrollment['previous_rfid'] : null,
        'level' => isset($enrollment['previous_level']) ? $enrollment['previous_level'] : null,
        'year_grade' => isset($enrollment['previous_year_grade']) ? $enrollment['previous_year_grade'] : null,
        'section' => isset($enrollment['previous_section']) ? $enrollment['previous_section'] : null,
        'strand' => isset($enrollment['previous_strand']) ? $enrollment['previous_strand'] : null,
        'course' => isset($enrollment['previous_course']) ? $enrollment['previous_course'] : null,
        'block' => isset($enrollment['previous_block']) ? $enrollment['previous_block'] : null,
        'status' => isset($enrollment['previous_status']) ? $enrollment['previous_status'] : null,
        'religion' => isset($enrollment['previous_religion']) ? $enrollment['previous_religion'] : null,
        'barangay' => isset($enrollment['previous_barangay']) ? $enrollment['previous_barangay'] : null,
        'municipality' => isset($enrollment['previous_municipality']) ? $enrollment['previous_municipality'] : null,
        'province' => isset($enrollment['previous_province']) ? $enrollment['previous_province'] : null,
        'emergency_contact' => isset($enrollment['previous_emergency_contact']) ? $enrollment['previous_emergency_contact'] : null,
        'contacts' => isset($enrollment['previous_contacts']) ? $enrollment['previous_contacts'] : null,
        'allergies' => isset($enrollment['previous_allergies']) ? $enrollment['previous_allergies'] : null
    ];
    
    // Construct full address from separate address fields
    if ($student['barangay'] || $student['municipality'] || $student['province']) {
        $addressParts = array_filter([
            $student['barangay'],
            $student['municipality'], 
            $student['province']
        ]);
        $student['address'] = implode(', ', $addressParts);
    }
    
    // If no previous data is available, fetch current student data as fallback
    if (!$student['name']) {
        try {
            $studentStmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
            $studentStmt->execute([$enrollment['student_id']]);
            $currentStudent = $studentStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($currentStudent) {
                $student = [
                    'id' => $currentStudent['id'],
                    'first_name' => $currentStudent['first_name'] ?? null,
                    'last_name' => $currentStudent['last_name'] ?? null,
                    'name' => $currentStudent['name'] ?? null,
                    'gender' => $currentStudent['gender'] ?? null,
                    'date_of_birth' => $currentStudent['date_of_birth'] ?? $currentStudent['dob'] ?? null,
                    'dob' => $currentStudent['dob'] ?? null,
                    'phone' => $currentStudent['phone'] ?? null,
                    'address' => $currentStudent['address'] ?? null,
                    'guardian_name' => $currentStudent['guardian'] ?? null,
                    'rfid' => $currentStudent['rfid'] ?? null,
                    'level' => $currentStudent['level'] ?? null,
                    'year_grade' => $currentStudent['year_grade'] ?? null,
                    'section' => $currentStudent['section'] ?? null,
                    'strand' => $currentStudent['strand'] ?? null,
                    'course' => $currentStudent['course'] ?? null,
                    'block' => $currentStudent['block'] ?? null,
                    'status' => $currentStudent['status'] ?? null,
                    'religion' => $currentStudent['religion'] ?? null,
                    'barangay' => $currentStudent['barangay'] ?? null,
                    'municipality' => $currentStudent['municipality'] ?? null,
                    'province' => $currentStudent['province'] ?? null,
                    'emergency_contact' => $currentStudent['emergency_contact'] ?? null,
                    'contacts' => $currentStudent['contacts'] ?? null,
                    'allergies' => $currentStudent['allergies'] ?? null
                ];
                
                // Construct full address from separate address fields for fallback
                if ($student['barangay'] || $student['municipality'] || $student['province']) {
                    $addressParts = array_filter([
                        $student['barangay'],
                        $student['municipality'], 
                        $student['province']
                    ]);
                    $student['address'] = implode(', ', $addressParts);
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching current student data: " . $e->getMessage());
        }
    }
    
    // Fetch historical medical history (before the enrollment date)
    $medicalHistory = [];
    try {
        // Check if medical_records table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'medical_records'");
        if ($tableCheck->rowCount() > 0) {
            $medicalHistoryStmt = $pdo->prepare('
                SELECT mr.*
                FROM medical_records mr
                WHERE mr.patient_id = ? AND mr.patient_type = "student" 
                AND mr.created_at < ?
                ORDER BY mr.created_at DESC
                LIMIT 10
            ');
            $medicalHistoryStmt->execute([$enrollment['student_id'], $enrollment['created_at']]);
            $medicalHistory = $medicalHistoryStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Table doesn't exist or query failed, medical history will be empty
        error_log("Medical history error: " . $e->getMessage());
        $medicalHistory = [];
    }
    
    // Fetch historical visitation logs (before the enrollment date)
    $visitationLogs = [];
    try {
        // Check if visitation_logs table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'visitation_logs'");
        if ($tableCheck->rowCount() > 0) {
            $visitationStmt = $pdo->prepare('
                SELECT vl.*
                FROM visitation_logs vl
                WHERE vl.patient_id = ? AND vl.patient_type = "student"
                AND vl.created_at < ?
                ORDER BY vl.created_at DESC
                LIMIT 10
            ');
            $visitationStmt->execute([$enrollment['student_id'], $enrollment['created_at']]);
            $visitationLogs = $visitationStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Table doesn't exist or query failed, visitation logs will be empty
        error_log("Visitation logs error: " . $e->getMessage());
        $visitationLogs = [];
    }
    
    echo json_encode([
        'success' => true,
        'enrollment' => $enrollment,
        'student' => $student,
        'medicalHistory' => $medicalHistory,
        'visitationLogs' => $visitationLogs
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
