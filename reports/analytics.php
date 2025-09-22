<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get date range from URL parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Validate dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Get statistics
$stats = [];

try {
    // Total students and faculty
    $stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $stats['total_faculty'] = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    
    // Visitation statistics for date range
    $visitationStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_visits,
            COUNT(CASE WHEN patient_type = 'student' THEN 1 END) as student_visits,
            COUNT(CASE WHEN patient_type = 'faculty' THEN 1 END) as faculty_visits,
            COUNT(CASE WHEN medication_given = 1 THEN 1 END) as medication_given,
            COUNT(CASE WHEN injury = 1 THEN 1 END) as injuries
        FROM visitation_logs 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $visitationStats->execute([$startDate, $endDate]);
    $stats = array_merge($stats, $visitationStats->fetch());
    
    // Top reasons for visits
    $topReasons = $pdo->prepare("
        SELECT reason, COUNT(*) as count 
        FROM visitation_logs 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY reason 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $topReasons->execute([$startDate, $endDate]);
    $stats['top_reasons'] = $topReasons->fetchAll();
    
    // Daily visit trends (last 30 days)
    $dailyTrends = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as visits
        FROM visitation_logs 
        WHERE DATE(created_at) BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $dailyTrends->execute([$endDate, $endDate]);
    $stats['daily_trends'] = $dailyTrends->fetchAll();
    
    // Monthly visit trends (last 12 months)
    $monthlyTrends = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as visits,
            COUNT(CASE WHEN patient_type = 'student' THEN 1 END) as student_visits,
            COUNT(CASE WHEN patient_type = 'faculty' THEN 1 END) as faculty_visits
        FROM visitation_logs 
        WHERE DATE(created_at) BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyTrends->execute([$endDate, $endDate]);
    $stats['monthly_trends'] = $monthlyTrends->fetchAll();
    
    // Most common medications
    $medications = $pdo->prepare("
        SELECT medication_name, COUNT(*) as count
        FROM visitation_logs 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND medication_given = 1 
        AND medication_name IS NOT NULL 
        AND medication_name != 'N/A'
        GROUP BY medication_name 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $medications->execute([$startDate, $endDate]);
    $stats['medications'] = $medications->fetchAll();
    
    // Age group analysis (students only)
    $ageGroups = $pdo->prepare("
        SELECT 
            CASE 
                WHEN age BETWEEN 16 AND 18 THEN '16-18'
                WHEN age BETWEEN 19 AND 21 THEN '19-21'
                WHEN age BETWEEN 22 AND 24 THEN '22-24'
                WHEN age >= 25 THEN '25+'
                ELSE 'Under 16'
            END as age_group,
            COUNT(*) as count
        FROM students s
        JOIN visitation_logs vl ON s.id = vl.patient_id AND vl.patient_type = 'student'
        WHERE DATE(vl.created_at) BETWEEN ? AND ?
        GROUP BY age_group
        ORDER BY age_group
    ");
    $ageGroups->execute([$startDate, $endDate]);
    $stats['age_groups'] = $ageGroups->fetchAll();
    
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $stats = [
        'total_students' => 0,
        'total_faculty' => 0,
        'total_visits' => 0,
        'student_visits' => 0,
        'faculty_visits' => 0,
        'medication_given' => 0,
        'injuries' => 0,
        'top_reasons' => [],
        'daily_trends' => [],
        'monthly_trends' => [],
        'medications' => [],
        'age_groups' => []
    ];
}

// Debug information (remove this in production)
$debug_info = [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'daily_trends_count' => count($stats['daily_trends']),
    'total_visits' => $stats['total_visits']
];

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
            
            <!-- Debug Information (remove in production) -->
            <?php if (isset($_GET['debug'])): ?>
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 class="font-semibold text-yellow-800">Debug Information:</h4>
                <p class="text-sm text-yellow-700">Date Range: <?= $debug_info['start_date'] ?> to <?= $debug_info['end_date'] ?></p>
                <p class="text-sm text-yellow-700">Daily Trends Count: <?= $debug_info['daily_trends_count'] ?></p>
                <p class="text-sm text-yellow-700">Total Visits: <?= $debug_info['total_visits'] ?></p>
                <p class="text-sm text-yellow-700">Add ?debug=1 to URL to see this info</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6 mb-6">
            <h2 class="text-xl font-semibold text-clinic-dark mb-4">Date Range Filter</h2>
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-clinic-dark/70 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                           class="w-full rounded-lg border border-clinic-tea/30 px-3 py-2 focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-clinic-dark/70 mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                           class="w-full rounded-lg border border-clinic-tea/30 px-3 py-2 focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors font-medium">
                        Update Reports
                    </button>
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

        <!-- Daily Trends Line Chart -->
        <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
            <h3 class="text-lg font-semibold text-clinic-dark mb-4">Daily Visit Trends (Last 30 Days)</h3>
            
            <?php if (!empty($stats['daily_trends'])): ?>
                <?php 
                $maxVisits = max(array_column($stats['daily_trends'], 'visits'));
                $trends = $stats['daily_trends'];
                $chartHeight = 200;
                $chartWidth = 100; // percentage
                ?>
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
                                $x = ($index / ($pointCount - 1)) * 100;
                                $y = $maxVisits > 0 ? 100 - (($trend['visits'] / $maxVisits) * 100) : 100;
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
                                $x = ($index / ($pointCount - 1)) * 100;
                                $y = $maxVisits > 0 ? 100 - (($trend['visits'] / $maxVisits) * 100) : 100;
                            ?>
                                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="1.5" fill="#3B82F6" class="hover:r-2 transition-all duration-200">
                                    <title><?= $trend['visits'] ?> visits on <?= date('M j', strtotime($trend['date'])) ?></title>
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
                <!-- Show sample data for demonstration -->
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
                                $x = ($index / ($pointCount - 1)) * 100;
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
                                $x = ($index / ($pointCount - 1)) * 100;
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

<?php include __DIR__ . '/../partials/footer.php'; ?>
