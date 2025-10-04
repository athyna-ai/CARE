<?php
/**
 * Security Analysis Dashboard
 * 
 * Provides real-time security monitoring, log analysis, and threat detection.
 * Demonstrates advanced security framework usage.
 * 
 * @author Security Framework Team
 * @version 1.0
 */

// Include security framework
require_once __DIR__ . '/../includes/security.php';

// Require admin authentication
requireLogin('admin');

// Log access to security analysis
logActivity('SECURITY_ANALYSIS_ACCESS', 'Security analysis dashboard accessed');

// Get security data
$security_stats = SecurityFramework::getSecurityStats();
$log_stats = getLogStats();

// Generate security report for last 7 days
$security_report = null;
try {
    $security_report = generateSecurityReport(7);
} catch (Exception $e) {
    logSecurityEvent('REPORT_GENERATION_ERROR', "Failed to generate security report: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Analysis - Care CMS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            overflow-x: hidden;
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
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .security-indicator {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            font-size: 0.9em;
        }
        
        .threat-high { background: #ff6b6b; color: white; }
        .threat-medium { background: #f39c12; color: white; }
        .threat-low { background: #27ae60; color: white; }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        
        .card-title {
            font-size: 1.4em;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .stats-grid {
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
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2.2em;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .critical-events {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .event-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.6);
            border-left: 4px solid #e74c3c;
        }
        
        .event-item.low-severity { border-left-color: #f39c12; }
        .event-item.high-severity { border-left-color: #e67e22; }
        .event-item.minimal { border-left-color: #95a5a6; }
        
        .event-time {
            font-size: 0.8em;
            color: #666;
            min-width: 100px;
        }
        
        .event-description {
            flex-grow: 1;
            margin: 0 15px;
            font-size: 0.9em;
        }
        
        .event-risk {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 600;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 5px;
            transition: transform 0.2s ease;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
        }
        
        .refresh-btn.primary { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2ecc71;
            animation: pulse 2s infinite;
            margin-right: 8px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .footer-security {
            text-align: center;
            margin-top: 50px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Header -->
        <div class="header">
            <h1>🛡️ Security Analysis Dashboard</h1>
            <p><span class="live-indicator"></span>Real-time security monitoring and threat detection system</p>
            
            <div class="security-indicator threat-<?= $security_report['summary']['security_threat_level'] ?? 'low' ?>">
                Current Threat Level: <?= strtoupper($security_report['summary']['security_threat_level'] ?? 'UNKNOWN') ?>
            </div>
            
            <button class="refresh-btn primary" onclick="refreshDashboard()">🔄 Refresh Dashboard</button>
            <button class="refresh-btn" onclick="generateDetailedReport()">📊 Generate Detailed Report</button>
            <button class="refresh-btn" onclick="exportSecurityLog()">📄 Export Security Log</button>
        </div>
        
        <!-- Statistics Overview -->
        <?php if ($security_report): ?>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?= $security_report['total_events'] ?></div>
                <div class="stat-label">Total Events (7 days)</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= count($security_report['critical_events']) ?></div>
                <div class="stat-label">Critical Events</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $security_report['summary']['average_risk_score'] ?? 0 ?></div>
                <div class="stat-label">Avg Risk Score</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $log_stats['log_size_mb'] ?></div>
                <div class="stat-label">Log Size (MB)</div>
            </div>
        </div>
        <?php else: ?>
        <div class="dashboard-card">
            <div style="text-align: center; padding: 40px;">
                <h3>⚠️ Unable to Load Security Report</h3>
                <p>Check log file permissions and configuration</p>
                <button class="refresh-btn" onclick="refreshDashboard()">Try Again</button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Security Events Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">📊</span>
                    <span class="card-title">Security Events Timeline</span>
                </div>
                <div class="chart-container">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>
            
            <!-- Threats by Severity -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">⚡</span>
                    <span class="card-title">Threat Severity Distribution</span>
                </div>
                <div class="chart-container">
                    <canvas id="severityChart"></canvas>
                </div>
            </div>
            
            <!-- Top Threat Sources -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">🌎</span>
                    <span class="card-title">Top IP Sources</span>
                </div>
                <div class="chart-container">
                    <canvas id="ipChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Critical Events -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">🚨</span>
                    <span class="card-title">Recent Critical Events</span>
                </div>
                <div class="critical-events">
                    <?php if ($security_report && !empty($security_report['critical_events'])): ?>
                        <?php foreach (array_slice($security_report['critical_events'], 0, 10) as $event): ?>
                        <div class="event-item <?= strtolower($event['risk_score'] > 7 ? 'high-severity' : ($event['risk_score'] > 4 ? 'low-severity' : 'minimal')) ?>">
                            <div class="event-time"><?= substr($event['timestamp'], 11, 8) ?></div>
                            <div class="event-description"><?= htmlspecialchars(substr($event['description'], 0, 80)) ?>...</div>
                            <div class="event-risk"><?= $event['risk_score'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="event-item minimal">
                            <div class="event-time">N/A</div>
                            <div class="event-description">No critical events detected in recent activity</div>
                            <div class="event-risk">0</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Security Configuration -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">⚙️</span>
                    <span class="card-title">Security Configuration</span>
                </div>
                <div style="font-size: 0.9em; line-height: 1.6;">
                    <p><strong>Session Timeout:</strong> <?= SecurityConfig::SESSION_TIMEOUT / 60 ?> minutes</p>
                    <p><strong>Max Login Attempts:</strong> <?= SecurityConfig::MAX_LOGIN_ATTEMPTS ?></p>
                    <p><strong>Brute Force Window:</strong> <?= SecurityConfig::BRUTE_FORCE_WINDOW / 60 ?> minutes</p>
                    <p><strong>PHP Version:</strong> <?= $security_stats['php_version'] ?></p>
                    <p><strong>Session Config:</strong> HTTPOnly=<?= $security_stats['session_config']['httponly'] ?>, Secure=<?= $security_stats['session_config']['secure'] ?></p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">🔧</span>
                    <span class="card-title">Quick Security Actions</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button class="refresh-btn primary" onclick="clearFailedAttempts()">Clear Failed Attempts</button>
                <button class="refresh-btn" onclick="rotateLogs()">Rotate Logs</button>
                <button class="refresh-btn" onclick="generateBackup()">Generate Backup</button>
                <button class="refresh-btn" onclick="checkIntegrity()">Check Integrity</button>
                <a href="admin_log_viewer.php" class="refresh-btn secondary">📋 View Security Logs</a>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-security">
            <p>🛡️ All security events are automatically monitored and logged</p>
            <p>Last update: <?= date('Y-m-d H:i:s') ?> | Session: <?= substr(session_id(), 0, 8) ?>...</p>
        </div>
    </div>
    
    <script>
        // Chart configurations
        function initializeCharts() {
            <?php if ($security_report): ?>
            
            // Events Timeline Chart
            const eventsCtx = document.getElementById('eventsChart').getContext('2d');
            new Chart(eventsCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Security Events',
                        data: [<?= implode(',', array_values($security_report['events_by_type'] ?? [])) ?>],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Severity Distribution Chart
            const severityCtx = document.getElementById('severityChart').getContext('2d');
            new Chart(severityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        data: [
                            <?= $security_report['events_by_severity']['CRITICAL'] ?? 0 ?>,
                            <?= $security_report['events_by_severity']['HIGH'] ?? 0 ?>,
                            <?= $security_report['events_by_severity']['MEDIUM'] ?? 0 ?>,
                            <?= $security_report['events_by_severity']['LOW'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            '#e74c3c',
                            '#f39c12', 
                            '#3498db',
                            '#2ecc71'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Top IPs Chart
            const ipCtx = document.getElementById('ipChart').getContext('2d');
            const topIPs = <?= json_encode(array_slice($security_report['top_ips'] ?? [], 0, 5, true)) ?>;
            
            new Chart(ipCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(topIPs),
                    datasets: [{
                        label: 'Events',
                        data: Object.values(topIPs),
                        backgroundColor: '#764ba2',
                        borderColor: '#667eea',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            <?php endif; ?>
        }
        
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', initializeCharts);
        
        // Refresh function
        function refreshDashboard() {
            window.location.reload();
        }
        
        // Action functions
        function generateDetailedReport() {
            if (confirm('Generate detailed security report? This may take a few moments.')) {
                window.location.href = 'security_analysis.php?action=detailed_report';
            }
        }
        
        function exportSecurityLog() {
            if (confirm('Export security log file? This includes sensitive information.')) {
                window.location.href = 'security_analysis.php?action=export_log';
            }
        }
        
        function clearFailedAttempts() {
            if (confirm('Clear all failed login attempts?')) {
                fetch('security_analysis.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'clear_failed_attempts'})
                }).then(() => refreshDashboard());
            }
        }
        
        function rotateLogs() {
            if (confirm('Rotate log files? This will archive current logs.')) {
                fetch('security_analysis.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'rotate_logs'})
                }).then(() => refreshDashboard());
            }
        }
        
        function generateBackup() {
            if (confirm('Generate security configuration backup?')) {
                fetch('security_analysis.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'generate_backup'})
                }).then(() => alert('Backup generated successfully'));
            }
        }
        
        function checkIntegrity() {
            fetch('security_analysis.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'check_integrity'})
            }).then(response => response.json())
            .then(result => {
                alert('Integrity check completed: ' + (result.integrity_ok ? 'All systems secure' : 'Issues detected'));
            });
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshDashboard, 30000);
    </script>
</body>
</html>
