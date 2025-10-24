<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$student = null;
$enrollmentHistory = [];

if ($studentId) {
    // Get student information
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if ($student) {
        // Get enrollment history (excluding initial entries and simple status changes)
        // Only show re-enrollments and level changes (not status changes to Graduated)
        $stmt = $pdo->prepare('
            SELECT eh.*, u.name as created_by_name
            FROM enrollment_history eh
            LEFT JOIN users u ON eh.created_by = u.id
            WHERE eh.student_id = ? 
            AND eh.enrollment_type != "initial"
            AND eh.enrollment_type != "status_change"
            ORDER BY eh.enrollment_date DESC
        ');
        $stmt->execute([$studentId]);
        $enrollmentHistory = $stmt->fetchAll();
    }
}

?>
<?php $pageTitle = 'Enrollment History'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/../partials/header.php'; ?>

<div class="min-h-screen bg-slate-50 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <button onclick="goBack()" class="inline-flex items-center gap-2 px-4 py-2 text-clinic-blue hover:text-clinic-tea hover:bg-clinic-blue/5 rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </button>
                <h1 class="text-3xl font-bold text-slate-800">Enrollment History</h1>
            </div>
            
            <?php if ($student): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-clinic-blue/10 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-slate-800 mb-2"><?= htmlspecialchars($student['name']) ?></h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                            <div>
                                <span class="font-medium">RFID:</span> <?= htmlspecialchars($student['rfid']) ?>
                            </div>
                            <div>
                                <span class="font-medium">Current Level:</span> <?= htmlspecialchars($student['level']) ?>
                            </div>
                            <div>
                                <span class="font-medium">Status:</span> 
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?php
                                    switch($student['status']) {
                                        case 'Active': echo 'bg-green-100 text-green-800'; break;
                                        case 'Graduated': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Inactive': echo 'bg-gray-100 text-gray-800'; break;
                                        case 'Transferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>
                                ">
                                    <?= htmlspecialchars($student['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <!-- Status-based action buttons -->
                        <?php if ($student['status'] === 'Graduated'): ?>
                        <div class="space-y-2">
                            <a href="reenroll_student.php?id=<?= $student['id'] ?>" 
                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Re-enroll Student
                            </a>
                            <p class="text-xs text-slate-500 text-center">Student has graduated</p>
                        </div>
                        <?php elseif ($student['status'] === 'Active'): ?>
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Currently Enrolled
                            </div>
                            <p class="text-xs text-slate-500 text-center">Student is active</p>
                        </div>
                        <?php elseif ($student['status'] === 'Transferred'): ?>
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Transferred Out
                            </div>
                            <p class="text-xs text-slate-500 text-center">Student transferred to another school</p>
                        </div>
                        <?php elseif ($student['status'] === 'Inactive'): ?>
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-800 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                </svg>
                                Inactive
                            </div>
                            <p class="text-xs text-slate-500 text-center">Student is inactive</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Current Status Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Current Enrollment Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="text-center p-4 rounded-lg border
                        <?php
                        switch($student['status']) {
                            case 'Active': echo 'bg-green-50 border-green-200'; break;
                            case 'Graduated': echo 'bg-blue-50 border-blue-200'; break;
                            case 'Transferred': echo 'bg-yellow-50 border-yellow-200'; break;
                            case 'Inactive': echo 'bg-gray-50 border-gray-200'; break;
                            default: echo 'bg-gray-50 border-gray-200';
                        }
                        ?>
                    ">
                        <div class="text-2xl font-bold
                            <?php
                            switch($student['status']) {
                                case 'Active': echo 'text-green-600'; break;
                                case 'Graduated': echo 'text-blue-600'; break;
                                case 'Transferred': echo 'text-yellow-600'; break;
                                case 'Inactive': echo 'text-gray-600'; break;
                                default: echo 'text-gray-600';
                            }
                            ?>
                        ">
                            <?= htmlspecialchars($student['status']) ?>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">
                            <?php
                            switch($student['status']) {
                                case 'Active': echo 'Currently enrolled and active'; break;
                                case 'Graduated': echo 'Completed studies and graduated'; break;
                                case 'Transferred': echo 'Transferred to another institution'; break;
                                case 'Inactive': echo 'No longer active in the system'; break;
                                default: echo 'Status unknown';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg border bg-slate-50 border-slate-200">
                        <div class="text-2xl font-bold text-slate-600">
                            <?= htmlspecialchars($student['level']) ?>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">Current Level</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg border bg-slate-50 border-slate-200">
                        <div class="text-2xl font-bold text-slate-600">
                            <?= htmlspecialchars($student['year_grade'] ?? 'N/A') ?>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">Year/Grade</div>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg border bg-slate-50 border-slate-200">
                        <div class="text-2xl font-bold text-slate-600">
                            <?= count($enrollmentHistory) ?>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">Enrollment Records</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enrollment History -->
        <?php if ($student && !empty($enrollmentHistory)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Enrollment History</h3>
                        <p class="text-sm text-slate-600 mt-1">Complete history of student enrollments and changes</p>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="space-y-3">
                    <?php foreach ($enrollmentHistory as $enrollment): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors cursor-pointer" onclick="showEnrollmentDetails(<?= $enrollment['id'] ?>)">
                        <div class="flex items-center gap-4">
                            <div class="w-3 h-3 rounded-full 
                                <?php
                                switch($enrollment['enrollment_type']) {
                                    case 'initial': echo 'bg-green-500'; break;
                                    case 're_enrollment': echo 'bg-blue-500'; break;
                                    case 'level_change': echo 'bg-orange-500'; break;
                                    case 'status_change': echo 'bg-purple-500'; break;
                                    default: echo 'bg-gray-500';
                                }
                                ?>
                            "></div>
                            <div>
                                <h4 class="font-medium text-slate-800 capitalize">
                                    <?= htmlspecialchars($enrollment['previous_level'] ?? str_replace('_', ' ', $enrollment['enrollment_type'])) ?>
                                </h4>
                                <p class="text-sm text-slate-600">
                                    <?= date('M j, Y g:i A', strtotime($enrollment['enrollment_date'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <?php if ($enrollment['previous_level'] && $enrollment['previous_level'] !== $enrollment['new_level']): ?>
                                <div class="text-sm">
                                    <span class="text-slate-600"><?= htmlspecialchars($enrollment['previous_level']) ?></span>
                                    <span class="mx-1 text-slate-400">→</span>
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($enrollment['new_level']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($enrollment['previous_status'] && $enrollment['previous_status'] !== $enrollment['new_status']): ?>
                                <div class="text-sm">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs"><?= htmlspecialchars($enrollment['previous_status']) ?></span>
                                    <span class="mx-1 text-slate-400">→</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs"><?= htmlspecialchars($enrollment['new_status']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php elseif ($student): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-2">No Enrollment History</h3>
            <p class="text-slate-600">This student doesn't have any enrollment history recorded yet.</p>
        </div>
        
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Student Not Found</h3>
            <p class="text-slate-600">Please select a valid student to view their enrollment history.</p>
        </div>
        <?php endif; ?>
        
    </div>
</div>

    <!-- Enrollment Details Modal -->
    <div id="enrollmentDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative w-full max-w-6xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-slate-800" id="enrollmentModalTitle">Enrollment Details</h2>
                <button onclick="closeEnrollmentDetailsModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="enrollmentDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Old Patient Information Modal -->
    <div id="oldPatientModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-slate-800">Previous Student Information</h2>
                <button onclick="closeOldPatientModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="oldPatientContent">
                <!-- Content will be loaded here -->
            </div>
    </div>
</div>

<!-- Medical History Details Modal -->
<div id="medicalHistoryDetailsModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50" onclick="closeMedicalHistoryDetailsModal()"></div>
    <div class="relative w-full max-w-5xl bg-white rounded-2xl border border-slate-200 shadow-2xl max-h-[calc(100vh-4rem)] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-slate-200 flex-shrink-0">
            <h3 class="text-xl font-semibold text-slate-800">Medical Record Details</h3>
            <button onclick="closeMedicalHistoryDetailsModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="medicalHistoryDetailsContent" class="flex-1 overflow-y-auto p-6">
            <!-- Content will be loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Visitation Log Details Modal -->
<div id="visitationLogDetailsModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50" onclick="closeVisitationLogDetailsModal()"></div>
    <div class="relative w-full max-w-5xl bg-white rounded-2xl border border-slate-200 shadow-2xl max-h-[calc(100vh-4rem)] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-slate-200 flex-shrink-0">
            <h3 class="text-xl font-semibold text-slate-800">Visitation Log Details</h3>
            <button onclick="closeVisitationLogDetailsModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="visitationLogDetailsContent" class="flex-1 overflow-y-auto p-6">
            <!-- Content will be loaded via JavaScript -->
        </div>
    </div>
</div>

<script>
function goBack() {
    if (document.referrer && document.referrer.includes(window.location.hostname)) {
        window.history.back();
    } else {
        window.location.href = '../admin/dashboard.php';
    }
}

function showOldPatientInfo(enrollmentId) {
    // Show loading state
    document.getElementById('oldPatientContent').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-slate-600">Loading...</span>
        </div>
    `;
    
    // Show modal
    document.getElementById('oldPatientModal').classList.remove('hidden');
    document.getElementById('oldPatientModal').classList.add('flex');
    
    // Fetch old patient information
    fetch(`get_old_patient_info.php?id=${enrollmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('oldPatientContent').innerHTML = `
                    <div class="space-y-6">
                        <div class="bg-slate-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-slate-800 mb-4">Previous Academic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-slate-700">Previous Level:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_level || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Previous Year/Grade:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_year_grade || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Previous Section:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_section || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Previous Strand:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_strand || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Previous Course:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_course || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Previous Block:</span>
                                    <span class="text-slate-900">${data.enrollment.previous_block || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-slate-800 mb-4">Previous Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-slate-700">Name:</span>
                                    <span class="text-slate-900">${data.student.name}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Gender:</span>
                                    <span class="text-slate-900">${data.student.gender || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Age:</span>
                                    <span class="text-slate-900">${data.student.age || 'N/A'} years old</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Date of Birth:</span>
                                    <span class="text-slate-900">${(data.student.dob || data.student.date_of_birth) ? new Date(data.student.dob || data.student.date_of_birth).toLocaleDateString() : 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Religion:</span>
                                    <span class="text-slate-900">${data.student.religion || 'N/A'}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Guardian:</span>
                                    <span class="text-slate-900">${data.student.guardian || 'N/A'}</span>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="font-medium text-slate-700">Address:</span>
                                    <span class="text-slate-900">${data.student.address || 'N/A'}</span>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="font-medium text-slate-700">Allergies:</span>
                                    <span class="text-slate-900">${data.student.allergies || 'None'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-slate-800 mb-4">Re-enrollment Details</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="font-medium text-slate-700">Re-enrollment Date:</span>
                                    <span class="text-slate-900">${new Date(data.enrollment.enrollment_date).toLocaleString()}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700">Notes:</span>
                                    <span class="text-slate-900">${data.enrollment.notes || 'No notes'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('oldPatientContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-red-600 mb-2">Error loading information</div>
                        <p class="text-slate-600">${data.message || 'Unable to load old patient information'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('oldPatientContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-600 mb-2">Error</div>
                    <p class="text-slate-600">Unable to load old patient information</p>
                </div>
            `;
        });
}

function closeOldPatientModal() {
    document.getElementById('oldPatientModal').classList.add('hidden');
    document.getElementById('oldPatientModal').classList.remove('flex');
}

function showEnrollmentDetails(enrollmentId) {
    // Show loading state
    document.getElementById('enrollmentDetailsContent').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-slate-600">Loading...</span>
        </div>
    `;
    
    // Show modal
    document.getElementById('enrollmentDetailsModal').classList.remove('hidden');
    document.getElementById('enrollmentDetailsModal').classList.add('flex');
    
    // Update modal title to show school level
    const modalTitle = document.getElementById('enrollmentModalTitle');
    if (modalTitle) {
        modalTitle.textContent = 'Loading...';
    }
    
    // Fetch enrollment details
    fetch(`get_enrollment_details.php?id=${enrollmentId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Enrollment details data:', data);
            console.log('Enrollment object:', data.enrollment);
            console.log('Student object:', data.student);
            console.log('Notes value:', data.enrollment ? data.enrollment.notes : 'enrollment is null/undefined');
            console.log('Student first_name:', data.student ? data.student.first_name : 'student is null/undefined');
            console.log('Student last_name:', data.student ? data.student.last_name : 'student is null/undefined');
            if (data.success) {
                // Update modal title back to "Enrollment Details"
                const modalTitle = document.getElementById('enrollmentModalTitle');
                if (modalTitle) {
                    modalTitle.textContent = 'Enrollment Details';
                }
                
                document.getElementById('enrollmentDetailsContent').innerHTML = `
                    <div class="space-y-4 flex flex-col h-full">
                        <!-- Header with School Level, Period, and Notes -->
                        <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-4 py-3 rounded-xl">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h2 class="text-lg font-comfortaa font-bold text-white">${data.enrollment.previous_level || 'Enrollment Details'}</h2>
                                    ${data.enrollmentPeriod ? `
                                        <p class="text-xs text-white/80 mt-1">
                                            ${data.enrollmentPeriod.start_date ? 
                                                `Period: ${new Date(data.enrollmentPeriod.start_date).toLocaleDateString()} - ${new Date(data.enrollmentPeriod.end_date).toLocaleDateString()}` :
                                                `Period: Start - ${new Date(data.enrollmentPeriod.end_date).toLocaleDateString()}`
                                            }
                                        </p>
                                    ` : ''}
                                </div>
                                ${(data.enrollment && data.enrollment.notes && data.enrollment.notes.trim() !== '') ? `
                                    <div class="bg-white/90 backdrop-blur-sm rounded-lg px-3 py-1 border border-white/30">
                                        <p class="text-sm font-poppins text-slate-800 font-medium">${data.enrollment.notes}</p>
                                    </div>
                                ` : `
                                    <div class="bg-white/60 backdrop-blur-sm rounded-lg px-3 py-1 border border-white/20">
                                        <p class="text-sm font-poppins text-slate-600">No notes</p>
                                    </div>
                                `}
                            </div>
                        </div>

                        <!-- Enrollment Information -->
                        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden flex flex-col" style="height: 30%;">
                            <div class="bg-gradient-to-r from-clinic-tea to-clinic-vanilla px-3 py-2">
                                <h2 class="text-sm font-comfortaa font-bold text-clinic-dark">Enrollment Information</h2>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <div class="bg-clinic-ivory/40 rounded-2xl p-3 border border-clinic-tea/20">
                                    <div class="space-y-1">
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Type</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark capitalize">${data.enrollment.enrollment_type.replace('_', ' ')}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Date</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${new Date(data.enrollment.enrollment_date).toLocaleString()}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Processed by</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.created_by_name || 'System'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Patient Information -->
                        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden flex flex-col" style="height: 35%;">
                            <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-3 py-2">
                                <h2 class="text-sm font-comfortaa font-bold text-white">Full Patient Information</h2>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                <div class="bg-clinic-ivory/40 rounded-2xl p-3 border border-clinic-tea/20">
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Personal Information -->
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Full Name</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && data.student.name ? data.student.name : 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Date of Birth</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && (data.student.date_of_birth || data.student.dob) ? new Date(data.student.date_of_birth || data.student.dob).toLocaleDateString() : 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Gender</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && data.student.gender ? data.student.gender : 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Address</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && data.student.address ? data.student.address : 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Guardian Name</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && data.student.guardian_name ? data.student.guardian_name : 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Emergency Contact</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.student && data.student.emergency_contact ? data.student.emergency_contact : 'N/A'}</p>
                                        </div>
                                        <!-- Academic Information -->
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Student ID</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.student_id || 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">RFID</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_rfid || 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Level</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_level || 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Status</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_status || 'N/A'}</p>
                                        </div>
                                        ${data.enrollment.previous_level && ['Elementary', 'High School', 'Senior High School', 'College'].includes(data.enrollment.previous_level) ? `
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">${data.enrollment.previous_level === 'College' ? 'Year' : 'Grade'}</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_year_grade || 'N/A'}</p>
                                        </div>
                                        ` : ''}
                                        ${data.enrollment.previous_level && ['Pre-school', 'Elementary', 'High School'].includes(data.enrollment.previous_level) ? `
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Section</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_section || 'N/A'}</p>
                                        </div>
                                        ` : ''}
                                        ${data.enrollment.previous_level === 'Senior High School' ? `
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Strand</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_strand || 'N/A'}</p>
                                        </div>
                                        ` : ''}
                                        ${data.enrollment.previous_level === 'College' ? `
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Course</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_course || 'N/A'}</p>
                                        </div>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Block</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">${data.enrollment.previous_block || 'N/A'}</p>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <!-- Historical Medical History -->
                        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden flex flex-col" style="height: 300px;">
                            <div class="bg-gradient-to-r from-clinic-tea to-clinic-vanilla px-3 py-2 flex-shrink-0">
                                <h2 class="text-sm font-comfortaa font-bold text-clinic-dark">Medical Records for ${data.enrollment.previous_level || 'This Period'}</h2>
                                <p class="text-xs text-clinic-dark/60 mt-0.5">Records during this enrollment period</p>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                ${data.medicalHistory && data.medicalHistory.length > 0 ? `
                                    ${data.medicalHistory.map(record => `
                                        <div class="bg-clinic-ivory/40 rounded-2xl p-3 border border-clinic-tea/20 mb-2">
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <p class="font-poppins font-semibold text-clinic-dark">${record.form_type || 'Medical Record'}</p>
                                                        ${record.record_status === 'archived' ? `
                                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-xs rounded-full font-medium">Archived</span>
                                                        ` : ''}
                                                        ${record.education_level ? `
                                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">${record.education_level}</span>
                                                        ` : ''}
                                                    </div>
                                                    <p class="text-xs text-clinic-dark/60">${new Date(record.created_at || record.archived_at).toLocaleDateString()}</p>
                                                </div>
                                                <button type="button" onclick="event.preventDefault(); event.stopPropagation(); viewMedicalRecordDetails(${record.id || record.original_id}, ${record.record_status === 'archived' ? 'true' : 'false'}); return false;" class="text-xs bg-clinic-tea/20 hover:bg-clinic-tea/30 text-clinic-blue px-2 py-1 rounded transition-colors flex-shrink-0">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                ` : `
                                    <div class="text-center py-3">
                                        <div class="w-8 h-8 rounded-lg bg-clinic-ivory/60 mx-auto mb-2 flex items-center justify-center">
                                            <div class="text-lg">📋</div>
                                        </div>
                                        <p class="text-clinic-dark/60 font-poppins font-medium text-xs">No medical records during ${data.enrollment.previous_level || 'this period'}</p>
                                    </div>
                                `}
                            </div>
                        </div>

                        <!-- Historical Visitation Logs -->
                        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden flex flex-col" style="height: 300px;">
                            <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-3 py-2 flex-shrink-0">
                                <h2 class="text-sm font-comfortaa font-bold text-white">Clinic Visits for ${data.enrollment.previous_level || 'This Period'}</h2>
                                <p class="text-xs text-white/80 mt-0.5">Visitation logs during this enrollment period</p>
                            </div>
                            <div class="p-2 flex-1 overflow-y-auto">
                                ${data.visitationLogs && data.visitationLogs.length > 0 ? `
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-clinic-tea/20">
                                                    <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">ID</th>
                                                    <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Reason</th>
                                                    <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Date/Time</th>
                                                    <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Status</th>
                                                    <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${data.visitationLogs.map(visit => `
                                                    <tr class="border-b border-clinic-tea/10">
                                                        <td class="py-2 px-3 font-poppins font-medium text-clinic-dark">${visit.id || visit.original_id}</td>
                                                        <td class="py-2 px-3 font-poppins text-clinic-dark">${visit.reason || 'N/A'}</td>
                                                        <td class="py-2 px-3 font-poppins text-clinic-dark/60">${new Date(visit.created_at || visit.archived_at).toLocaleString()}</td>
                                                        <td class="py-2 px-3">
                                                            <div class="flex flex-wrap gap-1">
                                                                ${visit.log_status === 'archived' ? `
                                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-xs rounded-full font-medium whitespace-nowrap">Archived</span>
                                                                ` : ''}
                                                                ${visit.education_level ? `
                                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full font-medium whitespace-nowrap">${visit.education_level}</span>
                                                                ` : ''}
                                                            </div>
                                                        </td>
                                                        <td class="py-2 px-3">
                                                            <button type="button" onclick="event.preventDefault(); event.stopPropagation(); viewVisitationDetails(${visit.id || visit.original_id}, ${visit.log_status === 'archived' ? 'true' : 'false'}); return false;" class="text-xs bg-clinic-tea/20 hover:bg-clinic-tea/30 text-clinic-blue px-2 py-1 rounded transition-colors whitespace-nowrap">
                                                                View Details
                                                            </button>
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : `
                                    <div class="text-center py-3">
                                        <div class="w-8 h-8 rounded-lg bg-clinic-ivory/60 mx-auto mb-2 flex items-center justify-center">
                                            <div class="text-lg">🏥</div>
                                        </div>
                                        <p class="text-clinic-dark/60 font-poppins font-medium text-xs">No clinic visits during ${data.enrollment.previous_level || 'this period'}</p>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('enrollmentDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-red-600 mb-2">Error loading details</div>
                        <p class="text-slate-600">${data.message || 'Unable to load enrollment details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading enrollment details:', error);
            document.getElementById('enrollmentDetailsContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-600 mb-2">Error</div>
                    <p class="text-slate-600">Unable to load enrollment details: ${error.message}</p>
                </div>
            `;
        });
}

function closeEnrollmentDetailsModal() {
    document.getElementById('enrollmentDetailsModal').classList.add('hidden');
    document.getElementById('enrollmentDetailsModal').classList.remove('flex');
}

function closeMedicalHistoryDetailsModal() {
    document.getElementById('medicalHistoryDetailsModal').classList.add('hidden');
    document.getElementById('medicalHistoryDetailsModal').classList.remove('flex');
}

function closeVisitationLogDetailsModal() {
    document.getElementById('visitationLogDetailsModal').classList.add('hidden');
    document.getElementById('visitationLogDetailsModal').classList.remove('flex');
}

function viewVisitationDetails(visitationId, isArchived = false) {
    console.log('Opening visitation details for ID:', visitationId, 'Archived:', isArchived);
    
    // Show loading
    document.getElementById('visitationLogDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></div>';
    
    // Show modal
    const modal = document.getElementById('visitationLogDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    console.log('Modal classes:', modal.className);
    
    // Fetch visitation log details (with archived parameter if needed)
    const archivedParam = isArchived ? '&archived=1' : '';
    fetch(`../logs/visitation_details_view.php?id=${visitationId}&ajax=1${archivedParam}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('visitationLogDetailsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading visitation log:', error);
            document.getElementById('visitationLogDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading visitation log details.</div>';
        });
}

function viewMedicalRecordDetails(recordId, isArchived = false) {
    console.log('Opening medical record details for ID:', recordId, 'Archived:', isArchived);
    
    // Show loading
    document.getElementById('medicalHistoryDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></div>';
    
    // Show modal
    const modal = document.getElementById('medicalHistoryDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    console.log('Modal classes:', modal.className);
    
    // Fetch medical record details (with archived parameter if needed)
    const archivedParam = isArchived ? '&archived=1' : '';
    fetch(`medical_record_view.php?id=${recordId}&ajax=1${archivedParam}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('medicalHistoryDetailsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading medical record:', error);
            document.getElementById('medicalHistoryDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading medical record details.</div>';
        });
}

</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
