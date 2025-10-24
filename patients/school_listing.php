<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get parameters from URL
$level = sanitize_string($_GET['level'] ?? '');
$type = sanitize_string($_GET['type'] ?? 'students');

// Redirect to dashboard if no level is specified (removes "All Students" page)
if (empty($level)) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$search = sanitize_string($_GET['search'] ?? '');
$sortBy = sanitize_string($_GET['sort'] ?? 'name');
$sortOrder = sanitize_string($_GET['order'] ?? 'asc');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$statusFilter = sanitize_string($_GET['status_filter'] ?? '');
$courseFilter = sanitize_string($_GET['course_filter'] ?? '');
$strandFilter = sanitize_string($_GET['strand_filter'] ?? '');
$sectionFilter = sanitize_string($_GET['section_filter'] ?? '');
$yearGradeFilter = sanitize_string($_GET['year_grade_filter'] ?? '');
$ageMin = (int)($_GET['age_min'] ?? 0);
$ageMax = (int)($_GET['age_max'] ?? 0);

// Validate sort parameters
$allowedSortFields = ['name', 'level', 'course', 'section', 'rfid', 'age', 'created_at'];
$sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'name';
$sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

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

// Add filter conditions
if ($statusFilter) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($courseFilter) {
    $whereConditions[] = "course = ?";
    $params[] = $courseFilter;
}

if ($strandFilter) {
    $whereConditions[] = "strand = ?";
    $params[] = $strandFilter;
}

if ($sectionFilter) {
    $whereConditions[] = "section = ?";
    $params[] = $sectionFilter;
}

if ($yearGradeFilter) {
    $whereConditions[] = "year_grade = ?";
    $params[] = $yearGradeFilter;
}

if ($ageMin > 0) {
    $whereConditions[] = "age >= ?";
    $params[] = $ageMin;
}

