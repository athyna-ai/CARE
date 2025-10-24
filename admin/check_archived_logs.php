<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

echo "<h2>Archived Logs Check</h2>";

// Check daily_logs table
$dailyLogs = $pdo->query("SELECT log_date, total_activities, total_visitations, created_at FROM daily_logs ORDER BY log_date DESC")->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Daily Logs Archive:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Date</th><th>Activities</th><th>Visitations</th><th>Created At</th></tr>";
foreach ($dailyLogs as $log) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($log['log_date']) . "</td>";
    echo "<td>" . htmlspecialchars($log['total_activities']) . "</td>";
    echo "<td>" . htmlspecialchars($log['total_visitations']) . "</td>";
    echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if we can see the actual data
if (!empty($dailyLogs)) {
    $firstLog = $dailyLogs[0];
    echo "<h3>Sample Archived Data for " . $firstLog['log_date'] . ":</h3>";
    
    // Get the actual JSON data
    $stmt = $pdo->prepare("SELECT activity_logs_data, visitation_logs_data FROM daily_logs WHERE log_date = ?");
    $stmt->execute([$firstLog['log_date']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $activityData = json_decode($data['activity_logs_data'], true);
        $visitationData = json_decode($data['visitation_logs_data'], true);
        
        echo "<h4>Activity Logs Sample:</h4>";
        echo "<pre>" . htmlspecialchars(json_encode($activityData[0] ?? 'No data', JSON_PRETTY_PRINT)) . "</pre>";
        
        echo "<h4>Visitation Logs Sample:</h4>";
        echo "<pre>" . htmlspecialchars(json_encode($visitationData[0] ?? 'No data', JSON_PRETTY_PRINT)) . "</pre>";
    }
}

// Check current tables
echo "<h3>Current Tables:</h3>";
$activityCount = $pdo->query("SELECT COUNT(*) as count FROM activity_logs")->fetch()['count'];
$visitationCount = $pdo->query("SELECT COUNT(*) as count FROM visitation_logs")->fetch()['count'];

echo "<p>Current Activity Logs: $activityCount</p>";
echo "<p>Current Visitation Logs: $visitationCount</p>";
?>
