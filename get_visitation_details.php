<?php
require_once 'config.php';
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || ($_SESSION['user']['is_admin'] ?? 0) !== 1) {
    http_response_code(401);
    echo '<div class="text-center py-8 text-red-600">Unauthorized access - Please refresh the page and try again</div>';
    exit;
}

// Debug: Log session info (remove in production)
error_log('Session user: ' . ($_SESSION['user']['id'] ?? 'not set'));
error_log('Session status: ' . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));

$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    http_response_code(400);
    echo '<div class="text-center py-8 text-red-600">Missing visit ID</div>';
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get visitation details
    $stmt = $pdo->prepare("SELECT * FROM visitation_logs WHERE id = ?");
    $stmt->execute([$visitId]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        echo '<div class="text-center py-8 text-red-600">Visitation record not found</div>';
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
        echo '<div class="text-center py-8 text-red-600">Patient not found</div>';
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
    
    echo '
    <div class="space-y-6">
        <!-- Patient Info -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-blue/20 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                Patient Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Name:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . htmlspecialchars($patient['name']) . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Type:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ucfirst($patientType) . '</p>
                </div>
            </div>
        </div>
        
        <!-- Visit Info -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-vanilla/40 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                Visit Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Date & Time:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $visitDate . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Reason:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $reason . '</p>
                </div>
            </div>
        </div>
        
        <!-- Symptoms & Notes -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-vanilla/40 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                Symptoms & Notes
            </h3>
            <div class="space-y-3">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Symptoms:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $symptoms . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Other Notes:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $otherNotes . '</p>
                </div>
            </div>
        </div>
        
        <!-- Vital Signs -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-blue/20 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                Vital Signs
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Heart Rate:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($heartRate ? htmlspecialchars((string)$heartRate) . ' bpm ' . $heartRateStatus : 'N/A') . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Blood Pressure:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($bloodPressure ? htmlspecialchars($bloodPressure) . ' ' . $bpStatus : 'N/A') . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Temperature:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($temperature ? htmlspecialchars($temperature) . '°C ' . $tempStatus : 'N/A') . '</p>
                </div>
            </div>
        </div>
        
        <!-- Medication -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-tea/20 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                </div>
                Medication
            </h3>
            <div class="space-y-3">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Medication Given:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($medicationGiven ? 'Yes' : 'No') . '</p>
                </div>';
                
    if ($medicationGiven) {
        echo '
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Medication Name:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $medicationName . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Other Treatment:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $otherTreatment . '</p>
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Medication Notes:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $medicationNotes . '</p>
                </div>';
    }
    
    echo '
            </div>
        </div>
        
        <!-- Injury & First Aid -->
        <div class="bg-clinic-ivory/40 rounded-2xl p-4 border border-clinic-tea/20">
            <h3 class="text-lg font-comfortaa font-bold text-clinic-dark mb-4 flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-clinic-vanilla/30 flex items-center justify-center">
                    <svg class="w-3 h-3 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                Injury & First Aid
            </h3>
            <div class="space-y-3">
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">Injury Occurred:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($injury ? 'Yes' : 'No') . '</p>
                </div>';
                
    if ($injury) {
        echo '
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">First Aid Given:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . ($firstAidGiven ? 'Yes' : 'No') . '</p>
                </div>';
        
        if ($firstAidGiven) {
            echo '
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-clinic-tea/20 shadow-sm">
                    <span class="text-xs font-poppins font-medium text-clinic-dark/60 block mb-1">First Aid Type:</span>
                    <p class="text-sm font-poppins font-semibold text-clinic-dark">' . $firstAidType . '</p>
                </div>';
        }
    }
    
    echo '
            </div>
        </div>
    </div>';
    
} catch (Exception $e) {
    error_log('Error fetching visitation details: ' . $e->getMessage());
    echo '<div class="text-center py-8 text-red-600">Error loading details</div>';
}
?>
