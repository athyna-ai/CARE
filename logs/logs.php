<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();

$pdo = get_pdo();

// Get filter parameter - default to all logs
$filter = $_GET['filter'] ?? 'all';

// Pagination parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50; // Reduced from 100 for better pagination
$offset = ($page - 1) * $perPage;

// Build query based on filter
$whereClause = '';
$params = [];

$whereClause = 'WHERE l.archived = 0';
if ($filter !== 'all') {
    if ($filter === 'security') {
        // Security-related actions - expanded list
        $whereClause .= ' AND (l.action IN ("login", "logout", "login_failed", "login_success", "account_locked", "rfid_verification_failed", "rfid_verified", "unauthorized_access", "password_update", "ip_blocked", "RFID_VERIFICATION_FAILED", "RFID_VERIFICATION_SUCCESS", "SECURITY_BREACH_DETECTED", "SQL_INJECTION_ATTEMPT", "UNAUTHORIZED_ACCESS_ATTEMPT") OR l.action LIKE "%BREACH%" OR l.action LIKE "%SECURITY%" OR l.action LIKE "%INJECTION%" OR l.action LIKE "%SETTINGS%" OR l.action LIKE "%CSRF%" OR l.action LIKE "%RATE%" OR l.success = 0)';
    } else {
        $whereClause .= ' AND l.action = ?';
        $params[] = $filter;
    }
}

