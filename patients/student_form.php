<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Ensure students table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  gender ENUM("Male","Female") NULL,
  level ENUM("Pre-school","Elementary","High School","Senior High School","College") NOT NULL,
  course VARCHAR(120) NULL,
  block VARCHAR(10) NULL,
  section VARCHAR(50) NULL,
  strand VARCHAR(50) NULL,
  year_grade VARCHAR(40) NULL,
  rfid VARCHAR(50) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  age INT NULL,
  dob DATE NULL,
  religion VARCHAR(80) NULL,
  guardian VARCHAR(120) NULL,
  allergies TEXT NULL,
  contacts JSON NULL,
  emergency_contact VARCHAR(120) NULL,
  medical_notes TEXT NULL,
  status ENUM("Active","Inactive","Graduated","Transferred") DEFAULT "Active",
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_level (level),
  INDEX idx_status (status),
  INDEX idx_rfid (rfid),
  INDEX idx_name (name)
) ENGINE=InnoDB');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$prefillRfid = sanitize_string($_GET['rfid'] ?? '');
$prefillLevel = sanitize_string($_GET['level'] ?? '');
$reenrolled = isset($_GET['reenrolled']) ? (int)$_GET['reenrolled'] : 0;

// Handle re-enrollment parameters
$newRfid = sanitize_string($_GET['new_rfid'] ?? '');
$newLevel = sanitize_string($_GET['new_level'] ?? '');
$newYearGrade = sanitize_string($_GET['new_year_grade'] ?? '');
$newSection = sanitize_string($_GET['new_section'] ?? '');
$newStrand = sanitize_string($_GET['new_strand'] ?? '');
$newCourse = sanitize_string($_GET['new_course'] ?? '');
$newBlock = sanitize_string($_GET['new_block'] ?? '');
$errors = [];
$info = [];
$student = null;
$reenrollmentDetected = false;
$reenrollmentStudent = null;

