<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get record ID from URL
$recordId = (int)($_GET['id'] ?? 0);

if ($recordId <= 0) {
    header('Location: dashboard.php?error=invalid_record');
    exit;
}

// Get medical record
$stmt = $pdo->prepare('SELECT * FROM medical_records WHERE id = ?');
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: dashboard.php?error=record_not_found');
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
    header('Location: dashboard.php?error=patient_not_found');
    exit;
}

// Parse form data
$formData = json_decode($record['form_data'], true);

$pageTitle = 'Medical Record View';
$showTopNav = true;
$showSidebar = false;
include __DIR__ . '/partials/header.php';
?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <button onclick="history.back()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
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
                        <div class="space-y-6">
                            <!-- Ongoing Conditions -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Ongoing Medical Conditions</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <?php if (!empty($formData['ongoing_conditions']) && is_array($formData['ongoing_conditions'])): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($formData['ongoing_conditions'] as $condition): ?>
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"><?= htmlspecialchars($condition) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-slate-500 italic">None reported</p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($formData['ongoing_conditions_other'])): ?>
                                        <div class="mt-2">
                                            <label class="text-sm font-medium text-slate-600">Other:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['ongoing_conditions_other']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Surgery History -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Surgery History</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="text-sm font-medium text-slate-600">Has had surgery:</span>
                                        <span class="ml-2 px-2 py-1 rounded text-sm <?= $formData['surgery_status'] === 'yes' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= ucfirst($formData['surgery_status']) ?>
                                        </span>
                                    </div>
                                    <?php if ($formData['surgery_status'] === 'yes' && !empty($formData['surgery_details'])): ?>
                                        <div class="mt-2">
                                            <label class="text-sm font-medium text-slate-600">Details:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['surgery_details']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Family Medical History -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Family Medical History</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <?php if (!empty($formData['family_conditions']) && is_array($formData['family_conditions'])): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($formData['family_conditions'] as $condition): ?>
                                                <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm"><?= htmlspecialchars($condition) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-slate-500 italic">None reported</p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($formData['family_conditions_other'])): ?>
                                        <div class="mt-2">
                                            <label class="text-sm font-medium text-slate-600">Other:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['family_conditions_other']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Smoke Exposure -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Smoke Exposure</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <span class="px-2 py-1 rounded text-sm <?= $formData['smoke_exposure'] === 'yes' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= ucfirst($formData['smoke_exposure']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Immunization -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">Immunization History</h3>
                                <div class="bg-slate-50 rounded-lg p-4">
                                    <?php if (!empty($formData['immunization']) && is_array($formData['immunization'])): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($formData['immunization'] as $vaccine): ?>
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $vaccine))) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-slate-500 italic">None reported</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- COVID-19 Information -->
                            <div>
                                <h3 class="text-md font-medium text-slate-700 mb-3">COVID-19 Information</h3>
                                <div class="bg-slate-50 rounded-lg p-4 space-y-3">
                                    <div>
                                        <label class="text-sm font-medium text-slate-600">Vaccination Status:</label>
                                        <?php if (!empty($formData['covid_vaccine']) && is_array($formData['covid_vaccine'])): ?>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <?php foreach ($formData['covid_vaccine'] as $dose): ?>
                                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $dose))) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-slate-500 italic">Not vaccinated</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-slate-600">COVID-19 Positive:</label>
                                        <span class="ml-2 px-2 py-1 rounded text-sm <?= $formData['covid_positive'] === 'yes' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= ucfirst($formData['covid_positive']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($formData['covid_positive'] === 'yes' && !empty($formData['covid_details'])): ?>
                                        <div>
                                            <label class="text-sm font-medium text-slate-600">Details:</label>
                                            <p class="text-slate-800"><?= htmlspecialchars($formData['covid_details']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Other form types -->
                        <div class="bg-slate-50 rounded-lg p-4">
                            <pre class="text-sm text-slate-700 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($formData, JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Edit Medical Record</h2>
            <button onclick="closeEditModal()" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editForm" method="POST" action="update_medical_record.php">
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
            <!-- Ongoing Conditions -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Ongoing Medical Conditions</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="ongoing_conditions[]" value="asthma" ${data.ongoing_conditions?.includes('asthma') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Asthma</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="ongoing_conditions[]" value="diabetes" ${data.ongoing_conditions?.includes('diabetes') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Diabetes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="ongoing_conditions[]" value="hypertension" ${data.ongoing_conditions?.includes('hypertension') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Hypertension</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="ongoing_conditions[]" value="error_refraction" ${data.ongoing_conditions?.includes('error_refraction') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Error of Refraction</span>
                    </label>
                </div>
                <div class="mt-2">
                    <input type="text" name="ongoing_conditions_other" value="${data.ongoing_conditions_other || ''}" placeholder="Other conditions..." class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>

            <!-- Surgery History -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Surgery History</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="surgery_status" value="no" ${data.surgery_status === 'no' ? 'checked' : ''} class="border-slate-300">
                        <span class="ml-2 text-sm">No</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="surgery_status" value="yes" ${data.surgery_status === 'yes' ? 'checked' : ''} class="border-slate-300">
                        <span class="ml-2 text-sm">Yes</span>
                    </label>
                </div>
                <div class="mt-2">
                    <textarea name="surgery_details" placeholder="Surgery details..." class="w-full px-3 py-2 border border-slate-300 rounded-lg" rows="3">${data.surgery_details || ''}</textarea>
                </div>
            </div>

            <!-- Family Medical History -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Family Medical History</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="diabetes" ${data.family_conditions?.includes('diabetes') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Diabetes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="hypertension" ${data.family_conditions?.includes('hypertension') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Hypertension</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="heart_disease" ${data.family_conditions?.includes('heart_disease') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Heart Disease</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="family_conditions[]" value="cancer" ${data.family_conditions?.includes('cancer') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Cancer</span>
                    </label>
                </div>
                <div class="mt-2">
                    <input type="text" name="family_conditions_other" value="${data.family_conditions_other || ''}" placeholder="Other family conditions..." class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>

            <!-- Smoke Exposure -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Smoke Exposure</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="smoke_exposure" value="no" ${data.smoke_exposure === 'no' ? 'checked' : ''} class="border-slate-300">
                        <span class="ml-2 text-sm">No</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="smoke_exposure" value="yes" ${data.smoke_exposure === 'yes' ? 'checked' : ''} class="border-slate-300">
                        <span class="ml-2 text-sm">Yes</span>
                    </label>
                </div>
            </div>

            <!-- Immunization -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Immunization History</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="immunization[]" value="mmr" ${data.immunization?.includes('mmr') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">MMR</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunization[]" value="dpt" ${data.immunization?.includes('dpt') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">DPT</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunization[]" value="bcg" ${data.immunization?.includes('bcg') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">BCG</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="immunization[]" value="hepatitis_b" ${data.immunization?.includes('hepatitis_b') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Hepatitis B</span>
                    </label>
                </div>
            </div>

            <!-- COVID-19 Information -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">COVID-19 Vaccination</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="covid_vaccine[]" value="first_dose" ${data.covid_vaccine?.includes('first_dose') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">First Dose</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="covid_vaccine[]" value="second_dose" ${data.covid_vaccine?.includes('second_dose') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Second Dose</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="covid_vaccine[]" value="booster_1" ${data.covid_vaccine?.includes('booster_1') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Booster 1</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="covid_vaccine[]" value="booster_2" ${data.covid_vaccine?.includes('booster_2') ? 'checked' : ''} class="rounded border-slate-300">
                        <span class="ml-2 text-sm">Booster 2</span>
                    </label>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">COVID-19 Positive</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="covid_positive" value="no" ${data.covid_positive === 'no' ? 'checked' : ''} class="border-slate-300">
                            <span class="ml-2 text-sm">No</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="covid_positive" value="yes" ${data.covid_positive === 'yes' ? 'checked' : ''} class="border-slate-300">
                            <span class="ml-2 text-sm">Yes</span>
                        </label>
                    </div>
                    <div class="mt-2">
                        <textarea name="covid_details" placeholder="COVID-19 details..." class="w-full px-3 py-2 border border-slate-300 rounded-lg" rows="3">${data.covid_details || ''}</textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateGenericEditForm(data) {
    return `
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Form Data (JSON)</label>
                <textarea name="form_data" class="w-full px-3 py-2 border border-slate-300 rounded-lg" rows="10">${JSON.stringify(data, null, 2)}</textarea>
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
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
