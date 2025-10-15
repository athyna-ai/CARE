<?php
// Include config FIRST so csrf_token() function is available
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

session_start();

// Simple auth check
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Pagination settings
$recordsPerPage = 50;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Set page variables FIRST
$pageTitle = 'Activity Logs';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';

// Use existing config system
$logs = [];
$error = null;
$totalRecords = 0;
$totalPages = 0;

try {
    $pdo = get_pdo();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM activity_logs WHERE archived = 0";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Get paginated logs
    $sql = "SELECT l.id, l.timestamp, l.action, l.description, l.success, u.name as user_name, l.ip_address
            FROM activity_logs l 
            LEFT JOIN users u ON u.id = l.user_id 
            WHERE l.archived = 0
            ORDER BY l.timestamp DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Activity Logs</h1>
                    <p class="text-sm text-slate-500">System activity records - Page <?= $currentPage ?> of <?= $totalPages ?></p>
                </div>
                <div class="text-sm text-slate-600">
                    Total Records: <?= number_format($totalRecords) ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-lg font-semibold text-slate-800">Activity Records</h2>
                <p class="text-sm text-slate-600">Showing <?= count($logs) ?> of <?= number_format($totalRecords) ?> records</p>
            </div>
            
            <?php if ($error): ?>
                <div class="p-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800">Error: <?= htmlspecialchars($error) ?></p>
                        <p class="text-sm text-red-600 mt-2">This is a database connection or query error.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-slate-500">No activity logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            <?= date('M j, Y g:i A', strtotime($log['timestamp'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900">
                                            <?= htmlspecialchars($log['description'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($log['success']): ?>
                                                <span class="text-green-600">✓ Success</span>
                                            <?php else: ?>
                                                <span class="text-red-600">✗ Failed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            Showing page <?= $currentPage ?> of <?= $totalPages ?> 
                            (<?= number_format($totalRecords) ?> total records)
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=1" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                                    First
                                </a>
                                <a href="?page=<?= $currentPage - 1 ?>" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="?page=<?= $i ?>" class="px-3 py-2 text-sm font-medium <?= $i == $currentPage ? 'text-white bg-blue-600 border-blue-600' : 'text-slate-500 bg-white border-slate-300 hover:bg-slate-50' ?> border rounded-md">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?= $currentPage + 1 ?>" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                                    Next
                                </a>
                                <a href="?page=<?= $totalPages ?>" class="px-3 py-2 text-sm font-medium text-slate-500 bg-white border border-slate-300 rounded-md hover:bg-slate-50">
                                    Last
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>