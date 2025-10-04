<?php
/**
 * Secure Log Export
 * 
 Takes log export with proper authorization and RFID verification
 * 
 * @author Security Framework Team
 * @version 1.0
 */

require_once __DIR__ . '/../includes/security.php';

// Require admin access and RFID verification
requireLogin('admin');

session_start();
if (!isset($_SESSION['rfid_verified']) || $_SESSION['rfid_verified'] !== true) {
    header('Location: rfid_verify.php?redirect=export_log.php');
    exit;
}

$selected_file = $_GET['file'] ?? 'security.log';
$format = $_GET['format'] ?? 'txt'; // txt, json, csv

// Validate file and format
$allowed_files = ['security.log', 'alerts.log'];
$allowed_formats = ['txt', 'json', 'csv'];

if (!in_array($selected_file, $allowed_files) || !in_array($format, $allowed_formats)) {
    die('Invalid file or format specified');
}

$log_path = __DIR__ . '/../logs/' . $selected_file;

// Log export activity
logActivity('LOG_EXPORT', "Admin exported {$selected_file} in {$format} format");

if (file_exists($log_path)) {
    $lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Set headers based on format
    $filename = "security_log_{$selected_file}_{$format}_" . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
            
            $export_data = [
                'export_info' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'admin_user' => $_SESSION['user']['username'] ?? 'admin',
                    'file' => $selected_file,
                    'total_entries' => count($lines)
                ],
                'log_entries' => []
            ];
            
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                $export_data['log_entries'][] = $decoded ?: ['raw_line' => $line];
            }
            
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            break;
            
        case 'csv':
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            
            echo "Timestamp,Event Type,Severity,IP Address,User ID,Description,Risk Score\n";
            
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded) {
                    echo sprintf(
                        '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                        $decoded['timestamp'],
                        $decoded['event_type'],
                        $decoded['severity'],
                        $decoded['ip_address'],
                        $decoded['user_id'],
                        str_replace('"', '""', $decoded['description']),
                        $decoded['risk_score']
                    );
                }
            }
            break;
            
        default: // txt
            header('Content-Type: text/plain');
            header("Content-Disposition: attachment; filename=\"{$filename}.txt\"");
            
            echo "Security Log Export\n";
            echo "==================\n";
            echo "File: {$selected_file}\n";
            echo "Exported: " . date('Y-m-d H:i:s') . "\n";
            echo "Admin: " . ($_SESSION['user']['username'] ?? 'admin') . "\n";
            echo "Total Entries: " . count($lines) . "\n";
            echo str_repeat("=", 50) . "\n\n";
            
            foreach ($lines as $line) {
                echo $line . "\n";
            }
            break;
    }
} else {
    die('Log file not found');
}
?>
