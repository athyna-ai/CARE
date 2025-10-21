<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/encryption.php';

// Include security breach detection
require_once __DIR__ . '/../security_breach_detector.php';

// REQUIRE ADMIN AUTHENTICATION for patient data access
require_admin_auth();
$pdo = get_pdo();

// Get patient data from encrypted token or fallback to old method
$patientId = 0;
$patientType = 'student';

if (isset($_GET['token'])) {
    // New encrypted token method
    $tokenData = PatientIdEncryption::validateToken($_GET['token']);
    if ($tokenData) {
        $patientId = (int)$tokenData['id'];
        $patientType = $tokenData['type'];
    } else {
        logSecurityBreach('INVALID_PATIENT_TOKEN', 'Invalid or expired patient token attempted', [
            'token' => substr($_GET['token'], 0, 20) . '...',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
        header('Location: ../admin/dashboard.php?error=invalid_patient_token');
        exit;
    }
} else {
    // Fallback to old method for backward compatibility
    try {
        $patientId = validate_patient_id($_GET['id'] ?? 0);
        $patientType = validate_patient_type($_GET['type'] ?? 'student');
    } catch (InvalidArgumentException $e) {
        logSecurityBreach('INVALID_PATIENT_PARAMS', 'Invalid patient parameters attempted', [
            'invalid_id' => $_GET['id'] ?? 'null',
            'invalid_type' => $_GET['type'] ?? 'null',
            'error' => $e->getMessage(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
        header('Location: ../admin/dashboard.php?error=invalid_patient_params');
        exit;
    }
}

// Patient view accessed

// Handle success/error messages
$message = $_GET['message'] ?? '';
$messageType = $_GET['message_type'] ?? 'info';

if ($patientId <= 0) {
    // Invalid patient ID, redirecting to dashboard
    header('Location: ../admin/dashboard.php?error=invalid_patient');
    exit;
}

// Get patient information
$patient = null;
$patientIdInt = (int)$patientId; // Ensure integer conversion

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
    logSecurityBreach('PATIENT_NOT_FOUND', 'Attempted to access non-existent patient', [
        'patient_id' => $patientId,
        'patient_type' => $patientType,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
    header('Location: ../admin/dashboard.php?error=patient_not_found');
    exit;
} else {
    // Log successful patient access
    log_patient_access($pdo, $patientId, $patientType, 'view');
    
    // Patient found
    // Ensure patient data is valid
    if (empty($patient['name'])) {
        logSecurityBreach('INCOMPLETE_PATIENT_DATA', 'Patient data is incomplete', [
            'patient_id' => $patientId,
            'patient_type' => $patientType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
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
        // Medical history retrieved
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
        // Archived medical records retrieved
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
        // Visitation logs retrieved
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
<!-- Notification container now handled globally in header.php -->

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


/* Spinner animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
    
    console.log('URL parameters:', { message, messageType, formType, openHistory });
    
    if (message) {
        // Create notification directly without relying on global system
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-[99999] max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 transform transition-all duration-500 ease-out translate-x-full opacity-0 ${
            messageType === 'success' ? 'border-l-4 border-l-green-500' : 
            messageType === 'error' ? 'border-l-4 border-l-red-500' : 
            messageType === 'warning' ? 'border-l-4 border-l-yellow-500' : 
            'border-l-4 border-l-blue-500'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">
                        ${messageType === 'success' ? '✅' : 
                          messageType === 'error' ? '❌' : 
                          messageType === 'warning' ? '⚠️' : 
                          'ℹ️'}
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${decodeURIComponent(message)}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                    <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        
        // Auto remove after 8 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 8000);
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
    
    // Initialize medical history form behavior
    initializeMedicalHistoryForm();
    
    // Test function to manually trigger notifications
    window.testNotification = function() {
        console.log('Testing notification system...');
        showNotification('Test notification - Medical form saved successfully!', 'success', 5000);
    };
    
    // Auto-test notification after 2 seconds if no URL parameters
    if (!message && !formType && !openHistory) {
        setTimeout(() => {
            console.log('Auto-testing notification system...');
            showNotification('Test: Medical form saved successfully!', 'success', 3000);
        }, 2000);
    }
});

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
                // Go back to appropriate listing page based on patient type
                if ($patientType === 'faculty') {
                    $backUrl = 'faculty_listing.php';
                } else {
                    // For students, we need to determine the level to go back to the right listing
                    $patientLevel = $patient['level'] ?? '';
                    if ($patientLevel) {
                        $backUrl = 'school_listing.php?level=' . urlencode($patientLevel);
                    } else {
                        $backUrl = '../admin/dashboard.php'; // Fallback to dashboard
                    }
                }
                ?>
                <a href="<?= $backUrl ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-200 font-poppins font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to List
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
                                <?php if ($patientType === 'faculty'): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-xl border font-poppins font-medium
                                        <?php
                                        switch($patient['status'] ?? 'Active') {
                                            case 'Active': echo 'bg-green-100 text-green-800 border-green-200'; break;
                                            case 'Retired': echo 'bg-purple-100 text-purple-800 border-purple-200'; break;
                                            case 'On Leave': echo 'bg-yellow-100 text-yellow-800 border-yellow-200'; break;
                                            case 'Resigned': echo 'bg-red-100 text-red-800 border-red-200'; break;
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
                                    <div class="ml-3 inline-block">
                                        <div class="relative group">
                                            <select onchange="changeStatus(this.value)" class="appearance-none text-xs px-4 py-2 pr-8 bg-gradient-to-r from-clinic-blue/10 to-clinic-tea/10 text-clinic-blue rounded-xl border border-clinic-blue/30 hover:border-clinic-blue/50 hover:bg-gradient-to-r hover:from-clinic-blue/20 hover:to-clinic-tea/20 transition-all duration-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-clinic-blue/30 focus:border-clinic-blue/50 shadow-sm hover:shadow-md">
                                                <option value="">🔄 Change Status</option>
                                                <option value="Active" <?= ($patient['status'] ?? 'Active') === 'Active' ? 'disabled' : '' ?>>✅ Active</option>
                                                <option value="Retired" <?= ($patient['status'] ?? 'Active') === 'Retired' ? 'disabled' : '' ?>>👴 Retired</option>
                                                <option value="On Leave" <?= ($patient['status'] ?? 'Active') === 'On Leave' ? 'disabled' : '' ?>>🏖️ On Leave</option>
                                                <option value="Resigned" <?= ($patient['status'] ?? 'Active') === 'Resigned' ? 'disabled' : '' ?>>👋 Resigned</option>
                                                <option value="Inactive" <?= ($patient['status'] ?? 'Active') === 'Inactive' ? 'disabled' : '' ?>>⏸️ Inactive</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <svg class="w-3 h-3 text-clinic-blue/60 group-hover:text-clinic-blue transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div class="ml-3 inline-block">
                                        <div class="relative group">
                                            <select onchange="changeStatus(this.value)" class="appearance-none text-xs px-4 py-2 pr-8 bg-gradient-to-r from-clinic-blue/10 to-clinic-tea/10 text-clinic-blue rounded-xl border border-clinic-blue/30 hover:border-clinic-blue/50 hover:bg-gradient-to-r hover:from-clinic-blue/20 hover:to-clinic-tea/20 transition-all duration-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-clinic-blue/30 focus:border-clinic-blue/50 shadow-sm hover:shadow-md">
                                                <option value="">🔄 Change Status</option>
                                                <option value="Active" <?= ($patient['status'] ?? 'Active') === 'Active' ? 'disabled' : '' ?>>✅ Active</option>
                                                <option value="Graduated" <?= ($patient['status'] ?? 'Active') === 'Graduated' ? 'disabled' : '' ?>>🎓 Graduated</option>
                                                <option value="Transferred" <?= ($patient['status'] ?? 'Active') === 'Transferred' ? 'disabled' : '' ?>>🔄 Transferred</option>
                                                <option value="Inactive" <?= ($patient['status'] ?? 'Active') === 'Inactive' ? 'disabled' : '' ?>>⏸️ Inactive</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <svg class="w-3 h-3 text-clinic-blue/60 group-hover:text-clinic-blue transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button onclick="openVisitationModal()" class="group px-6 py-3 bg-clinic-blue/10 border border-clinic-blue/30 text-clinic-blue rounded-2xl hover:bg-clinic-blue/20 hover:border-clinic-blue/50 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                        <div class="w-8 h-8 rounded-xl bg-clinic-blue/20 flex items-center justify-center group-hover:bg-clinic-blue/30 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        Add Visitation
                    </button>
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
                            <div class="flex items-center gap-3">
                                <button onclick="printPatientRecord()" class="px-4 py-2 bg-white/20 border border-white/30 text-slate-800 rounded-lg hover:bg-white/30 transition-colors flex items-center gap-2 no-print">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    Print Record
                                </button>
                                <button onclick="openEditModal()" class="px-4 py-2 bg-white/20 border border-white/30 text-slate-800 rounded-lg hover:bg-white/30 transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit Information
                                </button>
                            </div>
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
                <!-- TODO: FIX VISITATION LOG DETAILS VIEWING - Lines 850-902 -->
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
                            <div class="visitation-logs-table overflow-x-auto">
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
                                                        <button onclick="viewVisitationDetails(<?= $visit['id'] ?>)" class="px-3 py-1 bg-clinic-blue/20 text-clinic-blue text-xs font-medium rounded-lg hover:bg-clinic-blue/30 transition-colors">
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
    </div>
</div>

<!-- Edit Patient Information Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-8rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Edit Patient Information</h2>
            <button onclick="closeEditModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editPatientForm" method="POST" action="update_patient_info.php" onsubmit="return submitEditPatientForm(event);">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="space-y-6">
                <!-- Personal Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($patient['name']) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">RFID *</label>
                            <input type="text" name="rfid" value="<?= htmlspecialchars($patient['rfid']) ?>" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?= $patient['dob'] ? date('Y-m-d', strtotime($patient['dob'])) : '' ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Gender *</label>
                            <select name="gender" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= ($patient['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($patient['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Religion</label>
                            <input type="text" name="religion" value="<?= htmlspecialchars($patient['religion'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Address</label>
                            <textarea name="address" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200"><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Academic Information (for students) -->
                <?php if ($patientType === 'student'): ?>
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Academic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Level</label>
                            <select name="level" id="editLevel" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" onchange="updateStudentFields()">
                                <option value="">Select Level</option>
                                <option value="Pre-school" <?= ($patient['level'] ?? '') === 'Pre-school' ? 'selected' : '' ?>>Pre-school</option>
                                <option value="Elementary" <?= ($patient['level'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                                <option value="High School" <?= ($patient['level'] ?? '') === 'High School' ? 'selected' : '' ?>>High School</option>
                                <option value="Senior High School" <?= ($patient['level'] ?? '') === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                                <option value="College" <?= ($patient['level'] ?? '') === 'College' ? 'selected' : '' ?>>College</option>
                            </select>
                        </div>
                        <div id="editYearGradeField" class="<?= in_array($patient['level'] ?? '', ['Elementary', 'High School', 'Senior High School', 'College']) ? '' : 'hidden' ?>">
                            <label id="editYearGradeLabel" class="block text-sm font-medium text-slate-700 mb-2"><?= ($patient['level'] ?? '') === 'College' ? 'Year' : 'Grade' ?></label>
                            <input type="text" name="year_grade" id="editYearGrade" value="<?= htmlspecialchars($patient['year_grade'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div id="editCourseField" class="<?= ($patient['level'] ?? '') === 'College' ? '' : 'hidden' ?>">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Course</label>
                            <input type="text" name="course" id="editCourse" value="<?= htmlspecialchars($patient['course'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div id="editBlockField" class="<?= ($patient['level'] ?? '') === 'College' ? '' : 'hidden' ?>">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Block</label>
                            <input type="text" name="block" id="editBlock" value="<?= htmlspecialchars($patient['block'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div id="editStrandField" class="<?= ($patient['level'] ?? '') === 'Senior High School' ? '' : 'hidden' ?>">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Strand</label>
                            <input type="text" name="strand" id="editStrand" value="<?= htmlspecialchars($patient['strand'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div id="editSectionField" class="<?= in_array($patient['level'] ?? '', ['Pre-school', 'Elementary', 'High School']) ? '' : 'hidden' ?>">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Section</label>
                            <input type="text" name="section" id="editSection" value="<?= htmlspecialchars($patient['section'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Guardian/Parent</label>
                            <input type="text" name="guardian" value="<?= htmlspecialchars($patient['guardian'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Faculty Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Faculty Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Department</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($patient['department'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Contact Information</h3>
                    <div id="editContactNumbersContainer">
                        <?php 
                        $contacts = $patient['contacts'] ?? [];
                        if (is_string($contacts)) {
                            $contacts = json_decode($contacts, true) ?: [];
                        }
                        if (empty($contacts)) {
                            $contacts = [''];
                        }
                        foreach ($contacts as $index => $contact): 
                        ?>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Contact Number <?= $index + 1 ?> *</label>
                            <div class="flex gap-2">
                                <input type="tel" name="contacts[]" value="<?= htmlspecialchars($contact) ?>" required class="flex-1 rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
                                <?php if ($index > 0): ?>
                                <button type="button" onclick="removeContactNumber(this)" class="px-3 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addContactNumber()" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Contact Number
                    </button>
                </div>

                <!-- Medical Information -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Medical Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Allergies</label>
                        <textarea name="allergies" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Leave blank if none - List any allergies..."><?= htmlspecialchars($patient['allergies'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-8">
                <button type="button" onclick="closeEditModal()" class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Save Changes
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
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 max-h-[calc(100vh-8rem)] overflow-y-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">Add Visitation Record</h2>
                <button onclick="closeVisitationModal()" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                    <svg class="w-6 h-6 text-purple-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="visitationForm" method="POST" action="save_visitation.php" onsubmit="return submitVisitationForm(event);">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>">
            <input type="hidden" name="patient_type" value="<?= $patientType ?>">
            
            <div class="space-y-6">
                <!-- Patient Information Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Patient Information</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-lg p-3">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Name:</label>
                            <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($patient['name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Type:</label>
                            <div class="text-sm font-semibold text-slate-800"><?= ucfirst($patientType) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Visit Information Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Visit Information</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Reason for Visit *</label>
                            <select name="reason" id="reasonSelect" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" onchange="toggleOtherReason()">
                                <option value="">Select reason</option>
                                <option value="cold">Cold</option>
                                <option value="cough">Cough</option>
                                <option value="dizziness">Dizziness</option>
                                <option value="fever">Fever</option>
                                <option value="headache">Headache</option>
                                <option value="injury">Injury</option>
                                <option value="medication">Medication</option>
                                <option value="nausea">Nausea</option>
                                <option value="stomach_ache">Stomach Ache</option>
                                <option value="fatigue">Fatigue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Date & Time *</label>
                            <input type="datetime-local" name="visit_date" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    <div id="otherReasonDiv" class="hidden mt-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Specify Reason *</label>
                        <input type="text" name="other_reason" id="otherReasonInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Please specify the reason">
                    </div>
                </div>

                <!-- Symptoms & Notes Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Symptoms & Notes</h3>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms</label>
                            <textarea name="symptoms" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Describe symptoms and observations..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Other Notes</label>
                            <textarea name="other_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Additional notes or observations..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Vital Signs Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Vital Signs</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate (BPM)</label>
                            <input type="number" name="heart_rate" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="e.g., 72">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure</label>
                            <input type="text" name="blood_pressure" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="e.g., 120/80" oninput="formatBloodPressure(this)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Temperature (°C)</label>
                            <input type="number" name="temperature" step="0.1" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="e.g., 36.5">
                        </div>
                    </div>
                </div>

                <!-- Medication Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Medication</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="medication_given" id="medicationGiven" class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500" onchange="toggleMedicationFields()">
                            <label for="medicationGiven" class="text-sm font-medium text-slate-700">Medication Given</label>
                        </div>
                        <div id="medicationFields" class="hidden space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Medication Name</label>
                                <select name="medication_name" id="medicationSelect" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" onchange="toggleOtherMedication()">
                                    <option value="">Select medication</option>
                                    <option value="paracetamol">Paracetamol</option>
                                    <option value="ibuprofen">Ibuprofen</option>
                                    <option value="aspirin">Aspirin</option>
                                    <option value="antihistamine">Antihistamine</option>
                                    <option value="cough_syrup">Cough Syrup</option>
                                    <option value="vitamin_c">Vitamin C</option>
                                    <option value="bandage">Bandage</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div id="otherMedicationDiv" class="hidden">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Specify Medication *</label>
                                <input type="text" name="other_medication" id="otherMedicationInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Please specify the medication">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Medication Notes</label>
                                <textarea name="medication_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Dosage, instructions, etc..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Injury & First Aid Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">Injury & First Aid</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="injury" id="injuryOccurred" class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500" onchange="toggleInjuryFields()">
                            <label for="injuryOccurred" class="text-sm font-medium text-slate-700">Injury Occurred</label>
                        </div>
                        <div id="injuryFields" class="hidden space-y-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="first_aid_given" id="firstAidGiven" class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500">
                                <label for="firstAidGiven" class="text-sm font-medium text-slate-700">First Aid Given</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">First Aid Type</label>
                                <select name="first_aid_type" id="firstAidSelect" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" onchange="toggleOtherFirstAid()">
                                    <option value="">Select first aid type</option>
                                    <option value="bandage">Bandage</option>
                                    <option value="ice_pack">Ice Pack</option>
                                    <option value="cleaning">Wound Cleaning</option>
                                    <option value="elevation">Elevation</option>
                                    <option value="rest">Rest</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div id="otherFirstAidDiv" class="hidden">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Specify First Aid *</label>
                                <input type="text" name="other_first_aid" id="otherFirstAidInput" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="Please specify the first aid">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeVisitationModal()" class="px-6 py-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">Cancel</button>
                    <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-colors font-medium shadow-lg">Save Visitation Record</button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- Visitation Details Modal -->
<div id="visitationDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 max-h-[calc(100vh-8rem)] overflow-y-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">Visitation Details</h2>
                <button onclick="closeVisitationDetailsModal()" class="p-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                    <svg class="w-6 h-6 text-purple-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div id="visitationDetailsContent" class="space-y-6">
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
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
    <div class="relative w-full max-w-3xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 max-h-[calc(100vh-16rem)] overflow-y-auto">
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
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

// Edit modal functions
function openEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // Initialize the edit form when opening
        initializeEditForm();
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
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

function toggleMedicationFields() {
    const medicationCheckbox = document.getElementById('medicationGiven');
    const medicationFields = document.getElementById('medicationFields');
    
    if (medicationCheckbox.checked) {
        medicationFields.classList.remove('hidden');
    } else {
        medicationFields.classList.add('hidden');
    }
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

function toggleInjuryFields() {
    const injuryCheckbox = document.getElementById('injuryOccurred');
    const injuryFields = document.getElementById('injuryFields');
    
    if (injuryCheckbox.checked) {
        injuryFields.classList.remove('hidden');
    } else {
        injuryFields.classList.add('hidden');
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

function validateVisitationForm() {
    const reasonSelect = document.getElementById('reasonSelect');
    const otherReasonInput = document.getElementById('otherReasonInput');
    const medicationCheckbox = document.getElementById('medicationGiven');
    const medicationSelect = document.getElementById('medicationSelect');
    const otherMedicationInput = document.getElementById('otherMedicationInput');
    const injuryCheckbox = document.getElementById('injuryOccurred');
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
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-[99999] max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 transform transition-all duration-500 ease-out translate-x-full opacity-0 border-l-4 border-l-green-500`;
            
            notification.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">✅</div>
                    </div>
                    <div class="flex-1">
                        <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${data.message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                        <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            setTimeout(() => {
                closeVisitationModal();
                window.location.reload();
            }, 1000);
        } else {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-[99999] max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 transform transition-all duration-500 ease-out translate-x-full opacity-0 border-l-4 border-l-red-500`;
            
            notification.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">❌</div>
                    </div>
                    <div class="flex-1">
                        <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">${data.message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                        <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
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

function viewVisitationRecord(visitId, status = 'active') {
    // Show loading
    document.getElementById('visitationLogDetailsContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></div>';
    
    // Show modal
    document.getElementById('visitationLogDetailsModal').classList.remove('hidden');
    document.getElementById('visitationLogDetailsModal').classList.add('flex');
    
    // Fetch visitation log details
    fetch(`../logs/visitation_details_view.php?id=${visitId}&archived=${status === 'archived' ? '1' : '0'}&ajax=1`)
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
    showVisitationDetails(visitId);
}

function closeVisitationDetailsModal() {
    const modal = document.getElementById('visitationDetailsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function showVisitationDetails(visitId) {
    // Show the existing modal
    const modal = document.getElementById('visitationDetailsModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Show loading state
        document.getElementById('visitationDetailsContent').innerHTML = `
            <div class="text-center py-16">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mb-4"></div>
                <p class="text-slate-600 text-lg font-medium">Loading visitation details...</p>
            </div>
        `;
        
        // Load details
        fetch(`../logs/get_visitation_details.php?id=${visitId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('visitationDetailsContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('visitationDetailsContent').innerHTML = `
                    <div class="text-center py-16">
                        <div class="text-red-500 text-6xl mb-4">⚠️</div>
                        <h3 class="text-red-600 text-xl font-semibold mb-2">Error Loading Details</h3>
                        <p class="text-slate-600">Please try again or contact support if the problem persists.</p>
                    </div>
                `;
            });
    }
}

function archiveVisitation(visitId) {
    if (confirm('Are you sure you want to archive this visitation record? It will be moved to the patient\'s archive.')) {
        showNotification('Archiving visitation record...', 'info');
        
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
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-[99999] max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 transform transition-all duration-500 ease-out translate-x-full opacity-0 border-l-4 border-l-green-500`;
                
                notification.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">✅</div>
                        </div>
                        <div class="flex-1">
                            <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">Visitation record archived successfully!</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                            <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                setTimeout(() => notification.classList.remove('translate-x-full', 'opacity-0'), 100);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-[99999] max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-4 transform transition-all duration-500 ease-out translate-x-full opacity-0 border-l-4 border-l-red-500`;
                
                notification.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">❌</div>
                        </div>
                        <div class="flex-1">
                            <p class="text-clinic-dark font-poppins font-medium text-sm leading-relaxed">Error archiving visitation: ${data.message || 'Unknown error'}</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="close-btn flex-shrink-0 w-6 h-6 rounded-lg hover:bg-clinic-ivory/40 flex items-center justify-center transition-colors duration-200">
                            <svg class="w-4 h-4 text-clinic-dark/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                setTimeout(() => notification.classList.remove('translate-x-full', 'opacity-0'), 100);
            }
        })
        .catch(error => {
            showNotification('Error archiving visitation: ' + error.message, 'error');
        });
    }
}

function restoreVisitation(archiveId) {
    if (confirm('Are you sure you want to restore this archived visitation record? This will move it back to active records.')) {
        showNotification('Restoring visitation record...', 'info');
        
        fetch('../admin/restore_visitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `archive_id=${archiveId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showNotification('Visitation record restored successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Error restoring visitation record: ' + data.message, 'error');
                }
            } catch (e) {
                showNotification('Error restoring visitation record: Invalid response from server', 'error');
            }
        })
        .catch(error => {
            showNotification('Error restoring visitation record. Please try again.', 'error');
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
        // Show loading notification
        showNotification('Archiving medical record...', 'info');
        
        // Archive medical record using AJAX
        fetch('../admin/archive_medical_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${recordId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showNotification('Medical record archived successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Error archiving medical record: ' + data.message, 'error');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.error('Response text:', text);
                showNotification('Error archiving medical record: Invalid response from server', 'error');
            }
        })
        .catch(error => {
            console.error('Error archiving medical record:', error);
            showNotification('Error archiving medical record. Please try again.', 'error');
        });
    }
}

function restoreMedicalRecord(archiveId) {
    if (confirm('Are you sure you want to restore this archived medical record? This will move it back to active records.')) {
        // Show loading notification
        showNotification('Restoring medical record...', 'info');
        
        // Restore archived medical record
        fetch('../admin/restore_medical_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `archive_id=${archiveId}&patient_id=<?= $patientId ?>&patient_type=<?= $patientType ?>`
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showNotification('Medical record restored successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Error restoring medical record: ' + data.message, 'error');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.error('Response text:', text);
                showNotification('Error restoring medical record: Invalid response from server', 'error');
            }
        })
        .catch(error => {
            console.error('Error restoring medical record:', error);
            showNotification('Error restoring medical record. Please try again.', 'error');
        });
    }
}

// Notification system - now uses global system from header.php
// The showNotification function is now globally available

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



function submitEditPatientForm(event) {
    event.preventDefault();
    console.log('Form submission started...');
    
    // Get form data
    const form = document.getElementById('editPatientForm');
    const formData = new FormData(form);
    
    // Log form data for debugging
    console.log('Form data:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Validate required fields
    const requiredFields = ['name', 'rfid', 'gender'];
    const missingFields = [];
    
    for (const field of requiredFields) {
        if (!formData.get(field) || formData.get(field).trim() === '') {
            missingFields.push(field);
        }
    }
    
    // Validate contacts
    const contacts = formData.getAll('contacts[]').filter(contact => contact.trim() !== '');
    if (contacts.length === 0) {
        missingFields.push('contacts');
    }
    
    if (missingFields.length > 0) {
        alert('Please fill in all required fields: ' + missingFields.join(', '));
        return false;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;
    
    // Submit via AJAX
    fetch('update_patient_info.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (response.ok) {
            return response.text();
        } else {
            throw new Error('Server error: ' + response.status);
        }
    })
    .then(data => {
        console.log('Response data:', data);
        // Check if response contains success redirect
        if (data.includes('Location:') && data.includes('message_type=success')) {
            alert('Patient information updated successfully!');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert('Error updating patient information. Please try again.');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating patient information: ' + error.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
    
    return false;
}

function changeStatus(newStatus) {
    if (!newStatus) return;
    
    const currentStatus = '<?= $patient['status'] ?? 'Active' ?>';
    if (newStatus === currentStatus) {
        showNotification('Status is already ' + newStatus, 'info');
        return;
    }
    
    if (confirm(`Are you sure you want to change status from ${currentStatus} to ${newStatus}?`)) {
        // Get the select element and show loading
        const selectElement = event.target;
        const originalValue = selectElement.value;
        
        // Disable the select and show loading
        selectElement.disabled = true;
        selectElement.innerHTML = '<option value="">Updating...</option>';
        
        // Show loading notification
        showNotification('Updating status...', 'info');
        
        // Create form data for AJAX
        const formData = new FormData();
        formData.append('<?= $patientType === 'student' ? 'student_id' : 'faculty_id' ?>', '<?= $patientId ?>');
        formData.append('new_status', newStatus);
        formData.append('status_notes', `Status changed from ${currentStatus} to ${newStatus}`);
        
        // Submit via AJAX
        fetch('<?= $patientType === 'student' ? 'update_student_status.php' : 'update_faculty_status.php' ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('success') || data.includes('updated')) {
                showNotification(`Status updated from ${currentStatus} to ${newStatus}`, 'success');
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error updating status. Please try again.', 'error');
                // Reset select element
                selectElement.disabled = false;
                selectElement.value = '';
                // Reload the page to restore original state
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating status. Please try again.', 'error');
            // Reset select element
            selectElement.disabled = false;
            selectElement.value = '';
            // Reload the page to restore original state
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    } else {
        // Reset dropdown to default
        event.target.value = '';
    }
}

// Dynamic field visibility for edit form (same as registration forms)
function initializeEditForm() {
    const levelSelect = document.getElementById('editLevel');
    if (!levelSelect) return;
    
    const yearGradeField = document.getElementById('editYearGradeField');
    const yearGradeInput = document.getElementById('editYearGrade');
    const yearGradeLabel = document.getElementById('editYearGradeLabel');
    const courseField = document.getElementById('editCourseField');
    const blockField = document.getElementById('editBlockField');
    const strandField = document.getElementById('editStrandField');
    const sectionField = document.getElementById('editSectionField');
    
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

function updateStudentFields() {
    // This function is called from the onchange event in the edit modal
    initializeEditForm();
}


// Contact number management functions
function addContactNumber() {
    const container = document.getElementById('editContactNumbersContainer');
    const addBtn = document.querySelector('button[onclick="addContactNumber()"]');
    
    // Check if we already have 2 contacts (1 default + 1 added)
    const existingContacts = container.querySelectorAll('input[name="contacts[]"]');
    if (existingContacts.length >= 2) {
        return; // Don't add more than 2 total
    }
    
    const contactItem = document.createElement('div');
    contactItem.className = 'mb-3';
    
    const contactNumber = existingContacts.length + 1;
    
    contactItem.innerHTML = `
        <label class="block text-sm font-medium text-slate-700 mb-2">Contact Number ${contactNumber} *</label>
        <div class="flex gap-2">
            <input type="tel" name="contacts[]" value="" required class="flex-1 rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200">
            <button type="button" onclick="removeContactNumber(this)" class="px-3 py-3 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    `;
    
    container.appendChild(contactItem);
    
    // Disable the add button after adding extra contact
    addBtn.disabled = true;
    addBtn.classList.add('opacity-50', 'cursor-not-allowed');
    addBtn.classList.remove('hover:bg-blue-200');
}

function removeContactNumber(button) {
    const contactItem = button.closest('.mb-3');
    const addBtn = document.querySelector('button[onclick="addContactNumber()"]');
    
    contactItem.remove();
    
    // Re-enable the add button when extra contact is removed
    addBtn.disabled = false;
    addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    addBtn.classList.add('hover:bg-blue-200');
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

    // Print patient record function
    function printPatientRecord() {
        // Create the print content
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Patient Information - CARE</title>
                <style>
                    @media print {
                        * {
                            -webkit-print-color-adjust: exact !important;
                            color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        @page { margin: 0.5in; size: A4; }
                        body {
                            font-family: 'Times New Roman', serif !important;
                            font-size: 12pt !important;
                            line-height: 1.4 !important;
                            color: #000 !important;
                            background: white !important;
                            margin: 0 !important;
                            padding: 20px !important;
                        }
                        .print-container {
                            text-align: center !important;
                            width: 100% !important;
                        }
                        .print-header {
                            margin-bottom: 30px !important;
                            text-align: center !important;
                        }
                        .print-logo {
                            font-size: 32pt !important;
                            font-weight: bold !important;
                            color: #000 !important;
                            margin-bottom: 10px !important;
                        }
                        .print-institution {
                            font-size: 16pt !important;
                            color: #000 !important;
                            margin-bottom: 20px !important;
                        }
                        .print-subtitle {
                            font-size: 18pt !important;
                            font-weight: bold !important;
                            color: #000 !important;
                            margin-bottom: 30px !important;
                            border-bottom: 2px solid #000 !important;
                            padding-bottom: 10px !important;
                        }
                        .print-content {
                            display: grid !important;
                            grid-template-columns: 1fr 1fr !important;
                            gap: 30px !important;
                            text-align: left !important;
                            margin-top: 20px !important;
                            border-bottom: 2px solid #000 !important;
                            padding-bottom: 20px !important;
                        }
                        .print-section {
                            border: 1px solid #000 !important;
                            padding: 15px !important;
                            background: #f9f9f9 !important;
                            page-break-inside: avoid !important;
                            margin-bottom: 15px !important;
                        }
                        .print-section h3 {
                            font-size: 14pt !important;
                            font-weight: bold !important;
                            color: #000 !important;
                            margin-bottom: 10px !important;
                            border-bottom: 1px solid #000 !important;
                            padding-bottom: 5px !important;
                        }
                        .print-field {
                            margin: 8px 0 !important;
                            display: flex !important;
                            justify-content: space-between !important;
                            align-items: center !important;
                        }
                        .print-label {
                            font-weight: bold !important;
                            color: #000 !important;
                            font-size: 11pt !important;
                        }
                        .print-value {
                            color: #000 !important;
                            font-size: 11pt !important;
                            text-align: right !important;
                            margin-left: 10px !important;
                        }
                    }
                    @media screen {
                        body { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="print-container">
                    <div class="print-header">
                        <div class="print-logo">CARE</div>
                        <div class="print-institution">Our Lady of the Sacred Heart Inc.</div>
                        <div class="print-subtitle">Patient (<?= ucfirst($patientType) ?>) Information</div>
                    </div>
                    <div class="print-content">
                        <div>
                            <div class="print-section">
                                <h3>Personal Information</h3>
                                <div class="print-field">
                                    <span class="print-label">Name:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['name']) ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">ID:</span>
                                    <span class="print-value">#<?= $patientId ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">Type:</span>
                                    <span class="print-value"><?= ucfirst($patientType) ?></span>
                                </div>
                                <?php if (!empty($patient['age']) && $patient['age'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Age:</span>
                                    <span class="print-value"><?= htmlspecialchars((string)$patient['age']) ?> years old</span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['gender']) && $patient['gender'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Gender:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['gender']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['dob']) && $patient['dob'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Date of Birth:</span>
                                    <span class="print-value"><?= htmlspecialchars(date('M j, Y', strtotime($patient['dob']))) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['religion']) && $patient['religion'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Religion:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['religion']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($patientType === 'student'): ?>
                                <?php if (!empty($patient['level']) && $patient['level'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Level:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['level']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['year_grade']) && $patient['year_grade'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Year/Grade:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['year_grade']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['section']) && $patient['section'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Section:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['section']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['course']) && $patient['course'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Course:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['course']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['strand']) && $patient['strand'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Strand:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['strand']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['block']) && $patient['block'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Block:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['block']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php else: ?>
                                <?php if (!empty($patient['department']) && $patient['department'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Department:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['department']) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="print-field">
                                    <span class="print-label">Senior:</span>
                                    <span class="print-value"><?= ($patient['sr'] ?? 0) ? 'Yes' : 'No' ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="print-section">
                                <h3>Contact Information</h3>
                                <?php if (!empty($patient['address']) && $patient['address'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Address:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['address']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['guardian']) && $patient['guardian'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Guardian:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['guardian']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($patient['emergency_contact']) && $patient['emergency_contact'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Emergency Contact:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['emergency_contact']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="print-section">
                                <h3>Medical Information</h3>
                                <div class="print-field">
                                    <span class="print-label">Allergies:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['allergies'] ?? 'None') ?></span>
                                </div>
                                <?php if (!empty($patient['rfid'])): ?>
                                <div class="print-field">
                                    <span class="print-label">RFID:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['rfid']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        `;
        
        // Create a hidden iframe for printing
        const iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.left = '-9999px';
        iframe.style.top = '-9999px';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        
        document.body.appendChild(iframe);
        
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(printContent);
        iframeDoc.close();
        
        iframe.onload = function() {
            iframe.contentWindow.print();
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 1000);
        };
    }
</script>


<?php include __DIR__ . '/../partials/footer.php'; ?>