// Get security logs from database for security filter
if ($filter === 'security') {
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM activity_logs l 
                 WHERE l.archived = 0 AND (l.success = 0 OR l.user_type = 'system' OR l.action LIKE '%BREACH%' OR l.action LIKE '%SECURITY%' OR l.action LIKE '%INJECTION%' OR l.action LIKE '%SETTINGS%' OR l.action LIKE '%CSRF%' OR l.action LIKE '%RATE%' OR l.action LIKE '%ADMIN%' OR l.action LIKE '%UNAUTHORIZED%')";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Override the main query for security events with pagination
    $sql = "SELECT l.id, l.timestamp as created_at, l.action, 
                   COALESCE(l.action_description, l.description) as details, 
                   l.location, l.ip_address, l.user_type, l.success, l.error_message,
                   u.name AS user_name, u.email AS user_email 
            FROM activity_logs l 
            LEFT JOIN users u ON u.id = l.user_id 
            WHERE l.archived = 0 AND (l.success = 0 OR l.user_type = 'system' OR l.action LIKE '%BREACH%' OR l.action LIKE '%SECURITY%' OR l.action LIKE '%INJECTION%' OR l.action LIKE '%SETTINGS%' OR l.action LIKE '%CSRF%' OR l.action LIKE '%RATE%' OR l.action LIKE '%ADMIN%' OR l.action LIKE '%UNAUTHORIZED%')
            ORDER BY l.timestamp DESC 
            LIMIT $perPage OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    // Also add file-based security logs
    $alertsFile = __DIR__ . '/alerts.log';
    if (file_exists($alertsFile)) {
        $alertLines = file($alertsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach (array_reverse($alertLines) as $index => $line) {
            $parts = explode(' - ', $line, 3);
            if (count($parts) >= 3) {
                $logs[] = [
                    'id' => 'alert_file_' . $index,
                    'created_at' => $parts[0],
                    'action' => $parts[1],
                    'details' => $parts[2],
                    'location' => 'Security Framework',
                    'ip_address' => 'System',
                    'user_name' => 'Security Monitor',
                    'user_email' => null
                ];
            }
        }
    }
    
    // Sort combined logs by timestamp
    usort($logs, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 50 most recent
    $logs = array_slice($logs, 0, 50);
}

// Only run main query if not using security filter
if ($filter !== 'security') {
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM activity_logs l {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    $sql = "SELECT l.id, l.timestamp as created_at, l.action, 
                   COALESCE(l.action_description, l.description) as details, 
                   l.location, l.ip_address, l.user_type, l.success, l.error_message,
                   u.name AS user_name, u.email AS user_email 
            FROM activity_logs l 
            LEFT JOIN users u ON u.id = l.user_id 
            {$whereClause}
            ORDER BY l.timestamp DESC 
            LIMIT $perPage OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

// Get unique action types for dropdown
$actionStmt = $pdo->query('SELECT DISTINCT action FROM activity_logs WHERE archived = 0 ORDER BY action');
$actionTypes = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

// Debug: Get total counts
$totalNonArchivedLogs = $pdo->query('SELECT COUNT(*) FROM activity_logs WHERE archived = 0')->fetchColumn();
$totalArchivedLogs = $pdo->query('SELECT COUNT(*) FROM activity_logs WHERE archived = 1')->fetchColumn();
$totalDailyLogs = $pdo->query('SELECT COUNT(*) FROM daily_logs')->fetchColumn();
?>
<?php $pageTitle = 'Activity Logs'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/../partials/header.php'; ?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="grid gap-6">
		<div class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-xl p-6">
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
				<div>
					<div class="flex items-center gap-4 mb-2">
						<a href="../admin/dashboard.php" class="flex items-center gap-2 text-clinic-blue hover:text-clinic-tea transition-colors">
							<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
							</svg>
							Back to Dashboard
						</a>
					</div>
					<h1 class="text-2xl font-semibold mb-4 sm:mb-0">
						Activity Logs
						<?php if ($filter === 'security'): ?>
							<span class="text-sm font-normal text-slate-600">(Security Events)</span>
						<?php endif; ?>
					</h1>
					<!-- Debug Info -->
					<div class="text-xs text-slate-500 mb-2">
						Debug: Non-archived: <?= $totalNonArchivedLogs ?> | Archived: <?= $totalArchivedLogs ?> | Daily logs: <?= $totalDailyLogs ?>
					</div>
				</div>
				
				<!-- Filter Dropdown and Archives Button -->
				<div class="flex items-center gap-3">
					<label for="filterSelect" class="text-sm font-medium text-slate-700">Filter by type:</label>
					<select id="filterSelect" onchange="filterLogs(this.value)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
						<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Activities</option>
						<option value="security" <?= $filter === 'security' ? 'selected' : '' ?>>Security Events</option>
						<?php foreach ($actionTypes as $action): ?>
							<option value="<?= htmlspecialchars($action) ?>" <?= $filter === $action ? 'selected' : '' ?>>
								<?= htmlspecialchars(ucwords(str_replace('_', ' ', $action))) ?>
							</option>
						<?php endforeach; ?>
					</select>
					
				</div>
			</div>
			
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-200 text-sm">
					<thead class="bg-slate-50">
						<tr>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">No.</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">ID</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">Action Type</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">User</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">Description</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">Location</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">When</th>
							<th class="px-4 py-3 text-left font-semibold text-slate-700">IP Address</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-200">
						<?php 
						$rowNumber = $offset + 1; // Start numbering from the correct offset
						foreach ($logs as $row): ?>
							<tr class="hover:bg-slate-50">
								<td class="px-4 py-3 font-mono text-xs text-slate-500 font-semibold">
									<?= $rowNumber ?>
								</td>
								<td class="px-4 py-3 font-mono text-xs text-slate-500">#<?= htmlspecialchars((string)$row['id']) ?></td>
								<td class="px-4 py-3">
									<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
										switch($row['action']) {
											// Authentication actions
											case 'login': echo 'bg-green-100 text-green-800';
											case 'login_success': echo 'bg-green-100 text-green-800';
											case 'logout': echo 'bg-blue-100 text-blue-800';
											case 'login_failed': echo 'bg-red-100 text-red-800';
											case 'register': echo 'bg-green-100 text-green-800';
											case 'unauthorized_access': echo 'bg-red-100 text-red-800';
											case 'account_locked': echo 'bg-red-100 text-red-800';
											case 'ip_blocked': echo 'bg-red-100 text-red-800';
											
											// Student actions
											case 'student_register': echo 'bg-emerald-100 text-emerald-800';
											case 'student_update': echo 'bg-amber-100 text-amber-800';
											case 'student_archived': echo 'bg-orange-100 text-orange-800';
											case 'student_restored': echo 'bg-green-100 text-green-800';
											
											// Faculty actions
											case 'faculty_register': echo 'bg-purple-100 text-purple-800';
											case 'faculty_update': echo 'bg-orange-100 text-orange-800';
											case 'faculty_archived': echo 'bg-red-100 text-red-800';
											case 'faculty_restored': echo 'bg-green-100 text-green-800';
											
											// RFID actions
											case 'rfid_search': echo 'bg-cyan-100 text-cyan-800';
											case 'rfid_update': echo 'bg-teal-100 text-teal-800';
											
											// Profile actions
											case 'profile_update': echo 'bg-indigo-100 text-indigo-800';
											case 'password_update': echo 'bg-pink-100 text-pink-800';
											
											// Medical actions
											case 'medical_form_created': echo 'bg-blue-100 text-blue-800';
											case 'medical_record_updated': echo 'bg-amber-100 text-amber-800';
											case 'medical_history_created': echo 'bg-green-100 text-green-800';
											
											// Visitation actions
											case 'visitation_logged': echo 'bg-violet-100 text-violet-800';
											case 'visitation_archived': echo 'bg-orange-100 text-orange-800';
											case 'visitation_restored': echo 'bg-green-100 text-green-800';
											
											// System actions
											case 'rfid_verified': echo 'bg-cyan-100 text-cyan-800';
											case 'rfid_verification_failed': echo 'bg-red-100 text-red-800';
											
											default: echo 'bg-gray-100 text-gray-800';
										}
									?>">
										<?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['action']))) ?>
									</span>
								</td>
								<td class="px-4 py-3">
									<?php if ($row['user_name']): ?>
										<div class="text-sm font-medium text-slate-900">
											<?= htmlspecialchars(substr($row['user_name'], 0, 1) . '***' . substr($row['user_name'], -1)) ?>
											<?php if (isset($row['user_type']) && $row['user_type']): ?>
												<span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
													<?= htmlspecialchars(ucfirst($row['user_type'])) ?>
												</span>
											<?php endif; ?>
										</div>
									<?php else: ?>
										<span class="text-slate-400 italic">
											<?= isset($row['user_type']) && $row['user_type'] === 'system' ? 'System' : 'Unknown User' ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="px-4 py-3 text-sm text-slate-700">
									<?= htmlspecialchars($row['details'] ?: 'No description') ?>
									<?php if (isset($row['success']) && $row['success'] == 0): ?>
										<span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
											Failed
										</span>
									<?php elseif (isset($row['success']) && $row['success'] == 1): ?>
										<span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
											Success
										</span>
									<?php endif; ?>
									<?php if (!empty($row['error_message'])): ?>
										<div class="text-xs text-red-600 mt-1"><?= htmlspecialchars($row['error_message']) ?></div>
									<?php endif; ?>
								</td>
								<td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars($row['location'] ?: 'N/A') ?></td>
								<td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($row['created_at']))) ?></td>
								<td class="px-4 py-3 font-mono text-xs text-slate-400"><?= htmlspecialchars($row['ip_address'] ?: 'N/A') ?></td>
							</tr>
						<?php 
						$rowNumber++; // Increment row number for next iteration
						endforeach; ?>
					</tbody>
				</table>
			</div>
			
			<!-- Pagination Controls -->
			<?php if (isset($totalPages) && $totalPages > 1): ?>
			<div class="mt-6 flex items-center justify-between">
				<div class="text-sm text-slate-600">
					Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> results
				</div>
				<div class="flex items-center gap-2">
					<!-- Previous Button -->
					<?php if ($page > 1): ?>
						<a href="?filter=<?= urlencode($filter) ?>&page=<?= $page - 1 ?>" 
						   class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
							Previous
						</a>
					<?php else: ?>
						<span class="px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
							Previous
						</span>
					<?php endif; ?>
					
					<!-- Page Numbers -->
					<?php
					$startPage = max(1, $page - 2);
					$endPage = min($totalPages, $page + 2);
					
					if ($startPage > 1): ?>
						<a href="?filter=<?= urlencode($filter) ?>&page=1" 
						   class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">1</a>
						<?php if ($startPage > 2): ?>
							<span class="px-2 text-slate-400">...</span>
						<?php endif; ?>
					<?php endif; ?>
					
					<?php for ($i = $startPage; $i <= $endPage; $i++): ?>
						<?php if ($i == $page): ?>
							<span class="px-3 py-2 text-sm font-medium text-white bg-clinic-blue border border-clinic-blue rounded-lg">
								<?= $i ?>
							</span>
						<?php else: ?>
							<a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>" 
							   class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
								<?= $i ?>
							</a>
						<?php endif; ?>
					<?php endfor; ?>
					
					<?php if ($endPage < $totalPages): ?>
						<?php if ($endPage < $totalPages - 1): ?>
							<span class="px-2 text-slate-400">...</span>
						<?php endif; ?>
						<a href="?filter=<?= urlencode($filter) ?>&page=<?= $totalPages ?>" 
						   class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
							<?= $totalPages ?>
						</a>
					<?php endif; ?>
					
					<!-- Next Button -->
					<?php if ($page < $totalPages): ?>
						<a href="?filter=<?= urlencode($filter) ?>&page=<?= $page + 1 ?>" 
						   class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
							Next
						</a>
					<?php else: ?>
						<span class="px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
							Next
						</span>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>

<script>
function filterLogs(filterValue) {
    const url = new URL(window.location);
    if (filterValue === 'all') {
        url.searchParams.delete('filter');
    } else {
        url.searchParams.set('filter', filterValue);
    }
    window.location.href = url.toString();
}

// Popup notification functions
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'error' ? 'bg-red-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    notification.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg mb-2 transform transition-all duration-300 ease-in-out translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="font-medium">${message}</span>
            <button onclick="closeNotification(this)" class="ml-4 text-white hover:text-gray-200 transition-colors close-btn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-dismiss after 2 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            closeNotification(notification.querySelector('.close-btn'));
        }
    }, 2000);
}

function closeNotification(button) {
    const notification = button.closest('div');
    notification.classList.add('translate-x-full');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check for URL parameters to show notifications
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const type = urlParams.get('type') || 'info';
    
    if (message) {
        showNotification(decodeURIComponent(message), type);
        // Clean up URL
        const url = new URL(window.location);
        url.searchParams.delete('message');
        url.searchParams.delete('type');
        window.history.replaceState({}, '', url);
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>