if ($id) {
	$st = $pdo->prepare('SELECT * FROM students WHERE id = ?');
	$st->execute([$id]);
	$student = $st->fetch();
	
	// If this is a re-enrollment, update the student data with new values
	if ($reenrolled && $student) {
		if (!empty($newRfid)) $student['rfid'] = $newRfid;
		if (!empty($newLevel)) $student['level'] = $newLevel;
		if (!empty($newYearGrade)) $student['year_grade'] = $newYearGrade;
		if (!empty($newSection)) $student['section'] = $newSection;
		if (!empty($newStrand)) $student['strand'] = $newStrand;
		if (!empty($newCourse)) $student['course'] = $newCourse;
		if (!empty($newBlock)) $student['block'] = $newBlock;
	}
	
	// Load existing emergency contacts
	if ($student && !empty($student['contacts'])) {
		$existingContacts = json_decode($student['contacts'], true);
		if (is_array($existingContacts)) {
			$student['emergency_contacts'] = $existingContacts;
		}
	}
	
	// Parse existing address into separate fields
	if ($student && !empty($student['address'])) {
		$addressParts = explode(',', $student['address']);
		$student['barangay'] = trim($addressParts[0] ?? '');
		$student['municipality'] = trim($addressParts[1] ?? '');
		$student['province'] = trim($addressParts[2] ?? '');
	}
} else {
	// Check for potential re-enrollment when form is first loaded (for new registrations)
	if (!empty($prefillRfid)) {
		$rfidCheck = $pdo->prepare('SELECT * FROM students WHERE rfid = ? AND status = "Graduated"');
		$rfidCheck->execute([$prefillRfid]);
		$reenrollmentStudent = $rfidCheck->fetch();
		if ($reenrollmentStudent) {
			$reenrollmentDetected = true;
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		$name = sanitize_string($_POST['name'] ?? '');
		$gender = sanitize_string($_POST['gender'] ?? '');
		$level = sanitize_string($_POST['level'] ?? '');
		$course = sanitize_string($_POST['course'] ?? '');
		$block = sanitize_string($_POST['block'] ?? '');
		$section = sanitize_string($_POST['section'] ?? '');
		$strand = sanitize_string($_POST['strand'] ?? '');
		$year_grade = sanitize_string($_POST['year_grade'] ?? '');
		$rfid = sanitize_string($_POST['rfid'] ?? '');
		$barangay = sanitize_string($_POST['barangay'] ?? '');
		$municipality = sanitize_string($_POST['municipality'] ?? '');
		$province = sanitize_string($_POST['province'] ?? '');
		// Combine address fields
		$address = trim($barangay . ', ' . $municipality . ', ' . $province, ', ');
		$age = (int)($_POST['age'] ?? 0);
		$dob = sanitize_string($_POST['dob'] ?? '');
		$religion = sanitize_string($_POST['religion'] ?? '');
		$guardian = sanitize_string($_POST['guardian'] ?? '');
		$allergies = sanitize_string($_POST['allergies'] ?? '');
		// Save "N/A" if allergies field is left blank
		if (empty(trim($allergies))) {
			$allergies = 'N/A';
		}
		$contacts = $_POST['contacts'] ?? [];
		$consented = isset($_POST['consented']);

		if ($name === '' || $gender === '' || $level === '' || $rfid === '') { $errors[] = 'Name, Gender, Level and RFID are required.'; }
		if ($dob !== '' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dob)) { $errors[] = 'DOB must be DD/MM/YYYY.'; }
		if (!$consented) { $errors[] = 'You must agree to the data privacy consent.'; }
		
		// Validate that at least one contact is provided
		$validContacts = array_filter(array_map('trim', (array)$contacts), function($contact) {
			return !empty($contact);
		});
		if (empty($validContacts)) { $errors[] = 'At least one contact number is required.'; }

		if (!$errors) {
			// Check for re-enrollment scenario (only for new registrations, not edits)
			$reenrollmentStudent = null;
			if (!$id) {
				// Check if RFID already exists in graduated students
				$rfidCheck = $pdo->prepare('SELECT * FROM students WHERE rfid = ? AND status = "Graduated"');
				$rfidCheck->execute([$rfid]);
				$reenrollmentStudent = $rfidCheck->fetch();
				
				// If RFID not found, check by name and DOB for graduated students
				if (!$reenrollmentStudent && !empty($dob)) {
					$nameDobCheck = $pdo->prepare('SELECT * FROM students WHERE name = ? AND dob = STR_TO_DATE(?,"%d/%m/%Y") AND status = "Graduated"');
					$nameDobCheck->execute([$name, $dob]);
					$reenrollmentStudent = $nameDobCheck->fetch();
				}
			}
			
			// Store original data in main table (no hashing)
			$contactsJson = json_encode(array_values(array_filter(array_map('trim', (array)$contacts))));
			
			if ($id) {
				$upd = $pdo->prepare('UPDATE students SET name=?, gender=?, level=?, course=?, block=?, section=?, strand=?, year_grade=?, rfid=?, address=?, age=?, dob=STR_TO_DATE(?,"%d/%m/%Y"), religion=?, guardian=?, allergies=?, contacts=? WHERE id=?');
				$upd->execute([$name,$gender,$level,$course,$block,$section,$strand,$year_grade,$rfid,$address,$age,$dob,$religion,$guardian,$allergies,$contactsJson,$id]);
				$info[] = 'Student updated successfully.';
				
				// Log activity
				log_activity($pdo, (int)$_SESSION['user']['id'], 'student_update', "Updated student: {$name} ({$level})", 'student_form');
			} else {
				// Handle re-enrollment or new registration
				if ($reenrollmentStudent) {
					// Store previous enrollment data for history
					$previousLevel = $reenrollmentStudent['level'];
					$previousStatus = $reenrollmentStudent['status'];
					$previousYearGrade = $reenrollmentStudent['year_grade'];
					$previousSection = $reenrollmentStudent['section'];
					$previousStrand = $reenrollmentStudent['strand'];
					$previousCourse = $reenrollmentStudent['course'];
					$previousBlock = $reenrollmentStudent['block'];
					
					// Re-enroll existing graduated student
					$reenrollStmt = $pdo->prepare('UPDATE students SET name=?, gender=?, level=?, course=?, block=?, section=?, strand=?, year_grade=?, rfid=?, address=?, age=?, dob=STR_TO_DATE(?,"%d/%m/%Y"), religion=?, guardian=?, allergies=?, contacts=?, status="Active", updated_at=CURRENT_TIMESTAMP WHERE id=?');
					$reenrollStmt->execute([$name,$gender,$level,$course,$block,$section,$strand,$year_grade,$rfid,$address,$age,$dob,$religion,$guardian,$allergies,$contactsJson,$reenrollmentStudent['id']]);
					
					// Record enrollment history
					$historyStmt = $pdo->prepare('INSERT INTO enrollment_history (
						student_id, enrollment_type, previous_level, new_level, previous_status, new_status,
						previous_year_grade, new_year_grade, previous_section, new_section,
						previous_strand, new_strand, previous_course, new_course,
						previous_block, new_block, enrollment_year, notes, created_by
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
					
					$currentYear = date('Y');
					$notes = "Re-enrolled from {$previousLevel} to {$level}";
					
					$historyStmt->execute([
						$reenrollmentStudent['id'],
						're_enrollment',
						$previousLevel,
						$level,
						$previousStatus,
						'Active',
						$previousYearGrade,
						$year_grade,
						$previousSection,
						$section,
						$previousStrand,
						$strand,
						$previousCourse,
						$course,
						$previousBlock,
						$block,
						$currentYear,
						$notes,
						$_SESSION['user']['id']
					]);
					
					$info[] = 'Student re-enrolled successfully.';
					
					// Log activity
					log_activity($pdo, (int)$_SESSION['user']['id'], 'student_reenroll', "Re-enrolled student: {$name} ({$level})", 'student_form');
				} else {
					// Register new student
					$ins = $pdo->prepare('INSERT INTO students (name, gender, level, course, block, section, strand, year_grade, rfid, address, age, dob, religion, guardian, allergies, contacts) VALUES (?,?,?,?,?,?,?,?,?,?,?,STR_TO_DATE(?,"%d/%m/%Y"),?,?,?,?)');
					$ins->execute([$name,$gender,$level,$course,$block,$section,$strand,$year_grade,$rfid,$address,$age,$dob,$religion,$guardian,$allergies,$contactsJson]);
					
					$newStudentId = $pdo->lastInsertId();
					
					// Record initial enrollment history
					$historyStmt = $pdo->prepare('INSERT INTO enrollment_history (
						student_id, enrollment_type, new_level, new_status,
						new_year_grade, new_section, new_strand, new_course,
						new_block, enrollment_year, notes, created_by
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
					
					$currentYear = date('Y');
					$notes = "Initial enrollment in {$level}";
					
					$historyStmt->execute([
						$newStudentId,
						'initial',
						$level,
						'Active',
						$year_grade,
						$section,
						$strand,
						$course,
						$block,
						$currentYear,
						$notes,
						$_SESSION['user']['id']
					]);
					
					$info[] = 'Student registered successfully.';
					
					// Log activity
					log_activity($pdo, (int)$_SESSION['user']['id'], 'student_register', "Registered new student: {$name} ({$level})", 'student_form');
				}
			}
			// Redirect to dashboard after successful save
			header('Location: ../admin/dashboard.php?success=1');
			exit;
		}
	}
}

?>
<?php $pageTitle = $id ? 'Edit Student' : 'Register Student'; $showTopNav = true; $showSidebar = false; include __DIR__ . '/../partials/header.php'; ?>
	<div class="h-[calc(100vh-5rem)] flex items-start md:items-center justify-center p-4 md:p-8 overflow-hidden">
		<div class="w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10 max-h-full flex flex-col">
			<!-- Back Button -->
			<div class="mb-4">
				<button onclick="goBack()" class="inline-flex items-center gap-2 px-4 py-2 text-clinic-blue hover:text-clinic-tea hover:bg-clinic-blue/5 rounded-lg transition-colors duration-200">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
					</svg>
					Back
				</button>
			</div>
			<h1 class="text-2xl font-semibold mb-4"><?= $id ? 'Edit Student' : 'Register Student' ?></h1>
			
			<!-- Re-enrollment Detection Notice -->
			<?php if ($reenrollmentDetected && $reenrollmentStudent): ?>
			<div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
				<div class="flex items-start gap-3">
					<div class="flex-shrink-0">
						<svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
						</svg>
					</div>
					<div class="flex-1">
						<h3 class="text-sm font-medium text-blue-800 mb-1">Re-enrollment Detected</h3>
						<p class="text-sm text-blue-700">
							Found a graduated student with RFID <strong><?= htmlspecialchars($reenrollmentStudent['rfid']) ?></strong> 
							(Previously: <?= htmlspecialchars($reenrollmentStudent['name']) ?> - <?= htmlspecialchars($reenrollmentStudent['level']) ?>).
							<br>
							<strong>This will re-enroll the existing student instead of creating a new record.</strong>
						</p>
					</div>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Re-enrollment Success Notice -->
			<?php if ($reenrolled && $student): ?>
			<div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-2xl shadow-lg">
				<div class="flex items-start gap-4">
					<div class="flex-shrink-0">
						<div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
							<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
						</div>
					</div>
					<div class="flex-1">
						<h3 class="text-xl font-bold text-green-800 mb-3">✅ Re-enrollment Completed Successfully!</h3>
						<div class="space-y-2">
							<p class="text-lg text-green-700 font-medium">
								Student has been successfully re-enrolled with new information.
							</p>
							<?php if (!empty($newRfid)): ?>
							<div class="bg-white/60 backdrop-blur-sm rounded-lg p-4 border border-green-200">
								<p class="text-base text-green-800 font-semibold">
									🆔 RFID Updated: <span class="text-green-600"><?= htmlspecialchars($student['rfid']) ?></span>
								</p>
								<p class="text-sm text-green-700 mt-1">
									Level: <span class="font-medium"><?= htmlspecialchars($student['level']) ?></span>
									<?php if (!empty($student['year_grade'])): ?>
										| Grade/Year: <span class="font-medium"><?= htmlspecialchars($student['year_grade']) ?></span>
									<?php endif; ?>
									<?php if (!empty($student['section'])): ?>
										| Section: <span class="font-medium"><?= htmlspecialchars($student['section']) ?></span>
									<?php endif; ?>
								</p>
							</div>
							<?php endif; ?>
							<p class="text-base text-green-700 font-medium">
								📝 The form below has been pre-populated with the new information.
								<br>
								<strong class="text-green-800">You can now make additional updates if needed.</strong>
							</p>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Popup notifications container -->
			<div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>
			<form method="post" class="grid md:grid-cols-2 gap-4 flex-1 overflow-y-auto" autocomplete="on">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
				<div>
					<label class="block text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
					<input type="text" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" 
						   placeholder="e.g., Juan Dela Cruz Santos" 
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   required 
						   autofocus
						   data-next-field="gender" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Gender <span class="text-red-500">*</span></label>
					<select name="gender" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required data-next-field="level">
						<option value="">Select Gender</option>
						<option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
						<option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
					</select>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Education Level <span class="text-red-500">*</span></label>
					<select name="level" id="levelSelect" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required data-next-field="rfid">
						<option value="">Select Education Level</option>
						<option value="Pre-school" <?= (($student['level'] ?? $prefillLevel) === 'Pre-school') ? 'selected' : '' ?>>Pre-school</option>
						<option value="Elementary" <?= (($student['level'] ?? $prefillLevel) === 'Elementary') ? 'selected' : '' ?>>Elementary</option>
						<option value="High School" <?= (($student['level'] ?? $prefillLevel) === 'High School') ? 'selected' : '' ?>>High School</option>
						<option value="Senior High School" <?= (($student['level'] ?? $prefillLevel) === 'Senior High School') ? 'selected' : '' ?>>Senior High School</option>
						<option value="College" <?= (($student['level'] ?? $prefillLevel) === 'College') ? 'selected' : '' ?>>College</option>
					</select>
				</div>
				<!-- Year/Grade field (dynamic based on level) -->
				<div id="yearGradeField" class="hidden">
					<label class="block text-slate-700 mb-1" id="yearGradeLabel">Year/Grade</label>
					<select name="year_grade" id="yearGradeInput" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" data-existing-value="<?= htmlspecialchars($student['year_grade'] ?? '') ?>">
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
					<input type="text" name="rfid" value="<?= htmlspecialchars($prefillRfid) ?>" 
						   placeholder="e.g., 1234567890" 
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   required 
						   data-next-field="barangay" />
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Complete Address</label>
					<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
						<div>
							<label class="block text-slate-600 text-sm mb-1">Barangay</label>
							<input type="text" name="barangay" value="<?= htmlspecialchars($student['barangay'] ?? '') ?>" 
								   placeholder="e.g., Cawayang Bugtong" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="municipality" />
						</div>
						<div>
							<label class="block text-slate-600 text-sm mb-1">Municipality/City</label>
							<input type="text" name="municipality" value="<?= htmlspecialchars($student['municipality'] ?? '') ?>" 
								   placeholder="e.g., San Juan" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="province" />
						</div>
						<div>
							<label class="block text-slate-600 text-sm mb-1">Province</label>
							<input type="text" name="province" value="<?= htmlspecialchars($student['province'] ?? '') ?>" 
								   placeholder="e.g., Nueva Ecija" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="dob" />
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
						   maxlength="10" 
						   data-next-field="religion" 
						   required />
					<div id="dobError" class="text-red-500 text-sm mt-1 hidden"></div>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Age <span class="text-slate-500 text-sm">(Auto-calculated)</span></label>
					<input type="number" name="age" id="ageInput" value="<?= htmlspecialchars((string)($student['age'] ?? '')) ?>" readonly class="w-full rounded-xl bg-slate-100 border border-slate-300 px-4 py-3 text-slate-600 cursor-not-allowed" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Religion</label>
					<select name="religion" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" data-next-field="guardian">
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
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   data-next-field="contacts" />
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Emergency Contacts <span class="text-red-500">*</span></label>
					<div id="contactsContainer" class="space-y-2">
						<div class="flex items-center gap-2">
							<input type="text" name="contacts[]" 
								   class="w-full rounded-xl bg-white border border-slate-300 px-4 py-3" 
								   placeholder="09xxxxxxxxx" 
								   pattern="09[0-9]{9}" 
								   data-next-field="allergies" />
							<button type="button" class="px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors remove-contact-btn" style="display: none;">Remove</button>
						</div>
					</div>
					<button type="button" id="addContactBtn" class="mt-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Add another contact</button>
					<p class="mt-1 text-xs text-slate-500">Add emergency contact numbers (at least one required)</p>
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Allergies & Medical Notes</label>
					<textarea name="allergies" rows="3" 
							  placeholder="List any known allergies (e.g., peanuts, shellfish, medications). Leave blank if none." 
							  class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800"
							  data-next-field="consent"><?= htmlspecialchars($student['allergies'] ?? '') ?></textarea>
					<p class="mt-1 text-xs text-slate-500">Enter "None" or leave blank if the student has no known allergies or medical conditions.</p>
				</div>

				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Data Privacy Consent</label>
					<div class="rounded-xl border border-slate-300 bg-white p-3">
						<p class="text-sm text-slate-600">The Department of Education shall engage in the collection of health/medical information for tracking, provision of necessary health/medical interventions, and educational purposes...</p>
						<button type="button" id="showPrivacyBtn" class="mt-2 text-sky-600 hover:text-sky-700 text-sm underline">Show All</button>
					</div>
					<label class="mt-2 inline-flex items-center gap-2">
						<input type="checkbox" name="consented" id="consentedChk" class="h-4 w-4" disabled>
						<span class="text-slate-400" id="consentLabel">I understand and agree to the Data Privacy Consent terms</span>
					</label>
					<div id="consentHelp" class="mt-1 text-xs text-slate-500">
						<span id="consentHelpText">Click "Show All" above to read the complete Data Privacy Consent</span>
					</div>
				</div>

				<div class="md:col-span-2">
					<button id="saveStudentBtn" class="w-full bg-slate-400 text-white font-semibold py-3 rounded-xl transition cursor-not-allowed" disabled>
						<span id="continueText">Complete all required fields and read the Data Privacy Consent</span>
					</button>
					<div id="formHelp" class="mt-2 text-xs text-slate-500 text-center">
						<span id="formHelpText">Fill in all required fields (Name, Level, RFID) and read the complete Data Privacy Consent</span>
					</div>
					<a href="../admin/dashboard.php" class="mt-2 inline-block w-full text-center border border-slate-300 rounded-xl py-3 hover:bg-slate-50">Cancel</a>
				</div>
			</form>
		</div>
	</div>

	<!-- Data Privacy Modal -->
	<div id="privacyModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
		<div class="absolute inset-0 bg-slate-900/50"></div>
		<div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl p-6 max-h-[70vh] flex flex-col">
			<h2 class="text-xl font-semibold mb-4">Data Privacy Consent</h2>
			<div id="privacyContent" class="flex-1 overflow-y-auto space-y-3 text-sm text-slate-700 pr-2">
				<p>The Department of Education shall engage in the collection of health / medical information for the purposes of tracking, provision of necessary health / medical interventions, and educational purposes.</p>
				<p>This information shall be processed in accordance with the provisions of the Data Privacy Act and the Data Privacy Policies of the Department.</p>
				<p>This information shall be stored and held confidentially in accordance with the provisions of the Basic Education Act and may only be shared with other government IT agencies or third parties subject to Data sharing agreements and data privacy requirements for legitimate purposes only.</p>
				<p>For inquiries, requests and concerns regarding your data privacy rights, please contact the data privacy compliance officer, team of the school, schools division office or regional office concerned.</p>
				<p>I hereby authorize the Our Lady of the Sacred Heart College of Guimba, Inc. to use, collect, and process the information for the purposes of the above stated.</p>
			</div>
			<div class="mt-4 pt-4 border-t border-slate-200">
				<label class="flex items-center gap-3 text-sm cursor-pointer">
					<input type="checkbox" id="privacyReadChk" class="h-4 w-4 text-sky-600 border-slate-300 rounded focus:ring-sky-500" disabled>
					<span class="text-slate-700">I have read and understood the complete Data Privacy Consent</span>
				</label>
			</div>
			<div class="mt-4 flex justify-end gap-3">
				<button id="privacyCloseBtn" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Close</button>
			</div>
		</div>
	</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>
// Popup notification system
function showNotification(message, type = 'success', duration = 2000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `transform transition-all duration-300 ease-in-out translate-x-full opacity-0 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 p-4 ${
        type === 'success' ? 'border-emerald-500' : 
        type === 'error' ? 'border-red-500' : 
        type === 'warning' ? 'border-yellow-500' : 
        'border-blue-500'
    }`;
    
    // Create content
    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="text-lg">
                    ${type === 'success' ? '✅' : 
                      type === 'error' ? '❌' : 
                      type === 'warning' ? '⚠️' : 
                      'ℹ️'}
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-slate-800">${message}</p>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button class="close-btn text-slate-400 hover:text-slate-600 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Add event listener to close button
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        closeNotification(closeBtn);
    });
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(closeBtn);
        }, duration);
    }
}