if ($ageMax > 0) {
    $whereConditions[] = "age <= ?";
    $params[] = $ageMax;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM students $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get students
$sql = "SELECT * FROM students $whereClause ORDER BY $sortBy $sortOrder LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = "$level Students" . ($level ? " - $level" : '');
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<!-- Popup Notification Container -->
<!-- Notification container now handled globally in header.php -->

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="confirmationModalContent">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900" id="confirmationTitle">Confirm Action</h3>
                </div>
                <button onclick="closeConfirmationModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="mb-6">
                <p class="text-gray-600" id="confirmationMessage">Are you sure you want to perform this action?</p>
                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-yellow-800" id="confirmationWarning">This action can be undone from the archive management page.</p>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex gap-3 justify-end">
                <button onclick="closeConfirmationModal()" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors font-medium">
                    Cancel
                </button>
                <button onclick="confirmArchiveAction()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium" id="confirmButton">
                    Archive Student
                </button>
            </div>
        </div>
    </div>
</div>

<div class="h-[calc(100vh-5rem)] flex flex-col p-4">
    <div class="w-full bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 flex flex-col h-full">
        <!-- Header -->
        <div class="mb-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-comfortaa font-bold text-clinic-dark"><?= htmlspecialchars($level ?: 'All') ?> Students</h1>
                    <p class="text-clinic-dark/60 mt-2 font-poppins text-base md:text-lg">Manage student records and medical information</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <a href="../patients/student_form.php<?= $level ? '?level=' . urlencode($level) : '' ?>" class="group px-6 py-3 bg-clinic-tea/20 border border-clinic-tea/30 text-clinic-blue rounded-2xl hover:bg-clinic-tea/30 hover:border-clinic-tea/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-tea/30 flex items-center justify-center group-hover:bg-clinic-tea/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        </div>
                        Add <?= $level ? htmlspecialchars($level) : '' ?> Student
                    </a>
                    <a href="../admin/dashboard.php" class="group px-6 py-3 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-2xl hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
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
            <form method="GET" class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 p-4 space-y-4">
                <!-- Preserve level parameter -->
                <input type="hidden" name="level" value="<?= htmlspecialchars($level) ?>">
                
                <!-- Search Row -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" id="liveSearch" 
                           placeholder="Search by name, RFID, course, or section..." 
                               class="w-full px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark placeholder-clinic-dark/50 font-poppins text-sm"
                               style="background-color: #fbfcee !important; color: #343b1b !important;">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="clearSearch" 
                                class="px-3 py-2 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-lg hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-1 font-poppins font-medium text-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear
                        </button>
                    </div>
                </div>
                
                <!-- Sorting and Filters Row -->
                <div class="flex items-center gap-3 overflow-x-auto whitespace-nowrap">
                    <!-- Sorting -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <label class="text-xs font-poppins font-medium text-clinic-dark whitespace-nowrap">Sort by:</label>
                        <select name="sort" class="px-2 py-1.5 border border-clinic-tea/30 rounded-md focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-xs min-w-[100px]">
                            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                            <option value="level" <?= $sortBy === 'level' ? 'selected' : '' ?>>Level</option>
                            <option value="course" <?= $sortBy === 'course' ? 'selected' : '' ?>>Course</option>
                            <option value="section" <?= $sortBy === 'section' ? 'selected' : '' ?>>Section</option>
                            <option value="rfid" <?= $sortBy === 'rfid' ? 'selected' : '' ?>>RFID</option>
                            <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Added</option>
                        </select>
                        <select name="order" class="px-2 py-1.5 border border-clinic-tea/30 rounded-md focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-xs min-w-[100px]">
                            <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Ascending</option>
                            <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                    
                    <!-- Filters -->
                    <span class="text-sm font-poppins font-medium text-clinic-dark flex-shrink-0">Filters:</span>
                    
                    <!-- Status Filter -->
                    <select name="status_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Status</option>
                        <option value="Active" <?= ($_GET['status_filter'] ?? '') === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($_GET['status_filter'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="Graduated" <?= ($_GET['status_filter'] ?? '') === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
                        <option value="Transferred" <?= ($_GET['status_filter'] ?? '') === 'Transferred' ? 'selected' : '' ?>>Transferred</option>
                    </select>
                    
                    <!-- Course Filter (for College students) -->
                    <?php if (!$level || $level === 'College'): ?>
                    <select name="course_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Courses</option>
                        <option value="BS in Information Technology" <?= ($_GET['course_filter'] ?? '') === 'BS in Information Technology' ? 'selected' : '' ?>>BSIT</option>
                        <option value="BS in Education" <?= ($_GET['course_filter'] ?? '') === 'BS in Education' ? 'selected' : '' ?>>BSEd</option>
                        <option value="BS in Criminology" <?= ($_GET['course_filter'] ?? '') === 'BS in Criminology' ? 'selected' : '' ?>>BSCrim</option>
                        <option value="BS in Hospitality Management" <?= ($_GET['course_filter'] ?? '') === 'BS in Hospitality Management' ? 'selected' : '' ?>>BSHM</option>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Strand Filter (for Senior High) -->
                    <?php if (!$level || $level === 'Senior High School'): ?>
                    <select name="strand_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Strands</option>
                        <option value="STEM" <?= ($_GET['strand_filter'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM</option>
                        <option value="ABM" <?= ($_GET['strand_filter'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM</option>
                        <option value="HUMSS" <?= ($_GET['strand_filter'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                        <option value="TVL" <?= ($_GET['strand_filter'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL</option>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Section Filter (for Pre-school, Elementary, and High School) -->
                    <?php if (!$level || $level === 'Pre-school' || $level === 'Elementary' || $level === 'High School'): ?>
                    <select name="section_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Sections</option>
                        <?php
                        // Get unique sections for the current level
                        $sectionsQuery = "SELECT DISTINCT section FROM students WHERE 1=1";
                        $sectionsParams = [];
                        if ($level) {
                            $sectionsQuery .= " AND level = ?";
                            $sectionsParams[] = $level;
                        }
                        $sectionsQuery .= " AND section IS NOT NULL AND section != '' ORDER BY section ASC";
                        $sectionsStmt = $pdo->prepare($sectionsQuery);
                        $sectionsStmt->execute($sectionsParams);
                        $sections = $sectionsStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($sections as $section):
                        ?>
                            <option value="<?= htmlspecialchars($section) ?>" <?= ($_GET['section_filter'] ?? '') === $section ? 'selected' : '' ?>>
                                <?= htmlspecialchars($section) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Year/Grade Level Filter -->
                    <?php if ($level === 'College'): ?>
                    <!-- Year Filter for College -->
                    <select name="year_grade_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Years</option>
                        <option value="1st Year" <?= ($_GET['year_grade_filter'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                        <option value="2nd Year" <?= ($_GET['year_grade_filter'] ?? '') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3rd Year" <?= ($_GET['year_grade_filter'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                        <option value="4th Year" <?= ($_GET['year_grade_filter'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                        <option value="5th Year" <?= ($_GET['year_grade_filter'] ?? '') === '5th Year' ? 'selected' : '' ?>>5th Year</option>
                    </select>
                    <?php elseif ($level === 'Elementary'): ?>
                    <!-- Grade Filter for Elementary -->
                    <select name="year_grade_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Grades</option>
                        <option value="Grade 1" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 1' ? 'selected' : '' ?>>Grade 1</option>
                        <option value="Grade 2" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 2' ? 'selected' : '' ?>>Grade 2</option>
                        <option value="Grade 3" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 3' ? 'selected' : '' ?>>Grade 3</option>
                        <option value="Grade 4" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 4' ? 'selected' : '' ?>>Grade 4</option>
                        <option value="Grade 5" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 5' ? 'selected' : '' ?>>Grade 5</option>
                        <option value="Grade 6" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 6' ? 'selected' : '' ?>>Grade 6</option>
                    </select>
                    <?php elseif ($level === 'High School'): ?>
                    <!-- Grade Filter for High School -->
                    <select name="year_grade_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Grades</option>
                        <option value="Grade 7" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                        <option value="Grade 8" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                        <option value="Grade 9" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                        <option value="Grade 10" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                    </select>
                    <?php elseif ($level === 'Senior High School'): ?>
                    <!-- Grade Filter for Senior High School -->
                    <select name="year_grade_filter" class="px-3 py-2 border border-clinic-tea/30 rounded-lg focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue bg-clinic-ivory/40 text-clinic-dark font-poppins text-sm flex-shrink-0">
                        <option value="">All Grades</option>
                        <option value="Grade 11" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
                        <option value="Grade 12" <?= ($_GET['year_grade_filter'] ?? '') === 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
                    </select>
                    <?php endif; ?>
                    
                    <!-- Student Count -->
                    <div class="text-xs text-clinic-dark/60 font-poppins bg-clinic-ivory/20 px-2 py-1 rounded-md flex-shrink-0">
                        Showing <?= $totalRecords ?> students
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="flex gap-2 ml-auto flex-shrink-0">
                        <button type="submit" class="px-4 py-2 bg-clinic-blue hover:bg-clinic-blue/80 text-white rounded-lg transition-colors font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                        <button type="button" onclick="clearAllFilters()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear All
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex-1 flex flex-col min-h-0 max-h-[calc(100vh-20rem)]">
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
                    <a href="../patients/student_form.php" class="inline-flex items-center px-4 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add First Student
                    </a>
                </div>
            <?php else: ?>
                <!-- Table Header -->
                <div class="bg-slate-50 px-6 py-3 border-b border-slate-200">
                    <?php if ($level === 'Pre-school'): ?>
                        <!-- Pre-school: No., Name, Level, Section, Actions, Status, RFID -->
                        <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                            <div class="col-span-1">No.</div>
                            <div class="col-span-3">Name</div>
                            <div class="col-span-2">Level</div>
                            <div class="col-span-2">Section</div>
                            <div class="col-span-2">Actions</div>
                            <div class="col-span-1">Status</div>
                            <div class="col-span-1">RFID</div>
                        </div>
                    <?php elseif ($level === 'Elementary'): ?>
                        <!-- Elementary: No., Name, Level, Section, Actions, Status, RFID -->
                        <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                            <div class="col-span-1">No.</div>
                            <div class="col-span-3">Name</div>
                            <div class="col-span-2">Level</div>
                            <div class="col-span-2">Section</div>
                            <div class="col-span-2">Actions</div>
                            <div class="col-span-1">Status</div>
                            <div class="col-span-1">RFID</div>
                        </div>
                    <?php elseif ($level === 'High School'): ?>
                        <!-- High School: No., Name, Level, Section, Actions, Status, RFID -->
                        <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                            <div class="col-span-1">No.</div>
                            <div class="col-span-3">Name</div>
                            <div class="col-span-2">Level</div>
                            <div class="col-span-2">Section</div>
                            <div class="col-span-2">Actions</div>
                            <div class="col-span-1">Status</div>
                            <div class="col-span-1">RFID</div>
                        </div>
                    <?php elseif ($level === 'Senior High School'): ?>
                        <!-- Senior High: No., Name, Level, Strand, Actions, Status, RFID -->
                        <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                            <div class="col-span-1">No.</div>
                            <div class="col-span-3">Name</div>
                            <div class="col-span-2">Level</div>
                            <div class="col-span-2">Strand</div>
                            <div class="col-span-2">Actions</div>
                            <div class="col-span-1">Status</div>
                            <div class="col-span-1">RFID</div>
                        </div>
                    <?php elseif ($level === 'College'): ?>
                        <!-- College: No., Name, Year, Course, Actions, Status, RFID -->
                        <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                            <div class="col-span-1">No.</div>
                            <div class="col-span-3">Name</div>
                            <div class="col-span-2">Year</div>
                            <div class="col-span-2">Course</div>
                            <div class="col-span-2">Actions</div>
                            <div class="col-span-1">Status</div>
                            <div class="col-span-1">RFID</div>
                        </div>
                    <?php else: ?>
                        <!-- All levels: Default table -->
                    <div class="grid grid-cols-12 gap-4 text-sm font-medium text-slate-600">
                        <div class="col-span-1">No.</div>
                        <div class="col-span-2">Name</div>
                        <div class="col-span-2">Level</div>
                        <div class="col-span-2">Course/Section</div>
                        <div class="col-span-2">Age</div>
                        <div class="col-span-1">Actions</div>
                        <div class="col-span-1">Status</div>
                        <div class="col-span-1">RFID</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Table Body -->
                <div id="studentsTable" class="student-table-responsive divide-y divide-slate-200 flex-1 overflow-y-auto">
                    <?php 
                    $rowNumber = $offset + 1; // Start numbering from the correct offset for pagination
                    foreach ($students as $student): ?>
                        <div class="px-6 py-4 hover:bg-slate-50 transition-colors student-row" 
                             data-name="<?= htmlspecialchars(strtolower($student['name'] ?? '')) ?>"
                             data-rfid="<?= htmlspecialchars(strtolower($student['rfid'] ?? '')) ?>"
                             data-level="<?= htmlspecialchars(strtolower($student['level'] ?? '')) ?>"
                             data-section="<?= htmlspecialchars(strtolower($student['section'] ?? '')) ?>"
                             data-course="<?= htmlspecialchars(strtolower($student['course'] ?? '')) ?>"
                             data-strand="<?= htmlspecialchars(strtolower($student['strand'] ?? '')) ?>"
                             data-year="<?= htmlspecialchars(strtolower($student['year_grade'] ?? '')) ?>"
                             data-status="<?= htmlspecialchars(strtolower($student['status'] ?? 'active')) ?>">
                            <?php if ($level === 'Pre-school'): ?>
                                <!-- Pre-school: No., Name, Level, Section, Actions, Status, RFID -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-1">
                                        <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                    </div>
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
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['section'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                               class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                               class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($student['status'] ?? 'Active') {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                    </div>
                                </div>
                            <?php elseif ($level === 'Elementary'): ?>
                                <!-- Elementary: No., Name, Level, Section, Actions, Status, RFID -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-1">
                                        <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                    </div>
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
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['section'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                               class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                               class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($student['status'] ?? 'Active') {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                    </div>
                                </div>
                            <?php elseif ($level === 'High School'): ?>
                                <!-- High School: No., Name, Level, Section, Actions, Status, RFID -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-1">
                                        <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                    </div>
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
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['section'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                               class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                               class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($student['status'] ?? 'Active') {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                    </div>
                                </div>
                            <?php elseif ($level === 'Senior High School'): ?>
                                <!-- Senior High: No., Name, Level, Strand, Actions, Status, RFID -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-1">
                                        <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                    </div>
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
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['strand'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                               class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                               class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($student['status'] ?? 'Active') {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                    </div>
                                </div>
                            <?php elseif ($level === 'College'): ?>
                                <!-- College: No., Name, Year, Course, Actions, Status, RFID -->
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-1">
                                        <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                    </div>
                                    <div class="col-span-3">
                                        <div class="font-medium text-slate-900"><?= htmlspecialchars($student['name'] ?? '') ?></div>
                                        <div class="text-sm text-slate-500"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['year_grade'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-sm text-slate-900"><?= htmlspecialchars($student['course'] ?: 'N/A') ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="flex items-center space-x-2">
                                            <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                               class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                               class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            <?php
                                            switch($student['status'] ?? 'Active') {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                        ">
                                            <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- All levels: Default table -->
                            <div class="grid grid-cols-12 gap-4 items-center">
                                <div class="col-span-1">
                                    <span class="text-sm font-mono text-slate-500 font-semibold"><?= $rowNumber ?></span>
                                </div>
                                <div class="col-span-2">
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
                                    <span class="text-sm text-slate-900"><?= $student['age'] ?> years</span>
                                </div>
                                <div class="col-span-1">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= generate_patient_url($student['id'], 'student') ?>" 
                                           class="p-2 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition-colors" 
>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <a href="#" onclick="archiveStudent(<?= $student['id'] ?>)" 
                                           class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" 
>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-span-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch($student['status'] ?? 'Active') {
                                            case 'Active': echo 'bg-green-100 text-green-800'; break;
                                            case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>
                                    ">
                                        <?= htmlspecialchars($student['status'] ?? 'Active') ?>
                                    </span>
                                </div>
                                <div class="col-span-1">
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($student['rfid']) ?></code>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                    $rowNumber++; // Increment row number for next iteration
                    endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-slate-50 px-6 py-3 border-t border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-600">
                                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> results
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php 
                                // Build query string with all filters
                                $queryParams = [
                                    'level' => $level,
                                    'type' => $type,
                                    'search' => $search,
                                    'sort' => $sortBy,
                                    'order' => $sortOrder,
                                    'status_filter' => $statusFilter,
                                    'course_filter' => $courseFilter,
                                    'strand_filter' => $strandFilter,
                                    'section_filter' => $sectionFilter,
                                    'year_grade_filter' => $yearGradeFilter
                                ];
                                // Remove empty values
                                $queryParams = array_filter($queryParams, function($value) {
                                    return $value !== '' && $value !== null;
                                });
                                
                                function buildPaginationUrl($queryParams, $page) {
                                    $queryParams['page'] = $page;
                                    return '?' . http_build_query($queryParams);
                                }
                                ?>
                                
                                <?php if ($page > 1): ?>
                                    <a href="<?= buildPaginationUrl($queryParams, $page - 1) ?>" 
                                       class="px-3 py-1 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="<?= buildPaginationUrl($queryParams, $i) ?>" 
                                       class="px-3 py-1 <?= $i === $page ? 'bg-sky-600 text-white' : 'bg-white border border-slate-300 hover:bg-slate-50' ?> rounded-lg transition-colors">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?= buildPaginationUrl($queryParams, $page + 1) ?>" 
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
// Notification system - now uses global system from header.php
// The showNotification function is now globally available

// Global variable to store current student ID for archiving
let currentStudentId = null;

// Archive student function - shows confirmation modal
function archiveStudent(studentId) {
    currentStudentId = studentId;
    
    // Get student name for confirmation message
    const studentRow = document.querySelector(`[data-name][onclick*="${studentId}"]`);
    const studentName = studentRow ? studentRow.querySelector('.font-medium')?.textContent || 'this student' : 'this student';
    
    // Update modal content
    document.getElementById('confirmationTitle').textContent = 'Archive Student';
    document.getElementById('confirmationMessage').textContent = `Are you sure you want to archive ${studentName}?`;
    document.getElementById('confirmationWarning').textContent = 'This action can be undone from the archive management page.';
    document.getElementById('confirmButton').textContent = 'Archive Student';
    
    // Show modal
    showConfirmationModal();
}

// Show confirmation modal
function showConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    const content = document.getElementById('confirmationModalContent');
    
    modal.classList.remove('hidden');
    
    // Trigger animation
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Close confirmation modal
function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    const content = document.getElementById('confirmationModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Confirm archive action
function confirmArchiveAction() {
    if (!currentStudentId) return;
    
    // Close modal first
    closeConfirmationModal();
    
    // Show loading notification
    showNotification('Archiving student...', 'info');
    
    // Create form data
    const formData = new FormData();
    formData.append('student_id', currentStudentId);
    formData.append('csrf_token', '<?= csrf_token() ?>');
    
    // Submit via AJAX
    fetch('../admin/archive_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Check if the response indicates success
        if (data.includes('archived=1') || data.includes('success')) {
            showNotification('Student archived successfully!', 'success');
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification('Error archiving student. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error archiving student. Please try again.', 'error');
    });
    
    // Reset current student ID
    currentStudentId = null;
}

// Live search functionality
document.addEventListener('DOMContentLoaded', function() {
    const liveSearch = document.getElementById('liveSearch');
    const clearSearch = document.getElementById('clearSearch');
    const studentRows = document.querySelectorAll('.student-row');
    
    // Auto-focus search field
    if (liveSearch) {
        liveSearch.focus();
    }
    
    if (liveSearch && clearSearch) {
        // Live search as user types
        liveSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterStudents(searchTerm);
        });
        
        // Clear search
        clearSearch.addEventListener('click', function() {
            liveSearch.value = '';
            filterStudents('');
        });
    }
    
    function filterStudents(searchTerm) {
        let visibleCount = 0;
        
        studentRows.forEach(function(row) {
            const name = row.getAttribute('data-name') || '';
            const rfid = row.getAttribute('data-rfid') || '';
            const level = row.getAttribute('data-level') || '';
            const section = row.getAttribute('data-section') || '';
            const course = row.getAttribute('data-course') || '';
            const strand = row.getAttribute('data-strand') || '';
            const year = row.getAttribute('data-year') || '';
            const status = row.getAttribute('data-status') || '';
            
            const searchableText = `${name} ${rfid} ${level} ${section} ${course} ${strand} ${year} ${status}`;
            
            if (searchTerm === '' || searchableText.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide "No students found" message
        const noStudentsMessage = document.querySelector('.text-center.py-12');
        if (noStudentsMessage) {
            if (visibleCount === 0 && searchTerm !== '') {
                noStudentsMessage.style.display = 'block';
                noStudentsMessage.innerHTML = `
                    <div class="text-6xl text-slate-300 mb-4">🔍</div>
                    <h3 class="text-xl font-semibold text-slate-600 mb-2">No students found</h3>
                    <p class="text-slate-500 mb-6">No students match your search criteria.</p>
                `;
            } else if (visibleCount === 0) {
                noStudentsMessage.style.display = 'block';
            } else {
                noStudentsMessage.style.display = 'none';
            }
        }
    }
    
    // Handle sorting and filter form submission
    const sortSelects = document.querySelectorAll('select[name="sort"], select[name="order"], select[name="status_filter"], select[name="course_filter"], select[name="strand_filter"], select[name="section_filter"], select[name="year_grade_filter"]');
    sortSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Submit the form when sorting or filter options change
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // Clear all filters function
    window.clearAllFilters = function() {
        // Clear all filter inputs
        document.querySelectorAll('select[name="status_filter"], select[name="course_filter"], select[name="strand_filter"], select[name="section_filter"], select[name="year_grade_filter"]').forEach(select => {
            select.value = '';
        });
        
        document.querySelectorAll('input[name="age_min"], input[name="age_max"]').forEach(input => {
            input.value = '';
        });
        
        // Clear live search
        document.getElementById('liveSearch').value = '';
        
        // Submit the form to apply cleared filters
        const form = document.querySelector('form');
        if (form) {
            form.submit();
        }
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
