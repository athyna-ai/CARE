<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo '<div class="text-center py-8"><p class="text-red-600">Unauthorized access</p></div>';
    exit;
}

// Get parameters
$date = $_GET['date'] ?? '';
$type = $_GET['type'] ?? '';
$logType = $_GET['log_type'] ?? 'current';

if (empty($date) || empty($type)) {
    echo '<div class="text-center py-8"><p class="text-red-600">Missing required parameters</p></div>';
    exit;
}

try {
    $pdo = get_pdo();
    
    if ($logType === 'current') {
        // Show current logs (not archived)
        if ($type === 'visitation') {
            // Get visitation logs for the date
            $stmt = $pdo->prepare("
                SELECT vl.*, 
                       COALESCE(s.name, f.name) as patient_name,
                       COALESCE(s.rfid, f.rfid) as patient_rfid,
                       COALESCE(s.level, f.position) as grade_level_department,
                       COALESCE(s.course, f.position) as course_section_strand
                FROM visitation_logs vl
                LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
                LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
                WHERE DATE(vl.visit_date) = ? AND vl.archived = 0
                ORDER BY vl.visit_date DESC
            ");
            $stmt->execute([$date]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($logs)) {
                echo '<div class="text-center py-8"><p class="text-slate-600">No visitation logs found for this date</p></div>';
                exit;
            }
            
            // Display visitation logs table
            echo '<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">';
            echo '<div class="px-6 py-4 border-b border-slate-200 bg-slate-50">';
            echo '<h3 class="text-lg font-semibold text-slate-800">Visitation Logs - ' . htmlspecialchars($date) . '</h3>';
            echo '</div>';
            echo '<div class="overflow-x-auto">';
            echo '<table class="w-full min-w-max">';
            echo '<thead class="bg-slate-50">';
            echo '<tr>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Patient</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">RFID</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Level/Department</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Course/Section</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Visit Time</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Reason</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody class="bg-white divide-y divide-slate-200">';
            
            $rowNumber = 1;
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500 font-semibold">' . $rowNumber . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">' . htmlspecialchars($log['patient_name'] ?? 'Unknown') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['patient_rfid'] ?? 'N/A') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['grade_level_department'] ?? 'N/A') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['course_section_strand'] ?? 'N/A') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . date('M j, Y g:i A', strtotime($log['visit_date'])) . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['reason'] ?? 'N/A') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap">';
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
                echo '</td>';
                echo '</tr>';
                $rowNumber++;
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
            
        } elseif ($type === 'activity') {
            // Get activity logs for the date
            $stmt = $pdo->prepare("
                SELECT l.*, u.name as user_name, u.email as user_email
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE DATE(l.timestamp) = ? AND l.archived = 0
                ORDER BY l.timestamp DESC
            ");
            $stmt->execute([$date]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($logs)) {
                echo '<div class="text-center py-8"><p class="text-slate-600">No activity logs found for this date</p></div>';
                exit;
            }
            
            // Display activity logs table
            echo '<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">';
            echo '<div class="px-6 py-4 border-b border-slate-200 bg-slate-50">';
            echo '<h3 class="text-lg font-semibold text-slate-800">Activity Logs - ' . htmlspecialchars($date) . '</h3>';
            echo '</div>';
            echo '<div class="overflow-x-auto">';
            echo '<table class="w-full min-w-max">';
            echo '<thead class="bg-slate-50">';
            echo '<tr>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">User</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Action</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Description</th>';
            echo '<th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">IP</th>';
            echo '<th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Time</th>';
            echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody class="bg-white divide-y divide-slate-200">';
            
            $rowNumber = 1;
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500 font-semibold">' . $rowNumber . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">' . htmlspecialchars($log['user_name'] ?? 'Unknown User') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['action'] ?? 'N/A') . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['description'] ?? 'No description') . '</td>';
                echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($log['ip_address'] ?? 'N/A') . '</td>';
                 echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">' . date('M j, Y g:i A', strtotime($log['timestamp'])) . '</td>';
                echo '<td class="px-6 py-4 whitespace-nowrap">';
                if (isset($log['success']) && $log['success'] == 0) {
                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>';
                } else {
                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Success</span>';
                }
                echo '</td>';
                echo '</tr>';
                $rowNumber++;
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        // Show archived logs from daily_logs table
        $stmt = $pdo->prepare("
            SELECT * FROM daily_logs 
            WHERE log_date = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$date]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($logs)) {
            echo '<div class="text-center py-8"><p class="text-slate-600">No archived logs found for this date</p></div>';
            exit;
        }
        
        foreach ($logs as $log) {
            // Get the appropriate data based on the type requested
            if ($type === 'visitation') {
                $archivedData = json_decode($log['visitation_logs_data'], true);
            } else {
                $archivedData = json_decode($log['activity_logs_data'], true);
            }
            
            if ($type === 'visitation') {
                echo '<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-4">';
                echo '<div class="px-6 py-4 border-b border-slate-200 bg-slate-50">';
                echo '<h3 class="text-lg font-semibold text-slate-800">Visitation Logs - ' . htmlspecialchars($log['log_date']) . '</h3>';
                echo '</div>';
                echo '<div class="overflow-x-auto">';
                echo '<table class="w-full min-w-max">';
                echo '<thead class="bg-slate-50">';
                echo '<tr>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Patient</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">RFID</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Level/Department</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Course/Section</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Visit Time</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Reason</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody class="bg-white divide-y divide-slate-200">';
                
                if (is_array($archivedData)) {
                    $rowNumber = 1;
                    foreach ($archivedData as $visit) {
                        echo '<tr>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500 font-semibold">' . $rowNumber . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">' . htmlspecialchars($visit['patient_name'] ?? 'Unknown') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($visit['patient_rfid'] ?? 'N/A') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($visit['grade_level_department'] ?? 'N/A') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($visit['course_section_strand'] ?? 'N/A') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . date('M j, Y g:i A', strtotime($visit['visit_date'] ?? 'now')) . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($visit['reason'] ?? 'N/A') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap">';
                        echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
                        echo '</td>';
                        echo '</tr>';
                        $rowNumber++;
                    }
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                
            } elseif ($type === 'activity') {
                echo '<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-4">';
                echo '<div class="px-6 py-4 border-b border-slate-200 bg-slate-50">';
                echo '<h3 class="text-lg font-semibold text-slate-800">Activity Logs - ' . htmlspecialchars($log['log_date']) . '</h3>';
                echo '</div>';
                echo '<div class="overflow-x-auto">';
                echo '<table class="w-full min-w-max">';
                echo '<thead class="bg-slate-50">';
                echo '<tr>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No.</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">User</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Action</th>';
                echo '<th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Description</th>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">IP</th>';
                echo '<th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Time</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody class="bg-white divide-y divide-slate-200">';
                
                if (is_array($archivedData)) {
                    $rowNumber = 1;
                    foreach ($archivedData as $activity) {
                        echo '<tr>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500 font-semibold">' . $rowNumber . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">' . htmlspecialchars($activity['user_name'] ?? 'Unknown User') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($activity['action'] ?? 'N/A') . '</td>';
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($activity['description'] ?? 'No description') . '</td>';
                        echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">' . htmlspecialchars($activity['ip_address'] ?? 'N/A') . '</td>';
                         echo '<td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">' . date('M j, Y g:i A', strtotime($activity['timestamp'] ?? 'now')) . '</td>';
                        echo '</tr>';
                        $rowNumber++;
                    }
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
            }
        }
    }
    
} catch (Exception $e) {
    echo '<div class="text-center py-8"><p class="text-red-600">Error loading logs: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
?>
