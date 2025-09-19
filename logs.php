<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();

$pdo = get_pdo();

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$whereClause = '';
$params = [];

if ($filter !== 'all') {
    $whereClause = 'WHERE l.action = ?';
    $params[] = $filter;
}

$sql = "SELECT l.id, l.timestamp as created_at, l.action, l.description as details, l.location, l.ip_address, u.name AS user_name, u.email AS user_email, u.rfid AS user_rfid 
        FROM activity_logs l 
        LEFT JOIN users_masked u ON u.id = l.user_id 
        {$whereClause}
        ORDER BY l.id DESC 
        LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique action types for dropdown
$actionStmt = $pdo->query('SELECT DISTINCT action FROM activity_logs ORDER BY action');
$actionTypes = $actionStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<?php $pageTitle = 'Activity Logs'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="grid gap-6">
		<div class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-xl p-6">
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
				<div>
					<div class="flex items-center gap-4 mb-2">
						<a href="dashboard.php" class="flex items-center gap-2 text-clinic-blue hover:text-clinic-tea transition-colors">
							<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
							</svg>
							Back to Dashboard
						</a>
					</div>
					<h1 class="text-2xl font-semibold mb-4 sm:mb-0">Activity Logs</h1>
				</div>
				
				<!-- Filter Dropdown and Archives Button -->
				<div class="flex items-center gap-3">
					<label for="filterSelect" class="text-sm font-medium text-slate-700">Filter by type:</label>
					<select id="filterSelect" onchange="filterLogs(this.value)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
						<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Activities</option>
						<?php foreach ($actionTypes as $action): ?>
							<option value="<?= htmlspecialchars($action) ?>" <?= $filter === $action ? 'selected' : '' ?>>
								<?= htmlspecialchars(ucwords(str_replace('_', ' ', $action))) ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<!-- Archives Button -->
					<a href="archive_management.php" class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
						<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
						</svg>
						View Archives
					</a>
				</div>
			</div>
			
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-200 text-sm">
					<thead class="bg-slate-50">
						<tr>
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
						<?php foreach ($logs as $row): ?>
							<tr class="hover:bg-slate-50">
								<td class="px-4 py-3 font-mono text-xs text-slate-500">#<?= htmlspecialchars((string)$row['id']) ?></td>
								<td class="px-4 py-3">
									<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
										switch($row['action']) {
											// Authentication actions
											case 'login': echo 'bg-green-100 text-green-800';
											case 'logout': echo 'bg-blue-100 text-blue-800';
											case 'login_failed': echo 'bg-red-100 text-red-800';
											case 'register': echo 'bg-green-100 text-green-800';
											case 'unauthorized_access': echo 'bg-red-100 text-red-800';
											
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
										</div>
										<div class="text-xs text-slate-500">
											RFID: <?= htmlspecialchars(isset($row['user_rfid']) && $row['user_rfid'] ? '****' . substr($row['user_rfid'], -4) : 'N/A') ?>
										</div>
									<?php else: ?>
										<span class="text-slate-400 italic">Unknown User</span>
									<?php endif; ?>
								</td>
								<td class="px-4 py-3 text-sm text-slate-700"><?= htmlspecialchars($row['details'] ?: 'No description') ?></td>
								<td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars($row['location'] ?: 'N/A') ?></td>
								<td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($row['created_at']))) ?></td>
								<td class="px-4 py-3 font-mono text-xs text-slate-400"><?= htmlspecialchars($row['ip_address'] ?: 'N/A') ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
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

<?php include __DIR__ . '/partials/footer.php'; ?>


