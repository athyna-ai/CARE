<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get patient ID and type from URL
$patientId = (int)($_GET['id'] ?? 0);
$patientType = sanitize_string($_GET['type'] ?? 'student'); // student or faculty

if ($patientId <= 0) {
    header('Location: dashboard.php?error=invalid_patient');
    exit;
}

// Get patient information
$patient = null;
if ($patientType === 'student') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
} else {
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
}

if (!$patient) {
    header('Location: dashboard.php?error=patient_not_found');
    exit;
}

// Get medical history (check if table exists first)
$medicalHistory = [];
try {
    $pdo->query("SELECT 1 FROM medical_records LIMIT 1");
    $medicalStmt = $pdo->prepare('SELECT * FROM medical_records WHERE patient_id = ? AND patient_type = ? ORDER BY created_at DESC');
    $medicalStmt->execute([$patientId, $patientType]);
    $medicalHistory = $medicalStmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist, medical history will be empty
    $medicalHistory = [];
}

// Get visitation logs (check if table exists first)
$visitationLogs = [];
try {
    $pdo->query("SELECT 1 FROM visitation_logs LIMIT 1");
    $visitationStmt = $pdo->prepare('SELECT * FROM visitation_logs WHERE patient_id = ? AND patient_type = ? ORDER BY visit_date DESC');
    $visitationStmt->execute([$patientId, $patientType]);
    $visitationLogs = $visitationStmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist, visitation logs will be empty
    $visitationLogs = [];
}

$pageTitle = 'Patient Information';
$showTopNav = true; // Show top navigation for this page
$showSidebar = false;
include __DIR__ . '/partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<style>
html, body {
    height: 100vh;
    overflow: hidden;
    margin: 0;
    padding: 0;
}
</style>

