<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/encryption.php';

require_admin_auth();
$pdo = get_pdo();

// Get record ID from URL (handle both encrypted token and direct ID)
$recordId = 0;
$isArchived = false;

if (isset($_GET['token'])) {
    // New encrypted token method
    $tokenData = PatientIdEncryption::validateToken($_GET['token']);
    if ($tokenData) {
        $recordId = (int)$tokenData['id'];
        $isArchived = isset($_GET['archived']) && $_GET['archived'] == '1';
    } else {
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            echo '<div class="text-center py-8 text-red-600">Invalid medical record token.</div>';
            exit;
        }
        header('Location: ../admin/dashboard.php?error=invalid_medical_token');
        exit;
    }
} else {
    // Fallback to old method for backward compatibility
    $recordId = (int)($_GET['id'] ?? 0);
    $isArchived = isset($_GET['archived']) && $_GET['archived'] == '1';
}

if ($recordId <= 0) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo '<div class="text-center py-8 text-red-600">Invalid record ID.</div>';
        exit;
    }
    header('Location: ../admin/dashboard.php?error=invalid_record');
    exit;
}

// Get medical record (check if archived)
$record = null;
if ($isArchived) {
    // Try to get from archive tables - check both student and faculty archives
    $archiveTables = ['student_medical_archive', 'faculty_medical_archive'];
    foreach ($archiveTables as $table) {
        // Check if table exists first
        $tableExists = $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0;
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch();
            if ($record) {
                $record['is_archived'] = true;
                break;
            }
        }
    }
} else {
    // Get from active medical records
    $stmt = $pdo->prepare('SELECT * FROM medical_records WHERE id = ?');
    $stmt->execute([$recordId]);
    $record = $stmt->fetch();
}

if (!$record) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        // For AJAX requests, return error content instead of redirecting
        echo '<div class="text-center py-8 text-red-600">Medical record not found.</div>';
        exit;
    }
    header('Location: ../admin/dashboard.php?error=record_not_found');
    exit;
}

