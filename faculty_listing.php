<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get parameters from URL
$search = sanitize_string($_GET['search'] ?? '');
$sortBy = sanitize_string($_GET['sort'] ?? 'name');
$sortOrder = sanitize_string($_GET['order'] ?? 'asc');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Validate sort parameters
$allowedSortFields = ['name', 'department', 'rfid', 'age', 'created_at'];
$sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'name';
$sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

// Build query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(name LIKE ? OR rfid LIKE ? OR department LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM faculty $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get faculty
$sql = "SELECT * FROM faculty $whereClause ORDER BY $sortBy $sortOrder LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$faculty = $stmt->fetchAll();

$pageTitle = 'Faculty Members';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="min-h-[calc(100vh-5rem)] flex items-start md:items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-6xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
        <!-- Header -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-comfortaa font-bold text-clinic-dark">Faculty Members</h1>
                    <p class="text-clinic-dark/60 mt-2 font-poppins text-lg">Manage faculty records and medical information</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="faculty_form.php" class="group px-6 py-3 bg-clinic-tea/20 border border-clinic-tea/30 text-clinic-blue rounded-2xl hover:bg-clinic-tea/30 hover:border-clinic-tea/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-tea/30 flex items-center justify-center group-hover:bg-clinic-tea/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        Add Faculty
                    </a>
                    <a href="dashboard.php" class="group px-6 py-3 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-2xl hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-dark/20 flex items-center justify-center group-hover:bg-clinic-dark/30 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </div>
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-xl border border-clinic-tea/20 p-6">
                <form method="GET" class="space-y-4">
                    <!-- Search Row -->
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" id="liveSearch" 
                                   placeholder="Search by name, RFID, or department..." 
                                   class="w-full px-6 py-4 border border-clinic-tea/30 rounded-2xl focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark placeholder-clinic-dark/50 font-poppins"
                                   style="background-color: #fbfcee !important; color: #343b1b !important;">
                        </div>
                        <div class="flex gap-3">
                            <button type="button" id="clearSearch" 
                                    class="group px-6 py-4 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-2xl hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-2 font-poppins font-medium">
                                <div class="w-6 h-6 rounded-lg bg-clinic-dark/20 flex items-center justify-center group-hover:bg-clinic-dark/30 transition-colors duration-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                Clear
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sorting Row -->
                    <div class="flex flex-col md:flex-row gap-4 items-center">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-poppins font-medium text-clinic-dark">Sort by:</label>
                            <select name="sort" class="px-4 py-2 border border-clinic-tea/30 rounded-xl focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins">
                                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                                <option value="department" <?= $sortBy === 'department' ? 'selected' : '' ?>>Department</option>
                                <option value="rfid" <?= $sortBy === 'rfid' ? 'selected' : '' ?>>RFID</option>
                                <option value="age" <?= $sortBy === 'age' ? 'selected' : '' ?>>Age</option>
                                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Added</option>
                            </select>
                            <select name="order" class="px-4 py-2 border border-clinic-tea/30 rounded-xl focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins">
                                <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Ascending</option>
                                <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Descending</option>
                            </select>
                        </div>
                        <div class="text-sm text-clinic-dark/60 font-poppins">
                            Showing <?= $totalRecords ?> faculty members
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex-1 flex flex-col">
            <?php if (empty($faculty)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl text-slate-300 mb-4">👩‍🏫</div>
                    <h3 class="text-xl font-semibold text-slate-600 mb-2">No faculty found</h3>
                    <p class="text-slate-500 mb-6">
                        <?php if ($search): ?>
                            No faculty match your search criteria.
                        <?php else: ?>
                            No faculty registered yet.
                        <?php endif; ?>
                    </p>
                    <a href="faculty_form.php" class="inline-flex items-center px-4 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add First Faculty
                    </a>
                </div>
            <?php else: ?>
                <!-- Table Header -->
                <div class="bg-slate-50 px-6 py-3 border-b border-slate-200">
                    <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                        <div class="col-span-2">ID/RFID</div>
                        <div class="col-span-4">Name</div>
                        <div class="col-span-4">Department</div>
                        <div class="col-span-2">Actions</div>
                    </div>
                </div>

                <!-- Table Body -->
                <div id="facultyTable" class="divide-y divide-slate-200 flex-1 overflow-y-auto">
                    <?php foreach ($faculty as $member): ?>
                        <div class="px-6 py-4 hover:bg-slate-50 transition-colors faculty-row" 
                             data-name="<?= htmlspecialchars(strtolower($member['name'] ?? '')) ?>"
                             data-rfid="<?= htmlspecialchars(strtolower($member['rfid'] ?? '')) ?>"
                             data-department="<?= htmlspecialchars(strtolower($member['department'] ?? '')) ?>">
                            <div class="grid grid-cols-12 gap-4 items-center">
                                <div class="col-span-2">
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($member['rfid']) ?></code>
                                </div>
                                <div class="col-span-4">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars($member['name']) ?></div>
                                    <div class="text-sm text-slate-500"><?= htmlspecialchars($member['gender']) ?></div>
                                </div>
                                <div class="col-span-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <?= htmlspecialchars($member['department']) ?>
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <div class="flex items-center space-x-2">
                                        <a href="patient_view.php?id=<?= $member['id'] ?>&type=faculty" 
                                           class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <a href="#" onclick="archiveFaculty(<?= $member['id'] ?>)" 
                                           class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
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
                                    <a href="?search=<?= urlencode($search) ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>&page=<?= $page - 1 ?>" 
                                       class="px-3 py-1 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?search=<?= urlencode($search) ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>&page=<?= $i ?>" 
                                       class="px-3 py-1 <?= $i === $page ? 'bg-sky-600 text-white' : 'bg-white border border-slate-300 hover:bg-slate-50' ?> rounded-lg transition-colors">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?search=<?= urlencode($search) ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>&page=<?= $page + 1 ?>" 
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
    
    // Auto remove after 2 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, 2000);
}

