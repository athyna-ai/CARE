<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get parameters
$type = $_GET['type'] ?? 'students'; // students or faculty
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'archived_at'; // name, archived_at, level/department
$order = $_GET['order'] ?? 'desc'; // asc or desc

// Validate type
if (!in_array($type, ['students', 'faculty'])) {
    $type = 'students';
}

// Validate sort
$validSorts = ['name', 'archived_at', 'level', 'department'];
if (!in_array($sort, $validSorts)) {
    $sort = 'archived_at';
}

// Validate order
if (!in_array($order, ['asc', 'desc'])) {
    $order = 'desc';
}

// Build query based on type
if ($type === 'students') {
    $whereClause = 'WHERE 1=1';
    $params = [];
    
    // Add search if provided
    if ($search !== '') {
        $whereClause .= ' AND (name LIKE ? OR rfid LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    // Add sorting
    $orderClause = "ORDER BY {$sort} {$order}";
    if ($sort === 'level') {
        $orderClause = "ORDER BY level {$order}, name ASC";
    }
    
    $sql = "SELECT id, original_id, name, level, year_grade, section, strand, course, archived_at 
            FROM students_archive 
            {$whereClause} 
            {$orderClause}";
} else {
    $whereClause = 'WHERE 1=1';
    $params = [];
    
    // Add search if provided
    if ($search !== '') {
        $whereClause .= ' AND (name LIKE ? OR rfid LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    // Add sorting
    $orderClause = "ORDER BY {$sort} {$order}";
    if ($sort === 'department') {
        $orderClause = "ORDER BY department {$order}, name ASC";
    }
    
    $sql = "SELECT id, original_id, name, department, sr, archived_at 
            FROM faculty_archive 
            {$whereClause} 
            {$orderClause}";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = "Archive Management";
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<div class="h-[calc(100vh-5rem)] w-full p-4 flex flex-col">
    <div class="w-full flex flex-col h-full">
        <!-- Header -->
        <div class="mb-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="../admin/dashboard.php" class="inline-flex items-center text-slate-600 hover:text-slate-800 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">
                        Archive 
                        <span class="text-sky-600">Management</span>
                    </h1>
                    <p class="text-slate-600">
                        <?= count($records) ?> archived <?= count($records) === 1 ? 'record' : 'records' ?> found
                    </p>
                </div>
                
                <!-- Type Toggle -->
                <div class="flex bg-slate-100 rounded-lg p-1 mt-4 sm:mt-0">
                    <a href="?type=students&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $type === 'students' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        Students
                    </a>
                    <a href="?type=faculty&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $type === 'faculty' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        Faculty
                    </a>
                </div>
            </div>
            
            <!-- Search and Sort Controls -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <!-- Search Bar -->
                <div class="flex-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               id="searchInput"
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Search archived records..." 
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-slate-800"
                               style="background-color: white !important; color: #1e293b !important;"
                               autofocus>
                    </div>
                </div>
                
                <!-- Sort Dropdown -->
                <div class="flex gap-2">
                    <select id="sortSelect" class="px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort by Name</option>
                        <option value="archived_at" <?= $sort === 'archived_at' ? 'selected' : '' ?>>Sort by Archived Date</option>
                        <?php if ($type === 'students'): ?>
                            <option value="level" <?= $sort === 'level' ? 'selected' : '' ?>>Sort by Level</option>
                        <?php else: ?>
                            <option value="department" <?= $sort === 'department' ? 'selected' : '' ?>>Sort by Department</option>
                        <?php endif; ?>
                    </select>
                    
                    <button id="orderToggle" class="px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white hover:bg-slate-50">
                        <?= $order === 'asc' ? '↑' : '↓' ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Records Table -->
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-2xl shadow-xl overflow-hidden flex-1 flex flex-col min-h-0 max-h-[calc(100vh-20rem)]">
            <?php if (empty($records)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📦</div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">No archived records found</h3>
                    <p class="text-slate-500">
                        <?= $search ? 'Try adjusting your search terms.' : 'No records have been archived yet.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                                <?php if ($type === 'students'): ?>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Grade/Year</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Section/Strand/Course</th>
                                <?php else: ?>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Senior</th>
                                <?php endif; ?>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Archived Date</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php foreach ($records as $record): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500">
                                        #<?= htmlspecialchars((string)$record['original_id']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900">
                                            <?= htmlspecialchars($record['name']) ?>
                                        </div>
                                    </td>
                                    <?php if ($type === 'students'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?= htmlspecialchars($record['level']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?= htmlspecialchars($record['year_grade'] ?: 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?= htmlspecialchars($record['section'] ?: $record['strand'] ?: $record['course'] ?: 'N/A') ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?= htmlspecialchars($record['department'] ?: 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?php if ($record['sr']): ?>
                                                <span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded">Sr.</span>
                                            <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($record['archived_at']))) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <button onclick="restoreRecord(<?= $record['id'] ?>, '<?= htmlspecialchars($record['name']) ?>', '<?= $type ?>')" 
                                                    class="text-green-600 hover:text-green-800 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-search functionality
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateURL();
    }, 500); // 500ms delay
});

// Sort functionality
document.getElementById('sortSelect').addEventListener('change', function() {
    updateURL();
});

// Order toggle
document.getElementById('orderToggle').addEventListener('click', function() {
    const currentOrder = new URLSearchParams(window.location.search).get('order') || 'desc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    const url = new URL(window.location);
    url.searchParams.set('order', newOrder);
    window.location.href = url.toString();
});

function updateURL() {
    const search = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortSelect').value;
    const order = new URLSearchParams(window.location.search).get('order') || 'desc';
    const type = new URLSearchParams(window.location.search).get('type') || 'students';
    
    const url = new URL(window.location);
    
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    
    url.searchParams.set('sort', sort);
    url.searchParams.set('order', order);
    url.searchParams.set('type', type);
    
    window.location.href = url.toString();
}

// Restore record function
function restoreRecord(recordId, recordName, recordType) {
    if (confirm(`Are you sure you want to restore "${recordName}"? This will move the record back to the active list.`)) {
        // Create a form to submit the restore request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'restore_record.php';
        
        const recordIdInput = document.createElement('input');
        recordIdInput.type = 'hidden';
        recordIdInput.name = 'record_id';
        recordIdInput.value = recordId;
        
        const recordTypeInput = document.createElement('input');
        recordTypeInput.type = 'hidden';
        recordTypeInput.name = 'record_type';
        recordTypeInput.value = recordType;
        
        form.appendChild(recordIdInput);
        form.appendChild(recordTypeInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Focus search input on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').focus();
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