// Handle AJAX request for popup content
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Parse form data
    $formData = json_decode($record['form_data'], true);
    
    // Get patient information
    $patient = null;
    if ($record['patient_type'] === 'student') {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$record['patient_id']]);
        $patient = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
        $stmt->execute([$record['patient_id']]);
        $patient = $stmt->fetch();
    }
    
    // Helper functions for status calculations
    function getBMIStatus($bmi) {
        if ($bmi < 18.5) return ['status' => 'Underweight', 'color' => 'blue'];
        if ($bmi < 25) return ['status' => 'Normal', 'color' => 'green'];
        if ($bmi < 30) return ['status' => 'Overweight', 'color' => 'yellow'];
        return ['status' => 'Obese', 'color' => 'red'];
    }
    
    function getHeartRateStatus($hr) {
        if ($hr < 60) return ['status' => 'Low', 'color' => 'blue'];
        if ($hr <= 100) return ['status' => 'Normal', 'color' => 'green'];
        return ['status' => 'High', 'color' => 'red'];
    }
    
    function getTemperatureStatus($temp) {
        if ($temp < 36.1) return ['status' => 'Low', 'color' => 'blue'];
        if ($temp <= 37.2) return ['status' => 'Normal', 'color' => 'green'];
        return ['status' => 'High', 'color' => 'red'];
    }
    
    function getBloodPressureStatus($bp) {
        if (empty($bp)) return ['status' => 'N/A', 'color' => 'gray'];
        $parts = explode('/', $bp);
        if (count($parts) !== 2) return ['status' => 'N/A', 'color' => 'gray'];
        
        $systolic = (int)$parts[0];
        $diastolic = (int)$parts[1];
        
        if ($systolic < 90 || $diastolic < 60) return ['status' => 'Low', 'color' => 'blue'];
        if ($systolic <= 120 && $diastolic <= 80) return ['status' => 'Normal', 'color' => 'green'];
        if ($systolic <= 139 && $diastolic <= 89) return ['status' => 'Elevated', 'color' => 'yellow'];
        return ['status' => 'High', 'color' => 'red'];
    }
    
    // Calculate BMI if height and weight are available
    $calculatedBMI = null;
    $bmiStatus = null;
    if (!empty($formData['height']) && !empty($formData['weight'])) {
        $heightInMeters = $formData['height'] / 100;
        $calculatedBMI = round($formData['weight'] / ($heightInMeters * $heightInMeters), 1);
        $bmiStatus = getBMIStatus($calculatedBMI);
    }
    
    // Calculate statuses for vital signs
    $heartRateStatus = !empty($formData['heart_rate']) ? getHeartRateStatus($formData['heart_rate']) : null;
    $temperatureStatus = !empty($formData['temperature']) ? getTemperatureStatus($formData['temperature']) : null;
    $bloodPressureStatus = getBloodPressureStatus($formData['blood_pressure'] ?? '');
    
    // Return only the content part
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="space-y-6">
            <!-- Record Information -->
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-slate-800">Record Information</h3>
                    <?php if (isset($record['is_archived']) && $record['is_archived']): ?>
                        <span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">ARCHIVED</span>
                    <?php endif; ?>
                </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Record ID</div>
                            <div class="font-semibold text-slate-800">#<?= $record['id'] ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Form Type</div>
                            <div class="font-semibold text-slate-800"><?= ucfirst($record['form_type']) ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Created</div>
                            <div class="font-semibold text-slate-800"><?= date('M j, Y g:i A', strtotime($record['created_at'])) ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Last Updated</div>
                            <div class="font-semibold text-slate-800"><?= date('M j, Y g:i A', strtotime($record['updated_at'])) ?></div>
                        </div>
                    </div>
            </div>

            <!-- Form Data -->
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Form Data</h3>
                <?php if ($record['form_type'] === 'general'): ?>
                    <div class="space-y-3">
                        <!-- Physical Measurements -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Physical Measurements</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">Height:</span>
                                    <span class="font-semibold text-slate-800"><?= $formData['height'] ?? 'N/A' ?> cm</span>
                                </div>
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">Weight:</span>
                                    <span class="font-semibold text-slate-800"><?= $formData['weight'] ?? 'N/A' ?> kg</span>
                                </div>
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">BMI:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800"><?= $calculatedBMI ?? ($formData['bmi'] ?? 'N/A') ?></span>
                                        <?php if ($bmiStatus): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $bmiStatus['color'] ?>-100 text-<?= $bmiStatus['color'] ?>-800">
                                                <?= $bmiStatus['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vital Signs -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Vital Signs</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">Heart Rate:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800"><?= $formData['heart_rate'] ?? 'N/A' ?> BPM</span>
                                        <?php if ($heartRateStatus): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $heartRateStatus['color'] ?>-100 text-<?= $heartRateStatus['color'] ?>-800">
                                                <?= $heartRateStatus['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">Temperature:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800"><?= $formData['temperature'] ?? 'N/A' ?>°C</span>
                                        <?php if ($temperatureStatus): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $temperatureStatus['color'] ?>-100 text-<?= $temperatureStatus['color'] ?>-800">
                                                <?= $temperatureStatus['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-3 px-4 bg-white rounded-lg border border-slate-200">
                                    <span class="text-slate-600 font-medium">Blood Pressure:</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800"><?= $formData['blood_pressure'] ?? 'N/A' ?></span>
                                        <?php if ($bloodPressureStatus): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $bloodPressureStatus['color'] ?>-100 text-<?= $bloodPressureStatus['color'] ?>-800">
                                                <?= $bloodPressureStatus['status'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment & Plan -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-1">Assessment & Plan</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <span class="text-slate-600">Assessment:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= $formData['assessment'] ?? 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                <?php elseif ($record['form_type'] === 'medical_history'): ?>
                    <!-- Medical History Form Display (handles both old and new formats) -->
                    <div class="space-y-3">
                        <!-- Ongoing Conditions -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Ongoing Medical Conditions</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <?php 
                                $ongoingConditions = '';
                                if (isset($formData['ongoing_conditions'])) {
                                    if (is_array($formData['ongoing_conditions'])) {
                                        // Old format: array of conditions
                                        $ongoingConditions = !empty($formData['ongoing_conditions']) ? implode(', ', $formData['ongoing_conditions']) : '';
                                        if (!empty($formData['ongoing_conditions_other'])) {
                                            $ongoingConditions .= ($ongoingConditions ? ', ' : '') . $formData['ongoing_conditions_other'];
                                        }
                                    } else {
                                        // New format: string
                                        $ongoingConditions = $formData['ongoing_conditions'];
                                    }
                                }
                                ?>
                                <span class="text-slate-600">Conditions:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= !empty($ongoingConditions) ? htmlspecialchars($ongoingConditions) : 'N/A' ?></span>
                            </div>
                        </div>

                        <!-- Family History -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Family Medical History</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <?php 
                                $familyHistory = '';
                                if (isset($formData['family_history'])) {
                                    // New format: string
                                    $familyHistory = $formData['family_history'];
                                } elseif (isset($formData['family_conditions'])) {
                                    // Old format: array of conditions
                                    if (is_array($formData['family_conditions'])) {
                                        $familyHistory = !empty($formData['family_conditions']) ? implode(', ', $formData['family_conditions']) : '';
                                        if (!empty($formData['family_conditions_other'])) {
                                            $familyHistory .= ($familyHistory ? ', ' : '') . $formData['family_conditions_other'];
                                        }
                                    }
                                }
                                ?>
                                <span class="text-slate-600">Family History:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= !empty($familyHistory) ? htmlspecialchars($familyHistory) : 'N/A' ?></span>
                            </div>
                        </div>

                        <!-- Allergies -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Allergies</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <span class="text-slate-600">Allergies:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= !empty($formData['allergies']) ? htmlspecialchars($formData['allergies']) : 'N/A' ?></span>
                            </div>
                        </div>

                        <!-- Current Medications -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Current Medications</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <span class="text-slate-600">Medications:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= !empty($formData['current_medications']) ? htmlspecialchars($formData['current_medications']) : 'N/A' ?></span>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Additional Notes</h4>
                            <div class="py-2 px-3 bg-white rounded border">
                                <span class="text-slate-600">Notes:</span>
                                <span class="font-semibold text-slate-800 ml-2"><?= !empty($formData['additional_notes']) ? htmlspecialchars($formData['additional_notes']) : 'N/A' ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Other form types -->
                    <div class="space-y-3">
                        <?php foreach ($formData as $key => $value): ?>
                            <div class="flex justify-between items-center py-2 px-3 bg-white rounded border">
                                <span class="text-slate-600"><?= ucfirst(str_replace('_', ' ', $key)) ?>:</span>
                                <span class="font-semibold text-slate-800"><?= is_array($value) ? json_encode($value) : $value ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Restore Button for Archived Records -->
            <?php if (isset($record['is_archived']) && $record['is_archived']): ?>
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <button onclick="restoreMedicalRecord(<?= $record['id'] ?>)" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Restore This Medical Record
                    </button>
                    <p class="text-xs text-slate-500 text-center mt-2">This will move the record back to active medical records</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    exit;
}

// Get patient information
$patient = null;
if ($record['patient_type'] === 'student') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$record['patient_id']]);
    $patient = $stmt->fetch();
} else {
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([$record['patient_id']]);
    $patient = $stmt->fetch();
}

