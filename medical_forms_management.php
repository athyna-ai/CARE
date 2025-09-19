<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$patientId = (int)($_GET['patient_id'] ?? 0);
$patientType = $_GET['patient_type'] ?? '';

if (!$patientId || !in_array($patientType, ['student', 'faculty'])) {
    header('Location: dashboard.php');
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
        header('Location: dashboard.php');
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
include __DIR__ . '/partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

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
                                            <?= str_replace('_', ' ', $record['form_type']) ?>
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
                                    <button onclick="deleteMedicalRecord(<?= $record['id'] ?>)" class="group/btn w-10 h-10 rounded-xl bg-red-100 hover:bg-red-200 text-red-600 transition-all duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 group-hover/btn:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                General Medical Form
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
    if (type === 'medical_history') {
        window.location.href = `patient_view.php?id=<?= $patientId ?>&type=<?= $patientType ?>&open_history=true`;
    } else {
        window.location.href = `patient_view.php?id=<?= $patientId ?>&type=<?= $patientType ?>&form_type=${type}`;
    }
}

function viewMedicalRecord(recordId) {
    window.location.href = `medical_record_view.php?id=${recordId}`;
}

function editMedicalRecord(recordId) {
    window.location.href = `edit_medical_record.php?id=${recordId}`;
}

function deleteMedicalRecord(recordId) {
    if (confirm('Are you sure you want to delete this medical record? This action cannot be undone.')) {
        window.location.href = `delete_medical_record.php?id=${recordId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`;
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

<?php include __DIR__ . '/partials/footer.php'; ?>
