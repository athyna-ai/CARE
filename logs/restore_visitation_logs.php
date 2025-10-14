<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

echo "<h2>Restore Visitation Logs from Archive</h2>";

// Get all archived logs
$dailyLogs = $pdo->query("SELECT log_date, visitation_logs_data FROM daily_logs ORDER BY log_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$restoredCount = 0;
$errorCount = 0;

foreach ($dailyLogs as $dailyLog) {
    $logDate = $dailyLog['log_date'];
    $visitationData = json_decode($dailyLog['visitation_logs_data'], true);
    
    if (empty($visitationData)) {
        echo "<p>No visitation data for $logDate</p>";
        continue;
    }
    
    echo "<h3>Restoring logs for $logDate (" . count($visitationData) . " records)</h3>";
    
    foreach ($visitationData as $visitation) {
        try {
            // Insert back into visitation_logs table
            $stmt = $pdo->prepare("
                INSERT INTO visitation_logs 
                (patient_id, patient_type, reason, visit_date, symptoms, other_notes, 
                 heart_rate, blood_pressure, temperature, medication_given, medication_name, 
                 other_treatment, medication_notes, injury, first_aid_given, first_aid_type, 
                 first_aid_notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $visitation['patient_id'],
                $visitation['patient_type'],
                $visitation['reason'],
                $visitation['visit_date'],
                $visitation['symptoms'] ?? null,
                $visitation['other_notes'] ?? null,
                $visitation['heart_rate'] ?? null,
                $visitation['blood_pressure'] ?? null,
                $visitation['temperature'] ?? null,
                $visitation['medication_given'] ?? 0,
                $visitation['medication_name'] ?? null,
                $visitation['other_treatment'] ?? null,
                $visitation['medication_notes'] ?? null,
                $visitation['injury'] ?? 0,
                $visitation['first_aid_given'] ?? 0,
                $visitation['first_aid_type'] ?? null,
                $visitation['first_aid_notes'] ?? null,
                $visitation['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $restoredCount++;
            
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>Error restoring record: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

echo "<h3>Restoration Complete</h3>";
echo "<p>Restored: $restoredCount records</p>";
echo "<p>Errors: $errorCount records</p>";

// Check final counts
$finalActivityCount = $pdo->query("SELECT COUNT(*) as count FROM activity_logs")->fetch()['count'];
$finalVisitationCount = $pdo->query("SELECT COUNT(*) as count FROM visitation_logs")->fetch()['count'];

echo "<p>Final Activity Logs: $finalActivityCount</p>";
echo "<p>Final Visitation Logs: $finalVisitationCount</p>";
?>
