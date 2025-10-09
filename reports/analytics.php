<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get parameters from URL
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$chartType = $_GET['chart_type'] ?? 'daily'; // daily, weekly, monthly
$patientType = $_GET['patient_type'] ?? 'all'; // all, student, faculty
$department = $_GET['department'] ?? 'all'; // all, specific department
$timeRange = $_GET['time_range'] ?? '30'; // 7, 30, 90, 365 days

// Adjust date range based on time range selection FIRST
// If start_date and end_date are provided in URL, use those (from quick range buttons)
// Otherwise, calculate from time_range parameter
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Use the dates provided in the URL (from quick range buttons)
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
} elseif (isset($_GET['time_range']) && $_GET['time_range'] !== 'custom') {
    // Calculate dates from time_range parameter
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$timeRange days"));
}


// Get statistics
$stats = [];

try {
    // 1. Get total patient counts (not filtered by date)
    $stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
    $stats['total_faculty'] = $pdo->query("SELECT COUNT(*) FROM faculty WHERE status = 'Active'")->fetchColumn();
    
    // 2. Get visitation statistics for the selected date range
    $visitationQuery = $pdo->prepare("
        SELECT 
            COUNT(*) as total_visits,
            COUNT(CASE WHEN patient_type = 'student' THEN 1 END) as student_visits,
            COUNT(CASE WHEN patient_type = 'faculty' THEN 1 END) as faculty_visits,
            COUNT(CASE WHEN medication_given = 1 THEN 1 END) as medication_given,
            COUNT(CASE WHEN injury = 1 THEN 1 END) as injuries,
            AVG(CASE WHEN temperature IS NOT NULL THEN temperature END) as avg_temperature
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ?
    ");
    $visitationQuery->execute([$startDate, $endDate]);
    $visitationData = $visitationQuery->fetch();
    
    // Merge visitation data into stats
    if ($visitationData) {
        $stats = array_merge($stats, $visitationData);
    }
    
    // 3. Get top reasons for visits
    $reasonsQuery = $pdo->prepare("
        SELECT reason, COUNT(*) as count 
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ?
        GROUP BY reason 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $reasonsQuery->execute([$startDate, $endDate]);
    $stats['top_reasons'] = $reasonsQuery->fetchAll();
    
    // 4. Get most common medications
    $medicationsQuery = $pdo->prepare("
        SELECT medication_name, COUNT(*) as count 
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ? 
        AND medication_name IS NOT NULL 
        AND medication_name != ''
        GROUP BY medication_name 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $medicationsQuery->execute([$startDate, $endDate]);
    $stats['medications'] = $medicationsQuery->fetchAll();
    
    // 5. Get comprehensive visit trends for chart
    $trendsQuery = $pdo->prepare("
        SELECT 
            DATE(visit_date) as date, 
            COUNT(*) as total_visits,
            COUNT(CASE WHEN patient_type = 'student' THEN 1 END) as student_visits,
            COUNT(CASE WHEN patient_type = 'faculty' THEN 1 END) as faculty_visits,
            COUNT(CASE WHEN medication_given = 1 THEN 1 END) as medication_visits,
            COUNT(CASE WHEN injury = 1 THEN 1 END) as injury_visits,
            AVG(CASE WHEN temperature IS NOT NULL THEN temperature END) as avg_temperature
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ?
        GROUP BY DATE(visit_date)
        ORDER BY date ASC
    ");
    $trendsQuery->execute([$startDate, $endDate]);
    $stats['visit_trends'] = $trendsQuery->fetchAll();
    
    // 6. Get hourly distribution for the selected period
    $hourlyQuery = $pdo->prepare("
        SELECT 
            HOUR(visit_date) as hour,
            COUNT(*) as visits
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ?
        GROUP BY HOUR(visit_date)
        ORDER BY hour ASC
    ");
    $hourlyQuery->execute([$startDate, $endDate]);
    $stats['hourly_distribution'] = $hourlyQuery->fetchAll();
    
    // 7. Get patient type breakdown
    $patientTypeQuery = $pdo->prepare("
        SELECT 
            patient_type,
            COUNT(*) as count,
            COUNT(CASE WHEN medication_given = 1 THEN 1 END) as medication_count,
            COUNT(CASE WHEN injury = 1 THEN 1 END) as injury_count
        FROM visitation_logs 
        WHERE DATE(visit_date) BETWEEN ? AND ?
        GROUP BY patient_type
    ");
    $patientTypeQuery->execute([$startDate, $endDate]);
    $stats['patient_type_breakdown'] = $patientTypeQuery->fetchAll();
    
} catch (Exception $e) {
    // Handle errors gracefully
    $stats = [
        'total_students' => 0,
        'total_faculty' => 0,
        'total_visits' => 0,
        'student_visits' => 0,
        'faculty_visits' => 0,
        'medication_given' => 0,
        'injuries' => 0,
        'avg_temperature' => null,
        'top_reasons' => [],
        'medications' => [],
        'visit_trends' => [],
        'hourly_distribution' => [],
        'patient_type_breakdown' => []
    ];
}

$pageTitle = "Reports & Analytics";
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla p-4 md:p-6">
    <div class="w-full max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-clinic-dark">Reports & Analytics</h1>
            <p class="text-clinic-dark/70 mt-2">Comprehensive health data insights and reporting</p>
            <div class="mt-2">
                <a href="?debug=1" class="text-xs text-blue-600 hover:text-blue-800">Debug Mode</a>
            </div>
            
            <!-- Debug Information (remove in production) -->
            <?php if (isset($_GET['debug'])): ?>
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 class="font-semibold text-yellow-800">Debug Information:</h4>
                <p class="text-sm text-yellow-700">Date Range: <?= $startDate ?> to <?= $endDate ?></p>
                <p class="text-sm text-yellow-700">Time Range: <?= $timeRange ?></p>
                <p class="text-sm text-yellow-700">Patient Type: <?= $patientType ?></p>
                <p class="text-sm text-yellow-700">Department: <?= $department ?></p>
                <p class="text-sm text-yellow-700">Chart Type: <?= $chartType ?></p>
                <p class="text-sm text-yellow-700">Total Visits: <?= $stats['total_visits'] ?? 0 ?></p>
                <p class="text-sm text-yellow-700">Add ?debug=1 to URL to see this info</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Analytics Filters -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 mb-6">
            <h2 class="text-xl font-semibold text-clinic-dark mb-4">Analytics Filters</h2>
            <form method="GET" class="space-y-4" id="analyticsForm">
                <input type="hidden" name="time_range" id="timeRangeInput" value="<?= htmlspecialchars($timeRange) ?>">
                <!-- Quick Time Range -->
                <div class="flex flex-wrap items-center gap-4">
                    <label class="text-sm font-medium text-clinic-dark/70">Quick Range:</label>
                    <div class="flex gap-2">
                        <a href="?time_range=7&start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                           class="px-3 py-1 rounded-lg border transition-colors <?= $timeRange === '7' ? 'bg-clinic-blue text-white border-clinic-blue' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400' ?>">
                            Last 7 Days
                        </a>
                        <a href="?time_range=30&start_date=<?= date('Y-m-d', strtotime('-30 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                           class="px-3 py-1 rounded-lg border transition-colors <?= $timeRange === '30' ? 'bg-clinic-blue text-white border-clinic-blue' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400' ?>">
                            Last 30 Days
                        </a>
                        <a href="?time_range=90&start_date=<?= date('Y-m-d', strtotime('-90 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                           class="px-3 py-1 rounded-lg border transition-colors <?= $timeRange === '90' ? 'bg-clinic-blue text-white border-clinic-blue' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400' ?>">
                            Last 90 Days
                        </a>
                        <a href="?time_range=365&start_date=<?= date('Y-m-d', strtotime('-365 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                           class="px-3 py-1 rounded-lg border transition-colors <?= $timeRange === '365' ? 'bg-clinic-blue text-white border-clinic-blue' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400' ?>">
                            Last Year
                        </a>
                    </div>
                </div>
                
                <!-- Advanced Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-clinic-dark/70 mb-2">Chart Type</label>
                        <select name="chart_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-white">
                            <option value="daily" <?= $chartType === 'daily' ? 'selected' : '' ?>>Daily Trends</option>
                            <option value="weekly" <?= $chartType === 'weekly' ? 'selected' : '' ?>>Weekly Trends</option>
                            <option value="monthly" <?= $chartType === 'monthly' ? 'selected' : '' ?>>Monthly Trends</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-clinic-dark/70 mb-2">Patient Type</label>
                        <select name="patient_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-white">
                            <option value="all" <?= $patientType === 'all' ? 'selected' : '' ?>>All Patients</option>
                            <option value="student" <?= $patientType === 'student' ? 'selected' : '' ?>>Students Only</option>
                            <option value="faculty" <?= $patientType === 'faculty' ? 'selected' : '' ?>>Faculty Only</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-clinic-dark/70 mb-2">Department</label>
                        <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-white">
                            <option value="all" <?= $department === 'all' ? 'selected' : '' ?>>All Departments</option>
                            <option value="Elementary" <?= $department === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                            <option value="Junior High School" <?= $department === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
                            <option value="Senior High School" <?= $department === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                            <option value="College" <?= $department === 'College' ? 'selected' : '' ?>>College</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-clinic-dark/70 mb-2">Custom Range</label>
                        <div class="flex gap-2">
                            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                                   class="flex-1 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue text-sm bg-white"
                                   onchange="document.querySelector('input[name=\"time_range\"]').value='custom'">
                            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                                   class="flex-1 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue text-sm bg-white"
                                   onchange="document.querySelector('input[name=\"time_range\"]').value='custom'">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg border border-clinic-blue hover:bg-clinic-tea hover:border-clinic-tea transition-colors">
                        Update Analytics
                    </button>
                    <a href="analytics.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg border border-gray-500 hover:bg-gray-600 hover:border-gray-600 transition-colors">
                        Reset Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Patients -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-clinic-dark/70">Total Patients</p>
                        <p class="text-3xl font-bold text-clinic-blue"><?= number_format($stats['total_students'] + $stats['total_faculty']) ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-clinic-blue/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-clinic-dark/60">
                    <span><?= number_format($stats['total_students']) ?> Students</span> • 
                    <span><?= number_format($stats['total_faculty']) ?> Faculty</span>
                </div>
            </div>

            <!-- Total Visits -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-clinic-dark/70">Total Visits</p>
                        <p class="text-3xl font-bold text-clinic-tea"><?= number_format($stats['total_visits']) ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-clinic-tea/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-clinic-tea" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-clinic-dark/60">
                    <span><?= number_format($stats['student_visits']) ?> Students</span> • 
                    <span><?= number_format($stats['faculty_visits']) ?> Faculty</span>
                </div>
            </div>

            <!-- Medications Given -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-clinic-dark/70">Medications Given</p>
                        <p class="text-3xl font-bold text-clinic-vanilla"><?= number_format($stats['medication_given']) ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-clinic-vanilla/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-clinic-vanilla" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-clinic-dark/60">
                    <?= $stats['total_visits'] > 0 ? round(($stats['medication_given'] / $stats['total_visits']) * 100, 1) : 0 ?>% of visits
                </div>
            </div>

            <!-- Injuries -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-clinic-dark/70">Injuries Reported</p>
                        <p class="text-3xl font-bold text-red-500"><?= number_format($stats['injuries']) ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-sm text-clinic-dark/60">
                    <?= $stats['total_visits'] > 0 ? round(($stats['injuries'] / $stats['total_visits']) * 100, 1) : 0 ?>% of visits
                </div>
            </div>
        </div>

        <!-- Charts and Reports -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Top Reasons for Visits -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <h3 class="text-lg font-semibold text-clinic-dark mb-4">Top Reasons for Visits</h3>
                <div class="space-y-3">
                    <?php if (!empty($stats['top_reasons'])): ?>
                        <?php foreach ($stats['top_reasons'] as $reason): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-clinic-dark/70 capitalize"><?= htmlspecialchars(str_replace('_', ' ', $reason['reason'])) ?></span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-clinic-ivory rounded-full h-2">
                                        <div class="bg-clinic-blue h-2 rounded-full" style="width: <?= ($reason['count'] / $stats['top_reasons'][0]['count']) * 100 ?>%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-clinic-blue w-8"><?= $reason['count'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-clinic-dark/60 text-center py-4">No visit data for selected period</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Most Common Medications -->
            <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
                <h3 class="text-lg font-semibold text-clinic-dark mb-4">Most Common Medications</h3>
                <div class="space-y-3">
                    <?php if (!empty($stats['medications'])): ?>
                        <?php foreach ($stats['medications'] as $medication): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-clinic-dark/70 capitalize"><?= htmlspecialchars(str_replace('_', ' ', $medication['medication_name'])) ?></span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-clinic-ivory rounded-full h-2">
                                        <div class="bg-clinic-tea h-2 rounded-full" style="width: <?= ($medication['count'] / $stats['medications'][0]['count']) * 100 ?>%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-clinic-tea w-8"><?= $medication['count'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-clinic-dark/60 text-center py-4">No medication data for selected period</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Age Group Analysis -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 mb-6">
            <h3 class="text-lg font-semibold text-clinic-dark mb-4">Student Age Group Analysis</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <?php if (!empty($stats['age_groups'])): ?>
                    <?php foreach ($stats['age_groups'] as $ageGroup): ?>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-clinic-blue"><?= $ageGroup['count'] ?></div>
                            <div class="text-sm text-clinic-dark/70"><?= $ageGroup['age_group'] ?> years</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-clinic-dark/60 text-center py-4 col-span-full">No age group data for selected period</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Monitoring -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-clinic-dark">
                    Security Monitoring
                </h3>
                <a href="../logs/logs.php?filter=security" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs rounded-lg transition-colors">View Security Logs</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <?php
                try {
                    // Get current user's security status
                    $userId = $_SESSION['user']['id'];
                    $user = $pdo->prepare('SELECT failed_attempts, locked_until, last_login FROM users WHERE id = ?');
                    $user->execute([$userId]);
                    $userData = $user->fetch();
                    
                    // Failed Attempts Card
                    $failedAttempts = $userData['failed_attempts'] ?? 0;
                    $attemptsColor = $failedAttempts >= 3 ? 'text-red-600' : ($failedAttempts >= 1 ? 'text-orange-600' : 'text-green-600');
                    $attemptsBg = $failedAttempts >= 3 ? 'bg-red-50 border-red-200' : ($failedAttempts >= 1 ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200');
                    
                    echo '<div class="bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-lg p-4 ' . $attemptsBg . '">';
                    echo '<div class="flex items-center justify-between">';
                    echo '<div>';
                            echo '<div class="flex items-center gap-2 mb-2">';
                            echo '<h4 class="text-sm font-semibold text-clinic-dark">Failed Attempts</h4>';
                            echo '</div>';
                    echo '<p class="text-2xl font-bold ' . $attemptsColor . '">' . $failedAttempts . '</p>';
                    echo '<p class="text-xs text-slate-500">Recent failed logins</p>';
                    echo '</div>';
                    echo '<button onclick="clearFailedAttempts()" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs rounded-lg transition-colors">Clear</button>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Account Status Card
                    $isLocked = $userData['locked_until'] && strtotime($userData['locked_until']) > time();
                    $statusColor = $isLocked ? 'text-red-600' : 'text-green-600';
                    $statusBg = $isLocked ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200';
                    $statusText = $isLocked ? 'Locked' : 'Active';
                    $statusIcon = $isLocked ? '🔒' : '✅';
                    
                    echo '<div class="bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-lg p-4 ' . $statusBg . '">';
                    echo '<div class="flex items-center justify-between">';
                    echo '<div>';
                            echo '<div class="flex items-center gap-2 mb-2">';
                            echo '<h4 class="text-sm font-semibold text-clinic-dark">Account Status</h4>';
                            echo '</div>';
                    echo '<p class="text-2xl font-bold ' . $statusColor . '">' . $statusText . '</p>';
                    echo '<p class="text-xs text-slate-500">Current state</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Last Login Card
                    $lastLogin = $userData['last_login'] ? date('M j, g:i A', strtotime($userData['last_login'])) : 'Never';
                    echo '<div class="bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-lg p-4">';
                    echo '<div class="flex items-center justify-between">';
                    echo '<div>';
                            echo '<div class="flex items-center gap-2 mb-2">';
                            echo '<h4 class="text-sm font-semibold text-clinic-dark">Last Login</h4>';
                            echo '</div>';
                    echo '<p class="text-lg font-semibold text-clinic-dark">' . $lastLogin . '</p>';
                    echo '<p class="text-xs text-slate-500">Most recent login</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<p class="text-red-500 text-sm">Error loading security status</p>';
                }
                ?>
            </div>
            
            <!-- Recent Security Events (Summary Only) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm font-semibold text-clinic-dark mb-3">
                        Recent Activity
                    </h4>
                    <div class="space-y-2 max-h-[120px] overflow-y-auto pr-2">
                        <?php
                        try {
                            // Get only unique recent activities (grouped by description to avoid repetition)
                            $recentLogins = $pdo->query('
                                SELECT description, COUNT(*) as count, MAX(timestamp) as latest_time, MAX(success) as success
                                FROM activity_logs 
                                WHERE action IN ("login_success", "login_failed", "account_locked", "rfid_verification_failed")
                                AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                GROUP BY description
                                ORDER BY latest_time DESC 
                                LIMIT 3
                            ')->fetchAll();
                            
                            if ($recentLogins) {
                                foreach ($recentLogins as $log) {
                                    $timeAgo = time() - strtotime($log['latest_time']);
                                    $timeText = $timeAgo < 60 ? 'Just now' : 
                                               ($timeAgo < 3600 ? floor($timeAgo/60) . 'm ago' : 
                                               floor($timeAgo/3600) . 'h ago');
                                    
                                    $bgColor = $log['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                                    $textColor = $log['success'] ? 'text-green-800' : 'text-red-800';
                                    
                                    echo '<div class="flex items-center gap-3 p-2 rounded-lg border ' . $bgColor . '">';
                                    echo '<div class="flex-1">';
                                    echo '<p class="text-xs font-medium ' . $textColor . '">' . htmlspecialchars($log['description']) . '</p>';
                                    echo '<p class="text-xs text-slate-500">' . $log['count'] . ' times • ' . $timeText . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg border border-slate-200">';
                                echo '<div class="flex-1">';
                                echo '<p class="text-xs font-medium text-slate-600">No recent activity</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<p class="text-red-500 text-sm">Error loading activity: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold text-clinic-dark mb-3">
                        Security Alerts
                    </h4>
                    <div class="space-y-2 max-h-[120px] overflow-y-auto pr-2">
                        <?php
                        try {
                            // Check for suspicious IPs (only show if there are any)
                            $suspiciousIPs = $pdo->query('
                                SELECT ip_address, COUNT(*) as failed_count, MAX(timestamp) as last_attempt
                                FROM activity_logs 
                                WHERE action = "login_failed" 
                                AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                                GROUP BY ip_address 
                                HAVING failed_count >= 5
                                ORDER BY failed_count DESC
                                LIMIT 2
                            ')->fetchAll();
                            
                            if ($suspiciousIPs) {
                                foreach ($suspiciousIPs as $suspicious) {
                                    $timeAgo = time() - strtotime($suspicious['last_attempt']);
                                    $timeText = $timeAgo < 60 ? 'Just now' : 
                                               ($timeAgo < 3600 ? floor($timeAgo/60) . 'm ago' : 
                                               floor($timeAgo/3600) . 'h ago');
                                    
                                    $severity = $suspicious['failed_count'] >= 10 ? 'bg-red-100 border-red-300' : 'bg-orange-100 border-orange-300';
                                    $severityText = $suspicious['failed_count'] >= 10 ? 'text-red-800' : 'text-orange-800';
                                    
                                    echo '<div class="flex items-center gap-3 p-2 rounded-lg border ' . $severity . '">';
                                    echo '<div class="flex-1">';
                                    echo '<p class="text-xs font-medium ' . $severityText . '">' . $suspicious['failed_count'] . ' failed attempts</p>';
                                    echo '<p class="text-xs text-slate-500">' . htmlspecialchars($suspicious['ip_address']) . ' • ' . $timeText . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="flex items-center gap-3 p-2 bg-green-50 rounded-lg border border-green-200">';
                                echo '<div class="flex-1">';
                                echo '<p class="text-xs font-medium text-green-800">No security alerts</p>';
                                echo '<p class="text-xs text-slate-500">All systems secure</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<p class="text-red-500 text-sm">Error loading alerts: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comprehensive Visit Analytics Chart -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-clinic-dark">Live Visit Analytics</h3>
                <div class="text-sm text-clinic-dark/60">
                    <?= date('M j', strtotime($startDate)) ?> - <?= date('M j', strtotime($endDate)) ?>
                </div>
            </div>
            
            <?php if (!empty($stats['visit_trends'])): ?>
                <?php 
                $maxVisits = max(array_column($stats['visit_trends'], 'total_visits'));
                $trends = $stats['visit_trends'];
                $totalDays = count($trends);
                $totalVisits = array_sum(array_column($trends, 'total_visits'));
                $avgVisitsPerDay = $totalDays > 0 ? round($totalVisits / $totalDays, 1) : 0;
                ?>
                
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center p-3 bg-clinic-blue/5 rounded-lg">
                        <div class="text-2xl font-bold text-clinic-blue"><?= $totalVisits ?></div>
                        <div class="text-sm text-clinic-dark/60">Total Visits</div>
                    </div>
                    <div class="text-center p-3 bg-clinic-green/5 rounded-lg">
                        <div class="text-2xl font-bold text-clinic-green"><?= $avgVisitsPerDay ?></div>
                        <div class="text-sm text-clinic-dark/60">Avg/Day</div>
                    </div>
                    <div class="text-center p-3 bg-clinic-tea/5 rounded-lg">
                        <div class="text-2xl font-bold text-clinic-tea"><?= $totalDays ?></div>
                        <div class="text-sm text-clinic-dark/60">Days Tracked</div>
                    </div>
                    <div class="text-center p-3 bg-clinic-red/5 rounded-lg">
                        <div class="text-2xl font-bold text-clinic-red"><?= $maxVisits ?></div>
                        <div class="text-sm text-clinic-dark/60">Peak Day</div>
                    </div>
                </div>
                <div class="relative h-64 w-full">
                    <!-- Y-axis labels -->
                    <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-clinic-dark/60 pr-2">
                        <span><?= $maxVisits ?></span>
                        <span><?= round($maxVisits * 0.75) ?></span>
                        <span><?= round($maxVisits * 0.5) ?></span>
                        <span><?= round($maxVisits * 0.25) ?></span>
                        <span>0</span>
                    </div>
                    
                    <!-- Chart area -->
                    <div class="ml-8 mr-4 h-full relative">
                        <!-- Grid lines -->
                        <div class="absolute inset-0">
                            <div class="h-full w-full flex flex-col justify-between">
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                            </div>
                        </div>
                        
                        <!-- Line chart -->
                        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <?php
                            $pointCount = count($trends);
                            $points = [];
                            foreach ($trends as $index => $trend) {
                                // Fix division by zero when there's only one data point
                                $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                $y = $maxVisits > 0 ? 100 - (($trend['total_visits'] / $maxVisits) * 100) : 100;
                                $points[] = "$x,$y";
                            }
                            $pathData = "M " . implode(" L ", $points);
                            ?>
                            
                            <!-- Line path -->
                            <path d="<?= $pathData ?>" 
                                  stroke="url(#lineGradient)" 
                                  stroke-width="0.5" 
                                  fill="none" 
                                  stroke-linecap="round" 
                                  stroke-linejoin="round"/>
                            
                            <!-- Gradient definition -->
                            <defs>
                                <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#10B981;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#F59E0B;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Data points -->
                            <?php foreach ($trends as $index => $trend): 
                                $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                $y = $maxVisits > 0 ? 100 - (($trend['total_visits'] / $maxVisits) * 100) : 100;
                            ?>
                                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="1.5" fill="#3B82F6" class="hover:r-2 transition-all duration-200">
                                    <title><?= $trend['total_visits'] ?> visits on <?= date('M j', strtotime($trend['date'])) ?> (Students: <?= $trend['student_visits'] ?>, Faculty: <?= $trend['faculty_visits'] ?>)</title>
                                </circle>
                            <?php endforeach; ?>
                        </svg>
                        
                        <!-- X-axis labels -->
                        <div class="absolute -bottom-6 left-0 right-0 flex justify-between text-xs text-clinic-dark/60">
                            <?php foreach ($trends as $index => $trend): ?>
                                <?php if ($index % max(1, floor($pointCount / 8)) == 0): ?>
                                    <span class="transform -rotate-45 origin-left"><?= date('M j', strtotime($trend['date'])) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- No data message -->
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">No Visit Data Available</h4>
                    <p class="text-gray-500 text-sm">No visitation records found for the selected date range.</p>
                    <p class="text-gray-400 text-xs mt-2">Try adjusting the date range or add some visitation records.</p>
                </div>
                <div class="relative h-64 w-full">
                    <?php 
                    // Generate sample data for demonstration
                    $sampleData = [
                        ['date' => '2024-09-15', 'visits' => 3],
                        ['date' => '2024-09-16', 'visits' => 7],
                        ['date' => '2024-09-17', 'visits' => 2],
                        ['date' => '2024-09-18', 'visits' => 5],
                        ['date' => '2024-09-19', 'visits' => 8],
                        ['date' => '2024-09-20', 'visits' => 4],
                        ['date' => '2024-09-21', 'visits' => 6],
                        ['date' => '2024-09-22', 'visits' => 3],
                        ['date' => '2024-09-23', 'visits' => 9],
                        ['date' => '2024-09-24', 'visits' => 5]
                    ];
                    $maxVisits = max(array_column($sampleData, 'visits'));
                    $chartHeight = 200;
                    ?>
                    
                    <!-- Y-axis labels -->
                    <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-clinic-dark/60 pr-2">
                        <span><?= $maxVisits ?></span>
                        <span><?= round($maxVisits * 0.75) ?></span>
                        <span><?= round($maxVisits * 0.5) ?></span>
                        <span><?= round($maxVisits * 0.25) ?></span>
                        <span>0</span>
                    </div>
                    
                    <!-- Chart area -->
                    <div class="ml-8 mr-4 h-full relative">
                        <!-- Grid lines -->
                        <div class="absolute inset-0">
                            <div class="h-full w-full flex flex-col justify-between">
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                                <div class="border-t border-clinic-tea/20"></div>
                            </div>
                        </div>
                        
                        <!-- Line chart -->
                        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <?php
                            $pointCount = count($sampleData);
                            $points = [];
                            foreach ($sampleData as $index => $trend) {
                                $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                $y = 100 - (($trend['visits'] / $maxVisits) * 100);
                                $points[] = "$x,$y";
                            }
                            $pathData = "M " . implode(" L ", $points);
                            ?>
                            
                            <!-- Line path -->
                            <path d="<?= $pathData ?>" 
                                  stroke="url(#sampleLineGradient)" 
                                  stroke-width="0.5" 
                                  fill="none" 
                                  stroke-linecap="round" 
                                  stroke-linejoin="round"/>
                            
                            <!-- Gradient definition -->
                            <defs>
                                <linearGradient id="sampleLineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#10B981;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#F59E0B;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Data points -->
                            <?php foreach ($sampleData as $index => $trend): 
                                $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                $y = 100 - (($trend['visits'] / $maxVisits) * 100);
                            ?>
                                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="1.5" fill="#3B82F6" class="hover:r-2 transition-all duration-200">
                                    <title><?= $trend['visits'] ?> visits on <?= date('M j', strtotime($trend['date'])) ?></title>
                                </circle>
                            <?php endforeach; ?>
                        </svg>
                        
                        <!-- X-axis labels -->
                        <div class="absolute -bottom-6 left-0 right-0 flex justify-between text-xs text-clinic-dark/60">
                            <?php foreach ($sampleData as $index => $trend): ?>
                                <?php if ($index % 2 == 0): ?>
                                    <span class="transform -rotate-45 origin-left"><?= date('M j', strtotime($trend['date'])) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sample data notice -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-clinic-dark/60 italic">Sample Data - Add real visits to see trends</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Additional Analytics Charts -->
<?php if (!empty($stats['visit_trends'])): ?>
<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Hourly Distribution Chart -->
    <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
        <h3 class="text-lg font-semibold text-clinic-dark mb-4">Hourly Visit Distribution</h3>
        <?php if (!empty($stats['hourly_distribution'])): ?>
            <div class="space-y-2">
                <?php 
                $maxHourlyVisits = max(array_column($stats['hourly_distribution'], 'visits'));
                foreach ($stats['hourly_distribution'] as $hourData): 
                    $percentage = $maxHourlyVisits > 0 ? ($hourData['visits'] / $maxHourlyVisits) * 100 : 0;
                    $hour = $hourData['hour'];
                    $hourLabel = $hour < 12 ? ($hour == 0 ? '12 AM' : $hour . ' AM') : ($hour == 12 ? '12 PM' : ($hour - 12) . ' PM');
                ?>
                    <div class="flex items-center gap-3">
                        <div class="w-16 text-sm text-clinic-dark/70"><?= $hourLabel ?></div>
                        <div class="flex-1 bg-gray-200 rounded-full h-4">
                            <div class="bg-clinic-blue h-4 rounded-full transition-all duration-500" style="width: <?= $percentage ?>%"></div>
                        </div>
                        <div class="w-8 text-sm font-medium text-clinic-dark"><?= $hourData['visits'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-gray-500">No hourly data available</div>
        <?php endif; ?>
    </div>

    <!-- Patient Type Breakdown -->
    <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
        <h3 class="text-lg font-semibold text-clinic-dark mb-4">Patient Type Breakdown</h3>
        <?php if (!empty($stats['patient_type_breakdown'])): ?>
            <div class="space-y-4">
                <?php foreach ($stats['patient_type_breakdown'] as $typeData): ?>
                    <div class="p-4 rounded-lg border border-clinic-tea/20">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-clinic-dark capitalize"><?= $typeData['patient_type'] ?>s</h4>
                            <span class="text-2xl font-bold text-clinic-blue"><?= $typeData['count'] ?></span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-clinic-green rounded-full"></div>
                                <span class="text-clinic-dark/70">Medications: <?= $typeData['medication_count'] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-clinic-red rounded-full"></div>
                                <span class="text-clinic-dark/70">Injuries: <?= $typeData['injury_count'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-gray-500">No patient type data available</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Security action functions
function clearFailedAttempts() {
    if (confirm('Clear all failed login attempts for admin users?')) {
        fetch('admin/clear_failed_attempts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_failed_attempts'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Failed attempts cleared successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error clearing failed attempts', 'error');
            }
        })
        .catch(error => {
            showNotification('Error clearing failed attempts', 'error');
        });
    }
}

// Notification system
function showNotification(message, type = 'success', duration = 3000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center gap-2">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">×</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

</script>



<?php include __DIR__ . '/../partials/footer.php'; ?>
