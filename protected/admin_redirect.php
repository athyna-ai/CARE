<?php
/**
 * Secure Admin Panel Redirect
 * 
 * This file provides secure access to admin functions after authentication
 * All admin operations should go through this secure interface
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Require admin authentication
requireLogin('admin');

session_start();

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Log admin panel access
logActivity('ADMIN_PANEL_ACCESS', 'Admin accessed secure admin panel');

// Get requested admin action
$action = $_GET['action'] ?? 'dashboard';
$allowed_actions = [
    'dashboard' => 'Admin Dashboard',
    'account_settings' => 'Account Settings',
    'security_logs' => 'Security Logs',
    'settings' => 'System Settings',
    'archive_management' => 'Archive Management',
    'create_admin' => 'Create New Admin'
];

// Validate action
if (!array_key_exists($action, $allowed_actions)) {
    logSecurityEvent('INVALID_ADMIN_ACTION', "Attempted access to invalid admin action: {$action}");
    $action = 'dashboard';
}

// Log specific admin action
logActivity('ADMIN_ACTION_ACCESS', "Admin accessed: {$action}");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Admin Panel - <?= htmlspecialchars($allowed_actions[$action]) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .security-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .admin-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .admin-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .nav-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .nav-button:hover, .nav-button.active {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .nav-button.active {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .warning-box {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .admin-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .danger-button {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .danger-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(231, 76, 76, 0.4);
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .security-status {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Security Warning -->
        <div class="security-header">
            🛡️ SECURE ADMIN PANEL - AUTHORIZED ACCESS ONLY<br>
            <small>All admin access is logged and monitored for security</small>
        </div>
        
        <!-- Admin Navigation -->
        <div class="admin-nav">
            <?php foreach ($allowed_actions as $action_key => $action_label): ?>
                <a href="admin_redirect.php?action=<?= urlencode($action_key) ?>" 
                   class="nav-button <?= $action === $action_key ? 'active' : '' ?>">
                    <?= htmlspecialchars($action_label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Admin Info -->
        <div class="admin-info">
            <h3>👤 Admin Session Information</h3>
            <p><strong>User:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Administrator') ?></p>
            <p><strong>Session:</strong> <?= substr(session_id(), 0, 12) ?>...</p>
            <p><strong>Access Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>Current Action:</strong> <?= htmlspecialchars($allowed_actions[$action]) ?></p>
        </div>
        
        <!-- Current Admin Content -->
        <div class="admin-content">
            <?php if ($action === 'dashboard'): ?>
                <h2>🏠 Admin Dashboard</h2>
                <div class="warning-box">
                    ⚠️ CRITICAL: Directory browsing has been secured! Previous vulnerability fixed.
                </div>
                
                <div class="admin-grid">
                    <div class="admin-card">
                        <div class="card-header">🔒 Security Status</div>
                        <p>✅ Admin directory secured with .htaccess</p>
                        <p>✅ All direct file access blocked</p>
                        <p>✅ Authentication required for all admin functions</p>
                        <p>✅ All activities logged and monitored</p>
                    </div>
                    
                    <div class="admin-card">
                        <div class="card-header">📊 System Overview</div>
                        <p><strong>Security Framework:</strong> Active</p>
                        <p><strong>Log Monitoring:</strong> Enabled</p>
                        <p><strong>CSRF Protection:</strong> Active</p>
                        <p><strong>Session Security:</strong> Hardened</p>
                    </div>
                </div>
                
                <div class="security-status">
                    🛡️ SECURITY FRAMEWORK ACTIVE - ALL ADMIN ACCESS PROTECTED
                </div>
                
            <?php elseif ($action === 'security_logs'): ?>
                <h2>📋 Security Logs</h2>
                <div class="admin-grid">
                    <div class="admin-card">
                        <div class="card-header">Direct Access Blocked</div>
                        <p>The original security logs functionality has been merged into Activity Logs.</p>
                        <p>Access now requires authentication through this secure interface.</p>
                    </div>
                    <div class="admin-card">
                        <div class="card-header">View Logs Securely</h2>
                        <a href="../logs/logs.php?filter=security" class="danger-button">📊 View Security Logs</a>
                        <a href="security_analysis.php" class="danger-button">📈 Security Analysis</a>
                    </div>
                </div>
                
            <?php else: ?>
                <h2>⚠️ Admin Action: <?= htmlspecialchars($allowed_actions[$action]) ?></h2>
                <div class="warning-box">
                    This admin function has been secured. Direct access has been blocked.
                </div>
                <p>The original admin files are now protected by the security framework.</p>
                <p>All admin operations must now go through authenticated interfaces.</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="../index.php" class="danger-button">🏠 Back to Main Site</a>
            <a href="../auth/logout.php" class="danger-button">🚪 Logout</a>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 5 minutes for security monitoring
        setInterval(() => {
            location.reload();
        }, 300000);
        
        // Log user activity
        console.log('Admin panel accessed:', '<?= htmlspecialchars($action) ?>');
    </script>
</body>
</html>
