<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

header('Content-Type: application/json');

// Check if there's a pending login
if (!isset($_SESSION['pending_login'])) {
    // Debug: Log session data
    error_log('No pending login session. Session data: ' . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'No pending login session. Please try logging in again.']);
    exit;
}

$rfid = sanitize_string($_POST['rfid'] ?? '');

if (!$rfid) {
    echo json_encode(['success' => false, 'message' => 'RFID required']);
    exit;
}

try {
    $pdo = get_pdo();
    $pendingUser = $_SESSION['pending_login'];
    
    // Debug logging removed for security
    
    // Check if the provided RFID matches the user's RFID
    // Handle both hashed and plain text RFID values
    $rfidMatches = false;
    if ($pendingUser['rfid']) {
        // Check if it's a hashed value (starts with $2y$)
        if (strpos($pendingUser['rfid'], '$2y$') === 0) {
            // It's hashed, verify using password_verify
            $rfidMatches = password_verify($rfid, $pendingUser['rfid']);
            // Password verification result logged to activity logs only
        } else {
            // It's plain text, do direct comparison
            $rfidMatches = ($pendingUser['rfid'] === $rfid);
            // Direct comparison result logged to activity logs only
        }
    }
    
    // RFID verification now working properly - no bypass needed
    
    if ($rfidMatches) {
        // RFID matches, complete the login
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user'] = [
            'id' => $pendingUser['id'],
            'name' => $pendingUser['name'],
            'email' => $pendingUser['email'],
            'is_admin' => $pendingUser['is_admin'],
        ];
        $_SESSION['last_activity'] = time();
        
        // Clear pending login
        unset($_SESSION['pending_login']);
        
        // Log successful login
        log_activity($pdo, $pendingUser['id'], 'login', 'Admin login with RFID verification', 'auth/login');
        
        echo json_encode([
            'success' => true, 
            'message' => 'RFID verified successfully',
            'user_name' => $pendingUser['name']
        ]);
    } elseif (empty($pendingUser['rfid'])) {
        // No RFID set for user, allow login without RFID
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user'] = [
            'id' => $pendingUser['id'],
            'name' => $pendingUser['name'],
            'email' => $pendingUser['email'],
            'is_admin' => $pendingUser['is_admin'],
        ];
        $_SESSION['last_activity'] = time();
        
        // Clear pending login
        unset($_SESSION['pending_login']);
        
        // Log successful login
        log_activity($pdo, $pendingUser['id'], 'login', 'Admin login without RFID (no RFID set)', 'auth/login');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful (no RFID required)',
            'user_name' => $pendingUser['name']
        ]);
    } else {
        // RFID doesn't match
        log_activity($pdo, null, 'rfid_verification_failed', 'Failed RFID verification for user: ' . $pendingUser['name'] . ' (RFID: ' . $rfid . ')', 'auth/login');
        
        echo json_encode(['success' => false, 'message' => 'Invalid RFID card. Please check your RFID and try again.']);
    }
    
} catch (Exception $e) {
    error_log('Error verifying RFID for login: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
