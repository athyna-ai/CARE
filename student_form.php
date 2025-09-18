<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Ensure students table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_hash VARCHAR(255) NOT NULL,
  name_masked VARCHAR(120) NOT NULL,
  level ENUM("Pre-school","Elementary","High School","Senior High School","College") NOT NULL,
  course VARCHAR(120) NULL,
  section VARCHAR(50) NULL,
  strand VARCHAR(50) NULL,
  year_grade VARCHAR(40) NULL,
  rfid_hash VARCHAR(255) NOT NULL UNIQUE,
  address_hash VARCHAR(255) NULL,
  address_masked VARCHAR(255) NULL,
  age INT NULL,
  dob DATE NULL,
  religion VARCHAR(80) NULL,
  guardian_hash VARCHAR(255) NULL,
  guardian_masked VARCHAR(120) NULL,
  allergies TEXT NULL,
  contacts JSON NULL,
  emergency_contact_hash VARCHAR(255) NULL,
  emergency_contact_masked VARCHAR(120) NULL,
  medical_notes TEXT NULL,
  status ENUM("Active","Inactive","Graduated","Transferred") DEFAULT "Active",
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_level (level),
  INDEX idx_status (status),
  INDEX idx_rfid_hash (rfid_hash),
  INDEX idx_name_hash (name_hash)
) ENGINE=InnoDB');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$prefillRfid = sanitize_string($_GET['rfid'] ?? '');
$errors = [];
$info = [];
$student = null;

if ($id) {
	$st = $pdo->prepare('SELECT * FROM students WHERE id = ?');
	$st->execute([$id]);
	$student = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		$name = sanitize_string($_POST['name'] ?? '');
		$gender = sanitize_string($_POST['gender'] ?? '');
		$level = sanitize_string($_POST['level'] ?? '');
		$course = sanitize_string($_POST['course'] ?? '');
		$section = sanitize_string($_POST['section'] ?? '');
		$strand = sanitize_string($_POST['strand'] ?? '');
		$year_grade = sanitize_string($_POST['year_grade'] ?? '');
		$rfid = sanitize_string($_POST['rfid'] ?? '');
		$address = sanitize_string($_POST['address'] ?? '');
		$age = (int)($_POST['age'] ?? 0);
		$dob = sanitize_string($_POST['dob'] ?? '');
		$religion = sanitize_string($_POST['religion'] ?? '');
		$guardian = sanitize_string($_POST['guardian'] ?? '');
		$allergies = sanitize_string($_POST['allergies'] ?? '');
		// Set to N/A if empty
		if (empty($allergies)) {
			$allergies = 'N/A';
		}
		$emergency_contact = sanitize_string($_POST['emergency_contact'] ?? '');
		$contacts = $_POST['contacts'] ?? [];
		$consented = isset($_POST['consented']);

		if ($name === '' || $gender === '' || $level === '' || $rfid === '') { $errors[] = 'Name, Gender, Level and RFID are required.'; }
		if ($dob !== '' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dob)) { $errors[] = 'DOB must be DD/MM/YYYY.'; }
		if (!$consented) { $errors[] = 'You must agree to the data privacy consent.'; }

		if (!$errors) {
			// Store original data in main table (no hashing)
			$contactsJson = json_encode(array_values(array_filter(array_map('trim', (array)$contacts))));
			
			if ($id) {
				$upd = $pdo->prepare('UPDATE students SET name=?, gender=?, level=?, course=?, section=?, strand=?, year_grade=?, rfid=?, address=?, age=?, dob=STR_TO_DATE(?,"%d/%m/%Y"), religion=?, guardian=?, allergies=?, contacts=?, emergency_contact=? WHERE id=?');
				$upd->execute([$name,$gender,$level,$course,$section,$strand,$year_grade,$rfid,$address,$age,$dob,$religion,$guardian,$allergies,$contactsJson,$emergency_contact,$id]);
				$info[] = 'Student updated successfully.';
				
				// Log activity
				log_activity($pdo, (int)$_SESSION['user']['id'], 'student_update', "Updated student: {$name} ({$level})", 'student_form');
			} else {
				$ins = $pdo->prepare('INSERT INTO students (name, gender, level, course, section, strand, year_grade, rfid, address, age, dob, religion, guardian, allergies, contacts, emergency_contact) VALUES (?,?,?,?,?,?,?,?,?,?,STR_TO_DATE(?,"%d/%m/%Y"),?,?,?,?,?)');
				$ins->execute([$name,$gender,$level,$course,$section,$strand,$year_grade,$rfid,$address,$age,$dob,$religion,$guardian,$allergies,$contactsJson,$emergency_contact]);
				$info[] = 'Student registered successfully.';
				
				// Log activity
				log_activity($pdo, (int)$_SESSION['user']['id'], 'student_register', "Registered new student: {$name} ({$level})", 'student_form');
			}
			// Redirect to dashboard after successful save
			header('Location: dashboard.php?success=1');
			exit;
		}
	}
}

