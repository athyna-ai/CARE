<?php
/**
 * Secure Admin Panel Example
 * 
 * Demonstrates front-end security integration:
 * - Session authentication
 * - CSRF token integration
 * - Role-based access control
 * - Activity logging
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Enforce authentication and admin role
requireLogin('admin');

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Log admin panel access
logActivity('ADMIN_PANEL_ACCESS', 'Admin panel accessed');

// Security statistics for display
$security_stats = SecurityFramework::getSecurityStats();
$log_stats = getLogStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Admin Panel - Care CMS</title>
    <style>
        /* Security-themed styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .security-banner {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .panel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .security-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }
        
        .security-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
    ├─────────────┤ border-radius: 10px;
            text-align: center;
            border-top: 3px solid #667eea;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0               0 3px rgba(102, 126, 234, 0.2);
        }
        
        .button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button.danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .security-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .status-secure {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Security Banner -->
        <div class="security-banner">
            🔒 <strong>SECURE ADMIN PANEL</strong> - Protected by Security Framework v1.0
        </div>
        
        <!-- Header -->
        <div class="header">
            <h1>🛡️ Security Framework Dashboard</h1>
            <p>Welcome back, Admin! Your session is protected by comprehensive security measures.</p>
            <span class="security-status status-secure">🟢 SECURE</span>
            <span class="security-status status-secure">Login: <?= date('Y-m-d H:i:s') ?></span>
        </div>
        
        <!-- Security Statistics -->
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?= $log_stats['log_size_mb'] ?></div>
                <div class="stat-label">Log Size (MB)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $log_stats['archived_files_count'] ?></div>
                <div class="stat-label">Archived Logs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= substr($security_stats['session_id'], 0, 8) ?>...</div>
                <div class="stat-label">Session ID</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $security_stats['user_ip'] ?></div>
                <div class="stat-label">Your IP</div>
            </div>
        </div>
        
        <!-- Action Panels -->
        <div class="panel-grid">
            <!-- User Management -->
            <div class="security-card">
                <h3>👥 User Management</h3>
                <div class="form-group">
                    <label class="label">User ID to Delete:</label>
                    <input type="number" class="input" id="user-id" placeholder="Enter user ID" min="1">
                </div>
                <div class="form-group">
                    <label class="label">Confirmation Code:</label>
                    <input type="text" class="input" id="user-confirmation" placeholder="Type DELETE to confirm">
                </div>
                <button class="button danger" onclick="deleteUser()">🗑️ Delete User</button>
            </div>
            
            <!-- System Actions -->
            <div class="security-card">
                <h3>⚙️ System Actions</h3>
                <button class="button" onclick="generateReport()">📊 Generate Security Report</button>
                <button class="button" onclick="clearOldLogs()">🧹 Clear Old Logs</button>
                <button class="button" onclick="rotateLogs()">🔄 Rotate Logs</button>
            </div>
            
            <!-- Database Cleanup -->
            <div class="security-card">
                <h3>🗄️ Database Cleanup</h3>
                <div class="form-group">
                    <label class="label">Table to Clean:</label>
                    <select class="input" id="cleanup-table">
                        <option value="medical_records">Medical Records</option>
                        <option value="patients">Patients</option>
                        <option value="visitations">Visitations</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">Confirmation:</label>
                    <input type="text" class="input" id="cleanup-confirmation" placeholder="Type DELETE to confirm">
                </div>
                <button class="button danger" onclick="cleanupTable()">🧽 Cleanup Table</button>
            </div>
        </div>
        
        <!-- Security Form Example -->
        <div class="form-section">
            <h3>🔐 Secure Form Example</h3>
            <form id="secure-form" method="POST" action="secure_admin_panel.php">
                <!-- CSRF Token (Hidden) -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label class="label">Action Type:</label>
                        <select class="input" name="action_type" required>
                            <option value="">Select Action...</option>
                            <option value="backup">Create Backup</option>
                            <option value="update">System Update</option>
                            <option value="maintenance">Maintenance Mode</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="label">Target System:</label>
                        <input type="text" class="input" name="target" placeholder="System component" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="label">Confirmation Code:</label>
                        <input type="text" class="input" name="confirmation_code" placeholder="Required for security" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="label">Description:</label>
                    <textarea class="input" name="description" rows="3" placeholder="Detailed description of the action..." required></textarea>
                </div>
                
                <button type="submit" class="button">✅ Execute Secure Action</button>
            </form>
        </div>
        
        <!-- Results Area -->
        <div id="results" class="form-section" style="display: none;">
            <h3>📋 Action Results</h3>
            <div id="results-content"></div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>🔒 All actions are logged and monitored by the Security Framework</p>
            <p>Session expires: <?= date('Y-m-d H:i:s', time() + SecurityConfig::SESSION_TIMEOUT) ?></p>
        </div>
    </div>
    
    <script>
        // Client-side security measures
        let csrfToken = '<?= $csrf_token ?>';
        
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.style.display = 'none');
        }, 5000);
        
        function showResults(content, type = 'info') {
            const resultsDiv = document.getElementById('results');
            const contentDiv = document.getElementById('results-content');
            
            contentDiv.innerHTML = `<div class="alert ${type}">${content}</div>`;
            resultsDiv.style.display = 'block';
            resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }
        
        function deleteUser() {
            const userId = document.getElementById('user-id').value;
            const confirmation = document.getElementById('user-confirmation').value;
            
            if (!userId || confirmation !== 'DELETE') {
                showResults('Please provide user ID and confirmation code "DELETE"', 'danger');
                return;
            }
            
            performSecureAction({
                action: 'delete_user',
                user_id: userId,
                confirmation: confirmation
            });
        }
        
        function cleanupTable() {
            const table = document.getElementById('cleanup-table').value;
            const confirmation = document.getElementById('cleanup-confirmation').value;
            
            if (confirmation !== 'DELETE') {
                showResults('Please type "DELETE" to confirm cleanup', 'danger');
                return;
            }
            
            performSecureAction({
                action: 'cleanup_table',
                table_name: table,
                confirmation: confirmation
            });
        }
        
        function generateReport() {
            performSecureAction({
                action: 'generate_report');
        }
        
        function clearOldLogs() {
            performSecureAction({
                action: 'clear_logs',
                confirmation: 'CONFIRMED'
            });
        }
        
        function rotateLogs() {
            performSecureAction({
                action: 'rotate_logs',
                confirmation: 'CONFIRMED'
            });
        }
        
        function performSecureAction(data) {
            // Add CSRF token to all requests
            data.csrf_token = csrfToken;
            
            fetch('secure_admin_panel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showResults(result.message, 'success');
                } else {
                    showResults(result.error || 'Action failed', 'danger');
                }
            })
            .catch(error => {
                showResults('Request failed: ' + error.message, 'danger');
            });
        }
        
        // Handle form submission
        document.getElementById('secure-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            performSecureAction(data);
        });
    </script>
</body>
</html>
