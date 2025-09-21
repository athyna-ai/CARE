<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Get patient ID and type from URL
$patientId = (int)($_GET['id'] ?? 0);
$patientType = sanitize_string($_GET['type'] ?? 'student');

if ($patientId <= 0) {
    header('Location: ../admin/dashboard.php?error=invalid_patient');
    exit;
}

// Get patient information
$patient = null;
if ($patientType === 'student') {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
} else {
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
}

if (!$patient) {
    header('Location: ../admin/dashboard.php?error=patient_not_found');
    exit;
}

// Get archived visitation logs for this patient
$archivedVisits = [];
try {
    $archiveTable = $patientType . '_visitation_archive';
    $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE patient_id = ? ORDER BY archived_at DESC");
    $stmt->execute([$patientId]);
    $archivedVisits = $stmt->fetchAll();
} catch (Exception $e) {
    // Archive table doesn't exist yet
    $archivedVisits = [];
}

// Get archived medical records for this patient
$archivedMedical = [];
try {
    $archiveTable = $patientType . '_medical_archive';
    $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE patient_id = ? ORDER BY archived_at DESC");
    $stmt->execute([$patientId]);
    $archivedMedical = $stmt->fetchAll();
} catch (Exception $e) {
    // Archive table doesn't exist yet
    $archivedMedical = [];
}

include __DIR__ . '/../partials/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla">
    <!-- Header -->
    <div class="bg-white/95 backdrop-blur-md shadow-xl border-b border-clinic-tea/20">
        <div class="w-full px-6 py-4">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="../patients/patient_view.php?id=<?= $patientId ?>&type=<?= $patientType ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-200 font-poppins font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Patient
                </a>
            </div>
            
            <!-- Patient Header -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-clinic-blue/20 flex items-center justify-center text-clinic-blue text-2xl font-comfortaa font-bold">
                    <?= htmlspecialchars(substr($patient['name'], 0, 1)) ?>
                </div>
                <div>
                    <h1 class="text-3xl font-comfortaa font-bold text-clinic-dark"><?= htmlspecialchars($patient['name']) ?> - Archive</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-3 py-1 bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark text-sm font-poppins rounded-xl"><?= ucfirst($patientType) ?></span>
                        <span class="px-3 py-1 bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-dark text-sm font-poppins rounded-xl">ID: <?= htmlspecialchars($patient['rfid']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Archived Visitation Logs -->
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden">
                <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-6 py-4">
                    <h2 class="text-xl font-comfortaa font-bold text-white">Archived Visitation Logs</h2>
                </div>
                <div class="p-4">
                    <?php if (empty($archivedVisits)): ?>
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-lg bg-clinic-ivory/60 mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                </svg>
                            </div>
                            <p class="text-clinic-dark/60 font-poppins font-medium">No archived visits</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach ($archivedVisits as $visit): ?>
                                <div class="bg-clinic-ivory/40 rounded-xl p-3 border border-clinic-tea/20 hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-300">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-1 bg-clinic-blue/10 text-clinic-blue text-xs font-poppins font-medium rounded-lg">#<?= $visit['original_id'] ?></span>
                                                <p class="font-poppins font-semibold text-clinic-dark text-sm"><?= htmlspecialchars($visit['reason']) ?></p>
                                            </div>
                                            <p class="text-xs font-poppins text-clinic-dark/60"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($visit['visit_date']))) ?></p>
                                            <p class="text-xs font-poppins text-clinic-dark/50 mt-1">Archived: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($visit['archived_at']))) ?></p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="viewArchivedVisitationDetails(<?= $visit['id'] ?>, '<?= $patientType ?>')" class="px-3 py-1 bg-clinic-blue/20 text-clinic-blue text-xs font-medium rounded-lg hover:bg-clinic-blue/30 transition-colors">
                                                View
                                            </button>
                                            <button onclick="restoreVisitation(<?= $visit['id'] ?>, <?= $patientId ?>, '<?= $patientType ?>')" class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-lg hover:bg-green-200 transition-colors">
                                                Restore
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Archived Medical Records -->
            <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl border border-clinic-tea/20 overflow-hidden">
                <div class="bg-gradient-to-r from-clinic-tea to-clinic-vanilla px-6 py-4">
                    <h2 class="text-xl font-comfortaa font-bold text-white">Archived Medical Records</h2>
                </div>
                <div class="p-4">
                    <?php if (empty($archivedMedical)): ?>
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-lg bg-clinic-ivory/60 mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-clinic-dark/60 font-poppins font-medium">No archived medical records</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach ($archivedMedical as $record): ?>
                                <div class="bg-clinic-ivory/40 rounded-xl p-3 border border-clinic-tea/20 hover:bg-clinic-tea/20 hover:border-clinic-tea/40 transition-all duration-300">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-1 bg-clinic-tea/20 text-clinic-blue text-xs font-poppins font-medium rounded-lg">#<?= $record['original_id'] ?></span>
                                                <p class="font-poppins font-semibold text-clinic-dark text-sm"><?= htmlspecialchars($record['form_type']) ?></p>
                                            </div>
                                            <p class="text-xs font-poppins text-clinic-dark/60">Created: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($record['created_at']))) ?></p>
                                            <p class="text-xs font-poppins text-clinic-dark/50 mt-1">Archived: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($record['archived_at']))) ?></p>
                                        </div>
                                        <button onclick="viewArchivedMedicalRecord(<?= $record['id'] ?>, '<?= $patientType ?>')" class="px-3 py-1 bg-clinic-tea/20 text-clinic-blue text-xs font-medium rounded-lg hover:bg-clinic-tea/30 transition-colors">
                                            View
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archived Visitation Details Modal -->
<div id="archivedVisitationModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 md:p-8">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="relative w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-[calc(100vh-8rem)] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-slate-800">Archived Visitation Details</h2>
            <button onclick="closeArchivedVisitationModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="archivedVisitationContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function viewArchivedVisitationDetails(visitId, patientType) {
    // Show loading
    document.getElementById('archivedVisitationContent').innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-slate-600">Loading details...</p></div>';
    
    // Show modal
    document.getElementById('archivedVisitationModal').classList.remove('hidden');
    document.getElementById('archivedVisitationModal').classList.add('flex');
    
    // Load details
    fetch(`../logs/get_archived_visitation_details.php?id=${visitId}&type=${patientType}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('archivedVisitationContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('archivedVisitationContent').innerHTML = '<div class="text-center py-8 text-red-600">Error loading details. Please try again.</div>';
        });
}

function closeArchivedVisitationModal() {
    document.getElementById('archivedVisitationModal').classList.add('hidden');
    document.getElementById('archivedVisitationModal').classList.remove('flex');
}

function viewArchivedMedicalRecord(recordId, patientType) {
    // For now, just show an alert - you can implement this later
    alert('Medical record viewing not implemented yet. Record ID: ' + recordId);
}

function restoreVisitation(archiveId, patientId, patientType) {
    if (confirm('Are you sure you want to restore this archived visitation record? It will be moved back to the active logs.')) {
        // Show loading on the button
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Restoring...';
        button.disabled = true;
        
        // Restore visitation
        fetch('../admin/restore_visitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `archive_id=${archiveId}&patient_id=${patientId}&patient_type=${patientType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload the page
                alert('Visitation record restored successfully!');
                window.location.reload();
            } else {
                alert('Error restoring visitation: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('Error restoring visitation: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
