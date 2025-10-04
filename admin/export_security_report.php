<?php
declare(strict_types=1);
require_once __DIR__ . '/../security_breach_detector.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

try {
    $pdo = get_pdo();
    
    // Get security events from the last 30 days
    $stmt = $pdo->prepare('
        SELECT 
            al.timestamp,
            al.action,
            al.description,
            al.ip_address,
            al.success,
            al.user_agent,
            u.name as user_name,
            u.email as user_email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.action IN ("login_success", "login_failed", "account_locked", "rfid_verification_failed", "security_action")
        AND al.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY al.timestamp DESC
    ');
    $stmt->execute();
    $securityEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'security_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    fputcsv($output, [
        'Timestamp',
        'Action',
        'Description',
        'IP Address',
        'Success',
        'User Name',
        'User Email',
        'User Agent'
    ]);
    
    // Write data rows
    foreach ($securityEvents as $event) {
        fputcsv($output, [
            $event['timestamp'],
            $event['action'],
            $event['description'],
            $event['ip_address'],
            $event['success'] ? 'Yes' : 'No',
            $event['user_name'] ?? 'Unknown',
            $event['user_email'] ?? 'Unknown',
            $event['user_agent']
        ]);
    }
    
    fclose($output);
    
    // Log this action
    log_activity($pdo, $_SESSION['user']['id'], 'security_action', 'Exported security report with ' . count($securityEvents) . ' events', 'admin/settings');
    
} catch (Exception $e) {
    error_log('Error exporting security report: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error generating security report: ' . $e->getMessage();
}
?>
