<?php
require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rfid = $_POST['rfid'] ?? null;

if (!$rfid) {
    echo json_encode(['success' => false, 'message' => 'RFID required']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Check if RFID belongs to an admin user
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE rfid = ? AND role = 'admin'");
    $stmt->execute([$rfid]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Log the access
        log_activity(
            $pdo,
            $_SESSION['user']['id'],
            'rfid_verification',
            "RFID verification successful for admin: {$admin['name']} (RFID: {$rfid})",
            'patient_view.php'
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'RFID verified successfully',
            'admin_name' => $admin['name']
        ]);
    } else {
        // Log failed attempt
        log_activity(
            $pdo,
            $_SESSION['user']['id'],
            'rfid_verification_failed',
            "Failed RFID verification attempt: {$rfid}",
            'patient_view.php'
        );
        
        echo json_encode(['success' => false, 'message' => 'Invalid RFID card']);
    }
    
} catch (Exception $e) {
    error_log('Error verifying RFID: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error verifying RFID']);
}
?>
