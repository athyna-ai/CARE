<?php
/**
 * Enhanced Security Dashboard
 * Real-time security monitoring and threat analysis
 */

require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/advanced_rate_limiting.php';

require_admin_auth();

$pdo = get_pdo();

// Get security statistics
function getSecurityStats($pdo) {
    $stats = [];
    
    // Recent security events (last 24 hours)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_events,
            SUM(CASE WHEN action LIKE '%injection%' OR action LIKE '%xss%' OR action LIKE '%breach%' THEN 1 ELSE 0 END) as critical_events
        FROM activity_logs 
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $stats['recent_events'] = $stmt->fetch();
    
    // Top threat sources
    $stmt = $pdo->prepare("
        SELECT ip_address, COUNT(*) as attempts
        FROM activity_logs 
        WHERE success = 0 AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address 
        ORDER BY attempts DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $stats['top_threats'] = $stmt->fetchAll();
    
    // Security event trends (last 7 days)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(timestamp) as date,
            COUNT(*) as total_events,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_events
        FROM activity_logs 
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date DESC
    ");
    $stmt->execute();
    $stats['trends'] = $stmt->fetchAll();
    
    // Current rate limiting status
    $stats['rate_limits'] = AdvancedRateLimiter::getRateLimitStatus();
    
    return $stats;
}

$securityStats = getSecurityStats($pdo);

// Get recent critical events
$stmt = $pdo->prepare("
    SELECT * FROM activity_logs 
    WHERE (success = 0 OR action LIKE '%breach%' OR action LIKE '%injection%' OR action LIKE '%xss%')
    AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY timestamp DESC 
    LIMIT 20
");
$stmt->execute();
$recentEvents = $stmt->fetchAll();

$pageTitle = 'Enhanced Security Dashboard';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/partials/header.php';
?>

<div class="grid gap-6">
    <!-- Security Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Events (24h)</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $securityStats['recent_events']['total_events'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Failed Events</p>
                    <p class="text-2xl font-semibold text-red-600"><?= $securityStats['recent_events']['failed_events'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Critical Events</p>
                    <p class="text-2xl font-semibold text-orange-600"><?= $securityStats['recent_events']['critical_events'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Security Status</p>
                    <p class="text-lg font-semibold text-green-600">Protected</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Security Events -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Security Events (Last Hour)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentEvents as $event): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= date('H:i:s', strtotime($event['timestamp'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $event['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= htmlspecialchars($event['action']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($event['ip_address']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars(substr($event['description'], 0, 100)) ?>
                            <?= strlen($event['description']) > 100 ? '...' : '' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($event['success']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Success
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Failed
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top Threat Sources -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Top Threat Sources (Last 24 Hours)</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach ($securityStats['top_threats'] as $threat): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                        <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($threat['ip_address']) ?></span>
                    </div>
                    <span class="text-sm text-gray-500"><?= $threat['attempts'] ?> attempts</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
