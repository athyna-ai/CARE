<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];
$info = [];
$student = null;

if ($studentId) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $errors[] = 'Student not found.';
    }
} else {
    $errors[] = 'Student ID is required.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    $name = sanitize_string($_POST['name'] ?? '');
    $gender = sanitize_string($_POST['gender'] ?? '');
    $level = sanitize_string($_POST['level'] ?? '');
    $year_grade = sanitize_string($_POST['year_grade'] ?? '');
    $section = sanitize_string($_POST['section'] ?? '');
    $strand = sanitize_string($_POST['strand'] ?? '');
    $course = sanitize_string($_POST['course'] ?? '');
    $block = sanitize_string($_POST['block'] ?? '');
    $rfid = sanitize_string($_POST['rfid'] ?? '');
    $barangay = sanitize_string($_POST['barangay'] ?? '');
    $municipality = sanitize_string($_POST['municipality'] ?? '');
    $province = sanitize_string($_POST['province'] ?? '');
    $dob = sanitize_string($_POST['dob'] ?? '');
    $religion = sanitize_string($_POST['religion'] ?? '');
    $guardian = sanitize_string($_POST['guardian'] ?? '');
    $allergies = sanitize_string($_POST['allergies'] ?? '');
    
    // Handle blank RFID (keep current RFID)
    if (empty($rfid)) {
        $rfid = $student['rfid']; // Keep current RFID
    }
    
    // Validation
    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($gender)) $errors[] = 'Gender is required.';
    if (empty($level)) $errors[] = 'Education level is required.';
    if (empty($dob)) $errors[] = 'Date of birth is required.';
    
    // Check RFID uniqueness (if changed)
    if ($rfid !== $student['rfid']) {
        $rfidCheck = $pdo->prepare('SELECT id FROM students WHERE rfid = ? AND id != ?');
        $rfidCheck->execute([$rfid, $studentId]);
        if ($rfidCheck->fetch()) {
            $errors[] = 'RFID number is already in use.';
        }
    }
    
    // Process emergency contacts
    $contacts = [];
    if (isset($_POST['contacts']) && is_array($_POST['contacts'])) {
        foreach ($_POST['contacts'] as $contact) {
            $contact = trim($contact);
            if (!empty($contact)) {
                $contacts[] = $contact;
            }
        }
    }
    if (empty($contacts)) {
        $errors[] = 'At least one emergency contact is required.';
    }
    
    if (empty($errors)) {
        // Build address
        $addressParts = array_filter([$barangay, $municipality, $province]);
        $address = implode(', ', $addressParts);
        
        // Calculate age
        $age = null;
        if ($dob) {
            $dobDate = DateTime::createFromFormat('d/m/Y', $dob);
            if ($dobDate) {
                $age = $dobDate->diff(new DateTime())->y;
            }
        }
        
        // Prepare contacts JSON
        $contactsJson = json_encode($contacts);
        
        // Store old data for enrollment history
        $oldData = [
            'name' => $student['name'],
            'gender' => $student['gender'],
            'level' => $student['level'],
            'year_grade' => $student['year_grade'],
            'section' => $student['section'],
            'strand' => $student['strand'],
            'course' => $student['course'],
            'block' => $student['block'],
            'rfid' => $student['rfid'],
            'address' => $student['address'],
            'dob' => $student['dob'],
            'age' => $student['age'],
            'religion' => $student['religion'],
            'guardian' => $student['guardian'],
            'emergency_contact' => $student['emergency_contact'],
            'contacts' => $student['contacts'],
            'allergies' => $student['allergies']
        ];
        
        // Update student
        $updateStmt = $pdo->prepare('
            UPDATE students SET 
                name = ?, gender = ?, level = ?, year_grade = ?, section = ?, strand = ?, 
                course = ?, block = ?, rfid = ?, address = ?, dob = STR_TO_DATE(?,"%d/%m/%Y"), 
                age = ?, religion = ?, guardian = ?, emergency_contact = ?, contacts = ?, allergies = ?, 
                status = "Active", updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ');
        $updateStmt->execute([
            $name, $gender, $level, $year_grade, $section, $strand, $course, $block, $rfid, 
            $address, $dob, $age, $religion, $guardian, $emergency_contact, $contactsJson, $allergies, $studentId
        ]);
        
        // Archive old medical records before re-enrollment
        try {
            // Get all medical records for this student
            $medicalStmt = $pdo->prepare('SELECT * FROM medical_records WHERE patient_id = ? AND patient_type = ?');
            $medicalStmt->execute([$studentId, 'student']);
            $oldMedicalRecords = $medicalStmt->fetchAll();
            
            if (!empty($oldMedicalRecords)) {
                // Create archive table if it doesn't exist
                $archiveTable = 'student_medical_archive';
                $tableExists = $pdo->query("SHOW TABLES LIKE '{$archiveTable}'")->rowCount() > 0;
                
                if (!$tableExists) {
                    $createTableSQL = "
                    CREATE TABLE IF NOT EXISTS `{$archiveTable}` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `original_id` int(11) NOT NULL,
                        `patient_id` int(11) NOT NULL,
                        `patient_type` enum('student','faculty') NOT NULL,
                        `form_type` varchar(50) NOT NULL,
                        `form_data` longtext NOT NULL,
                        `created_at` timestamp NOT NULL,
                        `archived_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        `archived_by` int(11) NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `patient_id` (`patient_id`),
                        KEY `archived_at` (`archived_at`),
                        KEY `original_id` (`original_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ";
                    $pdo->exec($createTableSQL);
                }
                
                // Archive each medical record
                foreach ($oldMedicalRecords as $record) {
                    $archiveStmt = $pdo->prepare("
                        INSERT INTO `{$archiveTable}` 
                        (original_id, patient_id, patient_type, form_type, form_data, created_at, archived_at, archived_by)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
                    ");
                    $archiveStmt->execute([
                        $record['id'],
                        $record['patient_id'],
                        $record['patient_type'],
                        $record['form_type'],
                        $record['form_data'],
                        $record['created_at'],
                        $_SESSION['user']['id']
                    ]);
                }
                
                // Delete old medical records from active table
                $deleteStmt = $pdo->prepare('DELETE FROM medical_records WHERE patient_id = ? AND patient_type = ?');
                $deleteStmt->execute([$studentId, 'student']);
                
                error_log("Archived " . count($oldMedicalRecords) . " medical records for re-enrolled student ID: {$studentId}");
            }
        } catch (Exception $e) {
            error_log("Error archiving medical records during re-enrollment: " . $e->getMessage());
        }
        
        // Record enrollment history with previous personal data
        $historyStmt = $pdo->prepare('
            INSERT INTO enrollment_history 
            (student_id, enrollment_type, previous_level, previous_status, previous_year_grade, 
             previous_section, previous_strand, previous_course, previous_block, 
             new_level, new_status, new_year_grade, new_section, new_strand, new_course, 
             new_block, notes, created_by, previous_rfid, previous_name, previous_gender, previous_dob, 
             previous_age, previous_religion, previous_barangay, previous_municipality, 
             previous_province, previous_guardian_name, previous_emergency_contact, 
             previous_contacts, previous_allergies) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        // Parse old address
        $oldBarangay = '';
        $oldMunicipality = '';
        $oldProvince = '';
        if (!empty($oldData['address'])) {
            $oldAddressParts = explode(',', $oldData['address']);
            $oldBarangay = trim($oldAddressParts[0] ?? '');
            $oldMunicipality = trim($oldAddressParts[1] ?? '');
            $oldProvince = trim($oldAddressParts[2] ?? '');
        }
        
        $historyStmt->execute([
            $studentId, 're_enrollment', $oldData['level'], 'Graduated', $oldData['year_grade'],
            $oldData['section'], $oldData['strand'], $oldData['course'], $oldData['block'],
            $level, 'Active', $year_grade, $section, $strand, $course, $block,
            "Re-enrolled from {$oldData['level']} to {$level}", $_SESSION['user']['id'],
            // Previous personal data
            $oldData['rfid'], $oldData['name'], $oldData['gender'], $oldData['dob'], $oldData['age'],
            $oldData['religion'], $oldBarangay, $oldMunicipality, $oldProvince,
            $oldData['guardian'], $oldData['emergency_contact'], $oldData['contacts'], $oldData['allergies']
        ]);
        
        // Redirect to patient information with success message
        $_SESSION['success_message'] = 'Student re-enrolled successfully!';
        header("Location: patient_view.php?id=" . $studentId);
        exit();
    }
}

// Parse existing address into separate fields
if ($student && !empty($student['address'])) {
    $addressParts = explode(',', $student['address']);
    $student['barangay'] = trim($addressParts[0] ?? '');
    $student['municipality'] = trim($addressParts[1] ?? '');
    $student['province'] = trim($addressParts[2] ?? '');
}

// Load existing emergency contacts
if ($student && !empty($student['contacts'])) {
    $existingContacts = json_decode($student['contacts'], true);
    if (is_array($existingContacts)) {
        $student['emergency_contacts'] = $existingContacts;
    }
}

?>
<?php $pageTitle = 'Re-enroll Student'; $showTopNav = true; $showSidebar = false; include __DIR__ . '/../partials/header.php'; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="patient_view.php?id=<?= $studentId ?>" class="inline-flex items-center gap-2 px-4 py-2 text-clinic-blue hover:text-clinic-tea hover:bg-clinic-blue/5 rounded-lg transition-colors duration-200 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Patient Information
            </a>
            <h1 class="text-3xl font-bold text-slate-800">Re-enroll Student</h1>
            <p class="text-slate-600 mt-2">Update student information for re-enrollment</p>
        </div>

        <?php if ($student): ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="mb-8 p-6 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800 mb-1">Please fix the following errors:</h3>
                    <ul class="text-sm text-red-700 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-clinic-blue to-clinic-tea px-8 py-6">
                <h2 class="text-2xl font-bold text-white">Student Re-enrollment Form</h2>
                <p class="text-white/90 mt-1">Complete the form below to re-enroll the student</p>
            </div>
            
            <form method="post" class="grid md:grid-cols-2 gap-6 p-8" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                
                <!-- Personal Information -->
                <div>
                    <label class="block text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" 
                           placeholder="e.g., Juan Dela Cruz Santos" 
                           class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
                           required />
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Gender <span class="text-red-500">*</span></label>
                    <select name="gender" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Education Level <span class="text-red-500">*</span></label>
                    <select name="level" id="levelSelect" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required>
                        <option value="">Select Education Level</option>
                        <option value="Pre-school" <?= ($student['level'] ?? '') === 'Pre-school' ? 'selected' : '' ?>>Pre-school</option>
                        <option value="Elementary" <?= ($student['level'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                        <option value="High School" <?= ($student['level'] ?? '') === 'High School' ? 'selected' : '' ?>>High School</option>
                        <option value="Senior High School" <?= ($student['level'] ?? '') === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                        <option value="College" <?= ($student['level'] ?? '') === 'College' ? 'selected' : '' ?>>College</option>
                    </select>
                </div>
                
                <!-- Year/Grade field (dynamic based on level) -->
                <div id="yearGradeField" class="hidden">
                    <label class="block text-slate-700 mb-1" id="yearGradeLabel">Year/Grade</label>
                    <select name="year_grade" id="yearGradeInput" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Grade/Year</option>
                    </select>
                </div>
                
                <!-- Course field (for College) -->
                <div id="courseField" class="hidden">
                    <label class="block text-slate-700 mb-1">Course</label>
                    <select name="course" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Course</option>
                        <option value="BS in Information Technology" <?= ($student['course'] ?? '') === 'BS in Information Technology' ? 'selected' : '' ?>>BS in Information Technology</option>
                        <option value="BS in Education" <?= ($student['course'] ?? '') === 'BS in Education' ? 'selected' : '' ?>>BS in Education</option>
                        <option value="BS in Criminology" <?= ($student['course'] ?? '') === 'BS in Criminology' ? 'selected' : '' ?>>BS in Criminology</option>
                        <option value="BS in Hospitality Management" <?= ($student['course'] ?? '') === 'BS in Hospitality Management' ? 'selected' : '' ?>>BS in Hospitality Management</option>
                        <option value="BS in Office Administration" <?= ($student['course'] ?? '') === 'BS in Office Administration' ? 'selected' : '' ?>>BS in Office Administration</option>
                    </select>
                </div>
                
                <!-- Block field (for College) -->
                <div id="blockField" class="hidden">
                    <label class="block text-slate-700 mb-1">Block</label>
                    <select name="block" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Block</option>
                        <option value="A" <?= ($student['block'] ?? '') === 'A' ? 'selected' : '' ?>>Block A</option>
                        <option value="B" <?= ($student['block'] ?? '') === 'B' ? 'selected' : '' ?>>Block B</option>
                        <option value="C" <?= ($student['block'] ?? '') === 'C' ? 'selected' : '' ?>>Block C</option>
                        <option value="D" <?= ($student['block'] ?? '') === 'D' ? 'selected' : '' ?>>Block D</option>
                    </select>
                </div>
                
                <!-- Section field (for Pre-school to Senior High School) -->
                <div id="sectionField" class="hidden">
                    <label class="block text-slate-700 mb-1">Section</label>
                    <input type="text" name="section" value="<?= htmlspecialchars($student['section'] ?? '') ?>" placeholder="e.g., Section A, Section B, Alpha, Beta" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                </div>
                
                <!-- Strand field (for Senior High School) -->
                <div id="strandField" class="hidden">
                    <label class="block text-slate-700 mb-1">Strand</label>
                    <select name="strand" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Strand</option>
                        <option value="STEM" <?= ($student['strand'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering, Mathematics)</option>
                        <option value="ABM" <?= ($student['strand'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business and Management)</option>
                        <option value="HUMSS" <?= ($student['strand'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities and Social Sciences)</option>
                        <option value="TVL" <?= ($student['strand'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL (Technical-Vocational-Livelihood)</option>
                        <option value="GAS" <?= ($student['strand'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">RFID Number <span class="text-red-500">*</span></label>
                    <input type="text" name="rfid" value="" 
                           placeholder="Enter new RFID or leave blank" 
                           class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                    <p class="text-xs text-blue-600 mt-1">Current: <?= htmlspecialchars($student['rfid'] ?? '') ?></p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Complete Address</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-600 text-sm mb-1">Barangay</label>
                            <input type="text" name="barangay" value="<?= htmlspecialchars($student['barangay'] ?? '') ?>" 
                                   placeholder="e.g., Cawayang Bugtong" 
                                   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-slate-600 text-sm mb-1">Municipality/City</label>
                            <input type="text" name="municipality" value="<?= htmlspecialchars($student['municipality'] ?? '') ?>" 
                                   placeholder="e.g., San Juan" 
                                   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-slate-600 text-sm mb-1">Province</label>
                            <input type="text" name="province" value="<?= htmlspecialchars($student['province'] ?? '') ?>" 
                                   placeholder="e.g., Nueva Ecija" 
                                   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Enter each part of the address separately</p>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="text" name="dob" id="dob" 
                           value="<?= isset($student['dob']) && $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : '' ?>" 
                           pattern="\d{2}/\d{2}/\d{4}" 
                           placeholder="DD/MM/YYYY" 
                           class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
                           maxlength="10" required />
                    <div id="dobError" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Age <span class="text-slate-500 text-sm">(Auto-calculated)</span></label>
                    <input type="number" name="age" id="ageInput" value="<?= htmlspecialchars((string)($student['age'] ?? '')) ?>" readonly class="w-full rounded-xl bg-slate-100 border border-slate-300 px-4 py-3 text-slate-600 cursor-not-allowed" />
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Religion</label>
                    <select name="religion" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
                        <option value="">Select Religion</option>
                        <?php
                        $religions = ['Roman Catholic','Islam','Iglesia ni Cristo','Born Again Christian','United Methodist','Aglipayan (IFI)','Seventh-day Adventist','Baptist','Hindu','Buddhist','None'];
                        $curR = $student['religion'] ?? '';
                        foreach ($religions as $rel) {
                            $sel = $curR === $rel ? 'selected' : '';
                            echo "<option value=\"{$rel}\" {$sel}>{$rel}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-slate-700 mb-1">Parent/Guardian Name</label>
                    <input type="text" name="guardian" value="<?= htmlspecialchars($student['guardian'] ?? '') ?>" 
                           placeholder="e.g., Maria Santos Dela Cruz" 
                           class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Emergency Contacts <span class="text-red-500">*</span></label>
                    <div id="contactsContainer" class="space-y-2">
                        <?php if (!empty($student['emergency_contacts'])): ?>
                            <?php foreach ($student['emergency_contacts'] as $index => $contact): ?>
                            <div class="flex items-center gap-2">
                                <input type="text" name="contacts[]" value="<?= htmlspecialchars($contact) ?>"
                                       class="w-full rounded-xl bg-white border border-slate-300 px-4 py-3" 
                                       placeholder="09xxxxxxxxx" 
                                       pattern="09[0-9]{9}" />
                                <?php if ($index > 0): ?>
                                <button type="button" class="px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors remove-contact-btn">Remove</button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="flex items-center gap-2">
                            <input type="text" name="contacts[]" 
                                   class="w-full rounded-xl bg-white border border-slate-300 px-4 py-3" 
                                   placeholder="09xxxxxxxxx" 
                                   pattern="09[0-9]{9}" />
                            <button type="button" class="px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors remove-contact-btn" style="display: none;">Remove</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="addContactBtn" class="mt-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Add another contact</button>
                    <p class="mt-1 text-xs text-slate-500">Add emergency contact numbers (at least one required)</p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-slate-700 mb-1">Allergies & Medical Notes</label>
                    <textarea name="allergies" rows="3" 
                              placeholder="List any known allergies (e.g., peanuts, shellfish, medications). Leave blank if none." 
                              class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800"><?= htmlspecialchars($student['allergies'] ?? '') ?></textarea>
                    <p class="mt-1 text-xs text-slate-500">Enter "None" or leave blank if the student has no known allergies or medical conditions.</p>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-clinic-blue to-clinic-tea text-white font-semibold py-4 rounded-xl transition-all duration-200 hover:shadow-lg hover:scale-[1.02] text-lg">
                        Re-enroll Student
                    </button>
                    <a href="patient_view.php?id=<?= $studentId ?>" class="mt-4 inline-block w-full text-center border border-slate-300 rounded-xl py-3 hover:bg-slate-50 transition-colors">Cancel</a>
                </div>
            </form>
        </div>
        
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Student Not Found</h3>
            <p class="text-slate-600">Please select a valid student to re-enroll.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const levelSelect = document.getElementById('levelSelect');
    const yearGradeField = document.getElementById('yearGradeField');
    const yearGradeInput = document.getElementById('yearGradeInput');
    const yearGradeLabel = document.getElementById('yearGradeLabel');
    const sectionField = document.getElementById('sectionField');
    const strandField = document.getElementById('strandField');
    const courseField = document.getElementById('courseField');
    const blockField = document.getElementById('blockField');
    
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('ageInput');
    const dobError = document.getElementById('dobError');
    
    // Level change handler
    function toggleFields() {
        const level = levelSelect.value;
        
        // Hide all fields first
        yearGradeField.classList.add('hidden');
        sectionField.classList.add('hidden');
        strandField.classList.add('hidden');
        courseField.classList.add('hidden');
        blockField.classList.add('hidden');
        
        // Clear all values
        yearGradeInput.innerHTML = '<option value="">Select Grade/Year</option>';
        
        if (level === 'Pre-school') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Year';
            const years = ['Year 1', 'Year 2'];
            years.forEach(year => {
                yearGradeInput.innerHTML += `<option value="${year}">${year}</option>`;
            });
        } else if (level === 'Elementary') {
            yearGradeField.classList.remove('hidden');
            sectionField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade';
            const grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
            grades.forEach(grade => {
                yearGradeInput.innerHTML += `<option value="${grade}">${grade}</option>`;
            });
        } else if (level === 'High School') {
            yearGradeField.classList.remove('hidden');
            sectionField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Year';
            const years = ['Year 1', 'Year 2', 'Year 3', 'Year 4'];
            years.forEach(year => {
                yearGradeInput.innerHTML += `<option value="${year}">${year}</option>`;
            });
        } else if (level === 'Senior High School') {
            yearGradeField.classList.remove('hidden');
            strandField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade';
            const grades = ['Grade 11', 'Grade 12'];
            grades.forEach(grade => {
                yearGradeInput.innerHTML += `<option value="${grade}">${grade}</option>`;
            });
        } else if (level === 'College') {
            yearGradeField.classList.remove('hidden');
            courseField.classList.remove('hidden');
            blockField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Year';
            const years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            years.forEach(year => {
                yearGradeInput.innerHTML += `<option value="${year}">${year}</option>`;
            });
        }
        
        // Set existing values
        const existingYearGrade = yearGradeInput.getAttribute('data-existing-value');
        if (existingYearGrade) {
            yearGradeInput.value = existingYearGrade;
        }
    }
    
    levelSelect.addEventListener('change', toggleFields);
    
    // Initialize fields on page load
    toggleFields();
    
    // Date of birth validation and age calculation
    dobInput.addEventListener('input', function() {
        const dobValue = this.value;
        const dobPattern = /^\d{2}\/\d{2}\/\d{4}$/;
        
        if (dobPattern.test(dobValue)) {
            const [day, month, year] = dobValue.split('/');
            const dobDate = new Date(year, month - 1, day);
            const today = new Date();
            
            if (dobDate <= today && dobDate.getFullYear() >= 1900) {
                const age = today.getFullYear() - dobDate.getFullYear();
                const monthDiff = today.getMonth() - dobDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
                    ageInput.value = age - 1;
                } else {
                    ageInput.value = age;
                }
                
                dobError.classList.add('hidden');
                this.classList.remove('border-red-500');
            } else {
                dobError.textContent = 'Please enter a valid date of birth';
                dobError.classList.remove('hidden');
                this.classList.add('border-red-500');
                ageInput.value = '';
            }
        } else if (dobValue.length === 10) {
            dobError.textContent = 'Please enter date in DD/MM/YYYY format';
            dobError.classList.remove('hidden');
            this.classList.add('border-red-500');
            ageInput.value = '';
        } else {
            dobError.classList.add('hidden');
            this.classList.remove('border-red-500');
            ageInput.value = '';
        }
    });
    
    // Emergency contacts functionality
    const addContactBtn = document.getElementById('addContactBtn');
    const contactsContainer = document.getElementById('contactsContainer');
    
    addContactBtn.addEventListener('click', function() {
        const newContactDiv = document.createElement('div');
        newContactDiv.className = 'flex items-center gap-2';
        newContactDiv.innerHTML = `
            <input type="text" name="contacts[]" 
                   class="w-full rounded-xl bg-white border border-slate-300 px-4 py-3" 
                   placeholder="09xxxxxxxxx" 
                   pattern="09[0-9]{9}" />
            <button type="button" class="px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors remove-contact-btn">Remove</button>
        `;
        contactsContainer.appendChild(newContactDiv);
        
        // Show remove buttons if more than one contact
        updateRemoveButtons();
    });
    
    // Remove contact functionality
    contactsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-contact-btn')) {
            e.target.parentElement.remove();
            updateRemoveButtons();
        }
    });
    
    function updateRemoveButtons() {
        const contactDivs = contactsContainer.querySelectorAll('.flex.items-center.gap-2');
        contactDivs.forEach((div, index) => {
            const removeBtn = div.querySelector('.remove-contact-btn');
            if (removeBtn) {
                removeBtn.style.display = index === 0 ? 'none' : 'block';
            }
        });
    }
    
    // Initialize remove buttons
    updateRemoveButtons();
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>