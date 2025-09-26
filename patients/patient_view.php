<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get patient ID and type from URL
$patientId = (int)($_GET['id'] ?? 0);
$patientType = sanitize_string($_GET['type'] ?? 'student'); // student or faculty

// Debug: Log the URL parameters
error_log("Patient view - ID: {$patientId}, Type: {$patientType}");

// Handle success/error messages
$message = $_GET['message'] ?? '';
$messageType = $_GET['message_type'] ?? 'info';

if ($patientId <= 0) {
    error_log("Invalid patient ID: {$patientId}, redirecting to dashboard");
    header('Location: ../admin/dashboard.php?error=invalid_patient');
    exit;
}

// Get patient information
$patient = null;
$patientIdInt = (int)$patientId; // Ensure integer conversion
error_log("Patient lookup - ID: $patientId, Type: $patientType, Converted ID: $patientIdInt");

if ($patientType === 'student') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$patientIdInt]);
    $patient = $stmt->fetch();
} else {
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([$patientIdInt]);
    $patient = $stmt->fetch();
}

// For display purposes, we need to get the actual data from the contacts JSON
// The contacts field contains the original data for functionality
if ($patient && !empty($patient['contacts'])) {
    // Check if contacts is already an array or needs to be decoded
    if (is_string($patient['contacts'])) {
        $contacts = json_decode($patient['contacts'], true);
        if (is_array($contacts)) {
            $patient['contacts'] = $contacts;
        }
    }
    // If it's already an array, we can use it directly
}

// Since we're using hashed data in the database, we need to get the actual values for display
// For now, we'll use the contacts field which contains the original data
// In a real implementation, you'd need to decrypt the hashed fields
// For this fix, we'll assume the contacts field contains the original contact data

if (!$patient) {
    error_log("Patient not found - ID: {$patientId}, Type: {$patientType}, Converted ID: {$patientIdInt}, redirecting to dashboard");
    header('Location: ../admin/dashboard.php?error=patient_not_found');
    exit;
} else {
    error_log("Patient found - ID: {$patientId}, Type: {$patientType}, Name: " . ($patient['name'] ?? 'Unknown'));
    // Ensure patient data is valid
    if (empty($patient['name'])) {
        error_log("Patient data is incomplete, redirecting to dashboard");
        header('Location: ../admin/dashboard.php?error=incomplete_patient_data');
        exit;
    }
}


// Get medical history (check if table exists first)
$medicalHistory = [];
try {
    // First check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'medical_records'");
    if ($tableCheck->rowCount() > 0) {
        // Since we now archive medical records during re-enrollment, 
        // we can show all current medical records without filtering
        $medicalStmt = $pdo->prepare('
            SELECT * FROM medical_records 
            WHERE patient_id = ? AND patient_type = ? 
            ORDER BY created_at DESC
        ');
        $medicalStmt->execute([$patientId, $patientType]);
        $medicalHistory = $medicalStmt->fetchAll();
        error_log("Medical history query - Patient ID: {$patientId}, Type: {$patientType}, Count: " . count($medicalHistory));
    } else {
        error_log("Medical records table does not exist");
        $medicalHistory = [];
    }
} catch (Exception $e) {
    // Table doesn't exist or query failed, medical history will be empty
    error_log("Medical history error: " . $e->getMessage());
    $medicalHistory = [];
}

// Get archived medical records for this patient
$archivedMedical = [];
try {
    $archiveTable = $patientType . '_medical_archive';
    $archiveCheck = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'");
    if ($archiveCheck->rowCount() > 0) {
        $archiveStmt = $pdo->prepare("SELECT *, 'archived' as status FROM `{$archiveTable}` WHERE patient_id = ? ORDER BY archived_at DESC");
        $archiveStmt->execute([$patientId]);
        $archivedMedical = $archiveStmt->fetchAll();
        error_log("Archived medical query - Patient ID: {$patientId}, Type: {$patientType}, Count: " . count($archivedMedical));
    }
} catch (Exception $e) {
    error_log("Archived medical history error: " . $e->getMessage());
    $archivedMedical = [];
}

// Combine active and archived medical records
$allMedicalHistory = array_merge($medicalHistory, $archivedMedical);
// Sort by date (most recent first)
usort($allMedicalHistory, function($a, $b) {
    $dateA = isset($a['created_at']) ? $a['created_at'] : $a['archived_at'];
    $dateB = isset($b['created_at']) ? $b['created_at'] : $b['archived_at'];
    return strtotime($dateB) - strtotime($dateA);
});

// Get visitation logs (check if table exists first)
$visitationLogs = [];
try {
    // First check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'visitation_logs'");
    if ($tableCheck->rowCount() > 0) {
        // Get the most recent enrollment date to filter out historical visits
        $enrollmentDate = null;
        try {
            $enrollmentStmt = $pdo->prepare('
                SELECT MAX(created_at) as latest_enrollment 
                FROM enrollment_history 
                WHERE student_id = ?
            ');
            $enrollmentStmt->execute([$patientId]);
            $enrollmentResult = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);
            $enrollmentDate = $enrollmentResult['latest_enrollment'];
        } catch (Exception $e) {
            // If enrollment_history table doesn't exist or query fails, show all logs
            error_log("Enrollment history query failed: " . $e->getMessage());
        }
        
        // Build query based on whether we have an enrollment date
        if ($enrollmentDate) {
            // Only show visits from after the most recent enrollment
            $visitationStmt = $pdo->prepare('
                SELECT * FROM visitation_logs 
                WHERE patient_id = ? AND patient_type = ? 
                AND created_at > ?
                ORDER BY visit_date DESC
            ');
            $visitationStmt->execute([$patientId, $patientType, $enrollmentDate]);
        } else {
            // If no enrollment history, filter by current level context
            // For students without enrollment history, show only recent records (last 6 months)
            // This prevents showing very old records from previous academic years
            $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
            $visitationStmt = $pdo->prepare('
                SELECT * FROM visitation_logs 
                WHERE patient_id = ? AND patient_type = ? 
                AND created_at > ?
                ORDER BY visit_date DESC
            ');
            $visitationStmt->execute([$patientId, $patientType, $sixMonthsAgo]);
        }
        
        $visitationLogs = $visitationStmt->fetchAll();
        error_log("Visitation logs query - Patient ID: {$patientId}, Type: {$patientType}, Enrollment Date: {$enrollmentDate}, Count: " . count($visitationLogs));
    } else {
        error_log("Visitation logs table does not exist");
        $visitationLogs = [];
    }
} catch (Exception $e) {
    // Table doesn't exist or query failed, visitation logs will be empty
    error_log("Visitation logs error: " . $e->getMessage());
    $visitationLogs = [];
}

$pageTitle = 'Patient Information';
$showTopNav = true; // Show top navigation for this page
$showSidebar = false;
include __DIR__ . '/../partials/header.php';
?>

<!-- Notification Container -->
<div id="notificationContainer" class="fixed top-20 right-4 z-50 pointer-events-none"></div>

<style>
html, body {
    margin: 0;
    padding: 0;
}

/* Dismissible notification system */
.notification {
    pointer-events: auto;
    margin-bottom: 0.5rem;
    transform: translateX(100%);
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
    opacity: 0;
    max-width: 400px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #10b981;
    overflow: hidden;
}

.notification.show {
    transform: translateX(0);
    opacity: 1;
}

.notification.hide {
    transform: translateX(100%);
    opacity: 0;
}

.notification.success {
    border-left-color: #10b981;
}

.notification.error {
    border-left-color: #ef4444;
}

.notification.warning {
    border-left-color: #f59e0b;
}

.notification.info {
    border-left-color: #3b82f6;
}

.notification-content {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.notification-message {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    line-height: 1.4;
}

.notification-close {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.notification-close:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Debug modal visibility */
#visitationModal, #generalCheckUpModal {
    z-index: 9999 !important;
}

#visitationModal.flex, #generalCheckUpModal.flex {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

</style>

<script>
// Notification system
function showNotification(message, type = 'success', duration = 2000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    notification.className = `transform transition-all duration-500 ease-out translate-x-full opacity-0 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-6 ${
        type === 'success' ? 'border-l-4 border-l-clinic-tea' : 
        type === 'error' ? 'border-l-4 border-l-red-500' : 
        type === 'warning' ? 'border-l-4 border-l-clinic-vanilla' : 
        'border-l-4 border-l-clinic-blue'
    }`;
    
    // Create content
    notification.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">
                    ${type === 'success' ? '✅' : 
                      type === 'error' ? '❌' : 
                      type === 'warning' ? '⚠️' : 
                      'ℹ️'}
                </div>
            </div>
            <div class="flex-1">
                <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${message}</p>
            </div>
            <button class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Add event listener to close button
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        closeNotification(closeBtn);
    });
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(closeBtn);
        }, duration);
    }
}