// Archive faculty function
function archiveFaculty(facultyId) {
    if (confirm('Are you sure you want to archive this faculty member? This action can be undone from the archive management page.')) {
        // Create a form to submit the archive request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'archive_faculty.php';
        
        const facultyIdInput = document.createElement('input');
        facultyIdInput.type = 'hidden';
        facultyIdInput.name = 'faculty_id';
        facultyIdInput.value = facultyId;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= csrf_token() ?>';
        
        form.appendChild(facultyIdInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Live search functionality
document.addEventListener('DOMContentLoaded', function() {
    const liveSearch = document.getElementById('liveSearch');
    const clearSearch = document.getElementById('clearSearch');
    const facultyRows = document.querySelectorAll('.faculty-row');
    
    // Auto-focus search field
    if (liveSearch) {
        liveSearch.focus();
    }
    
    if (liveSearch && clearSearch) {
        // Live search as user types
        liveSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterFaculty(searchTerm);
        });
        
        // Clear search
        clearSearch.addEventListener('click', function() {
            liveSearch.value = '';
            filterFaculty('');
        });
    }
    
    function filterFaculty(searchTerm) {
        let visibleCount = 0;
        
        facultyRows.forEach(function(row) {
            const name = row.getAttribute('data-name') || '';
            const rfid = row.getAttribute('data-rfid') || '';
            const department = row.getAttribute('data-department') || '';
            
            const searchableText = `${name} ${rfid} ${department}`;
            
            if (searchTerm === '' || searchableText.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide "No faculty found" message
        const noFacultyMessage = document.querySelector('.text-center.py-12');
        if (noFacultyMessage) {
            if (visibleCount === 0 && searchTerm !== '') {
                noFacultyMessage.style.display = 'block';
                noFacultyMessage.innerHTML = `
                    <div class="text-6xl text-slate-300 mb-4">🔍</div>
                    <h3 class="text-xl font-semibold text-slate-600 mb-2">No faculty found</h3>
                    <p class="text-slate-500 mb-6">No faculty match your search criteria.</p>
                `;
            } else if (visibleCount === 0) {
                noFacultyMessage.style.display = 'block';
            } else {
                noFacultyMessage.style.display = 'none';
            }
        }
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
