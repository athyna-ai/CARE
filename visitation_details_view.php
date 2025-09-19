<?php
require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    header('Location: login.php');
    exit;
}

$visitId = $_GET['id'] ?? null;

if (!$visitId) {
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
    
    // Get visitation details
    $stmt = $pdo->prepare("SELECT * FROM visitation_logs WHERE id = ?");
    $stmt->execute([$visitId]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        echo '<div class="min-h-screen flex items-center justify-center bg-slate-100">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-red-600 mb-4">Not Found</h1>
                <p class="text-slate-600">Visitation record not found</p>
                <button onclick="window.close()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
            </div>
        </div>';
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
                        <button onclick="window.close()" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                            Close
                        </button>
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
                            <div class="md:col-span-2">
                                <span class="text-sm font-medium text-slate-600">Reason:</span>
                                <p class="text-slate-800"><?= $reason ?></p>
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