function closeNotification(button) {
    // Find the notification container (the outermost div with transform class)
    let notification = button;
    while (notification && !notification.classList.contains('transform')) {
        notification = notification.parentElement;
    }
    
    if (!notification) {
        console.log('Notification not found');
        return;
    }
    
    console.log('Closing notification:', notification.id);
    
    // Animate out
    notification.style.transition = 'all 0.3s ease-in-out';
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    // Remove after animation
    setTimeout(() => {
        if (notification && notification.parentNode) {
            console.log('Removing notification:', notification.id);
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Show notification on page load if there's a message
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('message_type') || 'info';
    const formType = urlParams.get('form_type');
    const openHistory = urlParams.get('open_history');
    
    if (message) {
        showNotification(decodeURIComponent(message), messageType, 8000);
    }
    
    // Handle form type parameter to open specific form popup
    if (formType) {
        if (formType === 'general') {
            openFullScreenMedicalForm('general');
        } else if (formType === 'athlete') {
            openFullScreenMedicalForm('athlete');
        } else if (formType === 'emergency') {
            openFullScreenMedicalForm('emergency');
        }
        
        // Clean up URL by removing form_type parameter
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id') + '&type=' + urlParams.get('type');
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Handle open_history parameter to open medical history modal
    if (openHistory === 'true') {
        openFullScreenMedicalForm('medical_history');
        
        // Clean up URL by removing open_history parameter
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id') + '&type=' + urlParams.get('type');
        window.history.replaceState({}, document.title, newUrl);
    }
    
    
    // Clean up URL if there are message parameters
    if (message) {
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id') + '&type=' + urlParams.get('type');
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Auto-capitalization functions
    function toTitleCase(str) {
        return str.replace(/\w\S*/g, function(txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
    
    function toSentenceCase(str) {
        return str.replace(/(^\w{1}|\.\s*\w{1})/gi, function(txt) {
            return txt.toUpperCase();
        });
    }
    
    // Apply auto-capitalization to form fields
    function setupAutoCapitalization() {
        // Name field - Title Case
        const nameField = document.querySelector('input[name="name"]');
        if (nameField) {
            nameField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Department field - Title Case
        const departmentField = document.querySelector('input[name="department"]');
        if (departmentField) {
            departmentField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Guardian field - Title Case
        const guardianField = document.querySelector('input[name="guardian"]');
        if (guardianField) {
            guardianField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Address field - Sentence Case
        const addressField = document.querySelector('input[name="address"]');
        if (addressField) {
            addressField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
        
        // Religion field - Title Case
        const religionField = document.querySelector('input[name="religion"]');
        if (religionField) {
            religionField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Section field - Title Case
        const sectionField = document.querySelector('input[name="section"]');
        if (sectionField) {
            sectionField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Sport field - Title Case
        const sportField = document.querySelector('input[name="sport"]');
        if (sportField) {
            sportField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Position field - Title Case
        const positionField = document.querySelector('input[name="position"]');
        if (positionField) {
            positionField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Chief complaint field - Sentence Case
        const chiefComplaintField = document.querySelector('input[name="chief_complaint"]');
        if (chiefComplaintField) {
            chiefComplaintField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
        
        // Duration field - Sentence Case
        const durationField = document.querySelector('input[name="duration"]');
        if (durationField) {
            durationField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
        
        // Other reason field - Sentence Case
        const otherReasonField = document.querySelector('input[name="other_reason"]');
        if (otherReasonField) {
            otherReasonField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
        
        // Other medication field - Sentence Case
        const otherMedicationField = document.querySelector('input[name="other_medication"]');
        if (otherMedicationField) {
            otherMedicationField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
        
        // Other first aid field - Sentence Case
        const otherFirstAidField = document.querySelector('input[name="other_first_aid"]');
        if (otherFirstAidField) {
            otherFirstAidField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
    }
    
    // Initialize auto-capitalization
    setupAutoCapitalization();
});

// Add notification functions to global scope for easy access
window.showNotification = showNotification;
window.closeNotification = closeNotification;

</script>

<div class="h-screen w-full bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla flex flex-col overflow-hidden" style="height: calc(100vh - 60px);">
    <!-- Header with Patient Info -->
    <div class="bg-white/95 backdrop-blur-md shadow-xl border-b border-clinic-tea/20 flex-shrink-0">
        <div class="w-full px-6 py-4">
            <!-- Back Button -->
            <div class="mb-4">
                <?php 
                // Always go back to dashboard
                $backUrl = '../admin/dashboard.php';
                ?>
                <a href="<?= $backUrl ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-200 font-poppins font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
            
            
            <!-- Patient Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg flex items-center justify-center">
                            <span class="text-white font-comfortaa font-bold text-lg">
                                <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                            </span>
                        </div>
                        <div>
                            <h1 class="text-3xl font-comfortaa font-bold text-clinic-dark mb-1">
                                <?= htmlspecialchars($patient['name']) ?>
                            </h1>
                            <div class="flex flex-wrap items-center gap-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue font-poppins font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                    </svg>
                                    RFID: <?= htmlspecialchars($patient['rfid']) ?>
                                </span>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue font-poppins font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <?= htmlspecialchars($patient['level'] ?? $patient['department'] ?? 'N/A') ?>
                                </span>
                                <?php if ($patientType === 'faculty' && $patient['sr']): ?>
                                    <span class="px-3 py-1 bg-clinic-vanilla/60 text-clinic-dark text-sm font-poppins font-semibold rounded-xl border border-clinic-vanilla/40">Sr.</span>
                                <?php endif; ?>
                                <?php if ($patientType === 'student'): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl border font-poppins font-medium
                                        <?php
                                        switch($patient['status'] ?? 'Active') {
                                            case 'Active': echo 'bg-green-100 text-green-800 border-green-200'; break;
                                            case 'Graduated': echo 'bg-blue-100 text-blue-800 border-blue-200'; break;
                                            case 'Transferred': echo 'bg-yellow-100 text-yellow-800 border-yellow-200'; break;
                                            case 'Inactive': echo 'bg-gray-100 text-gray-800 border-gray-200'; break;
                                            default: echo 'bg-gray-100 text-gray-800 border-gray-200';
                                        }
                                        ?>
                                    ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Status: <?= htmlspecialchars($patient['status'] ?? 'Active') ?>
                                    </span>
                                    <div class="ml-2 inline-block">
                                        <select onchange="changeStatus(this.value)" class="text-xs px-2 py-1 bg-clinic-blue/10 text-clinic-blue rounded-lg border border-clinic-blue/20 hover:bg-clinic-blue/20 transition-colors duration-200">
                                            <option value="">Change Status</option>
                                            <option value="Active" <?= ($patient['status'] ?? 'Active') === 'Active' ? 'disabled' : '' ?>>Active</option>
                                            <option value="Graduated" <?= ($patient['status'] ?? 'Active') === 'Graduated' ? 'disabled' : '' ?>>Graduated</option>
                                            <option value="Transferred" <?= ($patient['status'] ?? 'Active') === 'Transferred' ? 'disabled' : '' ?>>Transferred</option>
                                            <option value="Inactive" <?= ($patient['status'] ?? 'Active') === 'Inactive' ? 'disabled' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button onclick="viewAllMedicalForms()" class="group px-6 py-3 bg-clinic-tea/20 border border-clinic-tea/30 text-clinic-blue rounded-2xl hover:bg-clinic-tea/30 hover:border-clinic-tea/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-tea/30 flex items-center justify-center group-hover:bg-clinic-tea/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        Medical Forms
                    </button>
                    <?php if ($patientType === 'student'): ?>
                    <a href="enrollment_history.php?id=<?= $patientId ?>" class="group px-6 py-3 bg-purple-100/20 border border-purple-200/30 text-clinic-blue rounded-2xl hover:bg-purple-100/30 hover:border-purple-200/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-purple-100/30 flex items-center justify-center group-hover:bg-purple-100/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        Enrollment History
                    </a>
                    <?php endif; ?>
                    <button onclick="openVisitationModal()" class="group px-6 py-3 bg-clinic-blue/10 border border-clinic-blue/30 text-clinic-blue rounded-2xl hover:bg-clinic-blue/20 hover:border-clinic-blue/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-blue/20 flex items-center justify-center group-hover:bg-clinic-blue/30 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        Add Visitation
                    </button>
                    <a href="patient_archive.php?id=<?= $patientId ?>&type=<?= $patientType ?>" class="group px-6 py-3 bg-clinic-vanilla/20 border border-clinic-vanilla/30 text-clinic-blue rounded-2xl hover:bg-clinic-vanilla/30 hover:border-clinic-vanilla/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-vanilla/30 flex items-center justify-center group-hover:bg-clinic-vanilla/40 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                            </svg>
                        </div>
                        View Archive
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full px-4 py-4 flex-1 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-full">
            <!-- Patient Details Card -->
            <div class="lg:col-span-2">
                <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl border border-clinic-tea/20 overflow-hidden h-full">
                    <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-comfortaa font-bold text-white">Patient Information</h2>
                            <button onclick="editPatientInfo()" class="px-6 py-3 bg-white text-clinic-blue rounded-xl hover:bg-gray-100 transition-all duration-300 flex items-center gap-3 font-bold text-base border-2 border-white shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                EDIT PATIENT
                            </button>
                        </div>
                    </div>
                    <div class="p-3 flex-1 overflow-y-auto">
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <!-- Personal Information -->
                            <div class="bg-clinic-ivory/40 rounded-2xl p-3 border border-clinic-tea/20">
                                <h3 class="text-base font-comfortaa font-bold text-clinic-dark mb-3 flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-lg bg-clinic-blue/20 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    Personal Information
                                </h3>
                                <div class="space-y-1">
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Full Name</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['name']) ?></p>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Level/Department</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['level'] ?? $patient['department'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if ($patientType === 'student'): ?>
                                        <?php if (in_array($patient['level'] ?? '', ['Elementary', 'High School', 'Senior High School', 'College'])): ?>
                                            <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                                <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1"><?= ($patient['level'] ?? '') === 'College' ? 'Year' : 'Grade' ?></span>
                                                <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['year_grade'] ?? 'N/A') ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php 
                                        $level = $patient['level'] ?? '';
                                        if (in_array($level, ['Pre-school', 'Elementary', 'High School'])) {
                                            $fieldLabel = 'Section';
                                            $fieldValue = $patient['section'] ?? 'N/A';
                                        } elseif ($level === 'Senior High School') {
                                            $fieldLabel = 'Strand';
                                            $fieldValue = $patient['strand'] ?? 'N/A';
                                        } elseif ($level === 'College') {
                                            $fieldLabel = 'Course';
                                            $fieldValue = $patient['course'] ?? 'N/A';
                                        } else {
                                            $fieldLabel = 'Section';
                                            $fieldValue = 'N/A';
                                        }
                                        ?>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1"><?= $fieldLabel ?></span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($fieldValue) ?></p>
                                        </div>
                                        <?php if ($level === 'College'): ?>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Block</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['block'] ?? 'N/A') ?></p>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Age</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars((string)($patient['age'] ?? 'N/A')) ?> years old</p>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Date of Birth</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['dob'] ? date('M j, Y', strtotime($patient['dob'])) : 'N/A') ?></p>
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Religion</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['religion'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact & Medical Info -->
                            <div class="bg-clinic-ivory/40 rounded-2xl p-3 border border-clinic-tea/20">
                                <h3 class="text-base font-comfortaa font-bold text-clinic-dark mb-3 flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-lg bg-clinic-vanilla/40 flex items-center justify-center">
                                        <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </div>
                                    Contact & Medical
                                </h3>
                                <div class="space-y-1">
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Address</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['address'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if ($patientType === 'student'): ?>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Guardian/Parent</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['guardian'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($patientType === 'student'): ?>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Parent Contact</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark">
                                                <?php 
                                                // Get parent contact from contacts array
                                                $contacts = $patient['contacts'] ?? [];
                                                $parentContact = '';
                                                if (is_array($contacts) && !empty($contacts)) {
                                                    $parentContact = $contacts[0]; // First contact is usually parent
                                                }
                                                echo htmlspecialchars($parentContact ?: ($patient['emergency_contact'] ?? 'N/A'));
                                                ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                            <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Emergency Contact</span>
                                            <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['emergency_contact'] ?? 'N/A') ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="bg-white/80 backdrop-blur-sm rounded-lg p-2 border border-clinic-tea/20 shadow-sm">
                                        <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Allergies</span>
                                        <p class="text-sm font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($patient['allergies'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="lg:col-span-1">
                <!-- Visitation Logs -->
                <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden flex flex-col h-full">
                    <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-3 py-2">
                        <h2 class="text-sm font-comfortaa font-bold text-white">Visitation Logs</h2>
                    </div>
                    <div class="p-2 flex-1 overflow-y-auto">
                        <?php if (empty($visitationLogs)): ?>
                            <div class="text-center py-3">
                                <div class="w-8 h-8 rounded-lg bg-clinic-ivory/60 mx-auto mb-2 flex items-center justify-center">
                                    <div class="text-lg">🏥</div>
                                </div>
                                <p class="text-clinic-dark/60 font-poppins font-medium mb-2 text-xs">No visits recorded</p>
                                <button onclick="openVisitationModal()" class="group inline-flex items-center gap-1 px-2 py-1 bg-clinic-blue/20 border border-clinic-blue/30 text-clinic-blue rounded-lg hover:bg-clinic-blue/30 hover:border-clinic-blue/50 transition-all duration-200 font-poppins font-medium text-xs">
                                    <div class="w-3 h-3 rounded-md bg-clinic-blue/30 flex items-center justify-center group-hover:bg-clinic-blue/40 transition-colors duration-200">
                                        <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    Add
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b border-clinic-tea/20">
                                            <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">ID</th>
                                            <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Reason</th>
                                            <th class="text-left py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Date/Time</th>
                                            <th class="text-center py-2 px-3 font-poppins font-semibold text-clinic-dark/60">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($visitationLogs as $visit): ?>
                                            <tr class="border-b border-clinic-tea/10 hover:bg-clinic-ivory/30 transition-colors duration-200">
                                                <td class="py-2 px-3">
                                                    <span class="px-2 py-1 bg-clinic-blue/10 text-clinic-blue font-poppins font-medium rounded-lg">#<?= $visit['id'] ?></span>
                                                </td>
                                                <td class="py-2 px-3">
                                                    <p class="font-poppins font-semibold text-clinic-dark"><?= htmlspecialchars($visit['reason'] ?: 'N/A') ?></p>
                                                </td>
                                                <td class="py-2 px-3">
                                                    <p class="font-poppins text-clinic-dark/80"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($visit['visit_date']))) ?></p>
                                                </td>
                                                <td class="py-2 px-3">
                                                    <div class="flex gap-2 justify-center">
                                                        <button onclick="viewVisitationRecord(<?= $visit['id'] ?>)" class="px-3 py-1 bg-clinic-blue/20 text-clinic-blue text-xs font-medium rounded-lg hover:bg-clinic-blue/30 transition-colors">
                                                            View
                                                        </button>
                                                        <button onclick="archiveVisitation(<?= $visit['id'] ?>)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                                            Archive
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
            </div>
        </div>
    </div>
<!-- Full Screen Medical Form Modal -->
<div id="medicalFormFullScreen" class="fixed inset-0 z-50 hidden bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
    <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="bg-white shadow-lg border-b border-slate-200 px-6 py-4">

            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h2 id="medicalFormTitle" class="text-2xl font-semibold text-slate-800">Medical Form</h2>
                    <p id="medicalFormSubtitle" class="text-slate-600">Patient Information</p>
                </div>
                <button onclick="closeFullScreenMedicalForm()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="fullScreenMedicalForm" method="POST" action="../medical/save_medical_history.php">
                <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                <input type="hidden" name="patient_type" value="<?= $patientType ?>">
                <input type="hidden" name="form_type" id="formType">
                
                <div id="medicalFormContent" class="max-w-4xl mx-auto">
                    <!-- Form content will be loaded here -->
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Visitation Form Modal -->
<div id="visitationModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">Add Visitation Record</h2>
        <form id="visitationForm" method="POST" action="save_visitation.php" onsubmit="return submitVisitationForm(event);">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason *</label>
                    <select name="reason" id="reasonSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="toggleOtherReason()">
                        <option value="">Select reason</option>
                        <option value="cold">Cold</option>
                        <option value="cough">Cough</option>
                        <option value="dizziness">Dizziness</option>
                        <option value="fever">Fever</option>
                        <option value="headache">Headache</option>
                        <option value="injury">Injury</option>
                        <option value="medication">Medication</option>
                        <option value="nausea">Nausea</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Date *</label>
                    <input type="date" name="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div id="otherReasonDiv" class="hidden mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Specify Reason *</label>
                <input type="text" name="other_reason" id="otherReasonInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Please specify the reason">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms/Observations</label>
                <textarea name="symptoms" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Describe symptoms and observations..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Vital Signs</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Heart Rate (BPM)</label>
                        <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Temperature (°C)</label>
                        <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Blood Pressure (mmHg)</label>
                        <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" oninput="formatBloodPressure(this)">
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Treatment Given</label>
                <textarea name="treatment" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Describe treatment given..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeVisitationModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">Save Visitation</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit Patient Information Modal -->
<div id="editPatientModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-slate-800">Edit Patient Information</h2>
            <button onclick="closeEditPatientModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="editPatientForm" method="POST" action="update_patient_info.php">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">RFID Number *</label>
                            <input type="text" name="rfid" value="<?= htmlspecialchars($patient['rfid']) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                    </div>
                </div>

                <!-- Status Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Status Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Current Status *</label>
                            <select name="status" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                                <option value="enrolled" <?= $patient['status'] === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
                                <option value="graduated" <?= $patient['status'] === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                                <option value="transferred" <?= $patient['status'] === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                                <option value="dropped" <?= $patient['status'] === 'dropped' ? 'selected' : '' ?>>Dropped</option>
                            </select>
                        </div>
                    </div>
                </div>
            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeEditPatientModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-8 py-3 bg-clinic-blue text-white rounded-xl hover:bg-clinic-tea transition-colors font-medium" onclick="console.log('Submit button clicked'); return true;">
                    Update Patient
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Visitation Details Modal -->
<div id="visitationDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Visitation Details</h2>
            <button onclick="closeVisitationDetailsModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="visitationDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>
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
                        <input type="hidden" name="bmi_status" id="bmiStatusHidden">
                    </div>
                </div>
                
                <!-- Vital Signs -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Vital Signs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                            <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
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
                            <input type="hidden" name="heart_rate_status" id="heartRateStatusHidden">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Temperature Status</label>
                            <div id="temperatureStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                N/A
                            </div>
                            <input type="hidden" name="temperature_status" id="temperatureStatusHidden">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure Status</label>
                            <div id="bloodPressureStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                                N/A
                            </div>
                            <input type="hidden" name="blood_pressure_status" id="bloodPressureStatusHidden">
                        </div>
                    </div>
                </div>
                
                <!-- Assessment and Plan -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-xl font-semibold text-slate-800 mb-4">Assessment & Plan</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Assessment & Plan *</label>
                        <textarea name="assessment_plan" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Medical assessment, diagnosis, treatment plan, recommendations..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeGeneralCheckUpModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">
                    Save General CheckUp
                </button>
            </div>
        </form>
    </div>
</div>



<div id="medicalHistoryDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Medical Record Details</h2>
            <button onclick="closeMedicalHistoryDetailsModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div id="medicalHistoryDetailsContent">
            <!-- Content will be loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Visitation Log Details Modal -->
<div id="visitationLogDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Visitation Log Details</h2>
            <button onclick="closeVisitationLogDetailsModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div id="visitationLogDetailsContent">
            <!-- Content will be loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Visitation Form Modal -->
<div id="visitationModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">Add Visitation Record</h2>
        <form id="visitationForm" method="POST" action="save_visitation.php" onsubmit="return submitVisitationForm(event);">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason *</label>
                    <select name="reason" id="reasonSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="toggleOtherReason()">
                        <option value="">Select reason</option>
                        <option value="cold">Cold</option>
                        <option value="cough">Cough</option>
                        <option value="dizziness">Dizziness</option>
                        <option value="fever">Fever</option>
                        <option value="headache">Headache</option>
                        <option value="injury">Injury</option>
                        <option value="nausea">Nausea</option>
                        <option value="stomach_ache">Stomach Ache</option>
                        <option value="other">Other</option>
                    </select>
                    <div id="otherReasonDiv" class="mt-2 hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify reason *</label>
                        <input type="text" name="other_reason" id="otherReasonInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Enter specific reason...">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Date & Time *</label>
                    <input type="datetime-local" name="visit_date" value="<?= date('Y-m-d\TH:i') ?>" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms/Observations</label>
                <textarea name="symptoms" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Describe symptoms and observations..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (BPM)</label>
                    <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="N/A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure</label>
                    <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="120/80" oninput="formatBloodPressure(this)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                    <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="N/A">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">Other Notes</label>
                <textarea name="other_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Additional notes..."></textarea>
            </div>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="medication_given" id="medicationCheckbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="ml-2 text-sm font-medium text-slate-700">Medication was given</span>
                </label>
            </div>
            
            <div id="medicationForm" class="hidden mb-4 p-4 bg-slate-50 rounded-lg">
                <h3 class="text-lg font-semibold text-lg text-slate-800 mb-3">Medication Details</h3>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Medication Name</label>
                    <select name="medication_name" id="medicationSelect" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="toggleOtherMedication()">
                        <option value="">Select medication</option>
                        <option value="antacid">Antacid</option>
                        <option value="antihistamine">Antihistamine (Diphenhydramine)</option>
                        <option value="antiseptic">Antiseptic Solution</option>
                        <option value="aspirin">Aspirin</option>
                        <option value="band_aid">Band Aid/Plaster</option>
                        <option value="cough_syrup">Cough Syrup</option>
                        <option value="gauze">Gauze/Dressing</option>
                        <option value="ibuprofen">Ibuprofen</option>
                        <option value="ice_pack">Ice Pack</option>
                        <option value="paracetamol">Paracetamol (Acetaminophen)</option>
                        <option value="other">Other</option>
                    </select>
                    <div id="otherMedicationDiv" class="mt-2 hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify medication *</label>
                        <input type="text" name="other_medication" id="otherMedicationInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Enter specific medication...">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Additional Notes</label>
                    <textarea name="medication_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Additional medication notes or instructions..."></textarea>
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
                    <select name="first_aid_type" id="firstAidSelect" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="toggleOtherFirstAid()">
                        <option value="">Select first aid type</option>
                        <option value="antiseptic_cleaning">Antiseptic Cleaning</option>
                        <option value="bandage">Bandage/Dressing</option>
                        <option value="cold_compress">Cold Compress</option>
                        <option value="elevation">Elevation</option>
                        <option value="ice_pack">Ice Pack</option>
                        <option value="pressure">Pressure Application</option>
                        <option value="rest">Rest</option>
                        <option value="splint">Splint/Immobilization</option>
                        <option value="wound_cleaning">Wound Cleaning</option>
                        <option value="other">Other</option>
                    </select>
                    <div id="otherFirstAidDiv" class="mt-2 hidden">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify first aid type *</label>
                        <input type="text" name="other_first_aid" id="otherFirstAidInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Enter specific first aid type...">
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeVisitationModal()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">Save Visitation</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Patient Information Modal -->
<div id="editPatientModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
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
                            <?php
                            $currentLevel = $patient['level'] ?? '';
                            $currentYearGrade = $patient['year_grade'] ?? '';
                            
                            if ($currentLevel === 'Elementary') {
                                for ($i = 1; $i <= 6; $i++) {
                                    $grade = "Grade $i";
                                    $selected = ($currentYearGrade === $grade) ? 'selected' : '';
                                    echo "<option value=\"$grade\" $selected>$grade</option>";
                                }
                            } elseif ($currentLevel === 'High School') {
                                for ($i = 7; $i <= 10; $i++) {
                                    $grade = "Grade $i";
                                    $selected = ($currentYearGrade === $grade) ? 'selected' : '';
                                    echo "<option value=\"$grade\" $selected>$grade</option>";
                                }
                            } elseif ($currentLevel === 'Senior High School') {
                                for ($i = 11; $i <= 12; $i++) {
                                    $grade = "Grade $i";
                                    $selected = ($currentYearGrade === $grade) ? 'selected' : '';
                                    echo "<option value=\"$grade\" $selected>$grade</option>";
                                }
                            } elseif ($currentLevel === 'College') {
                                for ($i = 1; $i <= 5; $i++) {
                                    $year = $i === 1 ? '1st Year' : ($i === 2 ? '2nd Year' : ($i === 3 ? '3rd Year' : ($i === 4 ? '4th Year' : '5th Year (Irregular)')));
                                    $selected = ($currentYearGrade === $year) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                            }
                            ?>
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
                    
                    <!-- Block field (for College) -->
                    <div id="blockField" class="<?= ($patient['level'] ?? '') === 'College' ? '' : 'hidden' ?>">
                        <label class="block text-slate-700 mb-1">Block</label>
                        <select name="block" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                            <option value="">Select Block</option>
                            <option value="A" <?= ($patient['block'] ?? '') === 'A' ? 'selected' : '' ?>>Block A</option>
                            <option value="B" <?= ($patient['block'] ?? '') === 'B' ? 'selected' : '' ?>>Block B</option>
                            <option value="C" <?= ($patient['block'] ?? '') === 'C' ? 'selected' : '' ?>>Block C</option>
                            <option value="D" <?= ($patient['block'] ?? '') === 'D' ? 'selected' : '' ?>>Block D</option>
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
                    <input type="text" name="address" value="<?= htmlspecialchars($patient['address'] ?? '') ?>" placeholder="Barangay, Municipality/City, Province" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800 text-base leading-relaxed" style="min-height: 48px; line-height: 1.5;" />
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
                        $contacts = $patient['contacts'] ?? [];
                        
                        // If no contacts in array, check if there's an emergency_contact field (legacy data)
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
                    <button type="button" onclick="addContactNumber()" class="mt-2 inline-flex items-center px-3 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors text-sm">
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
                <button type="submit" class="px-8 py-3 bg-clinic-blue text-white rounded-xl hover:bg-clinic-tea transition-colors font-medium" onclick="console.log('Submit button clicked'); return true;">
                    Update Patient
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Visitation Details Modal -->
<div id="visitationDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-12rem)] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Visitation Details</h2>
            <button onclick="closeVisitationDetailsModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="visitationDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>


<script>
// Modal functions
function openVisitationModal() {
    const modal = document.getElementById('visitationModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeVisitationModal() {
    document.getElementById('visitationModal').classList.add('hidden');
    document.getElementById('visitationModal').classList.remove('flex');
}

function toggleOtherReason() {
    const reasonSelect = document.getElementById('reasonSelect');
    const otherReasonDiv = document.getElementById('otherReasonDiv');
    const otherReasonInput = document.getElementById('otherReasonInput');
    
    if (reasonSelect.value === 'other') {
        otherReasonDiv.classList.remove('hidden');
        otherReasonInput.required = true;
    } else {
        otherReasonDiv.classList.add('hidden');
        otherReasonInput.required = false;
        otherReasonInput.value = '';
    }
}

function validateVisitationForm() {
    const reasonSelect = document.getElementById('reasonSelect');
    const otherReasonInput = document.getElementById('otherReasonInput');
    const medicationCheckbox = document.getElementById('medicationCheckbox');
    const medicationSelect = document.getElementById('medicationSelect');
    const otherMedicationInput = document.getElementById('otherMedicationInput');
    const injuryCheckbox = document.getElementById('injuryCheckbox');
    const firstAidSelect = document.getElementById('firstAidSelect');
    const otherFirstAidInput = document.getElementById('otherFirstAidInput');
    
    if (reasonSelect.value === 'other') {
        if (!otherReasonInput.value.trim()) {
            alert('Please specify the reason when selecting "Other".');
            otherReasonInput.focus();
            return false;
        }
    }
    
    if (medicationCheckbox.checked) {
        if (medicationSelect.value === 'other') {
            if (!otherMedicationInput.value.trim()) {
                alert('Please specify the medication when selecting "Other".');
                otherMedicationInput.focus();
                return false;
            }
        }
    }
    
    if (injuryCheckbox.checked) {
        if (firstAidSelect.value === 'other') {
            if (!otherFirstAidInput.value.trim()) {
                alert('Please specify the first aid type when selecting "Other".');
                otherFirstAidInput.focus();
                return false;
            }
        }
    }
    
    return true;
}

function submitVisitationForm(event) {
    event.preventDefault();
    
    // Get form data
    const form = document.getElementById('visitationForm');
    const formData = new FormData(form);
    
    // Submit via AJAX
    fetch('save_visitation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification and close modal quickly
            showNotification(data.message, 'success');
            setTimeout(() => {
                closeVisitationModal();
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
            // Keep modal open so user can fix validation errors
        }
    })
    .catch(error => {
        showNotification('Error saving visitation record: ' + error.message, 'error');
        console.error('Error:', error);
    });
    
    return false; // Prevent default form submission
}

function toggleOtherMedication() {
    const medicationSelect = document.getElementById('medicationSelect');
    const otherMedicationDiv = document.getElementById('otherMedicationDiv');
    const otherMedicationInput = document.getElementById('otherMedicationInput');
    
    if (medicationSelect.value === 'other') {
        otherMedicationDiv.classList.remove('hidden');
        otherMedicationInput.required = true;
    } else {
        otherMedicationDiv.classList.add('hidden');
        otherMedicationInput.required = false;
        otherMedicationInput.value = '';
    }
}

function toggleOtherFirstAid() {
    const firstAidSelect = document.getElementById('firstAidSelect');
    const otherFirstAidDiv = document.getElementById('otherFirstAidDiv');
    const otherFirstAidInput = document.getElementById('otherFirstAidInput');
    
    if (firstAidSelect.value === 'other') {
        otherFirstAidDiv.classList.remove('hidden');
        otherFirstAidInput.required = true;
    } else {
        otherFirstAidDiv.classList.add('hidden');
        otherFirstAidInput.required = false;
        otherFirstAidInput.value = '';
    }
}

function viewMedicalRecord(recordId, status = 'active') {
    // Show loading
    document.getElementById('medicalHistoryDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></div>';
    
    // Show modal
    document.getElementById('medicalHistoryDetailsModal').classList.remove('hidden');
    document.getElementById('medicalHistoryDetailsModal').classList.add('flex');
    
    // Fetch medical record details
    fetch(`medical_record_view.php?id=${recordId}&archived=${status === 'archived' ? '1' : '0'}&ajax=1`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('medicalHistoryDetailsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading medical record:', error);
            document.getElementById('medicalHistoryDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading medical record details.</div>';
        });
}

function viewVisitationRecord(visitId) {
    // Show loading
    document.getElementById('visitationLogDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></div>';
    
    // Show modal
    document.getElementById('visitationLogDetailsModal').classList.remove('hidden');
    document.getElementById('visitationLogDetailsModal').classList.add('flex');
    
    // Fetch visitation log details
    fetch(`../logs/visitation_details_view.php?id=${visitId}&ajax=1`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('visitationLogDetailsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading visitation log:', error);
            document.getElementById('visitationLogDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading visitation log details.</div>';
        });
}

function viewVisitationDetails(visitId) {
    // Directly show visitation details without RFID verification
    showVisitationDetails(visitId);
}

function closeVisitationDetailsModal() {
    document.getElementById('visitationDetailsModal').classList.add('hidden');
    document.getElementById('visitationDetailsModal').classList.remove('flex');
}


function showVisitationDetails(visitId) {
    // Show loading
    document.getElementById('visitationDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-slate-600">Loading details...</p></div>';
    
    // Show modal
    document.getElementById('visitationDetailsModal').classList.remove('hidden');
    document.getElementById('visitationDetailsModal').classList.add('flex');
    
    // Load details using iframe method to maintain session
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = `../logs/get_visitation_details.php?id=${visitId}`;
    iframe.onload = function() {
        try {
            const content = iframe.contentDocument.body.innerHTML;
            document.getElementById('visitationDetailsContent').innerHTML = content;
            document.body.removeChild(iframe);
        } catch (e) {
            // Fallback to fetch if iframe fails
            fetch(`../logs/get_visitation_details.php?id=${visitId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('visitationDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('visitationDetailsContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading details. Please try again.</div>';
                });
        }
    };
    document.body.appendChild(iframe);
}

function archiveVisitation(visitId) {
    if (confirm('Are you sure you want to archive this visitation record? It will be moved to the patient\'s archive.')) {
        // Show loading notification
        showNotification('Archiving visitation record...', 'info');
        
        // Show loading on button
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Archiving...';
        button.disabled = true;
        
        // Archive visitation
        fetch('../admin/archive_visitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${visitId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Visitation record archived successfully!', 'success');
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error archiving visitation: ' + (data.message || 'Unknown error'), 'error');
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            showNotification('Error archiving visitation: ' + error.message, 'error');
            button.textContent = originalText;
            button.disabled = false;
        });
    }
}

function showMedicalFormFullScreen(type) {
    const modal = document.getElementById('medicalFormFullScreen');
    const title = document.getElementById('medicalFormTitle');
    const content = document.getElementById('medicalFormContent');
    
    // Set title based on form type
    const formTitles = {
        'athlete': 'Athlete Medical Form',
        'general': 'General CheckUp',
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
    form.action = '../medical/save_medical_history.php';
    
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
    } else if (type === 'medical_history') {
        form.innerHTML += getMedicalHistoryFormContent();
    }
    
    container.appendChild(form);
}

function getMedicalHistoryFormContent() {
    return `
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
            <h3 class="text-xl font-semibold text-lg text-slate-800 mb-6">Medical History Form</h3>
            
            <div class="space-y-6">
                <!-- Symptoms/Observations -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms/Observations</label>
                    <textarea name="symptoms_observations" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Describe any symptoms, observations, or concerns..."></textarea>
                </div>
                
                <!-- Medical History -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Medical History</label>
                    <textarea name="medical_history" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Previous medical conditions, surgeries, hospitalizations..."></textarea>
                </div>
                
                <!-- Medications -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Current Medications</label>
                    <textarea name="medications" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - List current medications, dosages, and frequency..."></textarea>
                </div>
                
                <!-- Allergies -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Allergies</label>
                    <textarea name="allergies" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Food allergies, drug allergies, environmental allergies..."></textarea>
                </div>
                
                <!-- Family History -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Family Medical History</label>
                    <textarea name="family_history" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Hereditary conditions, family medical history..."></textarea>
                </div>
                
                <!-- Assessment & Plan -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assessment & Plan</label>
                    <textarea name="assessment_plan" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Medical assessment, diagnosis, treatment plan, recommendations..."></textarea>
                </div>
                
                <!-- Other Notes -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Other Notes</label>
                    <textarea name="other_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Additional notes, follow-up instructions..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-8">
                <button type="button" onclick="closeMedicalFormFullScreen()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-clinic-blue text-white rounded-lg hover:bg-clinic-tea transition-colors">
                    Save Medical History
                </button>
            </div>
        </div>
    `;
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
            <h3 class="text-xl font-semibold text-lg text-slate-800 mb-6">General CheckUp Information</h3>
            
            <!-- Physical Measurements -->
            <div class="mb-6">
                <h4 class="text-lg font-medium text-slate-700 mb-4">Physical Measurements</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Height (cm) *</label>
                        <input type="number" name="height" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="calculateBMI()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Weight (kg) *</label>
                        <input type="number" name="weight" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="calculateBMI()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">BMI</label>
                        <input type="text" name="bmi" id="bmi" readonly class="w-full rounded-lg border border-slate-300 px-4 py-3 bg-slate-50">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">BMI Status</label>
                    <select name="bmi_status" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        <option value="">Select BMI Status</option>
                        <option value="Underweight">Underweight</option>
                        <option value="Normal">Normal</option>
                        <option value="Overweight">Overweight</option>
                        <option value="Obese">Obese</option>
                    </select>
                </div>
            </div>
            
            <!-- Vital Signs -->
            <div class="mb-6">
                <h4 class="text-lg font-medium text-slate-700 mb-4">Vital Signs</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (bpm)</label>
                        <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                        <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure</label>
                        <input type="text" name="blood_pressure" placeholder="e.g., 120/80" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" oninput="formatBloodPressure(this)">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate Status</label>
                        <select name="heart_rate_status" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                            <option value="">Select Status</option>
                            <option value="Normal">Normal</option>
                            <option value="Elevated">Elevated</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Temperature Status</label>
                        <select name="temperature_status" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                            <option value="">Select Status</option>
                            <option value="Normal">Normal</option>
                            <option value="Fever">Fever</option>
                            <option value="Hypothermia">Hypothermia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure Status</label>
                        <select name="blood_pressure_status" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                            <option value="">Select Status</option>
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Assessment and Plan -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Assessment & Plan *</label>
                <textarea name="assessment_plan" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - Medical assessment, diagnosis, treatment plan, recommendations..."></textarea>
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
                        <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="120/80" oninput="formatBloodPressure(this)">
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




function openGeneralCheckUpModal() {
    const modal = document.getElementById('generalCheckUpModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeGeneralCheckUpModal() {
    document.getElementById('generalCheckUpModal').classList.add('hidden');
    document.getElementById('generalCheckUpModal').classList.remove('flex');
}

function closeMedicalHistoryDetailsModal() {
    document.getElementById('medicalHistoryDetailsModal').classList.add('hidden');
    document.getElementById('medicalHistoryDetailsModal').classList.remove('flex');
}

function closeVisitationLogDetailsModal() {
    document.getElementById('visitationLogDetailsModal').classList.add('hidden');
    document.getElementById('visitationLogDetailsModal').classList.remove('flex');
}

function calculateGeneralBMI() {
    const height = parseFloat(document.querySelector('input[name="height"]').value);
    const weight = parseFloat(document.querySelector('input[name="weight"]').value);
    
    if (height && weight && height > 0) {
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);
        
        // Update BMI field
        const bmiField = document.getElementById('generalBMI');
        if (bmiField) {
            bmiField.value = bmi.toFixed(1);
        }
        
        // Update BMI status
        const statusElement = document.getElementById('bmiStatus');
        const hiddenInput = document.getElementById('bmiStatusHidden');
        if (statusElement) {
            let status, className;
            if (bmi < 18.5) {
                status = 'Underweight';
                className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
            } else if (bmi < 25) {
                status = 'Normal';
                className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
            } else if (bmi < 30) {
                status = 'Overweight';
                className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
            } else {
                status = 'Obese';
                className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
            }
            statusElement.textContent = status;
            statusElement.className = className;
            if (hiddenInput) hiddenInput.value = status;
        }
    }
}

function checkHeartRateStatus() {
    const hrValue = parseFloat(document.getElementById('heartRate').value);
    const statusElement = document.getElementById('heartRateStatus');
    const hiddenInput = document.getElementById('heartRateStatusHidden');
    
    if (hrValue && !isNaN(hrValue)) {
        let status, className;
        if (hrValue < 60) {
            status = 'Low';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
        } else if (hrValue <= 100) {
            status = 'Normal';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
        } else {
            status = 'High';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
        }
        statusElement.textContent = status;
        statusElement.className = className;
        if (hiddenInput) hiddenInput.value = status;
    } else {
        statusElement.textContent = 'N/A';
        statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600';
        if (hiddenInput) hiddenInput.value = '';
    }
}

function checkTemperatureStatus() {
    const tempValue = parseFloat(document.getElementById('temperature').value);
    const statusElement = document.getElementById('temperatureStatus');
    const hiddenInput = document.getElementById('temperatureStatusHidden');
    
    if (tempValue && !isNaN(tempValue)) {
        let status, className;
        if (tempValue < 36.1) {
            status = 'Low';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
        } else if (tempValue <= 37.2) {
            status = 'Normal';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
        } else {
            status = 'High';
            className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
        }
        statusElement.textContent = status;
        statusElement.className = className;
        if (hiddenInput) hiddenInput.value = status;
    } else {
        statusElement.textContent = 'N/A';
        statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600';
        if (hiddenInput) hiddenInput.value = '';
    }
}

function formatBloodPressure(input) {
    let value = input.value.replace(/\D/g, ''); // Remove all non-digits
    
    if (value.length >= 3) {
        // Format as XXX/XX
        const systolic = value.substring(0, 3);
        const diastolic = value.substring(3, 5);
        input.value = systolic + '/' + diastolic;
    } else if (value.length > 0) {
        // Just show the digits as they are typed
        input.value = value;
    }
    
    // Trigger status check for General CheckUp form
    if (input.id === 'bloodPressure') {
        checkBloodPressureStatus();
    }
}

function viewAllMedicalForms() {
    // Redirect directly to medical forms management
    window.location.href = `medical_forms_management.php?patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`;
}

function archiveMedicalRecord(recordId) {
    if (confirm('Are you sure you want to archive this medical record? This will move it to archived records.')) {
        // Create a form to submit the archive request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'archive_medical_record.php';
        
        // Add hidden fields
        const recordIdInput = document.createElement('input');
        recordIdInput.type = 'hidden';
        recordIdInput.name = 'record_id';
        recordIdInput.value = recordId;
        
        const patientIdInput = document.createElement('input');
        patientIdInput.type = 'hidden';
        patientIdInput.name = 'patient_id';
        patientIdInput.value = '<?= $patientId ?>';
        
        const patientTypeInput = document.createElement('input');
        patientTypeInput.type = 'hidden';
        patientTypeInput.name = 'patient_type';
        patientTypeInput.value = '<?= $patientType ?>';
        
        form.appendChild(recordIdInput);
        form.appendChild(patientIdInput);
        form.appendChild(patientTypeInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function openFullScreenMedicalForm(formType) {
    // Set the form type and title
    let title = formType.charAt(0).toUpperCase() + formType.slice(1) + ' Form';
    if (formType === 'general') {
        title = 'General CheckUp';
    }
    
    document.getElementById('medicalFormTitle').textContent = title;
    document.getElementById('medicalFormSubtitle').textContent = 'Patient: <?= htmlspecialchars($patient['name']) ?>';
    
    // Show the full screen modal
    const modal = document.getElementById('medicalFormFullScreen');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    
    // Set the form type in a hidden input for saving
    const formTypeInput = document.getElementById('formType');
    if (formTypeInput) {
        formTypeInput.value = formType;
    }
    
    // Load the form content
    const content = document.getElementById('medicalFormContent');
    loadMedicalFormContent(formType, content);
}



function calculateBMI() {
    const height = parseFloat(document.getElementById('height').value);
    const weight = parseFloat(document.getElementById('weight').value);
    
    if (height && weight && height > 0) {
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);
        const bmiValue = document.getElementById('bmiValue');
        const bmiStatus = document.getElementById('bmiStatus');
        const bmiHidden = document.getElementById('bmi');
        const bmiStatusHidden = document.getElementById('bmi_status');
        
        // Update BMI field (for general form)
        if (bmiHidden) {
            bmiHidden.value = bmi.toFixed(1);
        }
        
        // Update BMI status dropdown (for general form)
        const bmiStatusSelect = document.querySelector('select[name="bmi_status"]');
        if (bmiStatusSelect) {
            let statusValue = '';
            if (bmi < 18.5) {
                statusValue = 'Underweight';
            } else if (bmi >= 18.5 && bmi < 25) {
                statusValue = 'Normal';
            } else if (bmi >= 25 && bmi < 30) {
                statusValue = 'Overweight';
            } else {
                statusValue = 'Obese';
            }
            bmiStatusSelect.value = statusValue;
        }
        
        // Legacy support for other forms
        if (bmiValue) {
            bmiValue.textContent = bmi.toFixed(1);
        }
        
        if (bmiHidden) {
            bmiHidden.value = bmi.toFixed(1);
        }
        
        // Determine BMI status
        let statusText = '';
        if (bmi < 18.5) {
            statusText = 'Underweight';
            bmiStatus.textContent = statusText;
            bmiStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800';
        } else if (bmi >= 18.5 && bmi < 25) {
            statusText = 'Normal';
            bmiStatus.textContent = statusText;
            bmiStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800';
        } else if (bmi >= 25 && bmi < 30) {
            statusText = 'Overweight';
            bmiStatus.textContent = statusText;
            bmiStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800';
        } else {
            statusText = 'Obese';
            bmiStatus.textContent = statusText;
            bmiStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800';
        }
        
        // Update hidden input field for BMI status
        bmiStatusHidden.value = statusText;
    } else {
        document.getElementById('bmiValue').textContent = '--';
        document.getElementById('bmiStatus').textContent = '';
        document.getElementById('bmiStatus').className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium';
        // Clear hidden input fields
        document.getElementById('bmi').value = '';
        document.getElementById('bmi_status').value = '';
    }
}

function checkVitalStatus(inputId, statusId, minNormal, maxNormal) {
    const value = parseFloat(document.getElementById(inputId).value);
    const statusElement = document.getElementById(statusId);
    
    if (value) {
        if (value >= minNormal && value <= maxNormal) {
            statusElement.textContent = 'Normal';
            statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
        } else if (value < minNormal) {
            statusElement.textContent = 'Low';
            statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
        } else {
            statusElement.textContent = 'High';
            statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
        }
    } else {
        statusElement.textContent = '';
        statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium';
    }
}

function checkBloodPressureStatus() {
    const bpValue = document.getElementById('bloodPressure').value;
    const statusElement = document.getElementById('bloodPressureStatus');
    const hiddenInput = document.getElementById('bloodPressureStatusHidden');
    
    if (bpValue && bpValue.includes('/')) {
        const [systolic, diastolic] = bpValue.split('/').map(v => parseInt(v.trim()));
        
        if (!isNaN(systolic) && !isNaN(diastolic)) {
            if (systolic < 120 && diastolic < 80) {
                statusElement.textContent = 'Normal';
                statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
                if (hiddenInput) hiddenInput.value = 'Normal';
            } else if (systolic < 130 && diastolic < 80) {
                statusElement.textContent = 'Elevated';
                statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
                if (hiddenInput) hiddenInput.value = 'Elevated';
            } else if (systolic < 140 || diastolic < 90) {
                statusElement.textContent = 'High Stage 1';
                statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800';
                if (hiddenInput) hiddenInput.value = 'High Stage 1';
            } else {
                statusElement.textContent = 'High Stage 2';
                statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
                if (hiddenInput) hiddenInput.value = 'High Stage 2';
            }
        } else {
            statusElement.textContent = 'Invalid Format';
            statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
        }
    } else {
        statusElement.textContent = '';
        statusElement.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium';
    }
}


// Toggle medication form
document.getElementById('medicationCheckbox').addEventListener('change', function() {
    const medicationForm = document.getElementById('medicationForm');
    if (this.checked) {
        medicationForm.classList.remove('hidden');
    } else {
        medicationForm.classList.add('hidden');
        // Clear medication form fields when unchecked
        document.getElementById('medicationSelect').value = '';
        document.getElementById('otherMedicationInput').value = '';
        document.querySelector('textarea[name="medication_notes"]').value = '';
        // Hide other medication div
        document.getElementById('otherMedicationDiv').classList.add('hidden');
    }
});

// Toggle injury form
document.getElementById('injuryCheckbox').addEventListener('change', function() {
    const injuryForm = document.getElementById('injuryForm');
    if (this.checked) {
        injuryForm.classList.remove('hidden');
    } else {
        injuryForm.classList.add('hidden');
        // Clear injury form fields when unchecked
        document.querySelector('input[name="first_aid_given"]').checked = false;
        document.getElementById('firstAidSelect').value = '';
        document.getElementById('otherFirstAidInput').value = '';
        // Hide other first aid div
        document.getElementById('otherFirstAidDiv').classList.add('hidden');
    }
});


// Simple notification system
function showNotification(message, type = 'info') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    // Set colors based on type
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500', 
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const bgColor = colors[type] || colors.info;
    
    notification.innerHTML = `
        <div class="${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center justify-between">
            <span class="font-medium">${message}</span>
            <button onclick="removeNotification(this)" class="ml-4 text-white hover:text-gray-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Show animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        removeNotification(notification.querySelector('button'));
    }, 3000);
}

function removeNotification(button) {
    const notification = button.closest('.notification');
    if (!notification) return;
    
    // Hide animation
    notification.classList.remove('show');
    notification.classList.add('hide');
    
    // Remove from DOM after animation
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Initialize page on load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize medical history form behavior
    initializeMedicalHistoryForm();
    
    // No need to initialize remove buttons anymore
    
    // Edit form will be initialized when modal opens
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

function changeStatus(newStatus) {
    if (!newStatus) return;
    
    const currentStatus = '<?= $patient['status'] ?? 'Active' ?>';
    if (newStatus === currentStatus) {
        showNotification('Status is already ' + newStatus, 'info');
        return;
    }
    
    if (confirm(`Are you sure you want to change status from ${currentStatus} to ${newStatus}?`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_student_status.php';
        
        const studentIdInput = document.createElement('input');
        studentIdInput.type = 'hidden';
        studentIdInput.name = 'student_id';
        studentIdInput.value = '<?= $patientId ?>';
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = newStatus;
        
        const notesInput = document.createElement('input');
        notesInput.type = 'hidden';
        notesInput.name = 'status_notes';
        notesInput.value = `Status changed from ${currentStatus} to ${newStatus}`;
        
        form.appendChild(studentIdInput);
        form.appendChild(statusInput);
        form.appendChild(notesInput);
        
        document.body.appendChild(form);
        form.submit();
    } else {
        // Reset dropdown to default
        event.target.value = '';
    }
}

// Dynamic field visibility for edit form (same as registration forms)
function initializeEditForm() {
    const levelSelect = document.getElementById('levelSelect');
    if (!levelSelect) return;
    
    const yearGradeField = document.getElementById('yearGradeField');
    const yearGradeInput = document.getElementById('yearGradeInput');
    const yearGradeLabel = document.getElementById('yearGradeLabel');
    const courseField = document.getElementById('courseField');
    const blockField = document.getElementById('blockField');
    const strandField = document.getElementById('strandField');
    const sectionField = document.getElementById('sectionField');
    
    // Check if event listener is already attached
    if (levelSelect.hasAttribute('data-listener-attached')) return;
    
    function updateFields() {
        const level = levelSelect.value;
        
        // Hide all conditional fields first
        yearGradeField.classList.add('hidden');
        courseField.classList.add('hidden');
        blockField.classList.add('hidden');
        strandField.classList.add('hidden');
        sectionField.classList.add('hidden');
        
        // Only clear and reset year/grade options if we're changing the level
        // Preserve existing value if it's already set
        const currentValue = yearGradeInput.value;
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
            blockField.classList.remove('hidden');
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
        } else if (currentValue && currentValue !== '') {
            // Restore current value if no existing value
            yearGradeInput.value = currentValue;
        }
    }
    
    levelSelect.addEventListener('change', updateFields);
    levelSelect.setAttribute('data-listener-attached', 'true');
    
    // Initialize on page load
    updateFields();
}

// Initialize edit form when modal opens
function editPatientInfo() {
    const modal = document.getElementById('editPatientModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Add form submission listener for debugging
    const form = document.getElementById('editPatientForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submission event triggered');
            console.log('Form data:', new FormData(form));
        });
    }
    
    // No need to initialize remove buttons when modal opens
    
    // Form is already populated by PHP, no need to initialize JavaScript
    // The form will work correctly as-is
}

    // Contact number management functions
    function addContactNumber() {
        const container = document.getElementById('contactNumbersContainer');
        const addBtn = document.querySelector('button[onclick="addContactNumber()"]');
        
        // Check if we already have 2 contacts (1 default + 1 added)
        const existingContacts = container.querySelectorAll('input[name="contacts[]"]');
        if (existingContacts.length >= 2) {
            return; // Don't add more than 2 total
        }
        
        const contactItem = document.createElement('div');
        contactItem.className = 'contact-number-item flex items-center space-x-2 mb-2';
        
        // Create remove button for the new contact
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'p-2 text-red-500 hover:text-red-700 transition-colors';
        removeBtn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        `;
        removeBtn.addEventListener('click', () => {
            contactItem.remove();
            // Re-enable the add button when extra contact is removed
            addBtn.disabled = false;
            addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            addBtn.classList.add('hover:bg-sky-600');
        });
        
        contactItem.innerHTML = `
            <input type="text" name="contacts[]" value="" placeholder="09xxxxxxxxx" class="flex-1 rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
        `;
        
        contactItem.appendChild(removeBtn);
        container.appendChild(contactItem);
        
        // Disable the add button after adding extra contact
        addBtn.disabled = true;
        addBtn.classList.add('opacity-50', 'cursor-not-allowed');
        addBtn.classList.remove('hover:bg-sky-600');
    }
    
    function removeContactNumber(button) {
        const contactItem = button.closest('.contact-number-item');
        const addBtn = document.querySelector('button[onclick="addContactNumber()"]');
        
        contactItem.remove();
        
        // Re-enable the add button when extra contact is removed
        addBtn.disabled = false;
        addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        addBtn.classList.add('hover:bg-sky-600');
    }
    
    // No need for remove button functions anymore

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


<?php include __DIR__ . '/../partials/footer.php'; ?>
