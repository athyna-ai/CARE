<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get parameters from URL
$level = sanitize_string($_GET['level'] ?? '');
$type = sanitize_string($_GET['type'] ?? 'students');
$search = sanitize_string($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query based on level and type
$whereConditions = [];
$params = [];

if ($level) {
    $whereConditions[] = "level = ?";
    $params[] = $level;
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR rfid LIKE ? OR course LIKE ? OR section LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM students $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get students
$sql = "SELECT * FROM students $whereClause ORDER BY name ASC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = "$level Students" . ($level ? " - $level" : '');
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="min-h-[calc(100vh-5rem)] p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800"><?= htmlspecialchars($level ?: 'All') ?> Students</h1>
                    <p class="text-slate-600 mt-1">Manage student records and medical information</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="student_form.php" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Student
                    </a>
                    <a href="dashboard.php" class="px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="level" value="<?= htmlspecialchars($level) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    
                    <div class="flex-1">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name, RFID, course, or section..." 
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                            Search
                        </button>
                        <a href="school_listing.php?level=<?= urlencode($level) ?>&type=<?= urlencode($type) ?>" 
                           class="px-6 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600 transition-colors">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <?php if (empty($students)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl text-slate-300 mb-4">👥</div>
                    <h3 class="text-xl font-semibold text-slate-600 mb-2">No students found</h3>
                    <p class="text-slate-500 mb-6">
                        <?php if ($search): ?>
                            No students match your search criteria.
                        <?php else: ?>
                            No students registered yet.
                        <?php endif; ?>
                    </p>
                    <a href="student_form.php" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add First Student
                    </a>
                </div>
            <?php else: ?>
                <!-- Table Header -->
                <div class="bg-slate-50 px-6 py-3 border-b border-slate-200">
                    <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                        <div class="col-span-3">Name</div>
                        <div class="col-span-2">Level</div>
                        <div class="col-span-2">Course/Section</div>
                        <div class="col-span-2">RFID</div>
                        <div class="col-span-2">Age</div>
                        <div class="col-span-1">Actions</div>
                    </div>
                </div>

                <!-- Table Body -->
                <div class="divide-y divide-slate-200">
                    <?php foreach ($students as $student): ?>
                        <div class="px-6 py-4 hover:bg-slate-50 transition-colors">
                            <div class="grid grid-cols-12 gap-4 items-center">
                                <div class="col-span-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars($student['name'] ?? '') ?></div>
                                    <div class="text-sm text-slate-500"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></div>
                                </div>
                                <div class="col-span-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($student['level'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-sm text-slate-900"><?= htmlspecialchars($student['course'] ?: 'N/A') ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($student['section'] ?: 'N/A') ?></div>
                                </div>
                                <div class="col-span-2">
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm text-slate-900"><?= $student['age'] ?> years</span>
                                </div>
                                <div class="col-span-1">
                                    <div class="flex items-center space-x-2">
                                        <a href="patient_view.php?id=<?= $student['id'] ?>&type=student" 
                                           class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
                                           title="View Patient">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <a href="student_form.php?id=<?= $student['id'] ?>" 
                                           class="p-2 text-slate-600 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors" 
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-slate-50 px-6 py-3 border-t border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-600">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> results
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?level=<?= urlencode($level) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" 
                                       class="px-3 py-1 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?level=<?= urlencode($level) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" 
                                       class="px-3 py-1 <?= $i === $page ? 'bg-sky-600 text-white' : 'bg-white border border-slate-300 hover:bg-slate-50' ?> rounded-lg transition-colors">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?level=<?= urlencode($level) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" 
                                       class="px-3 py-1 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const type = urlParams.get('type') || 'info';
    
    if (message) {
        showNotification(decodeURIComponent(message), type);
        const url = new URL(window.location);
        url.searchParams.delete('message');
        url.searchParams.delete('type');
        window.history.replaceState({}, '', url);
    }
});

// Popup notification functions
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `${colors[type] || colors.info} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, 5000);
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
