<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();

$pdo = get_pdo();

// Handle cleanup action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    try {
        // Find orphaned visitation records (missing patient data or archived patients)
        $orphanedStmt = $pdo->query("
            SELECT vl.id, vl.patient_id, vl.patient_type 
            FROM visitation_logs vl
            LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = 'student'
            LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = 'faculty'
            LEFT JOIN students_archive sa ON s.id = sa.original_id
            LEFT JOIN faculty_archive fa ON f.id = fa.original_id
            WHERE (vl.patient_type = 'student' AND (s.id IS NULL OR sa.id IS NOT NULL)) 
               OR (vl.patient_type = 'faculty' AND (f.id IS NULL OR fa.id IS NOT NULL))
        ");
        $orphanedRecords = $orphanedStmt->fetchAll();
        
        if (!empty($orphanedRecords)) {
            // Create orphaned records archive table
            $pdo->exec('CREATE TABLE IF NOT EXISTS orphaned_visitation_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                original_id INT UNSIGNED NOT NULL,
                patient_id INT UNSIGNED NOT NULL,
                patient_type ENUM("student","faculty") NOT NULL,
                reason VARCHAR(255) NOT NULL,
                visit_date DATETIME NOT NULL,
                symptoms TEXT NULL,
                other_notes TEXT NULL,
                heart_rate INT NULL,
                blood_pressure VARCHAR(50) NULL,
                temperature DECIMAL(4,2) NULL,
                medication_given TINYINT(1) DEFAULT 0,
                medication_name VARCHAR(255) NULL,
                other_treatment VARCHAR(255) NULL,
                medication_notes TEXT NULL,
                injury TINYINT(1) DEFAULT 0,
                first_aid_given TINYINT(1) DEFAULT 0,
                first_aid_type VARCHAR(255) NULL,
                nurse_name VARCHAR(100) NULL,
                created_by INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL,
                cleaned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                cleaned_by INT UNSIGNED NOT NULL,
                INDEX idx_original_id (original_id),
                INDEX idx_patient_id (patient_id),
                INDEX idx_patient_type (patient_type),
                INDEX idx_visit_date (visit_date),
                INDEX idx_cleaned_at (cleaned_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            
            // Move orphaned records to archive
            $archiveStmt = $pdo->prepare('INSERT INTO orphaned_visitation_logs 
                (original_id, patient_id, patient_type, reason, visit_date, symptoms, other_notes, 
                 heart_rate, blood_pressure, temperature, medication_given, medication_name, 
                 other_treatment, medication_notes, injury, first_aid_given, first_aid_type, 
                 nurse_name, created_by, created_at, updated_at, cleaned_by)
                SELECT id, patient_id, patient_type, reason, visit_date, symptoms, other_notes,
                       heart_rate, blood_pressure, temperature, medication_given, medication_name,
                       other_treatment, medication_notes, injury, first_aid_given, first_aid_type,
                       nurse_name, created_by, created_at, updated_at, ?
                FROM visitation_logs vl
                LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = "student"
                LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = "faculty"
                LEFT JOIN students_archive sa ON s.id = sa.original_id
                LEFT JOIN faculty_archive fa ON f.id = fa.original_id
                WHERE (vl.patient_type = "student" AND (s.id IS NULL OR sa.id IS NOT NULL)) 
                   OR (vl.patient_type = "faculty" AND (f.id IS NULL OR fa.id IS NOT NULL))');
            
            $archiveStmt->execute([$_SESSION['user']['id']]);
            
            // Delete orphaned records from main table
            $deleteStmt = $pdo->prepare('DELETE vl FROM visitation_logs vl
                LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = "student"
                LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = "faculty"
                LEFT JOIN students_archive sa ON s.id = sa.original_id
                LEFT JOIN faculty_archive fa ON f.id = fa.original_id
                WHERE (vl.patient_type = "student" AND (s.id IS NULL OR sa.id IS NOT NULL)) 
                   OR (vl.patient_type = "faculty" AND (f.id IS NULL OR fa.id IS NOT NULL))');
            $deleteStmt->execute();
            
            // Log the activity
            log_activity($pdo, (int)$_SESSION['user']['id'], 'orphaned_visitations_cleaned', 
                "Cleaned up " . count($orphanedRecords) . " orphaned visitation records", 'visitation_logs');
            
            $success = "Cleaned up " . count($orphanedRecords) . " orphaned visitation records.";
        } else {
            $success = "No orphaned records found.";
        }
    } catch (Exception $e) {
        $error = "Error cleaning up orphaned records: " . $e->getMessage();
    }
}

// Handle archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $visitId = (int)($_POST['visit_id'] ?? 0);
    
    if ($visitId > 0) {
        try {
            // Get visitation details before archiving
            $stmt = $pdo->prepare('SELECT vl.*, 
                CASE 
                    WHEN vl.patient_type = "student" THEN s.name
                    WHEN vl.patient_type = "faculty" THEN f.name
                END as patient_name
                FROM visitation_logs vl
                LEFT JOIN students s ON vl.patient_id = s.id AND vl.patient_type = "student"
                LEFT JOIN faculty f ON vl.patient_id = f.id AND vl.patient_type = "faculty"
                WHERE vl.id = ?');
            $stmt->execute([$visitId]);
            $visit = $stmt->fetch();
            
            if ($visit) {
                // Create archive table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS visitation_logs_archive (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    original_id INT UNSIGNED NOT NULL,
                    patient_id INT UNSIGNED NOT NULL,
                    patient_type ENUM("student","faculty") NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    visit_date DATETIME NOT NULL,
                    symptoms TEXT NULL,
                    other_notes TEXT NULL,
                    heart_rate INT NULL,
                    blood_pressure VARCHAR(50) NULL,
                    temperature DECIMAL(4,2) NULL,
                    medication_given TINYINT(1) DEFAULT 0,
                    medication_name VARCHAR(255) NULL,
                    other_treatment VARCHAR(255) NULL,
                    medication_notes TEXT NULL,
                    injury TINYINT(1) DEFAULT 0,
                    first_aid_given TINYINT(1) DEFAULT 0,
                    first_aid_type VARCHAR(255) NULL,
                    nurse_name VARCHAR(100) NULL,
                    created_by INT UNSIGNED NOT NULL,
                    created_at TIMESTAMP NOT NULL,
                    updated_at TIMESTAMP NOT NULL,
                    archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    archived_by INT UNSIGNED NOT NULL,
                    INDEX idx_original_id (original_id),
                    INDEX idx_patient_id (patient_id),
                    INDEX idx_patient_type (patient_type),
                    INDEX idx_visit_date (visit_date),
                    INDEX idx_archived_at (archived_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                
                // Move to archive
                $archiveStmt = $pdo->prepare('INSERT INTO visitation_logs_archive 
                    (original_id, patient_id, patient_type, reason, visit_date, symptoms, other_notes, 
                     heart_rate, blood_pressure, temperature, medication_given, medication_name, 
                     other_treatment, medication_notes, injury, first_aid_given, first_aid_type, 
                     nurse_name, created_by, created_at, updated_at, archived_by)
                    SELECT id, patient_id, patient_type, reason, visit_date, symptoms, other_notes,
                           heart_rate, blood_pressure, temperature, medication_given, medication_name,
                           other_treatment, medication_notes, injury, first_aid_given, first_aid_type,
                           nurse_name, created_by, created_at, updated_at, ?
                    FROM visitation_logs WHERE id = ?');
                
                $archiveStmt->execute([$_SESSION['user']['id'], $visitId]);
                
                // Delete from main table
                $deleteStmt = $pdo->prepare('DELETE FROM visitation_logs WHERE id = ?');
                $deleteStmt->execute([$visitId]);
                
                // Log the activity
                log_activity($pdo, (int)$_SESSION['user']['id'], 'visitation_archived', 
                    "Archived visitation record for {$visit['patient_name']}", 'visitation_logs');
                
                $success = "Visitation record archived successfully.";
            }
        } catch (Exception $e) {
            $error = "Error archiving visitation record: " . $e->getMessage();
        }
    }
}

// Simple query to get all visitation records
$sql = "SELECT vl.id, vl.patient_id, vl.patient_type, vl.reason, vl.visit_date, vl.created_at,
        s.name as patient_name,
        s.rfid as patient_rfid,
        CASE 
            WHEN s.level IN ('Elementary', 'High School', 'Senior High School') THEN s.year_grade
            WHEN s.level = 'College' THEN s.year_grade
            ELSE s.level
        END as grade_level_department,
        CASE 
            WHEN s.level IN ('Elementary', 'High School') THEN s.section
            WHEN s.level = 'Senior High School' THEN s.strand
            WHEN s.level = 'College' THEN s.course
            ELSE s.section
        END as course_section_strand
        FROM visitation_logs vl
        INNER JOIN students s ON vl.patient_id = s.id
        LEFT JOIN students_archive sa ON s.id = sa.original_id
        WHERE sa.id IS NULL
        
        UNION ALL
        
        SELECT vl.id, vl.patient_id, vl.patient_type, vl.reason, vl.visit_date, vl.created_at,
        f.name as patient_name,
        f.rfid as patient_rfid,
        f.department as grade_level_department,
        f.position as course_section_strand
        FROM visitation_logs vl
        INNER JOIN faculty f ON vl.patient_id = f.id
        LEFT JOIN faculty_archive fa ON f.id = fa.original_id
        WHERE fa.id IS NULL
        
        ORDER BY visit_date DESC
        LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$visitations = $stmt->fetchAll();

?>
<?php $pageTitle = 'Visitation Logs'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>

<!-- Popup Notification Container -->
<div id="notificationContainer" class="fixed top-4 right-4 z-50"></div>

<div class="grid gap-6">
    <!-- Header -->
    <div class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-xl p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <a href="dashboard.php" class="flex items-center gap-2 text-clinic-blue hover:text-clinic-tea transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Visitation Logs</h1>
                <p class="text-slate-600">View and manage all visitation records</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to clean up orphaned visitation records? This will move records with missing patient data to an archive.')">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="px-4 py-2 bg-clinic-blue text-white rounded-xl hover:bg-clinic-tea transition-colors font-medium">
                        Cleanup Orphaned
                    </button>
                </form>
                <a href="archive_management.php" class="px-4 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-700 transition-colors font-medium">
                    View Archives
                </a>
            </div>
        </div>
    </div>


    <!-- Results -->
    <div class="rounded-2xl bg-white/80 backdrop-blur border border-slate-200 shadow-xl overflow-hidden">
        <?php if (empty($visitations)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-600 mb-2">No Visitation Records Found</h3>
                <p class="text-slate-500">No visitation records match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(0)">ID <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(1)">RFID <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(2)">Name <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(3)">Grade/Level/Year <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(4)">Course/Section/Strand <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(5)">Visit Date <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" onclick="sortTable(6)">Reason <span class="sort-arrow">↕</span></th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($visitations as $visit): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono text-sm text-slate-600">#<?= htmlspecialchars((string)$visit['id']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?php if ($visit['patient_rfid']): ?>
                                        ****<?= htmlspecialchars(substr($visit['patient_rfid'], -4)) ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        <?= htmlspecialchars($visit['patient_name'] ?? 'Unknown') ?>
                                    </div>
                                    <div class="text-xs text-slate-500 capitalize">
                                        <?= htmlspecialchars($visit['patient_type']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($visit['grade_level_department'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($visit['course_section_strand'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <div><?= date('M j, Y', strtotime($visit['visit_date'])) ?></div>
                                    <div class="text-xs text-slate-500"><?= date('g:i A', strtotime($visit['visit_date'])) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?= htmlspecialchars($visit['reason'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex gap-2">
                                        <a href="visitation_details_view.php?id=<?= $visit['id'] ?>" 
                                           class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-xs font-medium">
                                            View
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to archive this visitation record?')">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="visit_id" value="<?= $visit['id'] ?>">
                                            <button type="submit" class="px-3 py-1 bg-clinic-blue/10 text-clinic-blue rounded-lg hover:bg-clinic-blue/20 transition-colors text-xs font-medium">
                                                Archive
                                            </button>
                                        </form>
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

<script>
// Popup notification system
function showNotification(message, type = 'success', duration = 3000) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icon = type === 'success' ? 
        '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' :
        '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
    
    notification.innerHTML = `
        <div class="flex items-center gap-3 ${bgColor} text-white px-6 py-4 rounded-xl shadow-lg mb-2 transform transition-all duration-300 translate-x-full">
            ${icon}
            <span class="font-medium">${message}</span>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.querySelector('div').classList.remove('translate-x-full');
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        notification.querySelector('div').classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Show notifications if any
<?php if (isset($success)): ?>
    showNotification('<?= addslashes($success) ?>', 'success');
<?php endif; ?>

// Table sorting functionality
let sortDirection = {};

function sortTable(columnIndex) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
    
    // Update sort arrows
    document.querySelectorAll('.sort-arrow').forEach(arrow => {
        arrow.textContent = '↕';
    });
    document.querySelectorAll('th')[columnIndex].querySelector('.sort-arrow').textContent = 
        sortDirection[columnIndex] === 'asc' ? '↑' : '↓';
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Handle numeric columns (ID)
        if (columnIndex === 0) {
            const aNum = parseInt(aValue.replace('#', ''));
            const bNum = parseInt(bValue.replace('#', ''));
            return sortDirection[columnIndex] === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Handle date columns (Visit Date)
        if (columnIndex === 5) {
            const aDate = new Date(aValue);
            const bDate = new Date(bValue);
            return sortDirection[columnIndex] === 'asc' ? aDate - bDate : bDate - aDate;
        }
        
        // Handle text columns
        const comparison = aValue.localeCompare(bValue);
        return sortDirection[columnIndex] === 'asc' ? comparison : -comparison;
    });
    
    // Reorder rows in the table
    rows.forEach(row => tbody.appendChild(row));
}

<?php if (isset($error)): ?>
    showNotification('<?= addslashes($error) ?>', 'error');
<?php endif; ?>
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
