<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Include security breach detection
require_once __DIR__ . '/../security_breach_detector.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    logSecurityBreach('UNAUTHORIZED_MEDICAL_ACCESS', 'Unauthorized access attempt to medical forms', [
        'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    header('Location: ../auth/login.php');
    exit;
}

$patientId = (int)($_GET['patient_id'] ?? 0);
$patientType = $_GET['patient_type'] ?? '';

if (!$patientId || !in_array($patientType, ['student', 'faculty'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get patient information
    $tableName = $patientType === 'student' ? 'students' : 'faculty';
    $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header('Location: ../admin/dashboard.php');
        exit;
    }
    
    // Get medical records for this patient
    $stmt = $pdo->prepare('
        SELECT mr.*, u.name as created_by_name 
        FROM medical_records mr 
        LEFT JOIN users u ON mr.created_by = u.id 
        WHERE mr.patient_id = ? AND mr.patient_type = ? 
        ORDER BY mr.created_at DESC
    ');
    $stmt->execute([$patientId, $patientType]);
    $medicalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading medical forms: " . $e->getMessage());
    $medicalRecords = [];
}

$pageTitle = 'Medical Forms Management';
$showTopNav = true;
$showSidebar = false;
include __DIR__ . '/../partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-20 right-4 z-50"></div>

<div class="h-screen w-full bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla overflow-hidden">
    <!-- Header -->
    <div class="bg-white/95 backdrop-blur-md shadow-xl border-b border-clinic-tea/20">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="patient_view.php?id=<?= $patientId ?>&type=<?= $patientType ?>" class="group inline-flex items-center gap-2 px-4 py-2 bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-200 font-poppins font-medium rounded-xl">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Patient View
                </a>
            </div>
            
            <!-- Patient Info -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-comfortaa font-bold text-clinic-dark">Medical Forms Management</h1>
                    <p class="text-clinic-dark/60 mt-2 font-poppins text-lg">Patient: <?= htmlspecialchars($patient['name']) ?> (<?= ucfirst($patientType) ?>)</p>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="openMedicalFormModal()" class="group px-6 py-3 bg-clinic-blue/20 border border-clinic-blue/30 text-clinic-blue rounded-2xl hover:bg-clinic-blue/30 hover:border-clinic-blue/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-blue/30 flex items-center justify-center group-hover:bg-clinic-blue/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        Add New Form
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8 h-full overflow-y-auto">
        <?php if (empty($medicalRecords)): ?>
            <div class="text-center py-16">
                <div class="w-24 h-24 rounded-3xl bg-clinic-ivory/60 mx-auto mb-6 flex items-center justify-center">
                    <div class="text-5xl">📋</div>
                </div>
                <h3 class="text-2xl font-comfortaa font-bold text-clinic-dark mb-3">No Medical Forms Found</h3>
                <p class="text-clinic-dark/60 font-poppins text-lg mb-8">This patient doesn't have any medical forms yet.</p>
                <button onclick="openMedicalFormModal()" class="group px-8 py-4 bg-clinic-blue/20 border border-clinic-blue/30 text-clinic-blue rounded-2xl hover:bg-clinic-blue/30 hover:border-clinic-blue/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium mx-auto">
                    <div class="w-8 h-8 rounded-xl bg-clinic-blue/30 flex items-center justify-center group-hover:bg-clinic-blue/40 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    Create First Form
                </button>
            </div>
        <?php else: ?>
            <div class="max-h-[calc(100vh-12rem)] overflow-y-auto pr-2">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                <?php foreach ($medicalRecords as $record): ?>
                    <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl border border-clinic-tea/20 overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center
                                        <?= $record['form_type'] === 'medical_history' ? 'bg-clinic-blue/20' : 
                                           ($record['form_type'] === 'athlete' ? 'bg-clinic-vanilla/40' : 
                                           ($record['form_type'] === 'general' ? 'bg-clinic-tea/20' : 'bg-red-100')) ?>">
                                        <span class="text-2xl">
                                            <?= $record['form_type'] === 'medical_history' ? '📋' : 
                                               ($record['form_type'] === 'athlete' ? '🏃' : 
                                               ($record['form_type'] === 'general' ? '🏥' : '🚨')) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="font-comfortaa font-bold text-clinic-dark capitalize text-lg">
                                            <?= $record['form_type'] === 'general' ? 'General CheckUp' : str_replace('_', ' ', $record['form_type']) ?>
                                        </h3>
                                        <p class="text-sm font-poppins text-clinic-dark/60">
                                            <?= htmlspecialchars($record['created_by_name'] ?? 'Unknown') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="viewMedicalRecord(<?= $record['id'] ?>)" class="group/btn w-10 h-10 rounded-xl bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 group-hover/btn:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="archiveMedicalRecord(<?= $record['id'] ?>)" class="group/btn w-10 h-10 rounded-xl bg-orange-100 hover:bg-orange-200 text-orange-600 transition-all duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 group-hover/btn:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l4 4-4 4m5-4h6m-6 0V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V8"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-poppins text-clinic-dark/60">Created:</span>
                                    <span class="text-sm font-poppins font-medium text-clinic-dark"><?= date('M j, Y g:i A', strtotime($record['created_at'])) ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-poppins text-clinic-dark/60">Form ID:</span>
                                    <span class="px-2 py-1 bg-clinic-blue/10 text-clinic-blue text-xs font-poppins font-medium rounded-lg">#<?= $record['id'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Medical Form Type Selection Modal -->
<div id="medicalFormModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-clinic-dark/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-4xl mx-auto bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-clinic-tea/20 p-8">
        <h2 class="text-2xl font-comfortaa font-bold text-clinic-dark mb-6 text-center">Select Medical Form Type</h2>
        <div class="grid grid-cols-1 gap-4">
            <button onclick="createMedicalForm('medical_history')" class="group px-6 py-4 rounded-2xl bg-clinic-blue/20 border border-clinic-blue/30 text-clinic-blue hover:bg-clinic-blue/30 hover:border-clinic-blue/50 hover:shadow-lg transition-all duration-300 flex items-center gap-4 font-poppins font-medium">
                <div class="w-10 h-10 rounded-xl bg-clinic-blue/30 flex items-center justify-center group-hover:bg-clinic-blue/40 transition-colors duration-200">
                    <span class="text-lg">📋</span>
                </div>
                Medical History Form
            </button>
            <button onclick="createMedicalForm('athlete')" class="group px-6 py-4 rounded-2xl bg-clinic-vanilla/40 border border-clinic-vanilla/50 text-clinic-dark hover:bg-clinic-vanilla/60 hover:border-clinic-vanilla/70 hover:shadow-lg transition-all duration-300 flex items-center gap-4 font-poppins font-medium">
                <div class="w-10 h-10 rounded-xl bg-clinic-vanilla/60 flex items-center justify-center group-hover:bg-clinic-vanilla/80 transition-colors duration-200">
                    <span class="text-lg">🏃</span>
                </div>
                Athlete Medical Form
            </button>
            <button onclick="createMedicalForm('general')" class="group px-6 py-4 rounded-2xl bg-clinic-tea/20 border border-clinic-tea/30 text-clinic-blue hover:bg-clinic-tea/30 hover:border-clinic-tea/50 hover:shadow-lg transition-all duration-300 flex items-center gap-4 font-poppins font-medium">
                <div class="w-10 h-10 rounded-xl bg-clinic-tea/30 flex items-center justify-center group-hover:bg-clinic-tea/40 transition-colors duration-200">
                    <span class="text-lg">🏥</span>
                </div>
                General CheckUp
            </button>
            <button onclick="createMedicalForm('emergency')" class="group px-6 py-4 rounded-2xl bg-red-100 border border-red-200 text-red-700 hover:bg-red-200 hover:border-red-300 hover:shadow-lg transition-all duration-300 flex items-center gap-4 font-poppins font-medium">
                <div class="w-10 h-10 rounded-xl bg-red-200 flex items-center justify-center group-hover:bg-red-300 transition-colors duration-200">
                    <span class="text-lg">🚨</span>
                </div>
                Emergency Medical Form
            </button>
        </div>
        <div class="mt-8 flex justify-end">
            <button onclick="closeMedicalFormModal()" class="group px-6 py-3 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-2xl hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-2 font-poppins font-medium">
                <div class="w-6 h-6 rounded-lg bg-clinic-dark/20 flex items-center justify-center group-hover:bg-clinic-dark/30 transition-colors duration-200">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function openMedicalFormModal() {
    document.getElementById('medicalFormModal').classList.remove('hidden');
    document.getElementById('medicalFormModal').classList.add('flex');
}

function closeMedicalFormModal() {
    document.getElementById('medicalFormModal').classList.add('hidden');
    document.getElementById('medicalFormModal').classList.remove('flex');
}

function createMedicalForm(type) {
    // Close the selection modal first
    closeMedicalFormModal();
    
    // Open the appropriate form modal based on type
    if (type === 'medical_history') {
        openMedicalHistoryModal();
    } else if (type === 'general') {
        openGeneralCheckUpModal();
    } else if (type === 'athlete') {
        openAthleteModal();
    } else if (type === 'emergency') {
        openEmergencyModal();
    }
}

function viewMedicalRecord(recordId) {
    window.location.href = `medical_record_view.php?id=${recordId}`;
}

function editMedicalRecord(recordId) {
    window.location.href = `../medical/edit_medical_record.php?id=${recordId}`;
}

function archiveMedicalRecord(recordId) {
    console.log('archiveMedicalRecord called with ID:', recordId);
    
    if (confirm('Are you sure you want to archive this medical record? It will be moved to archived records.')) {
        console.log('User confirmed archiving');
        
        // Show loading notification
        showNotification('Archiving medical record...', 'info');
        
        // Show loading on button
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Archiving...';
        button.disabled = true;
        
        // Archive medical record using the same system as visitation logs
        fetch('../admin/archive_medical_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${recordId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload the page
                showNotification('Medical record archived successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Show error message
                showNotification('Error archiving medical record: ' + data.message, 'error');
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error archiving medical record:', error);
            showNotification('Error archiving medical record. Please try again.', 'error');
            button.textContent = originalText;
            button.disabled = false;
        });
    } else {
        console.log('User cancelled archiving');
    }
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
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
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
</script>

<!-- General CheckUp Form Modal -->
<div id="generalCheckUpModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-lg text-slate-800">General CheckUp Form</h2>
            <button onclick="closeGeneralCheckUpModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="generalCheckUpForm" method="POST" action="../medical/save_medical_history.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            <input type="hidden" name="form_type" value="general_checkup">
            <!-- Hidden inputs for status values -->
            <input type="hidden" name="bmi_status" id="bmiStatusValue">
            <input type="hidden" name="heart_rate_status" id="heartRateStatusValue">
            <input type="hidden" name="temperature_status" id="temperatureStatusValue">
            <input type="hidden" name="blood_pressure_status" id="bloodPressureStatusValue">
            
            <div class="space-y-6">
                <!-- Physical Measurements -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Physical Measurements</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Height (cm) *</label>
                            <input type="number" name="height" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="calculateGeneralBMI()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Weight (kg) *</label>
                            <input type="number" name="weight" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="calculateGeneralBMI()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">BMI</label>
                            <input type="text" name="bmi" id="generalBMI" readonly class="w-full rounded-lg border border-slate-300 px-4 py-3 bg-slate-50">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">BMI Status</label>
                        <div id="bmiStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                            N/A
                        </div>
                    </div>
                </div>
                
                <!-- Vital Signs -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Vital Signs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" id="heartRate" placeholder="e.g., 70" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="checkHeartRateStatus()" onblur="checkHeartRateStatus()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                            <input type="number" name="temperature" id="temperature" step="0.1" placeholder="e.g., 37.0" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="checkTemperatureStatus()" onblur="checkTemperatureStatus()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure</label>
                            <input type="text" name="blood_pressure" id="bloodPressure" placeholder="e.g., 120/80" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="checkBloodPressureStatus()" onblur="checkBloodPressureStatus()" oninput="formatBloodPressure(this)">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate Status</label>
                            <div id="heartRateStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                N/A
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Temperature Status</label>
                            <div id="temperatureStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                N/A
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure Status</label>
                            <div id="bloodPressureStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                N/A
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Assessment and Plan -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Medical Assessment & Recommendations</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Assessment & Plan *</label>
                        <textarea name="assessment_plan" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Medical findings, diagnosis, treatment recommendations, follow-up instructions..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeGeneralCheckUpModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button id="generalCheckupSubmitBtn" type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors flex items-center gap-2">
                    <span id="generalCheckupText">Save General CheckUp</span>
                    <div id="generalCheckupSpinner" class="hidden">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Medical History Form Modal -->
<div id="medicalHistoryModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-3xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 max-h-[calc(100vh-16rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Medical History Form</h2>
            <button onclick="closeMedicalHistoryModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" action="../medical/save_medical_history.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            <input type="hidden" name="form_type" value="medical_history">
            
            <div class="space-y-6">
                <!-- Ongoing Medical Conditions -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Ongoing Medical Conditions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="error_of_refraction" class="mr-2">
                            <span class="text-sm text-slate-700">Error of Refraction</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="asthma" class="mr-2">
                            <span class="text-sm text-slate-700">Asthma</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="seizure" class="mr-2">
                            <span class="text-sm text-slate-700">Seizure</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="heart_problem" class="mr-2">
                            <span class="text-sm text-slate-700">Heart Problem</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="anemia" class="mr-2">
                            <span class="text-sm text-slate-700">Anemia</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="bleeding_disorder" class="mr-2">
                            <span class="text-sm text-slate-700">Bleeding Disorder</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="hernia" class="mr-2">
                            <span class="text-sm text-slate-700">Hernia</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="medical_conditions[]" value="others" class="mr-2">
                            <span class="text-sm text-slate-700">Others</span>
                        </label>
                    </div>
                    <div id="other_medical_conditions_div" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other conditions:</label>
                        <textarea name="other_medical_conditions" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other ongoing medical conditions..."></textarea>
                    </div>
                </div>

                <!-- Surgery/Hospitalization -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Surgery/Hospitalization</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Have you ever had surgery/hospitalization?</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="surgery_status" value="no" class="mr-2" checked>
                                    <span class="text-sm text-slate-700">No</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="surgery_status" value="yes" class="mr-2">
                                    <span class="text-sm text-slate-700">Yes</span>
                                </label>
                            </div>
                        </div>
                        <div id="surgery_details" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please specify:</label>
                            <textarea name="surgery_details" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify surgery/hospitalization details..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Family Medical History -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Family Medical History</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="tuberculosis" class="mr-2">
                            <span class="text-sm text-slate-700">Tuberculosis</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="cancer" class="mr-2">
                            <span class="text-sm text-slate-700">Cancer</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="diabetes" class="mr-2">
                            <span class="text-sm text-slate-700">Diabetes</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="hypertension" class="mr-2">
                            <span class="text-sm text-slate-700">Hypertension</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="depression" class="mr-2">
                            <span class="text-sm text-slate-700">Depression</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="family_conditions[]" value="others" class="mr-2">
                            <span class="text-sm text-slate-700">Others</span>
                        </label>
                    </div>
                    <div id="other_family_conditions_div" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other family conditions:</label>
                        <textarea name="other_family_conditions" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other family medical conditions..."></textarea>
                    </div>
                </div>

                <!-- Exposure to Cigarette/Vape Smoke -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Exposure to Cigarette/Vape Smoke at Home</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="smoke_exposure" value="yes" class="mr-2">
                            <span class="text-sm text-slate-700">Yes</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="smoke_exposure" value="no" class="mr-2" checked>
                            <span class="text-sm text-slate-700">No</span>
                        </label>
                    </div>
                </div>

                <!-- Immunization Received -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Immunization Received</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="mmr" class="mr-2">
                            <span class="text-sm text-slate-700">MMR (Measles, Mumps, Rubella)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="dpt" class="mr-2">
                            <span class="text-sm text-slate-700">DPT (Diphtheria, Pertussis, Tetanus)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="bcg" class="mr-2">
                            <span class="text-sm text-slate-700">BCG (Bacillus Calmette-Guérin)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="chicken_pox" class="mr-2">
                            <span class="text-sm text-slate-700">Chicken Pox (Varicella)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="hepatitis_b" class="mr-2">
                            <span class="text-sm text-slate-700">Hepatitis B</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="immunizations[]" value="polio" class="mr-2">
                            <span class="text-sm text-slate-700">Polio</span>
                        </label>
                    </div>
                </div>

                <!-- COVID-19 Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">COVID-19 Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Have you ever had COVID-19?</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="covid_infection" value="no" class="mr-2" checked>
                                    <span class="text-sm text-slate-700">No</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="covid_infection" value="yes" class="mr-2">
                                    <span class="text-sm text-slate-700">Yes</span>
                                </label>
                            </div>
                        </div>
                        
                        <div id="covid_infection_details" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please specify when and details:</label>
                            <textarea name="covid_infection_details" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify when you had COVID-19, severity, treatment received..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- COVID-19 Vaccine Details -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">COVID-19 Vaccine Details</h3>
                    
                    <!-- Vaccine Brands -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-slate-700 mb-3">Vaccine Brand</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="pfizer" class="mr-2">
                                <span class="text-sm text-slate-700">Pfizer</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="moderna" class="mr-2">
                                <span class="text-sm text-slate-700">Moderna</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="astrazeneca" class="mr-2">
                                <span class="text-sm text-slate-700">AstraZeneca</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="janssen" class="mr-2">
                                <span class="text-sm text-slate-700">Janssen</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="sinovac" class="mr-2">
                                <span class="text-sm text-slate-700">Sinovac</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="sinopharm" class="mr-2">
                                <span class="text-sm text-slate-700">Sinopharm</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="sputnik" class="mr-2">
                                <span class="text-sm text-slate-700">Sputnik V</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine_brand[]" value="others" class="mr-2">
                                <span class="text-sm text-slate-700">Others</span>
                            </label>
                        </div>
                        <div id="other_vaccine_brand_div" class="hidden mt-3">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other vaccine brand:</label>
                            <textarea name="other_vaccine_brand" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other COVID-19 vaccine brand..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Vaccine Doses -->
                    <div>
                        <h4 class="text-lg font-medium text-slate-700 mb-3">Vaccine Doses</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="first_dose" class="mr-2">
                                <span class="text-sm text-slate-700">First Dose</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="second_dose" class="mr-2">
                                <span class="text-sm text-slate-700">Second Dose</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="booster_1" class="mr-2">
                                <span class="text-sm text-slate-700">Booster 1</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="booster_2" class="mr-2">
                                <span class="text-sm text-slate-700">Booster 2</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="booster_3" class="mr-2">
                                <span class="text-sm text-slate-700">Booster 3</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="covid_vaccine[]" value="annual_booster" class="mr-2">
                                <span class="text-sm text-slate-700">Annual Booster</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeMedicalHistoryModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button id="medicalHistorySubmitBtn" type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors flex items-center gap-2">
                    <span id="medicalHistoryText">Save Medical History</span>
                    <div id="medicalHistorySpinner" class="hidden">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMedicalHistoryModal() {
    document.getElementById('medicalHistoryModal').classList.remove('hidden');
    document.getElementById('medicalHistoryModal').classList.add('flex');
}

function openAthleteModal() {
    document.getElementById('athleteModal').classList.remove('hidden');
    document.getElementById('athleteModal').classList.add('flex');
}

function openEmergencyModal() {
    document.getElementById('emergencyModal').classList.remove('hidden');
    document.getElementById('emergencyModal').classList.add('flex');
}

function closeMedicalHistoryModal() {
    document.getElementById('medicalHistoryModal').classList.add('hidden');
    document.getElementById('medicalHistoryModal').classList.remove('flex');
}

function closeAthleteModal() {
    document.getElementById('athleteModal').classList.add('hidden');
    document.getElementById('athleteModal').classList.remove('flex');
}

function closeEmergencyModal() {
    document.getElementById('emergencyModal').classList.add('hidden');
    document.getElementById('emergencyModal').classList.remove('flex');
}

// Medical History Form Handlers
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Medical conditions "Others" toggle
    const medicalConditionsOthers = document.querySelector('input[name="medical_conditions[]"][value="others"]');
    const otherMedicalConditionsDiv = document.getElementById('other_medical_conditions_div');
    
    if (medicalConditionsOthers) {
        medicalConditionsOthers.addEventListener('change', function() {
            if (this.checked) {
                otherMedicalConditionsDiv.classList.remove('hidden');
            } else {
                otherMedicalConditionsDiv.classList.add('hidden');
            }
        });
    }
    
    // Family conditions "Others" toggle
    const familyConditionsOthers = document.querySelector('input[name="family_conditions[]"][value="others"]');
    const otherFamilyConditionsDiv = document.getElementById('other_family_conditions_div');
    
    if (familyConditionsOthers) {
        familyConditionsOthers.addEventListener('change', function() {
            if (this.checked) {
                otherFamilyConditionsDiv.classList.remove('hidden');
            } else {
                otherFamilyConditionsDiv.classList.add('hidden');
            }
        });
    }
    
            // COVID infection toggle
            const covidInfectionRadios = document.querySelectorAll('input[name="covid_infection"]');
            const covidInfectionDetails = document.getElementById('covid_infection_details');
            
            covidInfectionRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'yes') {
                        covidInfectionDetails.classList.remove('hidden');
                    } else {
                        covidInfectionDetails.classList.add('hidden');
                    }
                });
            });
            
            // COVID vaccine brand "Others" toggle
            const covidVaccineBrandOthers = document.querySelector('input[name="covid_vaccine_brand[]"][value="others"]');
            const otherVaccineBrandDiv = document.getElementById('other_vaccine_brand_div');
            
            if (covidVaccineBrandOthers) {
                covidVaccineBrandOthers.addEventListener('change', function() {
                    if (this.checked) {
                        otherVaccineBrandDiv.classList.remove('hidden');
                    } else {
                        otherVaccineBrandDiv.classList.add('hidden');
                    }
                });
            }
});

