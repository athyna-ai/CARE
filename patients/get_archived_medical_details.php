<?php
require_once __DIR__ . '/../core/config.php';

$recordId = (int)($_GET['id'] ?? 0);
$patientType = $_GET['type'] ?? 'student';

if ($recordId <= 0) {
    echo '<div class="text-center py-8 text-red-600">Invalid record ID</div>';
    exit;
}

try {
    $pdo = get_pdo();
    
    // Get the archived medical record
    $archiveTable = $patientType . '_medical_archive';
    $stmt = $pdo->prepare("SELECT * FROM `{$archiveTable}` WHERE id = ?");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo '<div class="text-center py-8 text-red-600">Archived medical record not found</div>';
        exit;
    }
    
    // Decode the form data
    $formData = json_decode($record['form_data'], true);
    
    // Get patient information
    $patient = null;
    if ($patientType === 'student') {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$record['patient_id']]);
        $patient = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
        $stmt->execute([$record['patient_id']]);
        $patient = $stmt->fetch();
    }
    
    if (!$patient) {
        echo '<div class="text-center py-8 text-red-600">Patient not found</div>';
        exit;
    }
    
    // Display the archived medical record
    ?>
    <div class="space-y-6">
        <!-- Record Information -->
        <div class="bg-slate-50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Record Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Patient Name</label>
                    <p class="text-slate-800 font-medium"><?= htmlspecialchars($patient['name']) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Patient Type</label>
                    <p class="text-slate-800 font-medium"><?= ucfirst($patientType) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Form Type</label>
                    <p class="text-slate-800 font-medium"><?= ucfirst(str_replace('_', ' ', $record['form_type'])) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Original Record ID</label>
                    <p class="text-slate-800 font-medium">#<?= $record['original_id'] ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Created</label>
                    <p class="text-slate-800 font-medium"><?= date('M j, Y g:i A', strtotime($record['created_at'])) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Archived</label>
                    <p class="text-slate-800 font-medium"><?= date('M j, Y g:i A', strtotime($record['archived_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Form Data -->
        <div class="bg-slate-50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Form Data</h3>
            <div class="space-y-4">
                <?php if ($formData): ?>
                    <?php foreach ($formData as $key => $value): ?>
                        <?php if (!empty($value) && $value !== 'N/A'): ?>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1"><?= ucfirst(str_replace('_', ' ', $key)) ?></label>
                                <div class="text-slate-800">
                                    <?php if (is_array($value)): ?>
                                        <?php if (isset($value[0]) && is_string($value[0])): ?>
                                            <!-- Array of strings (like checkboxes) -->
                                            <p><?= htmlspecialchars(implode(', ', $value)) ?></p>
                                        <?php else: ?>
                                            <!-- Complex array data -->
                                            <pre class="text-sm bg-white p-3 rounded border"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p><?= htmlspecialchars($value) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-600 italic">No form data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<div class="text-center py-8 text-red-600">Error loading archived medical record: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
