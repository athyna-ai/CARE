<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

header('Content-Type: application/json');

$enrollmentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$enrollmentId) {
    echo json_encode(['success' => false, 'message' => 'Enrollment ID required']);
    exit;
}

try {
    // Get enrollment history record
    $stmt = $pdo->prepare('
        SELECT eh.*
        FROM enrollment_history eh
        WHERE eh.id = ?
    ');
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Enrollment record not found']);
        exit;
    }
    
    // Construct address from separate address fields
    $address = '';
    $addressParts = array_filter([
        $enrollment['previous_barangay'] ?? '',
        $enrollment['previous_municipality'] ?? '',
        $enrollment['previous_province'] ?? ''
    ]);
    if (!empty($addressParts)) {
        $address = implode(', ', $addressParts);
    }
    
    // Return the data - using previous_* fields from enrollment history
    echo json_encode([
        'success' => true,
        'enrollment' => [
            'previous_level' => $enrollment['previous_level'] ?? null,
            'previous_year_grade' => $enrollment['previous_year_grade'] ?? null,
            'previous_section' => $enrollment['previous_section'] ?? null,
            'previous_strand' => $enrollment['previous_strand'] ?? null,
            'previous_course' => $enrollment['previous_course'] ?? null,
            'previous_block' => $enrollment['previous_block'] ?? null,
            'enrollment_date' => $enrollment['enrollment_date'] ?? null,
            'notes' => $enrollment['notes'] ?? null
        ],
        'student' => [
            'name' => $enrollment['previous_name'] ?? null,
            'gender' => $enrollment['previous_gender'] ?? null,
            'age' => $enrollment['previous_age'] ?? null,
            'dob' => $enrollment['previous_dob'] ?? null,
            'date_of_birth' => $enrollment['previous_dob'] ?? null,
            'religion' => $enrollment['previous_religion'] ?? null,
            'guardian' => $enrollment['previous_guardian_name'] ?? null,
            'address' => $address,
            'allergies' => $enrollment['previous_allergies'] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching old patient info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