// General CheckUp Modal Functions
function openGeneralCheckUpModal() {
    document.getElementById('generalCheckUpModal').classList.remove('hidden');
    document.getElementById('generalCheckUpModal').classList.add('flex');
    
    // Initialize status values to N/A
    document.getElementById('bmiStatusValue').value = 'N/A';
    document.getElementById('heartRateStatusValue').value = 'N/A';
    document.getElementById('temperatureStatusValue').value = 'N/A';
    document.getElementById('bloodPressureStatusValue').value = 'N/A';
}

function closeGeneralCheckUpModal() {
    document.getElementById('generalCheckUpModal').classList.add('hidden');
    document.getElementById('generalCheckUpModal').classList.remove('flex');
}

function calculateGeneralBMI() {
    const height = parseFloat(document.querySelector('#generalCheckUpModal input[name="height"]').value);
    const weight = parseFloat(document.querySelector('#generalCheckUpModal input[name="weight"]').value);
    
    const bmiField = document.getElementById('generalBMI');
    const bmiStatusDisplay = document.getElementById('bmiStatus');
    const bmiStatusValue = document.getElementById('bmiStatusValue');
    
    if (bmiField && bmiStatusDisplay && bmiStatusValue) {
        if (!height || !weight || height <= 0 || weight <= 0) {
            // Empty or invalid values - set to N/A
            bmiField.value = '';
            bmiStatusDisplay.textContent = 'N/A';
            bmiStatusDisplay.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600';
            bmiStatusValue.value = 'N/A';
        } else {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            
            // Update BMI field
            bmiField.value = bmi.toFixed(1);
            
            // Update BMI status display and hidden input
            let statusValue = '';
            let statusClass = '';
            if (bmi < 18.5) {
                statusValue = 'Underweight';
                statusClass = 'bg-yellow-100 text-yellow-800';
            } else if (bmi >= 18.5 && bmi < 25) {
                statusValue = 'Normal';
                statusClass = 'bg-green-100 text-green-800';
            } else if (bmi >= 25 && bmi < 30) {
                statusValue = 'Overweight';
                statusClass = 'bg-orange-100 text-orange-800';
            } else {
                statusValue = 'Obese';
                statusClass = 'bg-red-100 text-red-800';
            }
            bmiStatusDisplay.textContent = statusValue;
            bmiStatusDisplay.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
            bmiStatusValue.value = statusValue;
        }
    }
}

