<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

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
                    <div class="text-sm font-semibold text-slate-800">' . htmlspecialchars($patient['name']) . '</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Type:</label>
                    <div class="text-sm font-semibold text-slate-800">' . ucfirst($patientType) . '</div>
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
                <div class="bg-slate-50 rounded-lg p-3">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Date & Time:</label>
                    <div class="text-sm font-semibold text-slate-800">' . $visitDate . '</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Reason:</label>
                    <div class="text-sm font-semibold text-slate-800">' . $reason . '</div>
                </div>
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
                    <label class="block text-sm font-medium text-slate-700 mb-2">Symptoms:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-800">' . $symptoms . '</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Other Notes:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm text-slate-800">' . $otherNotes . '</div>
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
                    <label class="block text-sm font-medium text-slate-700 mb-2">Heart Rate:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($heartRate ? htmlspecialchars((string)$heartRate) . ' bpm ' . $heartRateStatus : 'N/A') . '</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Blood Pressure:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($bloodPressure ? htmlspecialchars($bloodPressure) . ' ' . $bpStatus : 'N/A') . '</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Temperature:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($temperature ? htmlspecialchars($temperature) . '°C ' . $tempStatus : 'N/A') . '</div>
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
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Medication Given:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($medicationGiven ? 'Yes' : 'No') . '</div>
                </div>';
                
    if ($medicationGiven) {
        echo '
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Medication Name:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . $medicationName . '</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Other Treatment:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . $otherTreatment . '</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Medication Notes:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . $medicationNotes . '</div>
                </div>';
    }
    
    echo '
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
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Injury Occurred:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($injury ? 'Yes' : 'No') . '</div>
                </div>';
                
    if ($injury) {
        echo '
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">First Aid Given:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . ($firstAidGiven ? 'Yes' : 'No') . '</div>
                </div>';
        
        if ($firstAidGiven) {
            echo '
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">First Aid Type:</label>
                    <div class="bg-slate-50 rounded-lg p-3 text-sm font-semibold text-slate-800">' . $firstAidType . '</div>
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