<div class="h-screen w-full bg-gradient-to-br from-slate-50 to-blue-50 overflow-hidden flex flex-col">
    <!-- Header with Patient Info -->
    <div class="bg-white shadow-lg border-b border-slate-200 flex-shrink-0">
        <div class="w-full px-4 py-3">
            <!-- Back Button -->
            <div class="mb-2">
                <?php 
                // Determine the correct back URL based on patient type
                $backUrl = ($patientType === 'student') ? 'school_listing.php' : 'faculty_listing.php';
                ?>
                <a href="<?= $backUrl ?>" class="inline-flex items-center text-slate-600 hover:text-lg text-slate-800 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to <?= ucfirst($patientType) ?> List
                </a>
            </div>
            
            <!-- Patient Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-2 lg:mb-0">
                    <h1 class="text-2xl font-bold text-lg text-slate-800 mb-1">
                        <?= htmlspecialchars($patient['name']) ?>
                    </h1>
                    <div class="flex flex-wrap items-center gap-4 text-slate-600">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                            </svg>
                            RFID: <?= htmlspecialchars($patient['rfid']) ?>
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <?= htmlspecialchars($patient['level'] ?? $patient['department'] ?? 'N/A') ?>
                        </span>
                        <?php if ($patientType === 'faculty' && $patient['sr']): ?>
                            <span class="px-2 py-1 bg-amber-100 text-amber-800 text-sm font-semibold rounded">Sr.</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="viewAllMedicalForms()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors flex items-center text-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Medical Forms
                    </button>
                    <button onclick="openVisitationModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center text-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Add Visitation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full px-2 py-2 flex-1 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-full">
            <!-- Patient Details Card -->
            <div class="lg:col-span-2 h-full">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden h-full flex flex-col">
                    <div class="bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3 flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-white">Patient Information</h2>
                            <button onclick="editPatientInfo()" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                        </div>
                    </div>
                    <div class="p-4 flex-1 overflow-y-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 h-full">
                            <!-- Personal Information -->
                            <div class="bg-slate-50 rounded-xl p-4 flex flex-col h-full">
                                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Personal Information
                                </h3>
                                <div class="space-y-3 flex-1">
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Full Name</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['name']) ?></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Level/Department</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['level'] ?? $patient['department'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if ($patientType === 'student'): ?>
                                        <div class="bg-white rounded-lg p-3 border border-slate-200">
                                            <span class="text-base font-semibold text-slate-600 block mb-1">Grade/Year</span>
                                            <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['year_grade'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 border border-slate-200">
                                            <span class="text-base font-semibold text-slate-600 block mb-1">Section/Strand/Course</span>
                                            <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['section'] ?? $patient['strand'] ?? $patient['course'] ?? 'N/A') ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Age</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars((string)($patient['age'] ?? 'N/A')) ?> years old</p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Date of Birth</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['dob'] ? date('M j, Y', strtotime($patient['dob'])) : 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact & Medical Info -->
                            <div class="bg-slate-50 rounded-xl p-4 flex flex-col h-full">
                                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    Contact & Medical
                                </h3>
                                <div class="space-y-3 flex-1">
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Address</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['address'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if ($patientType === 'student'): ?>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Guardian/Parent</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['guardian'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($patientType === 'student'): ?>
                                        <div class="bg-white rounded-lg p-3 border border-slate-200">
                                            <span class="text-base font-semibold text-slate-600 block mb-1">Parent Contact</span>
                                            <p class="text-lg font-bold text-slate-800">
                                                <?php 
                                                // Parse contacts JSON to get parent contact
                                                $contacts = json_decode($patient['contacts'] ?? '[]', true);
                                                $parentContact = '';
                                                if (is_array($contacts) && !empty($contacts)) {
                                                    $parentContact = $contacts[0]; // First contact is usually parent
                                                }
                                                echo htmlspecialchars($parentContact ?: ($patient['emergency_contact'] ?? 'N/A'));
                                                ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-white rounded-lg p-3 border border-slate-200">
                                            <span class="text-base font-semibold text-slate-600 block mb-1">Emergency Contact</span>
                                            <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['emergency_contact'] ?? 'N/A') ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Religion</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['religion'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-slate-200">
                                        <span class="text-base font-semibold text-slate-600 block mb-1">Allergies</span>
                                        <p class="text-lg font-bold text-slate-800"><?= htmlspecialchars($patient['allergies'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical History & Visitation Logs -->
            <div class="space-y-2 h-full flex flex-col">
                <!-- Medical History -->
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex-shrink-0">
                    <div class="bg-gradient-to-r from-emerald-500 to-green-600 px-3 py-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-white">Medical History</h2>
                            <button onclick="openMedicalHistoryForm()" class="text-white hover:text-emerald-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-3">
                        <?php if (empty($medicalHistory)): ?>
                            <div class="text-center py-4">
                                <div class="text-2xl text-slate-300 mb-2">📋</div>
                                <p class="text-sm text-slate-500 mb-3">No medical history</p>
                                <button onclick="openMedicalHistoryForm()" class="inline-flex items-center px-3 py-1 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add
                                </button>
                            </div>
                        <?php else: ?>
                            <?php $latestRecord = $medicalHistory[0]; ?>
                            <div class="border border-slate-200 rounded-lg p-2 hover:bg-slate-50 transition-colors cursor-pointer" onclick="viewMedicalRecord(<?= $latestRecord['id'] ?>)">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold text-sm text-slate-800"><?= htmlspecialchars($latestRecord['form_type']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($latestRecord['created_at']))) ?></p>
                                    </div>
                                    <span class="text-xs text-slate-400">#<?= $latestRecord['id'] ?></span>
                                </div>
                                <?php if (count($medicalHistory) > 1): ?>
                                    <div class="text-xs text-slate-400 mt-1">
                                        +<?= count($medicalHistory) - 1 ?> more
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visitation Logs -->
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex-1 flex flex-col">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-3 py-2 flex-shrink-0">
                        <h2 class="text-sm font-bold text-white">Visitation Logs</h2>
                    </div>
                    <div class="p-3 flex-1 overflow-y-auto">
                        <?php if (empty($visitationLogs)): ?>
                            <div class="text-center py-4">
                                <div class="text-2xl text-slate-300 mb-2">🏥</div>
                                <p class="text-sm text-slate-500 mb-3">No visits recorded</p>
                                <button onclick="openVisitationModal()" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Add Visit
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach (array_slice($visitationLogs, 0, 3) as $visit): ?>
                                    <div class="border border-slate-200 rounded-lg p-2 hover:bg-slate-50 transition-colors cursor-pointer" onclick="viewVisitationRecord(<?= $visit['id'] ?>)">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-sm text-slate-800"><?= htmlspecialchars($visit['reason']) ?></p>
                                                <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($visit['visit_date']))) ?></p>
                                            </div>
                                            <span class="text-xs text-slate-400">#<?= $visit['id'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($visitationLogs) > 3): ?>
                                    <div class="text-center pt-1">
                                        <button onclick="viewAllVisitations()" class="text-xs text-slate-500 hover:text-slate-700 underline">
                                            +<?= count($visitationLogs) - 3 ?> more
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Medical History Form Modal -->
<div id="medicalHistoryModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-lg text-slate-800">Medical History Form</h2>
            <button onclick="closeMedicalHistoryModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="medicalHistoryForm" method="POST" action="save_medical_history.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="space-y-6">
                <!-- Ongoing Medical Conditions -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">Ongoing Medical Conditions</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="error_refraction" id="error_refraction" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="error_refraction" class="text-slate-700">Error of Refraction</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="asthma" id="asthma" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="asthma" class="text-slate-700">Asthma</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="seizure" id="seizure" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="seizure" class="text-slate-700">Seizure</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="heart_problem" id="heart_problem" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="heart_problem" class="text-slate-700">Heart Problem</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="anemia" id="anemia" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="anemia" class="text-slate-700">Anemia</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="bleeding_disorder" id="bleeding_disorder" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="bleeding_disorder" class="text-slate-700">Bleeding Disorder</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="hernia" id="hernia" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="hernia" class="text-slate-700">Hernia</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="ongoing_conditions[]" value="others" id="others_ongoing" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="others_ongoing" class="text-slate-700">Others</label>
                        </div>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other conditions:</label>
                            <textarea name="ongoing_conditions_other" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other ongoing medical conditions..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Surgery/Hospitalization -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">Surgery/Hospitalization</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Have you ever had surgery/hospitalization?</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="surgery_status" value="no" class="text-sky-600 focus:ring-sky-500" checked>
                                    <span class="ml-2 text-slate-700">No</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="surgery_status" value="yes" class="text-sky-600 focus:ring-sky-500">
                                    <span class="ml-2 text-slate-700">Yes</span>
                                </label>
                            </div>
                        </div>
                        <div id="surgery_details" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please identify:</label>
                            <textarea name="surgery_details" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify surgeries and hospitalizations..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Family Medical History -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">Family Medical History</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="tuberculosis" id="tuberculosis" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="tuberculosis" class="text-slate-700">Tuberculosis</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="cancer" id="cancer" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="cancer" class="text-slate-700">Cancer</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="diabetes" id="diabetes" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="diabetes" class="text-slate-700">Diabetes</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="hypertension" id="hypertension" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="hypertension" class="text-slate-700">Hypertension</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="depression" id="depression" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="depression" class="text-slate-700">Depression</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="family_conditions[]" value="others" id="others_family" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="others_family" class="text-slate-700">Others</label>
                        </div>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other family conditions:</label>
                            <textarea name="family_conditions_other" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other family medical conditions..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Cigarette/Vape Exposure -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">Exposure to Cigarette/Vape Smoke at Home</h3>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="smoke_exposure" value="yes" class="text-sky-600 focus:ring-sky-500">
                            <span class="ml-2 text-slate-700">Yes</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="smoke_exposure" value="no" class="text-sky-600 focus:ring-sky-500" checked>
                            <span class="ml-2 text-slate-700">No</span>
                        </label>
                    </div>
                </div>

                <!-- Immunization -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">Immunization Received</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="mmr" id="mmr" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="mmr" class="text-slate-700">MMR (Measles, Mumps, Rubella)</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="dpt" id="dpt" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="dpt" class="text-slate-700">DPT (Diphtheria, Pertussis, Tetanus)</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="bcg" id="bcg" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="bcg" class="text-slate-700">BCG (Bacillus Calmette-Guérin)</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="chicken_pox" id="chicken_pox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="chicken_pox" class="text-slate-700">Chicken Pox (Varicella)</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="hepatitis_b" id="hepatitis_b" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="hepatitis_b" class="text-slate-700">Hepatitis B</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="immunization[]" value="polio" id="polio" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="polio" class="text-slate-700">Polio</label>
                        </div>
                    </div>
                </div>

                <!-- COVID-19 Vaccine -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">COVID-19 Vaccine Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="covid_vaccine[]" value="first_dose" id="first_dose" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="first_dose" class="text-slate-700">First Dose</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="covid_vaccine[]" value="second_dose" id="second_dose" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="second_dose" class="text-slate-700">Second Dose</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="covid_vaccine[]" value="booster_1" id="booster_1" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="booster_1" class="text-slate-700">Booster 1</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="covid_vaccine[]" value="booster_2" id="booster_2" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="booster_2" class="text-slate-700">Booster 2</label>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="covid_vaccine[]" value="bivalent" id="bivalent" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="bivalent" class="text-slate-700">Bivalent</label>
                        </div>
                    </div>
                </div>

                <!-- COVID-19 Test -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-3">COVID-19 Test History</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Have you tested positive for COVID-19?</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="covid_positive" value="no" class="text-sky-600 focus:ring-sky-500" checked>
                                    <span class="ml-2 text-slate-700">No</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="covid_positive" value="yes" class="text-sky-600 focus:ring-sky-500">
                                    <span class="ml-2 text-slate-700">Yes</span>
                                </label>
                            </div>
                        </div>
                        <div id="covid_details" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please identify when and details:</label>
                            <textarea name="covid_details" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify when you tested positive and any relevant details..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8">
                <button type="button" onclick="closeMedicalHistoryModal()" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors">
                    Save Medical History
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Full Screen Medical Form Modal -->
<div id="medicalFormFullScreen" class="fixed inset-0 z-50 hidden bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="bg-white shadow-lg border-b border-slate-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="closeMedicalFormFullScreen()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-lg text-slate-800" id="medicalFormTitle">Medical Form</h1>
                        <p class="text-slate-600" id="medicalFormSubtitle">Patient: <?= htmlspecialchars($patient['name']) ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="saveMedicalForm()" class="px-6 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors font-medium">
                        Save Form
                    </button>
                    <button onclick="closeMedicalFormFullScreen()" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="flex-1 overflow-hidden">
            <div class="h-full overflow-y-auto">
                <div class="p-6">
                    <!-- Form will be dynamically loaded here -->
                    <div id="medicalFormContent" class="space-y-6">
                        <!-- Content will be loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visitation Form Modal -->
<div id="visitationModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">Add Visitation Record</h2>
        <form id="visitationForm" method="POST" action="save_visitation.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason *</label>
                    <select name="reason" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        <option value="">Select reason</option>
                        <option value="fever">Fever</option>
                        <option value="headache">Headache</option>
                        <option value="injury">Injury</option>
                        <option value="stomach_ache">Stomach Ache</option>
                        <option value="dizziness">Dizziness</option>
                        <option value="nausea">Nausea</option>
                        <option value="cough">Cough</option>
                        <option value="cold">Cold</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Date & Time *</label>
                    <input type="datetime-local" name="visit_date" value="<?= date('Y-m-d\TH:i') ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms/Observations</label>
                <textarea name="symptoms" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Describe symptoms and observations..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (BPM)</label>
                    <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="N/A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure</label>
                    <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="N/A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                    <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="N/A">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Other Notes</label>
                <textarea name="other_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Additional notes..."></textarea>
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="medication_given" id="medicationCheckbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="ml-2 text-sm font-medium text-slate-700">Medication was given</span>
                </label>
            </div>
            
            <div id="medicationForm" class="hidden mb-4 p-4 bg-slate-50 rounded-lg">
                <h3 class="text-lg font-semibold text-lg text-slate-800 mb-3">Medication Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Medication Name</label>
                        <input type="text" name="medication_name" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Other Treatment</label>
                        <input type="text" name="other_treatment" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Additional Notes</label>
                    <textarea name="medication_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200"></textarea>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="injury" id="injuryCheckbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="ml-2 text-sm font-medium text-slate-700">Injury occurred</span>
                </label>
            </div>
            
            <div id="injuryForm" class="hidden mb-4 p-4 bg-slate-50 rounded-lg">
                <h3 class="text-lg font-semibold text-lg text-slate-800 mb-3">Injury Details</h3>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="first_aid_given" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span class="ml-2 text-sm font-medium text-slate-700">First aid was given</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type of First Aid</label>
                    <input type="text" name="first_aid_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="e.g., Bandage, Ice pack, etc.">
                </div>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeVisitationModal()" class="px-4 py-2 rounded-lg border border-slate-300">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Save Visitation</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Patient Information Modal -->
<div id="editPatientModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-6xl mx-auto bg-white rounded-2xl shadow-xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-slate-800">Edit Patient Information</h2>
            <button onclick="closeEditPatientModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editPatientForm" method="POST" action="update_patient.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div>
                    <label class="block text-slate-700 mb-1">Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                </div>
                
                <?php if ($patientType === 'student'): ?>
                    <div>
                        <label class="block text-slate-700 mb-1">Level *</label>
                        <select name="level" id="levelSelect" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                            <option value="">Select Level</option>
                            <option value="Pre-school" <?= ($patient['level'] ?? '') === 'Pre-school' ? 'selected' : '' ?>>Pre-school</option>
                            <option value="Elementary" <?= ($patient['level'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                            <option value="High School" <?= ($patient['level'] ?? '') === 'High School' ? 'selected' : '' ?>>High School</option>
                            <option value="Senior High School" <?= ($patient['level'] ?? '') === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                            <option value="College" <?= ($patient['level'] ?? '') === 'College' ? 'selected' : '' ?>>College</option>
                        </select>
                    </div>
                    
                    <!-- Year/Grade field (dynamic based on level) -->
                    <div id="yearGradeField" class="<?= in_array($patient['level'] ?? '', ['Elementary', 'High School', 'Senior High School', 'College']) ? '' : 'hidden' ?>">
                        <label class="block text-slate-700 mb-1" id="yearGradeLabel">Year/Grade</label>
                        <select name="year_grade" id="yearGradeInput" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" data-existing-value="<?= htmlspecialchars($patient['year_grade'] ?? '') ?>">
                            <option value="">Select Grade/Year</option>
                        </select>
                    </div>
                    
                    <!-- Course field (for College) -->
                    <div id="courseField" class="<?= ($patient['level'] ?? '') === 'College' ? '' : 'hidden' ?>">
                        <label class="block text-slate-700 mb-1">Course</label>
                        <select name="course" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                            <option value="">Select Course</option>
                            <option value="BS in Information Technology" <?= ($patient['course'] ?? '') === 'BS in Information Technology' ? 'selected' : '' ?>>BS in Information Technology</option>
                            <option value="BS in Education" <?= ($patient['course'] ?? '') === 'BS in Education' ? 'selected' : '' ?>>BS in Education</option>
                            <option value="BS in Criminology" <?= ($patient['course'] ?? '') === 'BS in Criminology' ? 'selected' : '' ?>>BS in Criminology</option>
                            <option value="BS in Hospitality Management" <?= ($patient['course'] ?? '') === 'BS in Hospitality Management' ? 'selected' : '' ?>>BS in Hospitality Management</option>
                            <option value="BS in Office Administration" <?= ($patient['course'] ?? '') === 'BS in Office Administration' ? 'selected' : '' ?>>BS in Office Administration</option>
                            <option value="BS in Business Administration" <?= ($patient['course'] ?? '') === 'BS in Business Administration' ? 'selected' : '' ?>>BS in Business Administration</option>
                            <option value="BS in Psychology" <?= ($patient['course'] ?? '') === 'BS in Psychology' ? 'selected' : '' ?>>BS in Psychology</option>
                            <option value="BS in Accountancy" <?= ($patient['course'] ?? '') === 'BS in Accountancy' ? 'selected' : '' ?>>BS in Accountancy</option>
                            <option value="BS in Computer Science" <?= ($patient['course'] ?? '') === 'BS in Computer Science' ? 'selected' : '' ?>>BS in Computer Science</option>
                            <option value="BS in Nursing" <?= ($patient['course'] ?? '') === 'BS in Nursing' ? 'selected' : '' ?>>BS in Nursing</option>
                            <option value="BS in Engineering" <?= ($patient['course'] ?? '') === 'BS in Engineering' ? 'selected' : '' ?>>BS in Engineering</option>
                            <option value="Other" <?= ($patient['course'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <!-- Strand field (for Senior High School) -->
                    <div id="strandField" class="<?= ($patient['level'] ?? '') === 'Senior High School' ? '' : 'hidden' ?>">
                        <label class="block text-slate-700 mb-1">Strand</label>
                        <select name="strand" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                            <option value="">Select Strand</option>
                            <option value="STEM" <?= ($patient['strand'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering, and Mathematics)</option>
                            <option value="ABM" <?= ($patient['strand'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business, and Management)</option>
                            <option value="HUMSS" <?= ($patient['strand'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities and Social Sciences)</option>
                            <option value="GAS" <?= ($patient['strand'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                            <option value="TVL" <?= ($patient['strand'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL (Technical-Vocational-Livelihood)</option>
                        </select>
                    </div>
                    
                    <!-- Section field (for Elementary, High School) -->
                    <div id="sectionField" class="<?= in_array($patient['level'] ?? '', ['Elementary', 'High School']) ? '' : 'hidden' ?>">
                        <label class="block text-slate-700 mb-1">Section</label>
                        <input type="text" name="section" value="<?= htmlspecialchars($patient['section'] ?? '') ?>" placeholder="e.g., Grade 1-A, Grade 7-B" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                    </div>
                <?php else: ?>
                    <div>
                        <label class="block text-slate-700 mb-1">Department *</label>
                        <input type="text" name="department" value="<?= htmlspecialchars($patient['department'] ?? '') ?>" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                    </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-slate-700 mb-1">Date of Birth *</label>
                    <input type="text" name="date_of_birth" value="<?= htmlspecialchars($patient['dob'] ?? '') ?>" placeholder="DD/MM/YYYY" maxlength="10" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" oninput="formatDateInput(this)" onkeypress="return isNumberKey(event)">
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Gender *</label>
                    <select name="gender" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($patient['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($patient['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Religion</label>
                    <input type="text" name="religion" value="<?= htmlspecialchars($patient['religion'] ?? '') ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Address *</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($patient['address'] ?? '') ?>" placeholder="Barangay/Municipality/City, Province" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                </div>
                
                <?php if ($patientType === 'student'): ?>
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Guardian/Parent</label>
                    <input type="text" name="guardian" value="<?= htmlspecialchars($patient['guardian'] ?? '') ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                </div>
                <?php endif; ?>
                
                <!-- Contact Numbers -->
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-2"><?= $patientType === 'student' ? 'Parent/Guardian Contact Numbers' : 'Emergency Contact Numbers' ?></label>
                    <div id="contactNumbersContainer">
                        <?php 
                        $contacts = json_decode($patient['contacts'] ?? '[]', true);
                        
                        // If no contacts in JSON, check if there's an emergency_contact field (legacy data)
                        if (empty($contacts) && !empty($patient['emergency_contact'])) {
                            $contacts = [$patient['emergency_contact']];
                        }
                        
                        // If still no contacts, show one empty field
                        if (empty($contacts)) {
                            $contacts = [''];
                        }
                        
                        foreach ($contacts as $index => $contact): 
                        ?>
                        <div class="contact-number-item flex items-center space-x-2 mb-2">
                            <input type="text" name="contacts[]" value="<?= htmlspecialchars($contact) ?>" placeholder="09xxxxxxxxx" class="flex-1 rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                            <?php if ($index > 0): ?>
                            <button type="button" onclick="removeContactNumber(this)" class="p-2 text-red-500 hover:text-red-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addContactNumber()" class="mt-2 inline-flex items-center px-3 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition-colors text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Contact Number
                    </button>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Allergies</label>
                    <textarea name="allergies" rows="3" placeholder="List any known allergies (e.g., peanuts, shellfish, medications). Leave blank if none." class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800"><?= htmlspecialchars($patient['allergies'] ?? 'N/A') ?></textarea>
                    <p class="mt-1 text-xs text-slate-500">Enter "None" or leave blank if the patient has no known allergies.</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-slate-200">
                <button type="button" onclick="closeEditPatientModal()" class="px-8 py-3 border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="px-8 py-3 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition-colors font-medium">
                    Update Patient
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openVisitationModal() {
    document.getElementById('visitationModal').classList.remove('hidden');
    document.getElementById('visitationModal').classList.add('flex');
}

function closeVisitationModal() {
    document.getElementById('visitationModal').classList.add('hidden');
    document.getElementById('visitationModal').classList.remove('flex');
}

function viewMedicalRecord(recordId) {
    // Redirect to medical record view page
    window.location.href = `medical_record_view.php?id=${recordId}`;
}

function viewVisitationRecord(visitId) {
    // Redirect to visitation record view page
    window.location.href = `visitation_record_view.php?id=${visitId}`;
}

function showMedicalFormFullScreen(type) {
    const modal = document.getElementById('medicalFormFullScreen');
    const title = document.getElementById('medicalFormTitle');
    const content = document.getElementById('medicalFormContent');
    
    // Set title based on form type
    const formTitles = {
        'athlete': 'Athlete Medical Form',
        'general': 'General Medical Form',
        'emergency': 'Emergency Medical Form'
    };
    
    title.textContent = formTitles[type] || 'Medical Form';
    
    // Load form content based on type
    loadMedicalFormContent(type, content);
    
    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeMedicalFormFullScreen() {
    const modal = document.getElementById('medicalFormFullScreen');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function loadMedicalFormContent(type, container) {
    // Clear existing content
    container.innerHTML = '';
    
    // Create form based on type
    const form = document.createElement('form');
    form.id = 'medicalForm';
    form.method = 'POST';
    form.action = 'save_medical_form.php';
    
    // Add hidden fields
    form.innerHTML = `
        <input type="hidden" name="patient_id" value="<?= $patientId ?>">
        <input type="hidden" name="patient_type" value="<?= $patientType ?>">
        <input type="hidden" name="form_type" value="${type}">
    `;
    
    // Add form content based on type
    if (type === 'athlete') {
        form.innerHTML += getAthleteFormContent();
    } else if (type === 'general') {
        form.innerHTML += getGeneralFormContent();
    } else if (type === 'emergency') {
        form.innerHTML += getEmergencyFormContent();
    }
    
    container.appendChild(form);
}

function getAthleteFormContent() {
    return `
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
            <h3 class="text-xl font-semibold text-lg text-slate-800 mb-6">Athlete Medical Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sport/Activity *</label>
                    <input type="text" name="sport" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Position/Event</label>
                    <input type="text" name="position" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Height (cm)</label>
                    <input type="number" name="height" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Weight (kg)</label>
                    <input type="number" name="weight" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Medical History</label>
                <textarea name="medical_history" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Previous injuries, conditions, medications..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Physical Examination</label>
                <textarea name="physical_exam" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Heart rate, blood pressure, general condition..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Recommendations</label>
                <textarea name="recommendations" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Activity restrictions, follow-up requirements..."></textarea>
            </div>
        </div>
    `;
}

function getGeneralFormContent() {
    return `
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
            <h3 class="text-xl font-semibold text-lg text-slate-800 mb-6">General Medical Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Chief Complaint *</label>
                    <input type="text" name="chief_complaint" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Main reason for visit">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Duration</label>
                    <input type="text" name="duration" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="How long has this been going on?">
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">History of Present Illness</label>
                <textarea name="history_present" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Detailed description of symptoms, onset, progression..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Past Medical History</label>
                <textarea name="past_medical" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Previous illnesses, surgeries, hospitalizations..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Physical Examination</label>
                <textarea name="physical_exam" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Vital signs, general appearance, specific findings..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Assessment & Plan</label>
                <textarea name="assessment_plan" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Diagnosis, treatment plan, follow-up..."></textarea>
            </div>
        </div>
    `;
}

function getEmergencyFormContent() {
    return `
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
            <h3 class="text-xl font-semibold text-lg text-slate-800 mb-6">Emergency Medical Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Emergency Type *</label>
                    <select name="emergency_type" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        <option value="">Select emergency type</option>
                        <option value="injury">Injury</option>
                        <option value="illness">Illness</option>
                        <option value="allergic_reaction">Allergic Reaction</option>
                        <option value="respiratory">Respiratory Emergency</option>
                        <option value="cardiac">Cardiac Emergency</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Severity Level *</label>
                    <select name="severity" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        <option value="">Select severity</option>
                        <option value="low">Low - Minor</option>
                        <option value="moderate">Moderate - Requires Attention</option>
                        <option value="high">High - Urgent</option>
                        <option value="critical">Critical - Life Threatening</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Description of Emergency</label>
                <textarea name="emergency_description" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="What happened, when, where, how..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Immediate Actions Taken</label>
                <textarea name="immediate_actions" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="First aid, medications given, emergency contacts..."></textarea>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Vital Signs</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Heart Rate (BPM)</label>
                        <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Blood Pressure</label>
                        <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="120/80">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Temperature (°C)</label>
                        <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Follow-up Required</label>
                <textarea name="follow_up" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Next steps, referrals, monitoring requirements..."></textarea>
            </div>
        </div>
    `;
}

function saveMedicalForm() {
    const form = document.getElementById('medicalForm');
    if (form) {
        form.submit();
    }
}

function openMedicalHistoryForm() {
    document.getElementById('medicalHistoryModal').classList.remove('hidden');
    document.getElementById('medicalHistoryModal').classList.add('flex');
}

function closeMedicalHistoryModal() {
    document.getElementById('medicalHistoryModal').classList.add('hidden');
    document.getElementById('medicalHistoryModal').classList.remove('flex');
}

function viewAllMedicalForms() {
    // Redirect to medical forms management page
    window.location.href = `medical_forms_management.php?patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`;
}

// Toggle medication form
document.getElementById('medicationCheckbox').addEventListener('change', function() {
    const medicationForm = document.getElementById('medicationForm');
    if (this.checked) {
        medicationForm.classList.remove('hidden');
    } else {
        medicationForm.classList.add('hidden');
    }
});

// Toggle injury form
document.getElementById('injuryCheckbox').addEventListener('change', function() {
    const injuryForm = document.getElementById('injuryForm');
    if (this.checked) {
        injuryForm.classList.remove('hidden');
    } else {
        injuryForm.classList.add('hidden');
    }
});

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
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        if (notification.parentNode) {
            closeNotification(notification.querySelector('.close-btn'));
        }
    }, 5000);
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
    
    // Initialize medical history form behavior
    initializeMedicalHistoryForm();
    
    // Initialize edit form behavior
    initializeEditForm();
});

function initializeMedicalHistoryForm() {
    // Surgery status toggle
    const surgeryRadios = document.querySelectorAll('input[name="surgery_status"]');
    const surgeryDetails = document.getElementById('surgery_details');
    
    surgeryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                surgeryDetails.classList.remove('hidden');
            } else {
                surgeryDetails.classList.add('hidden');
            }
        });
    });
    
    // COVID positive toggle
    const covidRadios = document.querySelectorAll('input[name="covid_positive"]');
    const covidDetails = document.getElementById('covid_details');
    
    covidRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                covidDetails.classList.remove('hidden');
            } else {
                covidDetails.classList.add('hidden');
            }
        });
    });
}


function closeEditPatientModal() {
    document.getElementById('editPatientModal').classList.add('hidden');
    document.getElementById('editPatientModal').classList.remove('flex');
}

// Dynamic field visibility for edit form (same as registration forms)
function initializeEditForm() {
    const levelSelect = document.getElementById('levelSelect');
    if (!levelSelect) return;
    
    const yearGradeField = document.getElementById('yearGradeField');
    const yearGradeInput = document.getElementById('yearGradeInput');
    const yearGradeLabel = document.getElementById('yearGradeLabel');
    const courseField = document.getElementById('courseField');
    const strandField = document.getElementById('strandField');
    const sectionField = document.getElementById('sectionField');
    
    function updateFields() {
        const level = levelSelect.value;
        
        // Hide all conditional fields first
        yearGradeField.classList.add('hidden');
        courseField.classList.add('hidden');
        strandField.classList.add('hidden');
        sectionField.classList.add('hidden');
        
        // Clear and reset year/grade options
        yearGradeInput.innerHTML = '<option value="">Select Grade/Year</option>';
        
        if (level === 'Elementary') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade';
            for (let i = 1; i <= 6; i++) {
                const option = document.createElement('option');
                option.value = `Grade ${i}`;
                option.textContent = `Grade ${i}`;
                yearGradeInput.appendChild(option);
            }
        } else if (level === 'High School') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade';
            for (let i = 7; i <= 10; i++) {
                const option = document.createElement('option');
                option.value = `Grade ${i}`;
                option.textContent = `Grade ${i}`;
                yearGradeInput.appendChild(option);
            }
        } else if (level === 'Senior High School') {
            yearGradeField.classList.remove('hidden');
            strandField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade';
            for (let i = 11; i <= 12; i++) {
                const option = document.createElement('option');
                option.value = `Grade ${i}`;
                option.textContent = `Grade ${i}`;
                yearGradeInput.appendChild(option);
            }
        } else if (level === 'College') {
            yearGradeField.classList.remove('hidden');
            courseField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Year';
            for (let i = 1; i <= 5; i++) {
                const option = document.createElement('option');
                option.value = `${i}st Year`;
                option.textContent = `${i}st Year`;
                if (i === 2) option.textContent = '2nd Year';
                if (i === 3) option.textContent = '3rd Year';
                if (i === 4) option.textContent = '4th Year';
                if (i === 5) option.textContent = '5th Year (Irregular)';
                yearGradeInput.appendChild(option);
            }
        }
        
        // Set existing value if available
        const existingValue = yearGradeInput.getAttribute('data-existing-value');
        if (existingValue && existingValue !== 'N/A') {
            yearGradeInput.value = existingValue;
        }
    }
    
    levelSelect.addEventListener('change', updateFields);
    
    // Initialize on page load
    updateFields();
}

// Initialize edit form when modal opens
function editPatientInfo() {
    const modal = document.getElementById('editPatientModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Re-initialize the form when modal opens
    setTimeout(() => {
        initializeEditForm();
        
        // Set existing values for year/grade field
        const levelSelect = document.getElementById('levelSelect');
        const yearGradeInput = document.getElementById('yearGradeInput');
        const existingValue = yearGradeInput.getAttribute('data-existing-value');
        
        if (levelSelect && yearGradeInput && existingValue) {
            // Trigger the field update first
            levelSelect.dispatchEvent(new Event('change'));
            
            // Then set the value
            setTimeout(() => {
                yearGradeInput.value = existingValue;
            }, 50);
        }
    }, 100);
}

    // Contact number management functions
    function addContactNumber() {
        const container = document.getElementById('contactNumbersContainer');
        const contactItem = document.createElement('div');
        contactItem.className = 'contact-number-item flex items-center space-x-2 mb-2';
        
        contactItem.innerHTML = `
            <input type="text" name="contacts[]" value="" placeholder="09xxxxxxxxx" class="flex-1 rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
            <button type="button" onclick="removeContactNumber(this)" class="p-2 text-red-500 hover:text-red-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        `;
        
        container.appendChild(contactItem);
    }

    function removeContactNumber(button) {
        const contactItem = button.closest('.contact-number-item');
        contactItem.remove();
    }

    // Date formatting function
    function formatDateInput(input) {
        let value = input.value.replace(/\D/g, ''); // Remove non-digits
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        if (value.length >= 5) {
            value = value.substring(0, 5) + '/' + value.substring(5, 9);
        }
        
        input.value = value;
    }

    // Number key validation for date input
    function isNumberKey(evt) {
        const charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