// Automatic vital signs status calculation functions
function checkHeartRateStatus() {
    const heartRate = parseFloat(document.getElementById('heartRate').value);
    const statusDisplay = document.getElementById('heartRateStatus');
    const statusValue = document.getElementById('heartRateStatusValue');
    
    if (statusDisplay && statusValue) {
        if (!heartRate || heartRate === 0) {
            // Empty or zero value - set to N/A
            statusDisplay.textContent = 'N/A';
            statusDisplay.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600';
            statusValue.value = 'N/A';
        } else {
            let status = '';
            let statusClass = '';
            if (heartRate >= 60 && heartRate <= 100) {
                status = 'Normal';
                statusClass = 'bg-green-100 text-green-800';
            } else if (heartRate < 60) {
                status = 'Low';
                statusClass = 'bg-yellow-100 text-yellow-800';
            } else {
                status = 'Elevated';
                statusClass = 'bg-red-100 text-red-800';
            }
            statusDisplay.textContent = status;
            statusDisplay.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
            statusValue.value = status;
        }
    }
}

function checkTemperatureStatus() {
    const temperature = parseFloat(document.getElementById('temperature').value);
    const statusDisplay = document.getElementById('temperatureStatus');
    const statusValue = document.getElementById('temperatureStatusValue');
    
    if (statusDisplay && statusValue) {
        if (!temperature || temperature === 0) {
            // Empty or zero value - set to N/A
            statusDisplay.textContent = 'N/A';
            statusDisplay.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600';
            statusValue.value = 'N/A';
        } else {
            let status = '';
            let statusClass = '';
            if (temperature >= 36.1 && temperature <= 37.2) {
                status = 'Normal';
                statusClass = 'bg-green-100 text-green-800';
            } else if (temperature > 37.2) {
                status = 'Fever';
                statusClass = 'bg-red-100 text-red-800';
            } else {
                status = 'Hypothermia';
                statusClass = 'bg-blue-100 text-blue-800';
            }
            statusDisplay.textContent = status;
            statusDisplay.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
            statusValue.value = status;
        }
    }
}

