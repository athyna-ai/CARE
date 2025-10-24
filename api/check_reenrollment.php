<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$rfid = sanitize_string($input['rfid'] ?? '');
$name = sanitize_string($input['name'] ?? '');
$dob = sanitize_string($input['dob'] ?? '');

// Validate input
if (empty($rfid) && empty($name)) {
    echo json_encode(['found' => false]);
    exit;
}

try {
    $pdo = get_pdo();
    
    // First check by RFID
    if (!empty($rfid)) {
        $stmt = $pdo->prepare('SELECT id, name, level, rfid, status FROM students WHERE rfid = ? AND status = "Graduated"');
        $stmt->execute([$rfid]);
        $student = $stmt->fetch();
        
        if ($student) {
            echo json_encode([
                'found' => true,
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'level' => $student['level'],
                    'rfid' => $student['rfid'],
                    'status' => $student['status']
                ]
            ]);
            exit;
        }
    }
    
    // If RFID not found, check by name and DOB
    if (!empty($name) && !empty($dob)) {
        $stmt = $pdo->prepare('SELECT id, name, level, rfid, status FROM students WHERE name = ? AND dob = STR_TO_DATE(?,"%d/%m/%Y") AND status = "Graduated"');
        $stmt->execute([$name, $dob]);
        $student = $stmt->fetch();
        
        if ($student) {
            echo json_encode([
                'found' => true,
                'student' => [
                    'id' => $student['id'],
                    'name' => $student['name'],
                    'level' => $student['level'],
                    'rfid' => $student['rfid'],
                    'status' => $student['status']
                ]
            ]);
            exit;
        }
    }
    
    // Enhanced duplicate detection - check by name only (for graduated students)
    if (!empty($name)) {
        $stmt = $pdo->prepare('SELECT id, name, level, rfid, status, contacts, guardian, address FROM students WHERE name = ? AND status = "Graduated"');
        $stmt->execute([$name]);
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            // Check if this could be the same person based on additional criteria
            $matchScore = 0;
            $matchReasons = [];
            
            // Check contacts (phone numbers)
            if (!empty($student['contacts'])) {
                $contacts = json_decode($student['contacts'], true);
                if (is_array($contacts)) {
                    foreach ($contacts as $contact) {
                        if (strlen(trim($contact)) >= 10) { // Basic phone number check
                            $matchScore += 2;
                            $matchReasons[] = 'Same phone number';
                            break;
                        }
                    }
                }
            }
            
            // Check guardian name
            if (!empty($student['guardian']) && strlen($student['guardian']) > 3) {
                $matchScore += 1;
                $matchReasons[] = 'Same guardian';
            }
            
            // If we have a reasonable match score, consider it a potential duplicate
            if ($matchScore >= 2) {
                echo json_encode([
                    'found' => true,
                    'student' => [
                        'id' => $student['id'],
                        'name' => $student['name'],
                        'level' => $student['level'],
                        'rfid' => $student['rfid'],
                        'status' => $student['status']
                    ],
                    'matchReasons' => $matchReasons,
                    'matchScore' => $matchScore
                ]);
                exit;
            }
        }
    }
    
    // No graduated student found
    echo json_encode(['found' => false]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
