<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    header('Location: login.php');
    exit;
}

$visitId = $_GET['id'] ?? null;
$isArchived = isset($_GET['archived']) && $_GET['archived'] == '1';

if (!$visitId) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo '<div class="text-center py-8 text-red-600">Missing visit ID</div>';
        exit;
    }
    echo '<div class="min-h-screen flex items-center justify-center bg-slate-100">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Error</h1>
            <p class="text-slate-600">Missing visit ID</p>
            <button onclick="window.close()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
        </div>
    </div>';
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get visitation details (check if archived)
    $visit = null;
    if ($isArchived) {
        // Try to get from archive table
        $archiveTable = 'visitation_logs_archive';
        $tableExists = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'")->rowCount() > 0;
        if ($tableExists) {
            // Search by original_id first (the ID we're passing is from the original record)
            $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE original_id = ? OR id = ?");
            $stmt->execute([$visitId, $visitId]);
            $visit = $stmt->fetch();
            if ($visit) {
                $visit['is_archived'] = true;
            }
        }
        
        // Debug log if not found
        if (!$visit) {
            error_log("Archived visitation log not found - ID: {$visitId}, Table exists: " . ($tableExists ? 'yes' : 'no'));
        }
    } else {
        // Get from active visitation logs
        $stmt = $pdo->prepare("SELECT * FROM visitation_logs WHERE id = ?");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();
    }
    
    if (!$visit) {
        error_log("Visitation log not found - ID: {$visitId}, Archived: " . ($isArchived ? 'yes' : 'no'));
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            echo '<div class="text-center py-8 text-red-600">
                <p class="font-bold mb-2">Visitation record not found.</p>
                <p class="text-sm">Visit ID: ' . htmlspecialchars($visitId) . '</p>
                <p class="text-sm">Archived: ' . ($isArchived ? 'Yes' : 'No') . '</p>
            </div>';
            exit;
        }
        echo '<div class="min-h-screen flex items-center justify-center bg-slate-100">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-red-600 mb-4">Not Found</h1>
                <p class="text-slate-600">Visitation record not found</p>
                <button onclick="window.close()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
            </div>
        </div>';
        exit;
    }

    // Handle AJAX request for popup content
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        // Get patient details
        $patientType = $visit['patient_type'];
        $patientId = $visit['patient_id'];
        
        if ($patientType === 'student') {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ?");
        }
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        // Return only the content part
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="space-y-6">
                <!-- Visit Information -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-slate-800">Visit Information</h3>
                        <?php if (isset($visit['is_archived']) && $visit['is_archived']): ?>
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">ARCHIVED</span>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Visit ID</div>
                            <div class="font-semibold text-slate-800">#<?= $visit['id'] ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Patient</div>
                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($patient['name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Visit Date</div>
                            <div class="font-semibold text-slate-800"><?= date('M j, Y g:i A', strtotime($visit['visit_date'])) ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Patient Type</div>
                            <div class="font-semibold text-slate-800"><?= ucfirst($visit['patient_type']) ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Nurse</div>
                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($visit['nurse_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                            <div class="text-xs text-slate-600 mb-1">Created</div>
                            <div class="font-semibold text-slate-800"><?= date('M j, Y g:i A', strtotime($visit['created_at'])) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Visit Details -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Visit Details</h3>
                    <div class="space-y-4">
                        <!-- Reason for Visit -->
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Reason for Visit</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($visit['reason'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                        
                        <!-- Symptoms -->
                        <?php if (!empty($visit['symptoms'])): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Symptoms</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($visit['symptoms']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Vital Signs -->
                        <?php if (!empty($visit['heart_rate']) || !empty($visit['blood_pressure']) || !empty($visit['temperature'])): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-3">Vital Signs</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <?php if (!empty($visit['heart_rate'])): ?>
                                <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                                    <div class="text-xs text-slate-600 mb-1">Heart Rate</div>
                                    <div class="font-semibold text-slate-800"><?= $visit['heart_rate'] ?> BPM</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($visit['blood_pressure'])): ?>
                                <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                                    <div class="text-xs text-slate-600 mb-1">Blood Pressure</div>
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($visit['blood_pressure']) ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($visit['temperature'])): ?>
                                <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                                    <div class="text-xs text-slate-600 mb-1">Temperature</div>
                                    <div class="font-semibold text-slate-800"><?= $visit['temperature'] ?>°C</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Medication -->
                        <?php if ($visit['medication_given'] == 1): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Medication Given</h4>
                            <div class="space-y-2">
                                <?php if (!empty($visit['medication_name'])): ?>
                                <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                                    <div class="text-xs text-slate-600 mb-1">Medication Name</div>
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($visit['medication_name']) ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($visit['medication_notes'])): ?>
                                <div class="py-2 px-3 bg-white rounded-lg border border-slate-200">
                                    <div class="text-xs text-slate-600 mb-1">Medication Notes</div>
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($visit['medication_notes']) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Other Treatment -->
                        <?php if (!empty($visit['other_treatment'])): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Other Treatment</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($visit['other_treatment']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- First Aid -->
                        <?php if ($visit['first_aid_given'] == 1): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">First Aid Given</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($visit['first_aid_type'] ?? 'First aid administered') ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Injury -->
                        <?php if ($visit['injury'] == 1): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Injury Reported</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800">Yes - Injury was reported</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Other Notes -->
                        <?php if (!empty($visit['other_notes'])): ?>
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Additional Notes</h4>
                            <div class="py-3 px-4 bg-white rounded-lg border border-slate-200">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($visit['other_notes']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Restore Button for Archived Records -->
                <?php if (isset($visit['is_archived']) && $visit['is_archived']): ?>
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <button onclick="restoreVisitation(<?= $visit['id'] ?>)" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Restore This Visitation Record
                        </button>
                        <p class="text-xs text-slate-500 text-center mt-2">This will move the record back to active visitation logs</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        exit;
    }
    
    // Get patient details
    $patientType = $visit['patient_type'];
    $patientId = $visit['patient_id'];
    
    if ($patientType === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ?");
    }
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        echo '<div class="min-h-screen flex items-center justify-center bg-slate-100">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-red-600 mb-4">Error</h1>
                <p class="text-slate-600">Patient not found</p>
                <button onclick="window.close()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
            </div>
        </div>';
        exit;
    }
    
    // Format the details
    $visitDate = date('M j, Y g:i A', strtotime($visit['visit_date']));
    $reason = htmlspecialchars($visit['reason']);
    $symptoms = !empty($visit['symptoms']) ? htmlspecialchars($visit['symptoms']) : 'N/A';
    $otherNotes = !empty($visit['other_notes']) ? htmlspecialchars($visit['other_notes']) : 'N/A';
    $heartRate = $visit['heart_rate'] ?? null;
    $bloodPressure = $visit['blood_pressure'] ?? null;
    $temperature = $visit['temperature'] ?? null;
    $medicationGiven = $visit['medication_given'] ?? 0;
    $medicationName = !empty($visit['medication_name']) ? htmlspecialchars($visit['medication_name']) : 'N/A';
    $otherTreatment = !empty($visit['other_treatment']) ? htmlspecialchars($visit['other_treatment']) : 'N/A';
    $medicationNotes = !empty($visit['medication_notes']) ? htmlspecialchars($visit['medication_notes']) : 'N/A';
    $injury = $visit['injury'] ?? 0;
    $firstAidGiven = $visit['first_aid_given'] ?? 0;
    $firstAidType = !empty($visit['first_aid_type']) ? htmlspecialchars($visit['first_aid_type']) : 'N/A';
    
    // Heart rate status
    $heartRateStatus = '';
    if ($heartRate) {
        if ($heartRate < 60) {
            $heartRateStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Low</span>';
        } elseif ($heartRate > 100) {
            $heartRateStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">High</span>';
        } else {
            $heartRateStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Normal</span>';
        }
    }
    
    // Blood pressure status
    $bpStatus = '';
    if ($bloodPressure && preg_match('/(\d+)\/(\d+)/', $bloodPressure, $matches)) {
        $systolic = (int)$matches[1];
        $diastolic = (int)$matches[2];
        
        if ($systolic > 140 || $diastolic > 90) {
            $bpStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">High</span>';
        } elseif ($systolic < 90 || $diastolic < 60) {
            $bpStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Low</span>';
        } else {
            $bpStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Normal</span>';
        }
    }
    
    // Temperature status
    $tempStatus = '';
    if ($temperature) {
        if ($temperature > 37.5) {
            $tempStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Fever</span>';
        } elseif ($temperature < 36.0) {
            $tempStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Low</span>';
        } else {
            $tempStatus = '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Normal</span>';
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visitation Details - #<?= $visit['id'] ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Poppins', sans-serif; }
        </style>
    </head>
    <body class="bg-slate-100">
        <div class="min-h-screen p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800">Visitation Details</h1>
                            <p class="text-slate-600 mt-1">Visit #<?= $visit['id'] ?> - <?= $visitDate ?></p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="window.close()" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <!-- Patient Info -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Patient Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Name:</span>
                                <p class="text-slate-800 font-medium"><?= htmlspecialchars($patient['name']) ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Type:</span>
                                <p class="text-slate-800"><?= ucfirst($patientType) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visit Info -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Visit Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Visit ID:</span>
                                <p class="text-slate-800 font-medium">#<?= $visit['id'] ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Date & Time:</span>
                                <p class="text-slate-800"><?= $visitDate ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Reason:</span>
                                <p class="text-slate-800"><?= $reason ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Attended by:</span>
                                <p class="text-slate-800 font-medium"><?= htmlspecialchars($visit['nurse_name'] ?? 'Unknown Nurse') ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Symptoms & Notes -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Symptoms & Notes</h2>
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Symptoms:</span>
                                <p class="text-slate-800 mt-1"><?= $symptoms ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Other Notes:</span>
                                <p class="text-slate-800 mt-1"><?= $otherNotes ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vital Signs -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Vital Signs</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Heart Rate:</span>
                                <p class="text-slate-800 mt-1"><?= $heartRate ? htmlspecialchars((string)$heartRate) . ' bpm ' . $heartRateStatus : 'N/A' ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Blood Pressure:</span>
                                <p class="text-slate-800 mt-1"><?= $bloodPressure ? htmlspecialchars($bloodPressure) . ' ' . $bpStatus : 'N/A' ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Temperature:</span>
                                <p class="text-slate-800 mt-1"><?= $temperature ? htmlspecialchars($temperature) . '°C ' . $tempStatus : 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medication -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Medication</h2>
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Medication Given:</span>
                                <p class="text-slate-800"><?= $medicationGiven ? 'Yes' : 'No' ?></p>
                            </div>
                            <?php if ($medicationGiven): ?>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Medication Name:</span>
                                <p class="text-slate-800"><?= $medicationName ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Other Treatment:</span>
                                <p class="text-slate-800"><?= $otherTreatment ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-slate-600">Medication Notes:</span>
                                <p class="text-slate-800"><?= $medicationNotes ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Injury & First Aid -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-slate-800 mb-4">Injury & First Aid</h2>
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-slate-600">Injury Occurred:</span>
                                <p class="text-slate-800"><?= $injury ? 'Yes' : 'No' ?></p>
                            </div>
                            <?php if ($injury): ?>
                            <div>
                                <span class="text-sm font-medium text-slate-600">First Aid Given:</span>
                                <p class="text-slate-800"><?= $firstAidGiven ? 'Yes' : 'No' ?></p>
                            </div>
                            <?php if ($firstAidGiven): ?>
                            <div>
                                <span class="text-sm font-medium text-slate-600">First Aid Type:</span>
                                <p class="text-slate-800"><?= $firstAidType ?></p>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Print visitation log function
        function printVisitationLog() {
            // Store original body content
            const originalBody = document.body.innerHTML;
            
            // Create the print content
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Visitation Log - CARE</title>
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
                            .print-grid {
                                display: grid !important;
                                grid-template-columns: 1fr 1fr 1fr !important;
                                gap: 10px !important;
                                margin: 10px 0 !important;
                            }
                            .print-grid-item {
                                border: 1px solid #000 !important;
                                padding: 8px !important;
                                background: white !important;
                                text-align: center !important;
                            }
                            .print-grid-label {
                                font-size: 9pt !important;
                                color: #666 !important;
                                margin-bottom: 3px !important;
                            }
                            .print-grid-value {
                                font-size: 11pt !important;
                                font-weight: bold !important;
                                color: #000 !important;
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
                            <div class="print-subtitle">CLINIC Details</div>
                        </div>
                        <div class="print-content">
                            <div>
                                <div class="print-section">
                                    <h3>Patient Information</h3>
                                    <div class="print-field">
                                        <span class="print-label">Name:</span>
                                        <span class="print-value"><?= htmlspecialchars($patient['name']) ?></span>
                                    </div>
                                    <?php if ($patientType === 'student'): ?>
                                        <?php if (!empty($patient['year_grade']) && $patient['year_grade'] !== 'N/A'): ?>
                                        <div class="print-field">
                                            <span class="print-label">Grade/Year:</span>
                                            <span class="print-value"><?= htmlspecialchars($patient['year_grade']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($patient['section']) && $patient['section'] !== 'N/A'): ?>
                                        <div class="print-field">
                                            <span class="print-label">Section:</span>
                                            <span class="print-value"><?= htmlspecialchars($patient['section']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($patient['strand']) && $patient['strand'] !== 'N/A'): ?>
                                        <div class="print-field">
                                            <span class="print-label">Strand:</span>
                                            <span class="print-value"><?= htmlspecialchars($patient['strand']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($patient['course']) && $patient['course'] !== 'N/A'): ?>
                                        <div class="print-field">
                                            <span class="print-label">Course:</span>
                                            <span class="print-value"><?= htmlspecialchars($patient['course']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (!empty($patient['department']) && $patient['department'] !== 'N/A'): ?>
                                        <div class="print-field">
                                            <span class="print-label">Department:</span>
                                            <span class="print-value"><?= htmlspecialchars($patient['department']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="print-section">
                                    <h3>Visitation Details</h3>
                                    <div class="print-field">
                                        <span class="print-label">Visit ID:</span>
                                        <span class="print-value">#<?= $visit['id'] ?></span>
                                    </div>
                                    <div class="print-field">
                                        <span class="print-label">Date:</span>
                                        <span class="print-value"><?= $visitDate ?></span>
                                    </div>
                                    <div class="print-field">
                                        <span class="print-label">Time:</span>
                                        <span class="print-value"><?= $visitTime ?></span>
                                    </div>
                                    <?php if (!empty($visit['reason']) && $visit['reason'] !== 'N/A'): ?>
                                    <div class="print-field">
                                        <span class="print-label">Reason:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['reason']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($visit['symptoms'])): ?>
                                    <div class="print-field">
                                        <span class="print-label">Symptoms:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['symptoms']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if (!empty($visit['heart_rate']) || !empty($visit['blood_pressure']) || !empty($visit['temperature'])): ?>
                                <div class="print-section">
                                    <h3>Vital Signs</h3>
                                    <div class="print-grid">
                                        <?php if (!empty($visit['heart_rate']) && $visit['heart_rate'] !== 'N/A'): ?>
                                        <div class="print-grid-item">
                                            <div class="print-grid-label">Heart Rate</div>
                                            <div class="print-grid-value"><?= $visit['heart_rate'] ?> BPM</div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($visit['blood_pressure']) && $visit['blood_pressure'] !== 'N/A'): ?>
                                        <div class="print-grid-item">
                                            <div class="print-grid-label">Blood Pressure</div>
                                            <div class="print-grid-value"><?= htmlspecialchars($visit['blood_pressure']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($visit['temperature']) && $visit['temperature'] !== 'N/A'): ?>
                                        <div class="print-grid-item">
                                            <div class="print-grid-label">Temperature</div>
                                            <div class="print-grid-value"><?= $visit['temperature'] ?>°C</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($visit['medication_given'] == 1): ?>
                                <div class="print-section">
                                    <h3>Medication Given</h3>
                                    <?php if (!empty($visit['medication_name']) && $visit['medication_name'] !== 'N/A'): ?>
                                    <div class="print-field">
                                        <span class="print-label">Medication:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['medication_name']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($visit['medication_dosage']) && $visit['medication_dosage'] !== 'N/A'): ?>
                                    <div class="print-field">
                                        <span class="print-label">Dosage:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['medication_dosage']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($visit['medication_notes']) && $visit['medication_notes'] !== 'N/A'): ?>
                                    <div class="print-field">
                                        <span class="print-label">Notes:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['medication_notes']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visit['first_aid_given']) && $visit['first_aid_given'] !== 'N/A'): ?>
                                <div class="print-section">
                                    <h3>First Aid Treatment</h3>
                                    <div class="print-field">
                                        <span class="print-label">Treatment:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['first_aid_given']) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visit['notes']) && $visit['notes'] !== 'N/A'): ?>
                                <div class="print-section">
                                    <h3>Additional Notes</h3>
                                    <div class="print-field">
                                        <span class="print-label">Notes:</span>
                                        <span class="print-value"><?= htmlspecialchars($visit['notes']) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `;
            
            // Replace body content with print content
            document.body.innerHTML = printContent;
            
            // Trigger print
            window.print();
            
            // Restore original content
            setTimeout(() => {
                document.body.innerHTML = originalBody;
            }, 1000);
        }
        
        // Print visitation record function (medical record format)
        function printVisitationRecord() {
            // Store original body content
            const originalBody = document.body.innerHTML;
            
            // Create the print content
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Medical Record - CARE</title>
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
                                text-align: left !important;
                                margin-top: 20px !important;
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
                            .print-grid {
                                display: grid !important;
                                grid-template-columns: 1fr 1fr 1fr !important;
                                gap: 10px !important;
                                margin: 10px 0 !important;
                            }
                            .print-grid-item {
                                border: 1px solid #000 !important;
                                padding: 8px !important;
                                background: white !important;
                                text-align: center !important;
                            }
                            .print-grid-label {
                                font-size: 9pt !important;
                                color: #666 !important;
                                margin-bottom: 3px !important;
                            }
                            .print-grid-value {
                                font-size: 11pt !important;
                                font-weight: bold !important;
                                color: #000 !important;
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
                            <div class="print-subtitle">Medical Record</div>
                        </div>
                        <div class="print-content">
                            <div class="print-section">
                                <h3>Patient Information</h3>
                                <div class="print-field">
                                    <span class="print-label">Name:</span>
                                    <span class="print-value"><?= htmlspecialchars($patient['name']) ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">Type:</span>
                                    <span class="print-value"><?= ucfirst($patientType) ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">Visit ID:</span>
                                    <span class="print-value">#<?= $visit['id'] ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">Date:</span>
                                    <span class="print-value"><?= $visitDate ?></span>
                                </div>
                                <div class="print-field">
                                    <span class="print-label">Time:</span>
                                    <span class="print-value"><?= $visitTime ?></span>
                                </div>
                            </div>
                            <div class="print-section">
                                <h3>Medical Assessment</h3>
                                <?php if (!empty($visit['reason']) && $visit['reason'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Chief Complaint:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['reason']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visit['symptoms'])): ?>
                                <div class="print-field">
                                    <span class="print-label">Symptoms:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['symptoms']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($visit['heart_rate']) || !empty($visit['blood_pressure']) || !empty($visit['temperature'])): ?>
                            <div class="print-section">
                                <h3>Vital Signs</h3>
                                <div class="print-grid">
                                    <?php if (!empty($visit['heart_rate']) && $visit['heart_rate'] !== 'N/A'): ?>
                                    <div class="print-grid-item">
                                        <div class="print-grid-label">Heart Rate</div>
                                        <div class="print-grid-value"><?= $visit['heart_rate'] ?> BPM</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($visit['blood_pressure']) && $visit['blood_pressure'] !== 'N/A'): ?>
                                    <div class="print-grid-item">
                                        <div class="print-grid-label">Blood Pressure</div>
                                        <div class="print-grid-value"><?= htmlspecialchars($visit['blood_pressure']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($visit['temperature']) && $visit['temperature'] !== 'N/A'): ?>
                                    <div class="print-grid-item">
                                        <div class="print-grid-label">Temperature</div>
                                        <div class="print-grid-value"><?= $visit['temperature'] ?>°C</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($visit['medication_given'] == 1): ?>
                            <div class="print-section">
                                <h3>Treatment Prescribed</h3>
                                <?php if (!empty($visit['medication_name']) && $visit['medication_name'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Medication:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['medication_name']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visit['medication_dosage']) && $visit['medication_dosage'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Dosage:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['medication_dosage']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visit['medication_notes']) && $visit['medication_notes'] !== 'N/A'): ?>
                                <div class="print-field">
                                    <span class="print-label">Instructions:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['medication_notes']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($visit['first_aid_given']) && $visit['first_aid_given'] !== 'N/A'): ?>
                            <div class="print-section">
                                <h3>First Aid Treatment</h3>
                                <div class="print-field">
                                    <span class="print-label">Treatment Administered:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['first_aid_given']) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($visit['notes']) && $visit['notes'] !== 'N/A'): ?>
                            <div class="print-section">
                                <h3>Clinical Notes</h3>
                                <div class="print-field">
                                    <span class="print-label">Notes:</span>
                                    <span class="print-value"><?= htmlspecialchars($visit['notes']) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </body>
                </html>
            `;
            
            // Replace body content with print content
            document.body.innerHTML = printContent;
            
            // Trigger print
            window.print();
            
            // Restore original content
            setTimeout(() => {
                document.body.innerHTML = originalBody;
            }, 1000);
        }
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    error_log('Error fetching visitation details: ' . $e->getMessage());
    echo '<div class="min-h-screen flex items-center justify-center bg-slate-100">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Error</h1>
            <p class="text-slate-600">Error loading details</p>
            <button onclick="window.close()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
        </div>
    </div>';
}
?>