if (!$patient) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo '<div class="text-center py-8 text-red-600">Patient not found.</div>';
        exit;
    }
    header('Location: ../admin/dashboard.php?error=patient_not_found');
    exit;
}

// Parse form data
$formData = json_decode($record['form_data'], true);

$pageTitle = 'Medical Record View';
$showTopNav = true;
$showSidebar = false;
include __DIR__ . '/../partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-20 right-4 z-50"></div>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <button onclick="goBack()" class="inline-flex items-center gap-2 px-4 py-2 text-clinic-blue hover:text-clinic-tea hover:bg-clinic-blue/5 rounded-lg transition-colors duration-200 mr-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back
                    </button>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">Medical Record</h1>
                        <p class="text-sm text-slate-500"><?= htmlspecialchars($patient['name']) ?> - <?= ucfirst($record['patient_type']) ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="editRecord()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                    <button onclick="printMedicalRecord()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors no-print">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                    <a href="patient_view.php?id=<?= $record['patient_id'] ?>&type=<?= $record['patient_type'] ?>" class="px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600 transition-colors">
                        Back to Patient
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Record Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Record Information</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-slate-500">Record ID</label>
                            <p class="text-slate-800">#<?= $record['id'] ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-500">Form Type</label>
                            <p class="text-slate-800"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $record['form_type']))) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-500">Created</label>
                            <p class="text-slate-800"><?= date('M j, Y g:i A', strtotime($record['created_at'])) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-500">Last Updated</label>
                            <p class="text-slate-800"><?= date('M j, Y g:i A', strtotime($record['updated_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Data -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-6">Form Data</h2>
                    
                    <?php if ($record['form_type'] === 'medical_history'): ?>
                        <!-- Medical History Form Display -->
                        <div class="space-y-3">
                            <!-- Ongoing Medical Conditions -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">Ongoing Medical Conditions</h3>
                                <?php 
                                $ongoingConditions = [];
                                if (isset($formData['ongoing_conditions'])) {
                                    if (is_array($formData['ongoing_conditions'])) {
                                        $ongoingConditions = $formData['ongoing_conditions'];
                                    }
                                }
                                $hasOngoingConditions = !empty(array_filter($ongoingConditions, function($condition) {
                                    return $condition !== 'others';
                                }));
                                ?>
                                <?php if ($hasOngoingConditions || !empty($formData['ongoing_conditions_other'])): ?>
                                    <div class="space-y-1">
                                        <?php if (in_array('error_of_refraction', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Error of Refraction</p>
                                        <?php endif; ?>
                                        <?php if (in_array('asthma', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Asthma</p>
                                        <?php endif; ?>
                                        <?php if (in_array('seizure', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Seizure</p>
                                        <?php endif; ?>
                                        <?php if (in_array('heart_problem', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Heart Problem</p>
                                        <?php endif; ?>
                                        <?php if (in_array('anemia', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Anemia</p>
                                        <?php endif; ?>
                                        <?php if (in_array('bleeding_disorder', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Bleeding Disorder</p>
                                        <?php endif; ?>
                                        <?php if (in_array('hernia', $ongoingConditions)): ?>
                                            <p class="text-xs text-slate-700">• Hernia</p>
                                        <?php endif; ?>
                                        <?php if (!empty($formData['ongoing_conditions_other'])): ?>
                                            <p class="text-xs text-slate-700">• <?= htmlspecialchars($formData['ongoing_conditions_other']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- Surgery/Hospitalization -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">Surgery/Hospitalization</h3>
                                <?php if (($formData['surgery_status'] ?? 'no') === 'yes'): ?>
                                    <p class="text-xs text-slate-700">Yes</p>
                                    <?php if (!empty($formData['surgery_details'])): ?>
                                        <textarea disabled class="w-full rounded border border-slate-300 px-3 py-2 bg-slate-100 text-slate-800 text-xs" rows="2"><?= htmlspecialchars($formData['surgery_details']) ?></textarea>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- Family Medical History -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">Family Medical History</h3>
                                <?php 
                                $familyConditions = [];
                                if (isset($formData['family_conditions'])) {
                                    if (is_array($formData['family_conditions'])) {
                                        $familyConditions = $formData['family_conditions'];
                                    }
                                }
                                $hasFamilyConditions = !empty(array_filter($familyConditions, function($condition) {
                                    return $condition !== 'others';
                                }));
                                ?>
                                <?php if ($hasFamilyConditions || !empty($formData['family_conditions_other'])): ?>
                                    <div class="space-y-1">
                                        <?php if (in_array('tuberculosis', $familyConditions)): ?>
                                            <p class="text-xs text-slate-700">• Tuberculosis</p>
                                        <?php endif; ?>
                                        <?php if (in_array('cancer', $familyConditions)): ?>
                                            <p class="text-xs text-slate-700">• Cancer</p>
                                        <?php endif; ?>
                                        <?php if (in_array('diabetes', $familyConditions)): ?>
                                            <p class="text-xs text-slate-700">• Diabetes</p>
                                        <?php endif; ?>
                                        <?php if (in_array('hypertension', $familyConditions)): ?>
                                            <p class="text-xs text-slate-700">• Hypertension</p>
                                        <?php endif; ?>
                                        <?php if (in_array('depression', $familyConditions)): ?>
                                            <p class="text-xs text-slate-700">• Depression</p>
                                        <?php endif; ?>
                                        <?php if (!empty($formData['family_conditions_other'])): ?>
                                            <p class="text-xs text-slate-700">• <?= htmlspecialchars($formData['family_conditions_other']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- Exposure to Cigarette/Vape Smoke -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">Exposure to Cigarette/Vape Smoke at Home</h3>
                                <?php if (($formData['smoke_exposure'] ?? 'no') === 'yes'): ?>
                                    <p class="text-xs text-slate-700">Yes</p>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- Immunization Received -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">Immunization Received</h3>
                                <?php 
                                $immunizations = $formData['immunization'] ?? [];
                                ?>
                                <?php if (!empty($immunizations)): ?>
                                    <div class="space-y-1">
                                        <?php if (in_array('mmr', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• MMR</p>
                                        <?php endif; ?>
                                        <?php if (in_array('dpt', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• DPT</p>
                                        <?php endif; ?>
                                        <?php if (in_array('bcg', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• BCG</p>
                                        <?php endif; ?>
                                        <?php if (in_array('chicken_pox', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• Chicken Pox</p>
                                        <?php endif; ?>
                                        <?php if (in_array('hepatitis_b', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• Hepatitis B</p>
                                        <?php endif; ?>
                                        <?php if (in_array('polio', $immunizations)): ?>
                                            <p class="text-xs text-slate-700">• Polio</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- COVID-19 Information -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">COVID-19 Information</h3>
                                <?php if (($formData['covid_positive'] ?? 'no') === 'yes'): ?>
                                    <p class="text-xs text-slate-700">Yes</p>
                                    <?php if (!empty($formData['covid_details'])): ?>
                                        <textarea disabled class="w-full rounded border border-slate-300 px-3 py-2 bg-slate-100 text-slate-800 text-xs" rows="2"><?= htmlspecialchars($formData['covid_details']) ?></textarea>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>

                            <!-- COVID-19 Vaccine Details -->
                            <div class="bg-slate-50 rounded-xl p-3">
                                <h3 class="text-base font-semibold text-slate-800 mb-2">COVID-19 Vaccine Details</h3>
                                <?php 
                                $covidVaccineBrands = $formData['covid_vaccine_brand'] ?? [];
                                $covidVaccines = $formData['covid_vaccine'] ?? [];
                                $hasVaccineData = !empty($covidVaccineBrands) || !empty($covidVaccines) || !empty($formData['other_vaccine_brand']);
                                ?>
                                <?php if ($hasVaccineData): ?>
                                    <!-- Vaccine Brands -->
                                    <?php if (!empty($covidVaccineBrands) || !empty($formData['other_vaccine_brand'])): ?>
                                        <div class="mb-3">
                                            <h4 class="text-sm font-medium text-slate-700 mb-2">Vaccine Brand</h4>
                                            <div class="space-y-1">
                                                <?php if (in_array('pfizer', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Pfizer</p>
                                                <?php endif; ?>
                                                <?php if (in_array('moderna', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Moderna</p>
                                                <?php endif; ?>
                                                <?php if (in_array('astrazeneca', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• AstraZeneca</p>
                                                <?php endif; ?>
                                                <?php if (in_array('janssen', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Janssen</p>
                                                <?php endif; ?>
                                                <?php if (in_array('sinovac', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Sinovac</p>
                                                <?php endif; ?>
                                                <?php if (in_array('sinopharm', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Sinopharm</p>
                                                <?php endif; ?>
                                                <?php if (in_array('sputnik', $covidVaccineBrands)): ?>
                                                    <p class="text-xs text-slate-700">• Sputnik V</p>
                                                <?php endif; ?>
                                                <?php if (!empty($formData['other_vaccine_brand'])): ?>
                                                    <p class="text-xs text-slate-700">• <?= htmlspecialchars($formData['other_vaccine_brand']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Vaccine Doses -->
                                    <?php if (!empty($covidVaccines)): ?>
                                        <div>
                                            <h4 class="text-sm font-medium text-slate-700 mb-2">Vaccine Doses</h4>
                                            <div class="space-y-1">
                                                <?php if (in_array('first_dose', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• First Dose</p>
                                                <?php endif; ?>
                                                <?php if (in_array('second_dose', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• Second Dose</p>
                                                <?php endif; ?>
                                                <?php if (in_array('booster_1', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• Booster 1</p>
                                                <?php endif; ?>
                                                <?php if (in_array('booster_2', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• Booster 2</p>
                                                <?php endif; ?>
                                                <?php if (in_array('booster_3', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• Booster 3</p>
                                                <?php endif; ?>
                                                <?php if (in_array('annual_booster', $covidVaccines)): ?>
                                                    <p class="text-xs text-slate-700">• Annual Booster</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($record['form_type'] === 'general' || $record['form_type'] === 'general_checkup'): ?>
                        <!-- General Check Up Form Display -->
                        <div class="space-y-6">
                            <!-- Physical Measurements -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Physical Measurements</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Height:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['height'] ?? 'N/A') ?> cm</p>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Weight:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['weight'] ?? 'N/A') ?> kg</p>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">BMI:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['bmi'] ?? 'N/A') ?></p>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">BMI Status:</label>
                                            <span class="px-2 py-1 rounded text-sm <?= 
                                                ($formData['bmi_status'] ?? '') === 'Normal' ? 'bg-green-100 text-green-800' : 
                                                (in_array($formData['bmi_status'] ?? '', ['Underweight', 'Overweight', 'Obese']) ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')
                                            ?>">
                                                <?= htmlspecialchars($formData['bmi_status'] ?? 'N/A') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vital Signs -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Vital Signs</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Heart Rate:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['heart_rate'] ?? 'N/A') ?> BPM</p>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Temperature:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['temperature'] ?? 'N/A') ?>°C</p>
                                        </div>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Blood Pressure:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['blood_pressure'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assessment & Plan -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Assessment & Plan</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <p class="text-slate-800"><?= htmlspecialchars($formData['assessment_plan'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($record['form_type'] !== 'medical_history' && $record['form_type'] !== 'general' && $record['form_type'] !== 'general_checkup'): ?>
                        <!-- Other form types -->
                        <div class="bg-slate-50 rounded-lg p-4">
                            <pre class="text-sm text-slate-700 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($formData, JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php elseif ($record['form_type'] === 'medical_history' && (empty($formData['ongoing_conditions']) && empty($formData['family_history']) && empty($formData['allergies']) && empty($formData['current_medications']) && empty($formData['additional_notes']))): ?>
                        <!-- Empty medical history -->
                        <div class="text-center py-8 text-slate-500">
                            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-lg font-medium">No medical history recorded</p>
                            <p class="text-sm">This medical history form is empty.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-3xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 max-h-[calc(100vh-16rem)] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Edit Medical Record</h2>
            <button onclick="closeEditModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editForm" method="POST" action="../medical/update_medical_record.php">
            <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
            <input type="hidden" name="patient_id" value="<?= $record['patient_id'] ?>">
            <input type="hidden" name="patient_type" value="<?= $record['patient_type'] ?>">
            
            <div class="space-y-6">
                <!-- Form data will be loaded here via JavaScript -->
                <div id="editFormContent">
                    <!-- Content will be dynamically loaded -->
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

<script>
function editRecord() {
    // Load the edit form content
    loadEditForm();
    
    // Show modal
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

function loadEditForm() {
    const formData = <?= json_encode($formData) ?>;
    const formType = '<?= $record['form_type'] ?>';
    
    let content = '';
    
    if (formType === 'medical_history') {
        content = generateMedicalHistoryEditForm(formData);
    } else {
        content = generateGenericEditForm(formData);
    }
    
    document.getElementById('editFormContent').innerHTML = content;
}

function generateMedicalHistoryEditForm(data) {
    return `
        <div class="space-y-6">
            <!-- Ongoing Medical Conditions -->
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-semibold text-slate-800 mb-4">Ongoing Medical Conditions</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="error_of_refraction" ${data.ongoing_conditions?.includes('error_of_refraction') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Error of Refraction</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="asthma" ${data.ongoing_conditions?.includes('asthma') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Asthma</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="diabetes" ${data.ongoing_conditions?.includes('diabetes') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Diabetes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="hypertension" ${data.ongoing_conditions?.includes('hypertension') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Hypertension</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="seizure" ${data.ongoing_conditions?.includes('seizure') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Seizure</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="heart_problem" ${data.ongoing_conditions?.includes('heart_problem') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Heart Problem</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="anemia" ${data.ongoing_conditions?.includes('anemia') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Anemia</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="bleeding_disorder" ${data.ongoing_conditions?.includes('bleeding_disorder') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Bleeding Disorder</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="hernia" ${data.ongoing_conditions?.includes('hernia') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Hernia</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="medical_conditions[]" value="others" ${data.ongoing_conditions?.includes('others') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Others</span>
                    </label>
                </div>
                <div id="other_medical_conditions_div">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other conditions:</label>
                    <textarea name="other_medical_conditions" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other ongoing medical conditions...">${data.ongoing_conditions_other || ''}</textarea>
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
                                <input type="radio" name="surgery_status" value="no" ${data.surgery_status === 'no' ? 'checked' : ''} class="mr-2">
                                <span class="text-sm text-slate-700">No</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="surgery_status" value="yes" ${data.surgery_status === 'yes' ? 'checked' : ''} class="mr-2">
                                <span class="text-sm text-slate-700">Yes</span>
                            </label>
                        </div>
                    </div>
                    <div id="surgery_details">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify:</label>
                        <textarea name="surgery_details" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify surgery/hospitalization details...">${data.surgery_details || ''}</textarea>
                    </div>
                </div>
            </div>

            <!-- Family Medical History -->
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-semibold text-slate-800 mb-4">Family Medical History</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="tuberculosis" ${data.family_conditions?.includes('tuberculosis') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Tuberculosis</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="cancer" ${data.family_conditions?.includes('cancer') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Cancer</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="diabetes" ${data.family_conditions?.includes('diabetes') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Diabetes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="hypertension" ${data.family_conditions?.includes('hypertension') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Hypertension</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="depression" ${data.family_conditions?.includes('depression') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Depression</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="others" ${data.family_conditions?.includes('others') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Others</span>
                    </label>
                </div>
                <div id="other_family_conditions_div">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other family conditions:</label>
                    <textarea name="other_family_conditions" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other family medical conditions...">${data.family_conditions_other || ''}</textarea>
                </div>
            </div>

            <!-- Exposure to Cigarette/Vape Smoke -->
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-semibold text-slate-800 mb-4">Exposure to Cigarette/Vape Smoke at Home</h3>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="smoke_exposure" value="yes" ${data.smoke_exposure === 'yes' ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Yes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="smoke_exposure" value="no" ${data.smoke_exposure === 'no' ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">No</span>
                    </label>
                </div>
            </div>

            <!-- Immunization Received -->
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-semibold text-slate-800 mb-4">Immunization Received</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="mmr" ${data.immunization?.includes('mmr') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">MMR (Measles, Mumps, Rubella)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="dpt" ${data.immunization?.includes('dpt') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">DPT (Diphtheria, Pertussis, Tetanus)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="bcg" ${data.immunization?.includes('bcg') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">BCG (Bacillus Calmette-Guérin)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="chicken_pox" ${data.immunization?.includes('chicken_pox') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Chicken Pox (Varicella)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="hepatitis_b" ${data.immunization?.includes('hepatitis_b') ? 'checked' : ''} class="mr-2">
                        <span class="text-sm text-slate-700">Hepatitis B</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunizations[]" value="polio" ${data.immunization?.includes('polio') ? 'checked' : ''} class="mr-2">
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
                                <input type="radio" name="covid_infection" value="no" ${data.covid_positive === 'no' ? 'checked' : ''} class="mr-2">
                                <span class="text-sm text-slate-700">No</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="covid_infection" value="yes" ${data.covid_positive === 'yes' ? 'checked' : ''} class="mr-2">
                                <span class="text-sm text-slate-700">Yes</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="covid_infection_details">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify when and details:</label>
                        <textarea name="covid_infection_details" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify when you had COVID-19, severity, treatment received...">${data.covid_details || ''}</textarea>
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
                            <input type="checkbox" name="covid_vaccine_brand[]" value="pfizer" ${data.covid_vaccine_brand?.includes('pfizer') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Pfizer</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="moderna" ${data.covid_vaccine_brand?.includes('moderna') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Moderna</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="astrazeneca" ${data.covid_vaccine_brand?.includes('astrazeneca') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">AstraZeneca</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="janssen" ${data.covid_vaccine_brand?.includes('janssen') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Janssen</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="sinovac" ${data.covid_vaccine_brand?.includes('sinovac') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Sinovac</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="sinopharm" ${data.covid_vaccine_brand?.includes('sinopharm') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Sinopharm</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="sputnik" ${data.covid_vaccine_brand?.includes('sputnik') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Sputnik V</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine_brand[]" value="others" ${data.covid_vaccine_brand?.includes('others') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Others</span>
                        </label>
                    </div>
                    <div id="other_vaccine_brand_div" class="mt-3">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Please specify other vaccine brand:</label>
                        <textarea name="other_vaccine_brand" rows="2" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-200" placeholder="Specify other COVID-19 vaccine brand...">${data.other_vaccine_brand || ''}</textarea>
                    </div>
                </div>
                
                <!-- Vaccine Doses -->
                <div>
                    <h4 class="text-lg font-medium text-slate-700 mb-3">Vaccine Doses</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="first_dose" ${data.covid_vaccine?.includes('first_dose') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">First Dose</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="second_dose" ${data.covid_vaccine?.includes('second_dose') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Second Dose</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="booster_1" ${data.covid_vaccine?.includes('booster_1') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Booster 1</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="booster_2" ${data.covid_vaccine?.includes('booster_2') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Booster 2</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="booster_3" ${data.covid_vaccine?.includes('booster_3') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Booster 3</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="covid_vaccine[]" value="annual_booster" ${data.covid_vaccine?.includes('annual_booster') ? 'checked' : ''} class="mr-2">
                            <span class="text-sm text-slate-700">Annual Booster</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateGenericEditForm(data) {
    const formType = '<?= $record['form_type'] ?>';
    
    if (formType === 'general' || formType === 'general_checkup') {
        return generateGeneralCheckupEditForm(data);
    }
    
    return `
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Form Data (JSON)</label>
                <textarea name="form_data" class="w-full px-3 py-2 border border-slate-300 rounded-lg" rows="10">${JSON.stringify(data, null, 2)}</textarea>
            </div>
        </div>
    `;
}

function generateGeneralCheckupEditForm(data) {
    return `
        <div class="space-y-6">
            <!-- Assessment & Plan -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Assessment & Plan</label>
                <textarea name="assessment_plan" class="w-full px-3 py-2 border border-slate-300 rounded-lg" rows="3" placeholder="Enter assessment and plan...">${data.assessment_plan || ''}</textarea>
            </div>

            <!-- Physical Measurements -->
            <div>
                <h3 class="text-lg font-medium text-slate-700 mb-4">Physical Measurements</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Height (cm)</label>
                        <input type="number" name="height" value="${data.height || ''}" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Enter height in cm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" value="${data.weight || ''}" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Enter weight in kg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">BMI</label>
                        <input type="number" name="bmi" value="${data.bmi || ''}" step="0.1" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="BMI will be calculated automatically">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">BMI Status</label>
                        <select name="bmi_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="">Select BMI Status</option>
                            <option value="Underweight" ${data.bmi_status === 'Underweight' ? 'selected' : ''}>Underweight</option>
                            <option value="Normal" ${data.bmi_status === 'Normal' ? 'selected' : ''}>Normal</option>
                            <option value="Overweight" ${data.bmi_status === 'Overweight' ? 'selected' : ''}>Overweight</option>
                            <option value="Obese" ${data.bmi_status === 'Obese' ? 'selected' : ''}>Obese</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Vital Signs -->
            <div>
                <h3 class="text-lg font-medium text-slate-700 mb-4">Vital Signs</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Heart Rate (BPM)</label>
                        <input type="number" name="heart_rate" value="${data.heart_rate || ''}" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Enter heart rate">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Temperature (°C)</label>
                        <input type="number" name="temperature" value="${data.temperature || ''}" step="0.1" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Enter temperature">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Blood Pressure</label>
                        <input type="text" name="blood_pressure" value="${data.blood_pressure || ''}" class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="e.g., 120/80">
                    </div>
                </div>
                
                <!-- Status fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Heart Rate Status</label>
                        <select name="heart_rate_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="">Select Status</option>
                            <option value="Low" ${data.heart_rate_status === 'Low' ? 'selected' : ''}>Low</option>
                            <option value="Normal" ${data.heart_rate_status === 'Normal' ? 'selected' : ''}>Normal</option>
                            <option value="High" ${data.heart_rate_status === 'High' ? 'selected' : ''}>High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Temperature Status</label>
                        <select name="temperature_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="">Select Status</option>
                            <option value="Low" ${data.temperature_status === 'Low' ? 'selected' : ''}>Low</option>
                            <option value="Normal" ${data.temperature_status === 'Normal' ? 'selected' : ''}>Normal</option>
                            <option value="High" ${data.temperature_status === 'High' ? 'selected' : ''}>High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Blood Pressure Status</label>
                        <select name="blood_pressure_status" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                            <option value="">Select Status</option>
                            <option value="Low" ${data.blood_pressure_status === 'Low' ? 'selected' : ''}>Low</option>
                            <option value="Normal" ${data.blood_pressure_status === 'Normal' ? 'selected' : ''}>Normal</option>
                            <option value="Elevated" ${data.blood_pressure_status === 'Elevated' ? 'selected' : ''}>Elevated</option>
                            <option value="High" ${data.blood_pressure_status === 'High' ? 'selected' : ''}>High</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Initialize form behavior
document.addEventListener('DOMContentLoaded', function() {
    // Surgery status toggle
    const surgeryRadios = document.querySelectorAll('input[name="surgery_status"]');
    const surgeryDetails = document.querySelector('textarea[name="surgery_details"]');
    
    surgeryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                surgeryDetails.closest('div').classList.remove('hidden');
            } else {
                surgeryDetails.closest('div').classList.add('hidden');
            }
        });
    });
    
    // COVID positive toggle
    const covidRadios = document.querySelectorAll('input[name="covid_positive"]');
    const covidDetails = document.querySelector('textarea[name="covid_details"]');
    
    covidRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'yes') {
                covidDetails.closest('div').classList.remove('hidden');
            } else {
                covidDetails.closest('div').classList.add('hidden');
            }
        });
    });
    
    // BMI calculation for general checkup forms
    const heightInput = document.querySelector('input[name="height"]');
    const weightInput = document.querySelector('input[name="weight"]');
    const bmiInput = document.querySelector('input[name="bmi"]');
    const bmiStatusSelect = document.querySelector('select[name="bmi_status"]');
    
    function calculateBMI() {
        if (heightInput && weightInput && bmiInput) {
            const height = parseFloat(heightInput.value);
            const weight = parseFloat(weightInput.value);
            
            if (height > 0 && weight > 0) {
                const heightInMeters = height / 100;
                const bmi = weight / (heightInMeters * heightInMeters);
                bmiInput.value = bmi.toFixed(1);
                
                // Auto-set BMI status
                if (bmiStatusSelect) {
                    let status = '';
                    if (bmi < 18.5) status = 'Underweight';
                    else if (bmi < 25) status = 'Normal';
                    else if (bmi < 30) status = 'Overweight';
                    else status = 'Obese';
                    
                    bmiStatusSelect.value = status;
                }
            }
        }
    }
    
    if (heightInput) heightInput.addEventListener('input', calculateBMI);
    if (weightInput) weightInput.addEventListener('input', calculateBMI);
});

// Back button function
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Fallback to patient view if no history
        window.location.href = 'patient_view.php?id=<?= $record['patient_id'] ?>&type=<?= $record['patient_type'] ?>';
    }
}

// Print medical record function
function printMedicalRecord() {
    // Create print content with CARE header and proper formatting
    const printContent = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Medical Record - <?= htmlspecialchars($patient['name']) ?></title>
            <style>
                @media print {
                    @page {
                        margin: 0;
                        size: A4;
                    }
                    
                    body {
                        font-family: 'Arial', sans-serif;
                        font-size: 12px;
                        line-height: 1.4;
                        color: #000;
                        background: white;
                        margin: 0;
                        padding: 20px;
                    }
                    
                    .print-header {
                        text-align: center;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 10px;
                    }
                    
                    .print-logo {
                        font-size: 24px;
                        font-weight: bold;
                        color: #2563eb;
                        margin-bottom: 5px;
                    }
                    
                    .print-institution {
                        font-size: 14px;
                        font-weight: 600;
                        margin-bottom: 5px;
                    }
                    
                    .print-subtitle {
                        font-size: 16px;
                        font-weight: bold;
                        margin-bottom: 20px;
                    }
                    
                    .print-content {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 20px;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 20px;
                    }
                    
                    .print-section {
                        margin-bottom: 15px;
                    }
                    
                    .print-section-title {
                        font-weight: bold;
                        font-size: 14px;
                        margin-bottom: 8px;
                        border-bottom: 1px solid #ccc;
                        padding-bottom: 3px;
                    }
                    
                    .print-field {
                        margin-bottom: 5px;
                        display: flex;
                    }
                    
                    .print-label {
                        font-weight: bold;
                        min-width: 120px;
                        margin-right: 10px;
                    }
                    
                    .print-value {
                        flex: 1;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <div class="print-logo">CARE</div>
                <div class="print-institution">Our Lady of the Sacred Heart Inc.</div>
                <div class="print-subtitle">Medical Record</div>
            </div>
            
            <div class="print-content">
                <!-- Patient Information -->
                <div class="print-section">
                    <div class="print-section-title">Patient Information</div>
                    <div class="print-field">
                        <span class="print-label">Name:</span>
                        <span class="print-value"><?= htmlspecialchars($patient['name']) ?></span>
                    </div>
                    <div class="print-field">
                        <span class="print-label">Type:</span>
                        <span class="print-value"><?= ucfirst($record['patient_type']) ?></span>
                    </div>
                    <div class="print-field">
                        <span class="print-label">Record ID:</span>
                        <span class="print-value">#<?= $record['id'] ?></span>
                    </div>
                    <div class="print-field">
                        <span class="print-label">Form Type:</span>
                        <span class="print-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $record['form_type']))) ?></span>
                    </div>
                    <div class="print-field">
                        <span class="print-label">Created:</span>
                        <span class="print-value"><?= date('M j, Y g:i A', strtotime($record['created_at'])) ?></span>
                    </div>
                </div>
                
                <!-- Medical Information -->
                <div class="print-section">
                    <div class="print-section-title">Medical Information</div>
                    <?php 
                    $formData = json_decode($record['form_data'], true);
                    if ($formData && is_array($formData)): 
                    ?>
                        <?php if (!empty($formData['ongoing_conditions'])): ?>
                            <?php 
                            $ongoingConditions = is_array($formData['ongoing_conditions']) ? $formData['ongoing_conditions'] : [$formData['ongoing_conditions']];
                            $conditions = [];
                            if (in_array('error_of_refraction', $ongoingConditions)) $conditions[] = 'Error of Refraction';
                            if (in_array('asthma', $ongoingConditions)) $conditions[] = 'Asthma';
                            if (in_array('seizure', $ongoingConditions)) $conditions[] = 'Seizure';
                            if (in_array('heart_problem', $ongoingConditions)) $conditions[] = 'Heart Problem';
                            if (in_array('anemia', $ongoingConditions)) $conditions[] = 'Anemia';
                            if (in_array('bleeding_disorder', $ongoingConditions)) $conditions[] = 'Bleeding Disorder';
                            if (in_array('hernia', $ongoingConditions)) $conditions[] = 'Hernia';
                            if (in_array('tuberculosis', $ongoingConditions)) $conditions[] = 'Tuberculosis';
                            if (in_array('diabetes', $ongoingConditions)) $conditions[] = 'Diabetes';
                            if (in_array('hypertension', $ongoingConditions)) $conditions[] = 'Hypertension';
                            if (in_array('kidney_disease', $ongoingConditions)) $conditions[] = 'Kidney Disease';
                            if (in_array('liver_disease', $ongoingConditions)) $conditions[] = 'Liver Disease';
                            if (in_array('cancer', $ongoingConditions)) $conditions[] = 'Cancer';
                            if (in_array('mental_illness', $ongoingConditions)) $conditions[] = 'Mental Illness';
                            if (in_array('others', $ongoingConditions) && !empty($formData['ongoing_conditions_other'])) {
                                $conditions[] = $formData['ongoing_conditions_other'];
                            }
                            ?>
                            <?php if (!empty($conditions)): ?>
                                <div class="print-field">
                                    <span class="print-label">Ongoing Conditions:</span>
                                    <span class="print-value"><?= implode(', ', $conditions) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['allergies'])): ?>
                            <div class="print-field">
                                <span class="print-label">Allergies:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['allergies']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['medications'])): ?>
                            <div class="print-field">
                                <span class="print-label">Current Medications:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['medications']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['family_history'])): ?>
                            <div class="print-field">
                                <span class="print-label">Family History:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['family_history']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['previous_surgeries'])): ?>
                            <div class="print-field">
                                <span class="print-label">Previous Surgeries:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['previous_surgeries']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['immunizations'])): ?>
                            <div class="print-field">
                                <span class="print-label">Immunizations:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['immunizations']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formData['additional_notes'])): ?>
                            <div class="print-field">
                                <span class="print-label">Additional Notes:</span>
                                <span class="print-value"><?= htmlspecialchars($formData['additional_notes']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Show all form data fields for debugging -->
                        <?php foreach ($formData as $key => $value): ?>
                            <?php if (!empty($value) && !in_array($key, ['ongoing_conditions', 'ongoing_conditions_other', 'allergies', 'medications', 'family_history', 'previous_surgeries', 'immunizations', 'additional_notes'])): ?>
                                <div class="print-field">
                                    <span class="print-label"><?= ucfirst(str_replace('_', ' ', $key)) ?>:</span>
                                    <span class="print-value"><?= is_array($value) ? implode(', ', $value) : htmlspecialchars($value) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="print-field">
                            <span class="print-label">Status:</span>
                            <span class="print-value">No medical data available</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
    `;
    
    // Create a hidden iframe for printing without changing the main page
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.left = '-9999px';
    iframe.style.top = '-9999px';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    
    document.body.appendChild(iframe);
    
    // Write content to iframe
    iframe.contentDocument.write(printContent);
    iframe.contentDocument.close();
    
    // Wait for content to load, then print
    iframe.onload = function() {
        iframe.contentWindow.print();
        
        // Remove iframe after printing
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    };
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
