<?php
/**
 * Security Monitor API
 * 
 * Provides real-time security alerts for failed attempts and breaches
 * 
 * @author Security Framework Team
 * @version 1.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Include security framework for logging
require_once __DIR__ . '/../includes/log_functions.php';

// Get parameters
$action = $_GET['action'] ?? 'check_alerts';
$user_id = $_GET['user_id'] ?? null;

// Check if user is authenticated
session_start();
if (!$user_id && !isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $user_id ?: $_SESSION['user']['id'];

try {
    switch ($action) {
        case 'check_alerts':
            $alerts = checkRecentSecurityAlerts();
            echo json_encode([
                'success' => true,
                'alerts' => $alerts,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'clear_alert':
            $alert_id = $_POST['alert_id'] ?? null;
            if ($alert_id) {
                clearSecurityAlert($alert_id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Alert cleared'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No alert ID provided'
                ]);
            }
            break;
            
        case 'get_stats':
            $stats = getSecurityStatistics();
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    logSecurityEvent('SECURITY_MONITOR_ERROR', "Error in security monitor: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

/**
 * Check for recent security alerts (last 5 minutes) from database
 */
function checkRecentSecurityAlerts() {
    try {
        // Include database connection
        require_once __DIR__ . '/../core/config.php';
        $pdo = get_pdo();
        
        // Get recent security events from database (last 5 minutes)
        $sql = "SELECT id, timestamp, action as event_type, description, ip_address, success, location,
                       'MEDIUM' as severity, 'database' as source
                FROM activity_logs 
                WHERE (success = 0 OR user_type = 'system') 
                AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY timestamp DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $db_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alerts = [];
        foreach ($db_alerts as $alert) {
            // Determine severity based on action
            $severity = 'MEDIUM';
            if (stripos($alert['event_type'], 'CRITICAL') !== false || 
                stripos($alert['event_type'], 'BREACH') !== false ||
                stripos($alert['event_type'], 'INJECTION') !== false) {
                $severity = 'CRITICAL';
            } elseif (stripos($alert['event_type'], 'HIGH') !== false ||
                     stripos($alert['event_type'], 'UNAUTHORIZED') !== false) {
                $severity = 'HIGH';
            }
            
            $alert_time = strtotime($alert['timestamp']);
            $alerts[] = [
                'id' => 'db_' . $alert['id'],
                'timestamp' => $alert['timestamp'],
                'event_type' => $alert['event_type'],
                'severity' => $severity,
                'description' => $alert['description'],
                'ip_address' => $alert['ip_address'],
                'time_ago' => getTimeAgo($alert_time),
                'age' => time() - $alert_time,
                'source' => 'database'
            ];
        }
    } catch (Exception $e) {
        error_log("Database security alerts failed: " . $e->getMessage());
        $alerts = [];
    }
    
    // Also check file-based alerts
    $alerts_file = __DIR__ . '/../logs/alerts.log';
    
    if (file_exists($alerts_file)) {
        $lines = file($alerts_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Get alerts from last 5 minutes
        $cutoff_time = time() - (5 * 60);
        
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded && isset($decoded['timestamp'])) {
                $alert_time = strtotime($decoded['timestamp']);
                if ($alert_time >= $cutoff_time) {
                    $alerts[] = [
                        'id' => md5($line),
                        'timestamp' => $decoded['timestamp'],
                        'event_type' => $decoded['event_type'],
                        'severity' => $decoded['severity'],
                        'description' => $decoded['description'],
                        'ip_address' => $decoded['ip_address'],
                        'time_ago' => getTimeAgo($alert_time),
                        'age' => time() - $alert_time
                    ];
                }
            }
        }
    }
    
    // Also check security.log for recent critical events
    $security_file = __DIR__ . '/../logs/security.log';
    if (file_exists($security_file)) {
        $lines = file($security_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cutoff_time = time() - (5 * 60);
        
        foreach (array_reverse($lines) as $line) {
            $decoded = json_decode($line, true);
            if ($decoded && isset($decoded['timestamp'])) {
                $log_time = strtotime($decoded['timestamp']);
                if ($log_time >= $cutoff_time && in_array($decoded['severity'], ['CRITICAL', 'HIGH'])) {
                    $alerts[] = [
                        'id' => 'sec_' . md5($line),
                        'timestamp' => $decoded['timestamp'],
                        'event_type' => $decoded['event_type'],
                        'severity' => $decoded['severity'],
                        'description' => $decoded['description'],
                        'ip_address' => $decoded['ip_address'],
                        'time_ago' => getTimeAgo($log_time),
                        'age' => time() - $log_time,
                        'source' => 'security.log'
                    ];
                }
            }
            
            // Limit to last 10 alerts to prevent memory issues
            if (count($alerts) >= 10) break;
        }
    }
    
    // Sort by timestamp (newest first)
    usort($alerts, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    return array_slice($alerts, 0, 5); // Return only 5 most recent
}

/**
 * Get security statistics
 */
function getSecurityStatistics() {
    $today = date('Y-m-d');
    $stats = [
        'today_critical' => 0,
        'today_high' => 0,
        'today_medium' => 0,
        'total_attempts' => 0,
        'blocked_ips' => 0
    ];
    
    // Check alerts.log
    $alerts_file = __DIR__ . '/../logs/alerts.log';
    if (file_exists($alerts_file)) {
        $lines = file($alerts_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded && isset($decoded['timestamp']) && strpos($decoded['timestamp'], $today) === 0) {
                $severity = $decoded['severity'] ?? 'low';
                $stats['today_' . strtolower($severity)]++;
                $stats['total_attempts']++;
            }
        }
    }
    
    // Check security.log
    $security_file = __DIR__ . '/../logs/security.log';
    if (file_exists($security_file)) {
        $lines = file($security_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded && isset($decoded['timestamp']) && strpos($decoded['timestamp'], $today) === 0) {
                if (in_array($decoded['event_type'], ['UNAUTHORIZED_ACCESS', 'BRUTE_FORCE', 'SQL_INJECTION'])) {
                    $stats['blocked_ips']++;
                }
            }
        }
    }
    
    return $stats;
}

/**
 * Clear a security alert
 */
function clearSecurityAlert($alert_id) {
    // For now, we log the alert clear action
    logActivity('SECURITY_ALERT_CLEARED', "Security alert cleared: {$alert_id}");
    return true;
}

/**
 * Get time ago string
 */
function getTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } else {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
}
?>
