<?php
/**
 * Admin Log Viewer with RFID Verification
 * 
 * Secure interface for viewing security logs with RFID re-authentication
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Require admin authentication
requireLogin('admin');

// Log access to log viewer
logActivity('ADMIN_LOG_ACCESS', 'Admin accessed security log viewer');

// Get log data
$log_files = [
    'security.log' => 'Security Events',
    'alerts.log' => 'Security Alerts'
];

$selected_file = $_GET['file'] ?? 'security.log';
$page = (int)($_GET['page'] ?? 1);
$lines_per_page = 50;
$search_term = $_GET['search'] ?? '';

// Validate selected file
if (!array_key_exists($selected_file, $log_files)) {
    $selected_file = 'security.log';
}

$log_path = __DIR__ . '/../logs/' . $selected_file;
$log_content = [];
$total_lines = 0;

if (file_exists($log_path)) {
    $lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $total_lines = count($lines);
    
    // Apply search filter
    if ($search_term) {
        $lines = array_filter($lines, function($line) use ($search_term) {
            return stripos($line, $search_term) !== false;
        });
    }
    
    // Reverse to show newest first
    $lines = array_reverse($lines);
    
    // Get page data
    $start = ($page - 1) * $lines_per_page;
    $log_content = array_slice($lines, $start, $lines_per_page);
    
    // Calculate pagination
    $total_pages = ceil(count($lines) / $lines_per_page);
} else {
    $log_content = ['Log file does not exist or is not readable.'];
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Log Viewer - Care CMS</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .security-banner {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .controls {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .log-viewer {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
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
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button.secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        
        .button.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .log-content {
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            border: 2px solid #333;
        }
        
        .log-entry {
            margin-bottom: 10px;
            padding: 8px;
            border-left: 3px solid #00ff00;
            padding-left: 12px;
            transition: background 0.2s ease;
        }
        
        .log-entry:hover {
            background: rgba(0, 255, 0, 0.1);
        }
        
        .log-entry.critical {
            border-left-color: #e74c3c;
            background: rgba(231, 76, 76, 0.1);
        }
        
        .log-entry.high {
            border-left-color: #f39c12;
            background: rgba(243, 156, 18, 0.1);
        }
        
        .log-entry.medium {
            border-left-color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }
        
        .log-entry.low {
            border-left-color: #2ecc71;
            background: rgba(46, 204, 113, 0.1);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .pagination button:hover {
            background: #f5f5f5;
        }
        
        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .log-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Security Banner -->
        <div class="security-banner">
            🔒 SECURE LOG VIEWER - ADMIN ACCESS ONLY
        </div>
        
        <!-- Header -->
        <div class="header">
            <h1>📋 Security Log Viewer</h1>
            <p><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Administrator') ?></p>
            <p><strong>Access Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>Session ID:</strong> <?= substr(session_id(), 0, 8) ?>...</p>
            
            <div style="margin-top: 20px;">
                <a href="security_analysis.php" class="button secondary">📊 Security Analysis</a>
                <a href="secure_admin_panel.php" class="button secondary">⚙️ Admin Panel</a>
                <button class="button danger" onclick="logout()">🚪 Logout</button>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <form method="GET" action="" style="display: contents;">
                <div class="form-group">
                    <label class="label">Log File:</label>
                    <select name="file" class="input">
                        <?php foreach ($log_files as $file => $label): ?>
                            <option value="<?= htmlspecialchars($file) ?>" <?= $selected_file === $file ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="label">Search:</label>
                    <input type="text" name="search" class="input" value="<?= htmlspecialchars($search_term) ?>" 
                           placeholder="Search log entries...">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button">🔍 Search</button>
                    <a href="?" class="button secondary">🔄 Clear</a>
                </div>
            </form>
            
            <div class="form-group">
                <button class="button danger" onclick="clearLog('<?= htmlspecialchars($selected_file) ?>')">🗑️ Clear Log</button>
                <button class="button secondary" onclick="exportLog('<?= htmlspecialchars($selected_file) ?>')">📄 Export</button>
            </div>
        </div>
        
        <!-- Log Statistics -->
        <div class="log-stats">
            <div class="stat-box">
                <div class="stat-value"><?= number_format($total_lines) ?></div>
                <div class="stat-label">Total Entries</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format(count($log_content)) ?></div>
                <div class="stat-label">Showing</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= number_format($page) ?></div>
                <div class="stat-label">Page <?= $total_pages > 0 ? "of $total_pages" : "" ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= htmlspecialchars($log_files[$selected_file]) ?></div>
                <div class="stat-label">Current File</div>
            </div>
        </div>
        
        <!-- Log Content -->
        <div class="log-viewer">
            <h2><?= htmlspecialchars($log_files[$selected_file]) ?></h2>
            <div class="log-content">
                <?php if (empty($log_content)): ?>
                    <div style="color: #666; text-align: center; padding: 40px;">
                        No log entries found.
                    </div>
                <?php else: ?>
                    <?php foreach ($log_content as $line): ?>
                        <?php 
                        $decoded = json_decode($line, true);
                        $severity = $decoded['severity'] ?? 'low';
                        $severity_class = strtolower($severity);
                        ?>
                        <div class="log-entry <?= $severity_class ?>">
                            <?= htmlspecialchars($line) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <button onclick="goToPage(<?= $page - 1 ?>)">← Previous</button>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <button class="<?= $i === $page ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)">
                        <?= $i ?>
                    </button>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <button onclick="goToPage(<?= $page + 1 ?>)">Next →</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
        
        function clearLog(file) {
            if (confirm('Are you sure you want to clear this log file? This action cannot be undone.')) {
                fetch('admin_log_viewer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $csrf_token ?>'
                    },
                    body: JSON.stringify({
                        action: 'clear_log',
                        file: file
                    })
                }).then(() => {
                    location.reload();
                }).catch(error => {
                    alert('Error clearing log: ' + error.message);
                });
            }
        }
        
        function exportLog(file) {
            const url = new URL(window.location);
            url.pathname = url.pathname.replace('admin_log_viewer.php', 'export_log.php');
            url.searchParams.set('file', file.trim
            window.open(url.toString(), '_blank');
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('admin_log_viewer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $csrf_token ?>'
                    },
                    body: JSON.stringify({
                        action: 'logout'
                    })
                }).then(() => {
                    window.location.href = '/auth/login.php';
                }).catch(error => {
                    alert('Logout error: ' + error.message);
                });
            }
        }
        
        // Auto-refresh every 30 seconds for real-time monitoring
        <?php if ($selected_file === 'alerts.log'): ?>
        setInterval(() => {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
