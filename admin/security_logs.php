<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();

$pdo = get_pdo();
$user = $_SESSION['user'];

// Get filter parameters
$filterAction = $_GET['action'] ?? '';
$filterSuccess = $_GET['success'] ?? '';
$filterDate = $_GET['date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = ['al.action IN ("login_success", "login_failed", "account_locked", "rfid_verification_failed", "security_action")'];
$params = [];

if ($filterAction) {
    $whereConditions[] = 'al.action = ?';
    $params[] = $filterAction;
}

if ($filterSuccess !== '') {
    $whereConditions[] = 'al.success = ?';
    $params[] = (int)$filterSuccess;
}

if ($filterDate) {
    $whereConditions[] = 'DATE(al.timestamp) = ?';
    $params[] = $filterDate;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE {$whereClause}");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Get security logs
$logsStmt = $pdo->prepare("
    SELECT 
        al.*,
        u.name as user_name,
        u.email as user_email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE {$whereClause}
    ORDER BY al.timestamp DESC
    LIMIT {$limit} OFFSET {$offset}
");
$logsStmt->execute($params);
$securityLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Security Logs';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<div class="min-h-[calc(100vh-5rem)] flex flex-col gap-8 px-4 md:px-6 lg:px-8">
    <!-- Header -->
    <div class="pt-8 md:pt-16">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-clinic-dark">🔒 Security Logs</h1>
                <p class="text-clinic-dark/60 mt-2">Detailed security event history and monitoring</p>
            </div>
            <a href="settings.php?section=security_monitoring" class="px-4 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-blue/90 transition-colors">
                ← Back to Security Monitoring
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 p-6">
        <h3 class="text-lg font-semibold text-clinic-dark mb-4">Filters</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-clinic-dark mb-2">Action</label>
                <select name="action" class="w-full px-3 py-2 border border-clinic-tea/20 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                    <option value="">All Actions</option>
                    <option value="login_success" <?= $filterAction === 'login_success' ? 'selected' : '' ?>>Login Success</option>
                    <option value="login_failed" <?= $filterAction === 'login_failed' ? 'selected' : '' ?>>Login Failed</option>
                    <option value="account_locked" <?= $filterAction === 'account_locked' ? 'selected' : '' ?>>Account Locked</option>
                    <option value="rfid_verification_failed" <?= $filterAction === 'rfid_verification_failed' ? 'selected' : '' ?>>RFID Failed</option>
                    <option value="security_action" <?= $filterAction === 'security_action' ? 'selected' : '' ?>>Security Action</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-clinic-dark mb-2">Status</label>
                <select name="success" class="w-full px-3 py-2 border border-clinic-tea/20 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
                    <option value="">All Status</option>
                    <option value="1" <?= $filterSuccess === '1' ? 'selected' : '' ?>>Success</option>
                    <option value="0" <?= $filterSuccess === '0' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-clinic-dark mb-2">Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="w-full px-3 py-2 border border-clinic-tea/20 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-blue/90 transition-colors">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Security Logs Table -->
    <div class="bg-white rounded-2xl shadow-lg border border-clinic-tea/20 overflow-hidden">
        <div class="p-6 border-b border-clinic-tea/20">
            <h3 class="text-lg font-semibold text-clinic-dark">Security Events (<?= number_format($totalRecords) ?> total)</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-clinic-ivory/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-clinic-dark uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-clinic-tea/20">
                    <?php if ($securityLogs): ?>
                        <?php foreach ($securityLogs as $log): ?>
                            <tr class="hover:bg-clinic-ivory/30">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-clinic-dark">
                                    <?= date('M j, Y g:i A', strtotime($log['timestamp'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $log['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-clinic-dark">
                                    <?= $log['user_name'] ? htmlspecialchars($log['user_name']) : 'Unknown User' ?>
                                    <?php if ($log['user_email']): ?>
                                        <br><span class="text-xs text-clinic-dark/60"><?= htmlspecialchars($log['user_email']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-clinic-dark">
                                    <?= htmlspecialchars($log['description']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-clinic-dark">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $log['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $log['success'] ? 'Success' : 'Failed' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-clinic-dark/60">
                                No security events found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-clinic-tea/20">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-clinic-dark/60">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= number_format($totalRecords) ?> results
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1 bg-clinic-blue text-white rounded hover:bg-clinic-blue/90 transition-colors">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1 <?= $i === $page ? 'bg-clinic-blue text-white' : 'bg-clinic-ivory text-clinic-dark hover:bg-clinic-blue hover:text-white' ?> rounded transition-colors">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1 bg-clinic-blue text-white rounded hover:bg-clinic-blue/90 transition-colors">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