?>
<?php $pageTitle = $id ? 'Edit Student' : 'Register Student'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>
	<div class="min-h-[calc(100vh-5rem)] flex items-start md:items-center justify-center p-4 md:p-8">
		<div class="w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
			<h1 class="text-2xl font-semibold mb-4"><?= $id ? 'Edit Student' : 'Register Student' ?></h1>
			<!-- Popup notifications container -->
			<div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
			<form method="post" class="grid md:grid-cols-2 gap-4" autocomplete="on">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
				<div>
					<label class="block text-slate-700 mb-1">Name</label>
					<input type="text" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Gender</label>
					<select name="gender" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required>
						<option value="">Select Gender</option>
						<option value="Male" <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
						<option value="Female" <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
					</select>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Level</label>
					<select name="level" id="levelSelect" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required>
						<option value="">Select Level</option>
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
					<label class="block text-slate-700 mb-1">RFID</label>
					<input type="text" name="rfid" value="<?= htmlspecialchars($prefillRfid) ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required />
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Address</label>
					<input type="text" name="address" value="<?= htmlspecialchars($student['address'] ?? '') ?>" placeholder="Barangay/Municipality/City, Province" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Date of Birth (DD/MM/YYYY)</label>
					<input type="text" name="dob" id="dob" value="<?= isset($student['dob']) && $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : '' ?>" pattern="\d{2}/\d{2}/\d{4}" placeholder="DD/MM/YYYY" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" maxlength="10" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Age</label>
					<input type="number" name="age" id="ageInput" value="<?= htmlspecialchars((string)($student['age'] ?? '')) ?>" readonly class="w-full rounded-xl bg-slate-100 border border-slate-300 px-4 py-3 text-slate-600" />
					<p class="text-xs text-slate-500 mt-1">Age is automatically calculated from date of birth</p>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Religion</label>
					<select name="religion" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800">
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
					<label class="block text-slate-700 mb-1">Parent/Guardian</label>
					<input type="text" name="guardian" value="<?= htmlspecialchars($student['guardian'] ?? '') ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Emergency Contact</label>
					<input type="text" name="emergency_contact" value="<?= htmlspecialchars($student['emergency_contact'] ?? '') ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" placeholder="09xxxxxxxxx" />
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Contact Numbers</label>
					<div id="contactsContainer" class="space-y-2">
						<input type="text" name="contacts[]" class="w-full rounded-xl bg-white border border-slate-300 px-4 py-3" placeholder="09xxxxxxxxx" />
					</div>
					<button type="button" id="addContactBtn" class="mt-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700">Add another</button>
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Allergies</label>
					<textarea name="allergies" rows="3" placeholder="List any known allergies (e.g., peanuts, shellfish, medications). Leave blank if none." class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800"><?= htmlspecialchars($student['allergies'] ?? '') ?></textarea>
					<p class="mt-1 text-xs text-slate-500">Enter "None" or leave blank if the student has no known allergies.</p>
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
					<a href="rfid_portal.php" class="mt-2 inline-block w-full text-center border border-slate-300 rounded-xl py-3 hover:bg-slate-50">Cancel</a>
				</div>
			</form>
		</div>
	</div>

	<!-- Data Privacy Modal -->
	<div id="privacyModal" class="fixed inset-0 z-50 hidden items-center justify-center">
		<div class="absolute inset-0 bg-slate-900/50"></div>
		<div class="relative w-full max-w-2xl mx-4 bg-white rounded-2xl shadow-xl p-6 max-h-[80vh] flex flex-col">
			<h2 class="text-xl font-semibold mb-4">Data Privacy Consent</h2>
			<div id="privacyContent" class="flex-1 overflow-y-auto space-y-3 text-sm text-slate-700 pr-2">
				<p>The Department of Education shall engage in the collection of health / medical information for the purposes of tracking, provision of necessary health / medical interventions, and educational purposes.</p>
				<p>This information shall be processed in accordance with the provisions of the Data Privacy Act and the Data Privacy Policies of the Department.</p>
				<p>This information shall be stored and held confidentially in accordance with the provisions of the Basic Education Act and may only be shared with other government IT agencies or third parties subject to Data sharing agreements and data privacy requirements for legitimate purposes only.</p>
				<p>For inquiries, requests and concerns regarding your data privacy rights, please contact the data privacy compliance officer, team of the school, schools division office or regional office concerned.</p>
				<p>I hereby authorize the Our Lady of the Sacred Heart College of Guimba, Inc. to use, collect, and process the information for the purposes of the above stated.</p>
			</div>
			<div class="mt-4 pt-4 border-t border-slate-200">
				<label class="flex items-center gap-2 text-sm">
					<input type="checkbox" id="privacyReadChk" class="h-4 w-4" disabled>
					<span>I have read and understood the complete Data Privacy Consent</span>
				</label>
			</div>
			<div class="mt-4 flex justify-end gap-3">
				<button id="privacyCloseBtn" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700">Close</button>
			</div>
		</div>
	</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// Popup notification system
function showNotification(message, type = 'success', duration = 5000) {
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
            showNotification('<?= addslashes(htmlspecialchars($message)) ?>', 'success', 5000);
        <?php endforeach; ?>
    <?php endif; ?>
});
</script>