function closeNotification(button) {
    const notification = button.closest('div');
    if (!notification) return;
    
    // Animate out
    notification.classList.remove('translate-x-0', 'opacity-100');
    notification.classList.add('translate-x-full', 'opacity-0');
    
    // Remove after animation
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Show notifications on page load
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            showNotification('<?= addslashes(htmlspecialchars($error)) ?>', 'error', 7000);
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if ($info): ?>
        <?php foreach ($info as $message): ?>
            showNotification('<?= addslashes(htmlspecialchars($message)) ?>', 'success', 2000);
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Level change handler to show/hide dependent fields
    const levelSelect = document.getElementById('levelSelect');
    const yearGradeField = document.getElementById('yearGradeField');
    const yearGradeLabel = document.getElementById('yearGradeLabel');
    const yearGradeInput = document.getElementById('yearGradeInput');
    const courseField = document.getElementById('courseField');
    const blockField = document.getElementById('blockField');
    const sectionField = document.getElementById('sectionField');
    const strandField = document.getElementById('strandField');

    function updateFieldsForLevel(level) {
        // Hide all fields first
        yearGradeField.classList.add('hidden');
        courseField.classList.add('hidden');
        blockField.classList.add('hidden');
        sectionField.classList.add('hidden');
        strandField.classList.add('hidden');

        if (level === 'Pre-school') {
            // Pre-school has no grade level or section fields
        } else if (level === 'Elementary') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade Level';
            yearGradeInput.innerHTML = '<option value="">Select Grade Level</option><option value="Grade 1">Grade 1</option><option value="Grade 2">Grade 2</option><option value="Grade 3">Grade 3</option><option value="Grade 4">Grade 4</option><option value="Grade 5">Grade 5</option><option value="Grade 6">Grade 6</option>';
            sectionField.classList.remove('hidden');
        } else if (level === 'High School') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade Level';
            yearGradeInput.innerHTML = '<option value="">Select Grade Level</option><option value="Grade 7">Grade 7</option><option value="Grade 8">Grade 8</option><option value="Grade 9">Grade 9</option><option value="Grade 10">Grade 10</option>';
            sectionField.classList.remove('hidden');
        } else if (level === 'Senior High School') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Grade Level';
            yearGradeInput.innerHTML = '<option value="">Select Grade Level</option><option value="Grade 11">Grade 11</option><option value="Grade 12">Grade 12</option>';
            strandField.classList.remove('hidden');
        } else if (level === 'College') {
            yearGradeField.classList.remove('hidden');
            yearGradeLabel.textContent = 'Year Level';
            yearGradeInput.innerHTML = '<option value="">Select Year Level</option><option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option><option value="3rd Year">3rd Year</option><option value="4th Year">4th Year</option><option value="5th Year">5th Year</option>';
            courseField.classList.remove('hidden');
            blockField.classList.remove('hidden');
        }

        // Restore existing value if available
        const existingValue = yearGradeInput.getAttribute('data-existing-value');
        if (existingValue) {
            yearGradeInput.value = existingValue;
        }
    }

    if (levelSelect) {
        levelSelect.addEventListener('change', function() {
            updateFieldsForLevel(this.value);
        });

        // Initialize fields if level is already selected
        if (levelSelect.value) {
            updateFieldsForLevel(levelSelect.value);
        }
    }

    
    // Enter key navigation functionality
    function setupEnterNavigation() {
        const form = document.querySelector('form');
        if (!form) return;
        
        // Add event listener to all form elements
        const formElements = form.querySelectorAll('input, select, textarea');
        formElements.forEach(element => {
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Get the next field from data attribute
                    const nextFieldName = this.getAttribute('data-next-field');
                    if (nextFieldName) {
                        const nextField = form.querySelector(`[name="${nextFieldName}"]`);
                        if (nextField) {
                            nextField.focus();
                            // If it's a select, open it
                            if (nextField.tagName === 'SELECT') {
                                nextField.click();
                            }
                        }
                    } else {
                        // If no next field specified, try to find the next input
                        const currentIndex = Array.from(formElements).indexOf(this);
                        const nextElement = formElements[currentIndex + 1];
                        if (nextElement) {
                            nextElement.focus();
                            if (nextElement.tagName === 'SELECT') {
                                nextElement.click();
                            }
                        }
                    }
                }
            });
        });
    }
    
    // Initialize enter navigation
    setupEnterNavigation();
    
    // Emergency contacts functionality - simple and clean
    const addContactBtn = document.getElementById('addContactBtn');
    const contactsContainer = document.getElementById('contactsContainer');
    
    if (addContactBtn && contactsContainer) {
        addContactBtn.addEventListener('click', function() {
            // Check if we already have 2 contacts (1 default + 1 added)
            const existingContacts = contactsContainer.querySelectorAll('input[name="contacts[]"]');
            if (existingContacts.length >= 2) {
                return; // Don't add more than 2 total
            }
            
            // Create the new contact field
            const newContactDiv = document.createElement('div');
            newContactDiv.className = 'flex items-center gap-2';
            
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'contacts[]';
            newInput.className = 'w-full rounded-xl bg-white border border-slate-300 px-4 py-3';
            newInput.placeholder = '09xxxxxxxxx';
            newInput.pattern = '09[0-9]{9}';
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = function() {
                newContactDiv.remove();
                // Re-enable the add button when extra contact is removed
                addContactBtn.disabled = false;
                addContactBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                addContactBtn.classList.add('hover:bg-slate-50');
            };
            
            newContactDiv.appendChild(newInput);
            newContactDiv.appendChild(removeBtn);
            contactsContainer.appendChild(newContactDiv);
            
            // Disable the add button after adding extra contact
            addContactBtn.disabled = true;
            addContactBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addContactBtn.classList.remove('hover:bg-slate-50');
        });
    }
    
    // Auto-trigger level change if prefill level is set
    <?php if ($prefillLevel && !$student): ?>
    const levelSelectForAuto = document.getElementById('levelSelect');
    if (levelSelectForAuto) {
        // Set the value first, then trigger change event
        levelSelectForAuto.value = '<?= htmlspecialchars($prefillLevel) ?>';
        // Trigger change event to populate dependent fields
        levelSelectForAuto.dispatchEvent(new Event('change'));
    }
    <?php endif; ?>
    
    // Auto-capitalization functions
    function toTitleCase(str) {
        return str.replace(/\w\S*/g, function(txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
    
    function toSentenceCase(str) {
        return str.replace(/(^\w{1}|\.\s*\w{1})/gi, function(txt) {
            return txt.toUpperCase();
        });
    }
    
    // Apply auto-capitalization to form fields
    function setupAutoCapitalization() {
        // Name field - Title Case
        const nameField = document.querySelector('input[name="name"]');
        if (nameField) {
            nameField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Guardian field - Title Case
        const guardianField = document.querySelector('input[name="guardian"]');
        if (guardianField) {
            guardianField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Address fields - Sentence Case
        const addressFields = ['barangay', 'municipality', 'province'];
        addressFields.forEach(fieldName => {
            const field = document.querySelector(`input[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        this.value = toSentenceCase(this.value.trim());
                    }
                });
            }
        });
        
        // Religion field - Title Case
        const religionField = document.querySelector('input[name="religion"]');
        if (religionField) {
            religionField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Allergies field - Sentence Case
        const allergiesField = document.querySelector('input[name="allergies"]');
        if (allergiesField) {
            allergiesField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toSentenceCase(this.value.trim());
                }
            });
        }
    }
    
    // Initialize auto-capitalization
    setupAutoCapitalization();
    
});

// Handle ESC key to redirect to dashboard (outside DOMContentLoaded)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Check if any form element is focused
        const activeElement = document.activeElement;
        const isFormElement = activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.tagName === 'SELECT' ||
            activeElement.tagName === 'BUTTON' ||
            activeElement.closest('form')
        );
        
        // Only redirect if no form element is focused
        if (!isFormElement) {
            e.preventDefault();
            e.stopPropagation();
            window.location.replace('../admin/dashboard.php');
        }
    }
});

// Privacy Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const showPrivacyBtn = document.getElementById('showPrivacyBtn');
    const privacyModal = document.getElementById('privacyModal');
    const closePrivacyBtn = document.getElementById('privacyCloseBtn');
    const privacyReadChk = document.getElementById('privacyReadChk');
    const consentedChk = document.getElementById('consentedChk');
    const consentLabel = document.getElementById('consentLabel');
    const consentHelpText = document.getElementById('consentHelpText');
    const continueText = document.getElementById('continueText');
    const formHelpText = document.getElementById('formHelpText');

    if (showPrivacyBtn && privacyModal) {
        showPrivacyBtn.addEventListener('click', function() {
            privacyModal.classList.remove('hidden');
            privacyModal.classList.add('flex');
            
            // Enable the checkbox in the modal after showing
            if (privacyReadChk) {
                privacyReadChk.disabled = false;
                privacyReadChk.checked = false; // Reset to unchecked
            }
        });
    }

    if (closePrivacyBtn && privacyModal) {
        closePrivacyBtn.addEventListener('click', function() {
            privacyModal.classList.add('hidden');
            privacyModal.classList.remove('flex');
        });
    }

    // Also add direct onclick handler as backup
    if (closePrivacyBtn) {
        closePrivacyBtn.onclick = function() {
            console.log('Close button clicked');
            if (privacyModal) {
                privacyModal.classList.add('hidden');
                privacyModal.classList.remove('flex');
                console.log('Modal closed');
            }
        };
    }

    // Sync modal checkbox with main form checkbox
    if (privacyReadChk && consentedChk) {
        privacyReadChk.addEventListener('change', function() {
            if (this.checked) {
                consentedChk.checked = true;
                consentedChk.disabled = false;
                if (consentLabel) {
                    consentLabel.classList.remove('text-slate-400');
                    consentLabel.classList.add('text-slate-700');
                }
                if (consentHelpText) {
                    consentHelpText.textContent = 'You have read the Data Privacy Consent. You may now check the box to proceed.';
                }
                if (continueText) {
                    continueText.textContent = 'You may now check the consent box and continue with registration.';
                }
                if (formHelpText) {
                    formHelpText.textContent = 'All required fields completed. Check the consent box to proceed.';
                }
                // Mark as read in sessionStorage
                sessionStorage.setItem('privacyRead', 'true');
            }
        });
    }

    // Close modal when clicking outside
    if (privacyModal) {
        privacyModal.addEventListener('click', function(e) {
            if (e.target === privacyModal) {
                privacyModal.classList.add('hidden');
                privacyModal.classList.remove('flex');
            }
        });
    }

    // Enable checkbox after reading privacy policy
    if (consentedChk && consentLabel && consentHelpText && continueText && formHelpText) {
        // Check if privacy was already read (stored in sessionStorage)
        if (sessionStorage.getItem('privacyRead') === 'true') {
            consentedChk.disabled = false;
            consentLabel.classList.remove('text-slate-400');
            consentLabel.classList.add('text-slate-700');
            consentHelpText.textContent = 'You have read the Data Privacy Consent. You may now check the box to proceed.';
            continueText.textContent = 'You may now check the consent box and continue with registration.';
            formHelpText.textContent = 'All required fields completed. Check the consent box to proceed.';
        }

        // Enable checkbox when privacy modal is closed
        const originalCloseHandler = closePrivacyBtn?.onclick;
        if (closePrivacyBtn) {
            closePrivacyBtn.onclick = function() {
                privacyModal.classList.add('hidden');
                privacyModal.classList.remove('flex');
                
                // Enable checkbox and update UI
                consentedChk.disabled = false;
                consentLabel.classList.remove('text-slate-400');
                consentLabel.classList.add('text-slate-700');
                consentHelpText.textContent = 'You have read the Data Privacy Consent. You may now check the box to proceed.';
                continueText.textContent = 'You may now check the consent box and continue with registration.';
                formHelpText.textContent = 'All required fields completed. Check the consent box to proceed.';
                
                // Mark as read in sessionStorage
                sessionStorage.setItem('privacyRead', 'true');
            };
        }
    }

    // Age auto-calculation and birth date validation
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('ageInput');
    const dobError = document.getElementById('dobError');

    function calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age >= 0 ? age : 0;
    }

    function validateBirthDate(dateString) {
        const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        const match = dateString.match(dateRegex);
        
        if (!match) {
            return { valid: false, message: 'Please enter date in DD/MM/YYYY format' };
        }
        
        const day = parseInt(match[1], 10);
        const month = parseInt(match[2], 10);
        const year = parseInt(match[3], 10);
        
        // Check if date is valid
        const date = new Date(year, month - 1, day);
        if (date.getDate() !== day || date.getMonth() !== month - 1 || date.getFullYear() !== year) {
            return { valid: false, message: 'Please enter a valid date' };
        }
        
        // Check if date is not in the future
        const today = new Date();
        if (date > today) {
            return { valid: false, message: 'Birth date cannot be in the future' };
        }
        
        // Check if age is reasonable (not more than 120 years old)
        const age = calculateAge(date);
        if (age > 120) {
            return { valid: false, message: 'Please enter a valid birth date' };
        }
        
        return { valid: true, age: age };
    }

    function updateAge() {
        const dobValue = dobInput.value.trim();
        
        if (dobValue) {
            const validation = validateBirthDate(dobValue);
            
            if (validation.valid) {
                ageInput.value = validation.age;
                dobError.classList.add('hidden');
                dobInput.classList.remove('border-red-500');
                dobInput.classList.add('border-slate-300');
            } else {
                ageInput.value = '';
                dobError.textContent = validation.message;
                dobError.classList.remove('hidden');
                dobInput.classList.add('border-red-500');
                dobInput.classList.remove('border-slate-300');
            }
        } else {
            ageInput.value = '';
            dobError.classList.add('hidden');
            dobInput.classList.remove('border-red-500');
            dobInput.classList.add('border-slate-300');
        }
    }

    // Add event listeners
    dobInput.addEventListener('input', updateAge);
    dobInput.addEventListener('blur', updateAge);

    // Auto-format date input (add slashes automatically)
    dobInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        if (value.length >= 5) {
            value = value.substring(0, 5) + '/' + value.substring(5, 9);
        }
        
        e.target.value = value;
    });

    // Calculate age on page load if dob is already filled
    if (dobInput.value) {
        updateAge();
    }

    // Initialize checkbox as disabled
    if (consentedChk) {
        consentedChk.disabled = true;
        consentedChk.checked = false;
    }

    // Form validation function
    function validateForm() {
        const name = document.querySelector('input[name="name"]').value.trim();
        const gender = document.querySelector('select[name="gender"]').value;
        const level = document.querySelector('select[name="level"]').value;
        const rfid = document.querySelector('input[name="rfid"]').value.trim();
        const dob = document.querySelector('input[name="dob"]').value.trim();
        const contacts = document.querySelectorAll('input[name="contacts[]"]');
        const consented = document.querySelector('input[name="consented"]').checked;
        
        // Check required fields
        const hasName = name !== '';
        const hasGender = gender !== '';
        const hasLevel = level !== '';
        const hasRfid = rfid !== '';
        const hasDob = dob !== '' && /^\d{2}\/\d{2}\/\d{4}$/.test(dob);
        
        // Check at least one contact
        let hasContact = false;
        contacts.forEach(contact => {
            if (contact.value.trim() !== '') {
                hasContact = true;
            }
        });
        
        // Check if all required fields are filled and consent is given
        const allRequired = hasName && hasGender && hasLevel && hasRfid && hasDob && hasContact && consented;
        
        const saveBtn = document.getElementById('saveStudentBtn');
        const continueText = document.getElementById('continueText');
        const formHelpText = document.getElementById('formHelpText');
        
        if (allRequired) {
            saveBtn.disabled = false;
            saveBtn.classList.remove('bg-slate-400', 'cursor-not-allowed');
            saveBtn.classList.add('bg-sky-600', 'hover:bg-sky-700', 'cursor-pointer');
            if (continueText) continueText.textContent = 'All requirements completed! You can now submit the form.';
            if (formHelpText) formHelpText.textContent = 'All required fields completed and consent given. You can now submit the form.';
        } else {
            saveBtn.disabled = true;
            saveBtn.classList.add('bg-slate-400', 'cursor-not-allowed');
            saveBtn.classList.remove('bg-sky-600', 'hover:bg-sky-700', 'cursor-pointer');
            
            if (!consented) {
                if (continueText) continueText.textContent = 'Complete all required fields and agree to Data Privacy Consent';
                if (formHelpText) formHelpText.textContent = 'Fill in all required fields and read the complete Data Privacy Consent';
            } else {
                if (continueText) continueText.textContent = 'Complete all required fields';
                if (formHelpText) formHelpText.textContent = 'Fill in all required fields (Name, Gender, Level, RFID, Date of Birth, and at least one contact)';
            }
        }
    }

    // Add event listeners to all form fields for real-time validation
    const formFields = document.querySelectorAll('input, select, textarea');
    formFields.forEach(field => {
        field.addEventListener('input', validateForm);
        field.addEventListener('change', validateForm);
    });

    // Initial validation
    validateForm();
});

// Smart back navigation function
function goBack() {
    const referrer = document.referrer;
    const currentUrl = window.location.href;
    
    // If there's a referrer and it's not the same page
    if (referrer && referrer !== currentUrl) {
        // Check if coming from specific pages and navigate accordingly
        if (referrer.includes('school_listing.php')) {
            window.location.href = '../patients/school_listing.php';
        } else if (referrer.includes('rfid_portal.php')) {
            window.location.href = '../rfid/rfid_portal.php';
        } else if (referrer.includes('dashboard.php')) {
            window.location.href = '../admin/dashboard.php';
        } else {
            // Default to browser back
            window.history.back();
        }
    } else {
        // Default fallback - go to dashboard
        window.location.href = '../admin/dashboard.php';
    }
}

// Re-enrollment detection system
let reenrollmentCheckTimeout;
let currentReenrollmentStudent = null;

function checkForReenrollment() {
    const rfidInput = document.querySelector('input[name="rfid"]');
    const nameInput = document.querySelector('input[name="name"]');
    const dobInput = document.querySelector('input[name="dob"]');
    
    if (!rfidInput || !nameInput) return;
    
    const rfid = rfidInput.value.trim();
    const name = nameInput.value.trim();
    const dob = dobInput.value.trim();
    
    // Clear previous timeout
    if (reenrollmentCheckTimeout) {
        clearTimeout(reenrollmentCheckTimeout);
    }
    
    // Only check if we have meaningful input
    if (rfid.length < 3 && name.length < 3) {
        hideReenrollmentNotice();
        return;
    }
    
    // Debounce the check
    reenrollmentCheckTimeout = setTimeout(() => {
        fetch('../api/check_reenrollment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                rfid: rfid,
                name: name,
                dob: dob
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.found && data.student) {
                currentReenrollmentStudent = data.student;
                showReenrollmentNotice(data.student);
            } else {
                currentReenrollmentStudent = null;
                hideReenrollmentNotice();
            }
        })
        .catch(error => {
            console.log('Re-enrollment check error:', error);
            hideReenrollmentNotice();
        });
    }, 500);
}

function showReenrollmentNotice(student) {
    // Remove existing notice
    hideReenrollmentNotice();
    
    // Create notice element
    const notice = document.createElement('div');
    notice.id = 'reenrollment-notice';
    notice.className = 'mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl';
    notice.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-blue-800 mb-1">Re-enrollment Detected</h3>
                <p class="text-sm text-blue-700">
                    Found a graduated student with RFID <strong>${student.rfid}</strong> 
                    (Previously: ${student.name} - ${student.level}).
                    <br>
                    <strong>This will re-enroll the existing student instead of creating a new record.</strong>
                </p>
            </div>
        </div>
    `;
    
    // Insert after the header
    const header = document.querySelector('h1');
    if (header) {
        header.parentNode.insertBefore(notice, header.nextSibling);
    }
}

function hideReenrollmentNotice() {
    const existingNotice = document.getElementById('reenrollment-notice');
    if (existingNotice) {
        existingNotice.remove();
    }
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const rfidInput = document.querySelector('input[name="rfid"]');
    const nameInput = document.querySelector('input[name="name"]');
    const dobInput = document.querySelector('input[name="dob"]');
    
    if (rfidInput) {
        rfidInput.addEventListener('input', checkForReenrollment);
    }
    if (nameInput) {
        nameInput.addEventListener('input', checkForReenrollment);
    }
    if (dobInput) {
        dobInput.addEventListener('input', checkForReenrollment);
    }
});
</script>

<script src="../core/validation.js"></script>
