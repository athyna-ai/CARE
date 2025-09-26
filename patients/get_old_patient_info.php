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
        SELECT eh.*, s.* 
        FROM enrollment_history eh
        LEFT JOIN students s ON eh.student_id = s.id
        WHERE eh.id = ?
    ');
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Enrollment record not found']);
        exit;
    }
    
    // Return the data
    echo json_encode([
        'success' => true,
        'enrollment' => [
            'previous_level' => $enrollment['previous_level'],
            'previous_year_grade' => $enrollment['previous_year_grade'],
            'previous_section' => $enrollment['previous_section'],
            'previous_strand' => $enrollment['previous_strand'],
            'previous_course' => $enrollment['previous_course'],
            'previous_block' => $enrollment['previous_block'],
            'enrollment_date' => $enrollment['enrollment_date'],
            'notes' => $enrollment['notes']
        ],
        'student' => [
            'name' => $enrollment['name'],
            'gender' => $enrollment['gender'],
            'age' => $enrollment['age'],
            'dob' => $enrollment['dob'],
            'religion' => $enrollment['religion'],
            'guardian' => $enrollment['guardian'],
            'address' => $enrollment['address'],
            'allergies' => $enrollment['allergies']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching old patient info: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