function checkBloodPressureStatus() {
    const bloodPressure = document.getElementById('bloodPressure').value;
    const statusDisplay = document.getElementById('bloodPressureStatus');
    const statusValue = document.getElementById('bloodPressureStatusValue');
    
    if (statusDisplay && statusValue) {
        if (!bloodPressure || bloodPressure.trim() === '') {
            // Empty value - set to N/A
            statusDisplay.textContent = 'N/A';
            statusDisplay.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600';
            statusValue.value = 'N/A';
        } else {
            // Parse blood pressure (e.g., "120/80")
            const parts = bloodPressure.split('/');
            if (parts.length === 2) {
                const systolic = parseFloat(parts[0]);
                const diastolic = parseFloat(parts[1]);
                
                let status = '';
                let statusClass = '';
                if (systolic < 90 || diastolic < 60) {
                    status = 'Low';
                    statusClass = 'bg-yellow-100 text-yellow-800';
                } else if (systolic >= 90 && systolic <= 120 && diastolic >= 60 && diastolic <= 80) {
                    status = 'Normal';
                    statusClass = 'bg-green-100 text-green-800';
                } else if (systolic > 120 || diastolic > 80) {
                    status = 'High';
                    statusClass = 'bg-red-100 text-red-800';
                } else {
                    status = 'Normal';
                    statusClass = 'bg-green-100 text-green-800';
                }
                statusDisplay.textContent = status;
                statusDisplay.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
                statusValue.value = status;
            } else {
                // Invalid format - set to N/A
                statusDisplay.textContent = 'N/A';
                statusDisplay.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600';
                statusValue.value = 'N/A';
            }
        }
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;
    
    // Set background color based on type
    if (type === 'success') {
        notification.classList.add('bg-green-500', 'text-white');
    } else if (type === 'error') {
        notification.classList.add('bg-red-500', 'text-white');
    } else {
        notification.classList.add('bg-blue-500', 'text-white');
    }
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

// Handle URL parameters for notifications
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

// Form submission handlers with loading spinners
document.addEventListener('DOMContentLoaded', function() {
    // General CheckUp form
    const generalCheckupForm = document.querySelector('#generalCheckUpModal form');
    const generalCheckupBtn = document.getElementById('generalCheckupSubmitBtn');
    const generalCheckupText = document.getElementById('generalCheckupText');
    const generalCheckupSpinner = document.getElementById('generalCheckupSpinner');
    
    if (generalCheckupForm && generalCheckupBtn) {
        generalCheckupForm.addEventListener('submit', function(e) {
            // Show loading spinner
            generalCheckupBtn.disabled = true;
            generalCheckupText.textContent = 'Saving...';
            generalCheckupSpinner.classList.remove('hidden');
        });
    }
    
    // Medical History form
    const medicalHistoryForm = document.querySelector('#medicalHistoryModal form');
    const medicalHistoryBtn = document.getElementById('medicalHistorySubmitBtn');
    const medicalHistoryText = document.getElementById('medicalHistoryText');
    const medicalHistorySpinner = document.getElementById('medicalHistorySpinner');
    
    if (medicalHistoryForm && medicalHistoryBtn) {
        medicalHistoryForm.addEventListener('submit', function(e) {
            // Show loading spinner
            medicalHistoryBtn.disabled = true;
            medicalHistoryText.textContent = 'Saving...';
            medicalHistorySpinner.classList.remove('hidden');
        });
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
