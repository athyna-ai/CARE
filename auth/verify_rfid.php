<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rfid = $_POST['rfid'] ?? $_POST['rfid_code'] ?? null;

if (!$rfid) {
    echo json_encode(['success' => false, 'message' => 'RFID required']);
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get current user's RFID for verification
    $stmt = $pdo->prepare("SELECT id, name, email, rfid FROM users WHERE id = ? AND is_admin = 1");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['rfid']) {
        echo json_encode(['success' => false, 'message' => 'No RFID set for this user']);
        exit;
    }
    
    // Check if RFID matches (handle both hashed and plain text)
    $rfidMatches = false;
    if (strpos($user['rfid'], '$2y$') === 0) {
        // It's hashed, verify using password_verify
        $rfidMatches = password_verify($rfid, $user['rfid']);
        error_log("RFID verification (hashed): " . ($rfidMatches ? 'SUCCESS' : 'FAILED') . " for user: " . $user['name']);
    } else {
        // It's plain text, do direct comparison
        $rfidMatches = ($user['rfid'] === $rfid);
        error_log("RFID verification (plain): " . ($rfidMatches ? 'SUCCESS' : 'FAILED') . " for user: " . $user['name']);
    }
    
    if ($rfidMatches) {
        // Log the access
        log_activity(
            $pdo,
            $_SESSION['user']['id'],
            'rfid_verification',
            "RFID verification successful for admin: {$user['name']} (RFID: {$rfid})",
            'rfid_verify'
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'RFID verified successfully',
            'admin_name' => $user['name']
        ]);
    } else {
        // Log failed attempt
        log_activity(
            $pdo,
            $_SESSION['user']['id'],
            'rfid_verification_failed',
            "Failed RFID verification attempt: {$rfid}",
            'rfid_verify'
        );
        
        echo json_encode(['success' => false, 'message' => 'Invalid RFID card']);
    }
    
} catch (Exception $e) {
    error_log('Error verifying RFID: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error verifying RFID']);
}
?>
