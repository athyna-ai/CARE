<?php
/**
 * RFID Verification Page
 * 
 * Secondary authentication using RFID for sensitive admin operations
 * 
 * @author Security Framework Team  
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Require login first
requireLogin('admin');

session_start();
$redirect_url = $_GET['redirect'] ?? 'secure_admin_panel.php';

// Handle RFID verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfid_code = $_POST['rfid_code'] ?? '';
    
    if (!empty($rfid_code)) {
        // Clean the RFID input
        $rfid_code = trim($rfid_code);
        
        try {
            $pdo = get_pdo();
            
            // Get current user's RFID for verification
            $stmt = $pdo->prepare("SELECT id, name, email, rfid FROM users WHERE id = ? AND is_admin = 1");
            $stmt->execute([$_SESSION['user']['id']]);
            $user = $stmt->fetch();
            
            if (!$user || !$user['rfid']) {
                $error_message = "No RFID set for this user.";
            } else {
                // Check if RFID matches (handle both hashed and plain text)
                $rfidMatches = false;
                error_log("RFID Debug - Stored: " . substr($user['rfid'], 0, 30) . "...");
                error_log("RFID Debug - Provided: " . $rfid_code);
                error_log("RFID Debug - Stored length: " . strlen($user['rfid']));
                
                if (strpos($user['rfid'], '$2y$') === 0) {
                    // It's hashed, verify using password_verify
                    $rfidMatches = password_verify($rfid_code, $user['rfid']);
                    error_log("RFID verification (hashed): " . ($rfidMatches ? 'SUCCESS' : 'FAILED'));
                } else {
                    // It's plain text, do direct comparison
                    $rfidMatches = ($user['rfid'] === $rfid_code);
                    error_log("RFID verification (plain): " . ($rfidMatches ? 'SUCCESS' : 'FAILED'));
                }
                
                if ($rfidMatches) {
                    $_SESSION['rfid_verified'] = true;
                    $_SESSION['rfid_verification_required'] = false;
                    
                    // Log successful RFID verification
                    log_activity($pdo, $_SESSION['user']['id'], 'rfid_verification', "RFID verified for admin: {$user['name']} (RFID: {$rfid_code})", 'rfid_verify');
                    
                    // Redirect to intended page
                    header("Location: {$redirect_url}");
                    exit;
                } else {
                    // Log failed attempt
                    log_activity($pdo, $_SESSION['user']['id'], 'rfid_verification_failed', "Failed RFID verification attempt: {$rfid_code}", 'rfid_verify');
                    $error_message = "Invalid RFID code. Please try again.";
                }
            }
        } catch (Exception $e) {
            error_log('Error verifying RFID: ' . $e->getMessage());
            $error_message = "Error verifying RFID. Please try again.";
        }
    } else {
        $error_message = "Please enter your RFID code.";
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Log verification page access
logActivity('RFID_VERIFICATION_PAGE', "RFID verification page accessed for: {$redirect_url}");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Verification - Care CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .verification-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .security-icon {
            font-size: 4em;
            margin-bottom: 20px;
            color: #667eea;
            animation: pulse 2s infinite;
        }
        
        .verification-title {
            font-size: 2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .verification-subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }
        
        .rfid-input {
            width: 100%;
            padding: 15px 20px;
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            font-size: 18px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .rfid-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
            background: white;
        }
        
        .rfid-input::placeholder {
            color: #999;
            font-weight: normal;
            letter-spacing: normal;
        }
        
        .verify-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .verify-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .verify-button:active {
            transform: translateY(-1px);
        }
        
        .error-message {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            display?: none;
        }
        
        .security-info {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .security-info h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .security-info p {
            color: #666;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .user-info {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            padding: #22ba00;
        }
        
        .user-info strong {
            color: #27ae60;
        }
        
        .back-button {
            background: rgba(255, 255, 255, 0.3);
            color: #666;
            border: 2px solid #e0e0e0;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: inline-block;
        }
        
        .back-button:hover {
            background: white;
            color: #333;
            transform: translateY(-2px);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .rfid-animation {
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .help-text {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: left;
        }
        
        .help-text h4 {
            color: #3498db;
            margin-bottom: 8px;
            font-size: 1em;
        }
        
        .help-text ul {
            color: #666;
            font-size: 0.9em;
            padding-left: 20px;
        }
        
        .help-text li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="verification-container rfid-animation">
        <!-- Security Icon -->
        <div class="security-icon">🔐</div>
        
        <!-- Title -->
        <h1 class="verification-title">RFID Verification</h1>
        <p class="verification-subtitle">Multi-Factor Authentication Required</p>
        
        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="error-message">⚠️ <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <!-- User Info -->
        <div class="user-info">
            <strong>Logged in as:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?><br>
            <strong>Requesting access to:</strong> <?= htmlspecialchars(basename($redirect_url)) ?>
        </div>
        
        <!-- Current Instructions -->
        <div class="security-info">
            <h3>🛡️ Security Verification Required</h3>
            <p>To access sensitive administrative functions, please scan your RFID card or enter your RFID code below.</p>
        </div>
        
        <!-- RFID Input Form -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_url) ?>">
            
            <div class="form-group">
                <label class="label">Enter RFID Code:</label>
                <input type="text" name="rfid_code" class="rfid-input" 
                       placeholder="Scan RFID card or enter code"
                       autocomplete="off" 
                       required 
                       autofocus
                       pattern="[0-9A-Fa-f]{8,16}"
                       title="Enter your RFID code (8-16 characters)">
            </div>
            
            <button type="submit" class="verify-button">
                🔓 Verify RFID & Continue
            </button>
        </form>
        
        <!-- Help Information -->
        <div class="help-text">
            <h4>How to proceed:</h4>
            <ul>
                <li>Scan your physical RFID card near the reader</li>
                <li>Or manually type your RFID code in the field</li>
                <li>The system will verify your credentials</li>
                <li>You'll be redirected to the requested page</li>
            </ul>
        </div>
        
        <!-- Navigation -->
        <a href="secure_admin_panel.php" class="back-button">← Back to Admin Panel</a>
    </div>
    
    <script>
        // Focus on RFID input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const rfidInput = document.querySelector('.rfid-input');
            if (rfidInput) {
                rfidInput.focus();
                
                // Format RFID input (add spaces every 4 characters)
                rfidInput.addEventListener('input', function() {
                    let value = this.value.replace(/\s/g, '');
                    if (value.length > 0) {
                        value = value.match(/.{1,4}/g).join(' ');
                        this.value = value;
                    }
                });
            }
            
            // Handle RFID reader input (if available)
            if (window.RFIDReader) {
                window.RFIDReader.onScan = function(code) {
                    document.querySelector('.rfid-input').value = code;
                    document.querySelector('form').submit();
                };
            }
        });
        
        // Auto-submit on Enter key
        document.querySelector('.rfid-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
        
        // Security check - hide form if page is accessed suspiciously
        if (document.referrer === '' && window.history.length <= 1) {
            console.warn('Security: Possible direct access detected');
        }
    </script>
</body>
</html>
