<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// REQUIRE ADMIN AUTHENTICATION for faculty registration
require_admin_auth();
$pdo = get_pdo();

// Ensure faculty table exists
$pdo->exec('CREATE TABLE IF NOT EXISTS faculty (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  department VARCHAR(100) NULL,
  gender ENUM("Male","Female","Other") NULL,
  rfid VARCHAR(50) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  age INT NULL,
  sr TINYINT(1) NOT NULL DEFAULT 0,
  dob DATE NULL,
  religion VARCHAR(80) NULL,
  emergency_contact VARCHAR(120) NULL,
  allergies TEXT NULL,
  medical_notes TEXT NULL,
  employee_id VARCHAR(50) NULL,
  position VARCHAR(100) NULL,
  status ENUM("Active","Inactive","Retired","Resigned") DEFAULT "Active",
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_department (department),
  INDEX idx_status (status),
  INDEX idx_rfid (rfid),
  INDEX idx_name (name)
) ENGINE=InnoDB');

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$prefillRfid = sanitize_string($_GET['rfid'] ?? '');
$errors = [];
$info = [];
$row = null;

if ($id) {
	$st = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
	$st->execute([$id]);
	$row = $st->fetch();
	
	// Parse existing address into separate fields
	if ($row && !empty($row['address'])) {
		$addressParts = explode(',', $row['address']);
		$row['barangay'] = trim($addressParts[0] ?? '');
		$row['municipality'] = trim($addressParts[1] ?? '');
		$row['province'] = trim($addressParts[2] ?? '');
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		$name = sanitize_string($_POST['name'] ?? '');
		$department = sanitize_string($_POST['department'] ?? '');
		$gender = sanitize_string($_POST['gender'] ?? '');
		$rfid = sanitize_string($_POST['rfid'] ?? '');
	$barangay = sanitize_string($_POST['barangay'] ?? '');
	$municipality = sanitize_string($_POST['municipality'] ?? '');
	$province = sanitize_string($_POST['province'] ?? '');
	// Combine address fields
	$address = trim($barangay . ', ' . $municipality . ', ' . $province, ', ');
		$age = (int)($_POST['age'] ?? 0);
		$dob = sanitize_string($_POST['dob'] ?? '');
		$religion = sanitize_string($_POST['religion'] ?? '');
		$emergency = sanitize_string($_POST['emergency_contact'] ?? '');
		$allergies = sanitize_string($_POST['allergies'] ?? '');
		// Set to N/A if empty
		if (empty($allergies)) {
			$allergies = 'N/A';
		}
		$consented = isset($_POST['consented']);

		if ($name === '' || $gender === '' || $rfid === '') { $errors[] = 'Name, Gender and RFID are required.'; }
		if ($dob !== '' && !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dob)) { $errors[] = 'DOB must be DD/MM/YYYY.'; }
		if (!$consented) { $errors[] = 'You must agree to the data privacy consent.'; }
		if (empty(trim($emergency))) { $errors[] = 'Emergency contact number is required.'; }

		if (!$errors) {
			// Store original data in main table (no hashing)
			$sr = ($age >= 60) ? 1 : 0;
			
			if ($id) {
				$upd = $pdo->prepare('UPDATE faculty SET name=?, department=?, gender=?, rfid=?, address=?, age=?, sr=?, dob=STR_TO_DATE(?,"%d/%m/%Y"), religion=?, emergency_contact=?, allergies=? WHERE id=?');
				$upd->execute([$name,$department,$gender,$rfid,$address,$age,$sr,$dob,$religion,$emergency,$allergies,$id]);
				$info[] = 'Faculty updated successfully.';
				
				// Log activity (public registration - no user session)
				try {
					log_activity($pdo, 0, 'faculty_update', "Updated faculty: {$name} ({$department})", 'faculty_form');
				} catch (Exception $e) {
					error_log("Activity logging failed: " . $e->getMessage());
				}
			} else {
				$ins = $pdo->prepare('INSERT INTO faculty (name, department, gender, rfid, address, age, sr, dob, religion, emergency_contact, allergies) VALUES (?,?,?,?,?,?,?,STR_TO_DATE(?,"%d/%m/%Y"),?,?,?)');
				$ins->execute([$name,$department,$gender,$rfid,$address,$age,$sr,$dob,$religion,$emergency,$allergies]);
				$info[] = 'Faculty registered successfully.';
				
				// Log activity (public registration - no user session)
				try {
					log_activity($pdo, 0, 'faculty_register', "Registered new faculty: {$name} ({$department})", 'faculty_form');
				} catch (Exception $e) {
					error_log("Activity logging failed: " . $e->getMessage());
				}
			}
			
			// Smart redirect based on where user came from
			try {
				$referrer = $_SERVER['HTTP_REFERER'] ?? '';
				if (strpos($referrer, 'dashboard.php') !== false) {
					// Came from dashboard - redirect back to dashboard
					header('Location: ../admin/dashboard.php?success=1&type=faculty');
				} elseif (strpos($referrer, 'rfid_portal.php') !== false) {
					// Came from RFID portal - redirect back to RFID portal
					header('Location: ../rfid/rfid_portal.php?success=1&type=faculty');
				} elseif (strpos($referrer, 'faculty_listing.php') !== false) {
					// Came from faculty listing - redirect back to faculty listing
					header('Location: ../patients/faculty_listing.php?success=1&type=faculty');
				} else {
					// Default fallback - redirect to dashboard
					header('Location: ../admin/dashboard.php?success=1&type=faculty');
				}
				exit;
			} catch (Exception $e) {
				// Fallback redirect if anything fails
				error_log("Redirect failed: " . $e->getMessage());
				header('Location: ../admin/dashboard.php?success=1&type=faculty');
				exit;
			}
		}
	}
}

?>
<?php $pageTitle = $id ? 'Edit Faculty' : 'Register Faculty'; $showTopNav = true; $showSidebar = false; include __DIR__ . '/../partials/header.php'; ?>

<style>
/* Custom date picker styling to match clinic theme */
input[type="date"] {
    color-scheme: light;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    background: transparent;
    cursor: pointer;
    width: 20px;
    height: 20px;
    margin-right: 8px;
    opacity: 1;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%233971b8'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 20px 20px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
    background-color: rgba(57, 113, 184, 0.1);
    transform: scale(1.05);
}

input[type="date"]::-webkit-datetime-edit {
    color: #334155;
    font-weight: 500;
}

input[type="date"]::-webkit-datetime-edit-fields-wrapper {
    background: transparent;
}

input[type="date"]::-webkit-datetime-edit-text {
    color: #64748b;
    padding: 0 2px;
}

input[type="date"]::-webkit-datetime-edit-month-field,
input[type="date"]::-webkit-datetime-edit-day-field,
input[type="date"]::-webkit-datetime-edit-year-field {
    color: #334155;
    background: transparent;
}

/* Firefox date picker styling */
input[type="date"]::-moz-placeholder {
    color: #94a3b8;
}

/* Custom calendar popup styling */
input[type="date"]:focus {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>
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
			<div class="flex items-center justify-between mb-4">
				<h1 class="text-2xl font-semibold"><?= $id ? 'Edit Faculty' : 'Register Faculty' ?></h1>
				<button type="button" id="clearAllFieldsBtnTop" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center gap-2">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
					</svg>
					Clear All
				</button>
			</div>
			<!-- Popup notifications container -->
			<div id="notificationContainer" class="fixed top-20 right-4 z-50 space-y-2"></div>
			<form method="post" class="grid md:grid-cols-2 gap-4 flex-1 overflow-y-auto" autocomplete="on">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
				<div>
					<label class="block text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
					<input type="text" name="name" value="<?= htmlspecialchars($row['name'] ?? '') ?>" 
						   placeholder="e.g., Dr. Maria Santos Dela Cruz" 
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   required 
						   autofocus
						   data-next-field="department" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Department</label>
					<input type="text" name="department" value="<?= htmlspecialchars($row['department'] ?? '') ?>" 
						   placeholder="e.g., Mathematics, English, Science" 
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   data-next-field="gender" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Gender <span class="text-red-500">*</span></label>
					<select name="gender" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" required data-next-field="rfid">
						<option value="">Select Gender</option>
						<option value="Male" <?= ($row['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
						<option value="Female" <?= ($row['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
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
							<input type="text" name="barangay" value="<?= htmlspecialchars($row['barangay'] ?? '') ?>" 
								   placeholder="e.g., Cawayang Bugtong" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="municipality" />
						</div>
						<div>
							<label class="block text-slate-600 text-sm mb-1">Municipality/City</label>
							<input type="text" name="municipality" value="<?= htmlspecialchars($row['municipality'] ?? '') ?>" 
								   placeholder="e.g., San Juan" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="province" />
						</div>
						<div>
							<label class="block text-slate-600 text-sm mb-1">Province</label>
							<input type="text" name="province" value="<?= htmlspecialchars($row['province'] ?? '') ?>" 
								   placeholder="e.g., Nueva Ecija" 
								   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
								   data-next-field="dob" />
						</div>
					</div>
					<p class="text-xs text-slate-500 mt-2">Enter each part of the address separately</p>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
					<div class="relative">
						<input type="text" name="dob" id="dob" 
							   value="<?= isset($row['dob']) && $row['dob'] && $row['dob'] !== '0000-00-00' ? date('d/m/Y', strtotime($row['dob'])) : '' ?>" 
							   placeholder="DD/MM/YYYY"
							   class="w-full rounded-xl bg-white border border-slate-300 focus:border-clinic-blue focus:ring-2 focus:ring-clinic-blue/20 px-4 py-3 text-slate-800 cursor-pointer hover:border-clinic-tea/40 transition-colors duration-200" 
							   data-next-field="religion" 
							   readonly
							   required />
						<div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="toggleCustomCalendar('dob')">
							<svg class="w-5 h-5 text-clinic-blue hover:text-clinic-tea transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
							</svg>
						</div>
					</div>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Age</label>
					<div class="flex items-center gap-2">
						<input type="number" name="age" id="ageInput" value="<?= htmlspecialchars((string)($row['age'] ?? '')) ?>" readonly class="w-full rounded-xl bg-slate-100 border border-slate-300 px-4 py-3 text-slate-600" />
						<span id="srTag" class="hidden px-2 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded">Sr.</span>
					</div>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Religion</label>
					<select name="religion" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" data-next-field="emergency_contact">
						<option value="">Select Religion</option>
						<?php $rel=$row['religion'] ?? ''; $religions=['Roman Catholic','Islam','Iglesia ni Cristo','Born Again Christian','United Methodist','Aglipayan (IFI)','Seventh-day Adventist','Baptist','Hindu','Buddhist','None']; foreach($religions as $r){$sel=$rel===$r?'selected':''; echo "<option value=\"{$r}\" {$sel}>{$r}</option>";} ?>
					</select>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Emergency Contact</label>
					<input type="text" name="emergency_contact" value="<?= htmlspecialchars($row['emergency_contact'] ?? '') ?>" 
						   placeholder="e.g., 09xxxxxxxxx" 
						   pattern="09[0-9]{9}" 
						   class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
						   data-next-field="allergies" />
				</div>
				<div class="md:col-span-2">
					<label class="block text-slate-700 mb-1">Allergies & Medical Notes</label>
					<textarea name="allergies" 
							  placeholder="List any known allergies (e.g., peanuts, shellfish, medications). Leave blank if none." 
							  class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" 
							  rows="3"
							  data-next-field="consent"><?= htmlspecialchars($row['allergies'] ?? '') ?></textarea>
					<p class="mt-1 text-xs text-slate-500">Enter "None" or leave blank if the faculty member has no known allergies or medical conditions.</p>
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
					<div class="flex gap-3">
						<button id="saveStudentBtn" class="flex-1 bg-slate-400 text-white font-semibold py-3 rounded-xl transition cursor-not-allowed" disabled>
							<span id="continueText">Complete all required fields and read the Data Privacy Consent</span>
						</button>
					</div>
					<div id="formHelp" class="mt-2 text-xs text-slate-500 text-center">
						<span id="formHelpText">Fill in all required fields (Name, RFID) and read the complete Data Privacy Consent</span>
					</div>
					<a href="../admin/dashboard.php" class="mt-2 inline-block w-full text-center border border-slate-300 rounded-xl py-3 hover:bg-slate-50">Cancel</a>
				</div>
			</form>
		</div>
	</div>

<!-- Custom Calendar Modal -->
<div id="customCalendarModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
	<div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 w-80 max-w-[90vw] mx-4">
		<!-- Calendar Header -->
		<div class="flex items-center justify-between p-3 border-b border-clinic-tea/20">
			<button id="prevMonth" class="p-1.5 rounded-lg bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 flex-shrink-0">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
				</svg>
			</button>
			<div class="flex items-center gap-1 flex-1 justify-center overflow-hidden">
				<select id="monthSelect" class="bg-transparent text-clinic-dark font-semibold text-sm focus:outline-none cursor-pointer" size="1">
					<option value="0">Jan</option>
					<option value="1">Feb</option>
					<option value="2">Mar</option>
					<option value="3">Apr</option>
					<option value="4">May</option>
					<option value="5">Jun</option>
					<option value="6">Jul</option>
					<option value="7">Aug</option>
					<option value="8">Sep</option>
					<option value="9">Oct</option>
					<option value="10">Nov</option>
					<option value="11">Dec</option>
				</select>
				<select id="yearSelect" class="bg-transparent text-clinic-dark font-semibold text-sm focus:outline-none cursor-pointer" size="1">
					<!-- Years will be populated by JavaScript -->
				</select>
			</div>
			<button id="nextMonth" class="p-1.5 rounded-lg bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 flex-shrink-0">
				<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
				</svg>
			</button>
		</div>
		
		<!-- Calendar Body -->
		<div class="p-3">
			<!-- Days of week -->
			<div class="grid grid-cols-7 gap-0.5 mb-1">
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">S</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">M</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">T</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">W</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">T</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">F</div>
				<div class="text-center text-xs font-semibold text-clinic-dark/60 py-1">S</div>
			</div>
			
			<!-- Calendar grid with fixed height -->
			<div id="calendarGrid" class="grid grid-cols-7 gap-0.5 h-48">
				<!-- Calendar days will be populated by JavaScript -->
			</div>
		</div>
		
		<!-- Calendar Footer -->
		<div class="flex items-center justify-between p-3 border-t border-clinic-tea/20">
			<button id="clearDate" class="px-3 py-1.5 text-clinic-red hover:bg-clinic-red/10 rounded-lg transition-colors duration-200 font-medium text-sm">
				Clear
			</button>
			<button id="todayDate" class="px-3 py-1.5 bg-clinic-blue text-white hover:bg-clinic-blue/80 rounded-lg transition-colors duration-200 font-medium text-sm">
				Today
			</button>
		</div>
		</div>
	</div>

<!-- Back Button Script -->
<script>
// Simple back navigation function - defined early for onclick handlers
function goBack() {
    console.log('goBack called - history length:', window.history.length);
    // Always use browser back - this goes to the actual previous page
    window.history.back();
}

// Add ESC key support for back navigation
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            console.log('ESC pressed - going back');
            goBack();
        }
    });
});
</script>

<!-- Calendar Dropdown Styling -->
<style>
	/* Calendar modal container */
	#customCalendarModal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 9999;
	}
	
	/* Limit dropdown select width */
	#monthSelect, #yearSelect {
		max-width: 75px;
		min-width: 60px;
		text-align: center;
		padding: 2px 4px;
		border: 1px solid rgba(200, 214, 155, 0.3);
		border-radius: 4px;
	}
	
	#monthSelect {
		max-width: 65px;
	}
	
	#yearSelect {
		max-width: 70px;
	}
	
	/* Style the dropdown options to prevent overflow */
	#yearSelect option, #monthSelect option {
		padding: 4px;
		font-size: 13px;
	}
	
	/* Fixed calendar grid height */
	#calendarGrid {
		min-height: 192px;
		max-height: 192px;
		height: 192px;
	}
	
	/* Ensure calendar modal stays within viewport */
	#customCalendarModal > div {
		max-height: calc(100vh - 2rem);
		max-width: calc(100vw - 2rem);
	}
</style>

	<!-- Data Privacy Modal -->
	<div id="privacyModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
		<div class="absolute inset-0 bg-slate-900/50"></div>
		<div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl p-6 max-h-[80vh] flex flex-col">
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

<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>
// Clear All Fields functionality (Top Button) - Added to main DOMContentLoaded


// Age calculation is handled by validation.js

// Popup notification system
function showNotification(message, type = 'success', duration = 2000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Clear any existing notifications to prevent duplicates
    container.innerHTML = '';
    
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
        
        // Department field - Title Case
        const departmentField = document.querySelector('input[name="department"]');
        if (departmentField) {
            departmentField.addEventListener('blur', function() {
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
        
        // Emergency contact field - Title Case
        const emergencyContactField = document.querySelector('input[name="emergency_contact"]');
        if (emergencyContactField) {
            emergencyContactField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.value = toTitleCase(this.value.trim());
                }
            });
        }
        
        // Allergies field - Sentence Case
        const allergiesField = document.querySelector('textarea[name="allergies"]');
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
    
    // Clear All Fields functionality (Top Button)
    const clearAllBtnTop = document.getElementById('clearAllFieldsBtnTop');
    if (clearAllBtnTop && !clearAllBtnTop.hasAttribute('data-listener-attached')) {
        clearAllBtnTop.setAttribute('data-listener-attached', 'true');
        clearAllBtnTop.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all fields? This action cannot be undone.')) {
                // Clear all input fields
                const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="date"], input[type="time"], textarea');
                inputs.forEach(input => {
                    input.value = '';
                });
                
                // Clear all select fields
                const selects = document.querySelectorAll('select');
                selects.forEach(select => {
                    select.selectedIndex = 0;
                });
                
                // Clear checkboxes
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Reset consent checkbox
                const consentCheckbox = document.getElementById('consentedChk');
                if (consentCheckbox) {
                    consentCheckbox.checked = false;
                    consentCheckbox.disabled = true;
                }
                
                // Reset submit button
                const submitBtn = document.getElementById('saveStudentBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.className = submitBtn.className.replace('bg-clinic-blue', 'bg-slate-400');
                }
                
                // Reset form help text
                const formHelpText = document.getElementById('formHelpText');
                if (formHelpText) {
                    formHelpText.textContent = 'Fill in all required fields (Name, RFID) and read the complete Data Privacy Consent';
                }
                
                // Reset continue text
                const continueText = document.getElementById('continueText');
                if (continueText) {
                    continueText.textContent = 'Complete all required fields and read the Data Privacy Consent';
                }
            }
        });
    }
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
    const consentedChk = document.getElementById('consentedChk');
    const consentLabel = document.getElementById('consentLabel');
    const consentHelpText = document.getElementById('consentHelpText');
    const continueText = document.getElementById('continueText');
    const formHelpText = document.getElementById('formHelpText');

    if (showPrivacyBtn && privacyModal) {
        showPrivacyBtn.addEventListener('click', function() {
            privacyModal.classList.remove('hidden');
            privacyModal.classList.add('flex');
        });
    }

    if (closePrivacyBtn && privacyModal) {
        closePrivacyBtn.addEventListener('click', function() {
            privacyModal.classList.add('hidden');
            privacyModal.classList.remove('flex');
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
});

// Custom Calendar Functionality
let currentCalendarDate = new Date();
let selectedDate = null;
let currentInputField = null;

function toggleCustomCalendar(inputId) {
    currentInputField = inputId;
    const modal = document.getElementById('customCalendarModal');
    const input = document.getElementById(inputId);
    
    if (!modal) {
        console.error('Calendar modal not found!');
        return;
    }
    
    // Parse current date from input if it exists
    if (input.value) {
        const dateParts = input.value.split('/');
        if (dateParts.length === 3) {
            currentCalendarDate = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]);
            selectedDate = new Date(currentCalendarDate);
        }
    }
    
    updateCalendar();
    modal.classList.remove('hidden');
    console.log('Calendar modal opened for:', inputId);
}

function updateCalendar() {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarGrid = document.getElementById('calendarGrid');
    
    if (!monthSelect || !yearSelect || !calendarGrid) {
        console.error('Calendar elements not found!');
        return;
    }
    
    // Update month and year selects
    monthSelect.value = currentCalendarDate.getMonth();
    
    // Populate years (1900 to current year + 10)
    if (yearSelect.children.length === 0) {
        const currentYear = new Date().getFullYear();
        for (let year = currentYear + 10; year >= 1900; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
    }
    yearSelect.value = currentCalendarDate.getFullYear();
    
    // Clear calendar grid
    calendarGrid.innerHTML = '';
    
    // Get first day of month and number of days
    const firstDay = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth(), 1);
    const lastDay = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'h-8 flex items-center justify-center text-clinic-dark/30';
        calendarGrid.appendChild(emptyCell);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'h-8 flex items-center justify-center text-clinic-dark hover:bg-clinic-blue/10 rounded cursor-pointer transition-colors duration-200 text-sm';
        dayCell.textContent = day;
        
        // Check if this is the selected date
        if (selectedDate && 
            selectedDate.getDate() === day && 
            selectedDate.getMonth() === currentCalendarDate.getMonth() && 
            selectedDate.getFullYear() === currentCalendarDate.getFullYear()) {
            dayCell.className += ' bg-clinic-blue text-white hover:bg-clinic-blue/80';
        }
        
        // Check if this is today
        const today = new Date();
        if (day === today.getDate() && 
            currentCalendarDate.getMonth() === today.getMonth() && 
            currentCalendarDate.getFullYear() === today.getFullYear()) {
            dayCell.className += ' border-2 border-clinic-tea';
        }
        
        dayCell.addEventListener('click', () => selectDate(day));
        calendarGrid.appendChild(dayCell);
    }
}

function selectDate(day) {
    selectedDate = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth(), day);
    const input = document.getElementById(currentInputField);
    // Format as DD/MM/YYYY to match PHP backend expectation
    const formattedDate = `${String(day).padStart(2, '0')}/${String(currentCalendarDate.getMonth() + 1).padStart(2, '0')}/${currentCalendarDate.getFullYear()}`;
    input.value = formattedDate;
    
    // Close calendar
    document.getElementById('customCalendarModal').classList.add('hidden');
    
    // Trigger age calculation
    calculateAgeFromDate(selectedDate);
}

function calculateAgeFromDate(birthDate) {
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    const ageInput = document.getElementById('ageInput');
    if (ageInput) {
        ageInput.value = age >= 0 ? age : 0;
    }
}

// Calendar event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('customCalendarModal');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const clearDate = document.getElementById('clearDate');
    const todayDate = document.getElementById('todayDate');
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    // Navigation buttons
    prevMonth.addEventListener('click', function() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
        updateCalendar();
    });
    
    nextMonth.addEventListener('click', function() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
        updateCalendar();
    });
    
    // Month/year select changes
    monthSelect.addEventListener('change', function() {
        currentCalendarDate.setMonth(parseInt(this.value));
        updateCalendar();
    });
    
    yearSelect.addEventListener('change', function() {
        currentCalendarDate.setFullYear(parseInt(this.value));
        updateCalendar();
    });
    
    // Clear date
    clearDate.addEventListener('click', function() {
        const input = document.getElementById(currentInputField);
        input.value = '';
        selectedDate = null;
        modal.classList.add('hidden');
    });
    
    // Today button
    todayDate.addEventListener('click', function() {
        const today = new Date();
        currentCalendarDate = new Date(today);
        selectedDate = new Date(today);
        updateCalendar();
    });
});
</script>

<script src="../core/validation.js"></script>

<!-- Custom Calendar Script -->
<script>
// Custom Calendar Functionality - Global Scope
let currentCalendarDate = new Date();
let selectedDate = null;
let currentInputField = null;

function toggleCustomCalendar(inputId) {
    currentInputField = inputId;
    const modal = document.getElementById('customCalendarModal');
    const input = document.getElementById(inputId);
    
    if (!modal) {
        console.error('Calendar modal not found!');
        return;
    }
    
    // Parse current date from input if it exists
    if (input.value) {
        const dateParts = input.value.split('/');
        if (dateParts.length === 3) {
            currentCalendarDate = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]);
            selectedDate = new Date(currentCalendarDate);
        }
    }
    
    updateCalendar();
    modal.classList.remove('hidden');
    console.log('Calendar modal opened for:', inputId);
}

function updateCalendar() {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const calendarGrid = document.getElementById('calendarGrid');
    
    if (!monthSelect || !yearSelect || !calendarGrid) {
        console.error('Calendar elements not found!');
        return;
    }
    
    // Update month and year selects
    monthSelect.value = currentCalendarDate.getMonth();
    
    // Populate years (1900 to current year + 10)
    if (yearSelect.children.length === 0) {
        const currentYear = new Date().getFullYear();
        for (let year = currentYear + 10; year >= 1900; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
    }
    yearSelect.value = currentCalendarDate.getFullYear();
    
    // Clear calendar grid
    calendarGrid.innerHTML = '';
    
    // Get first day of month and number of days
    const firstDay = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth(), 1);
    const lastDay = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'h-8 flex items-center justify-center text-clinic-dark/30';
        calendarGrid.appendChild(emptyCell);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'h-8 flex items-center justify-center text-clinic-dark hover:bg-clinic-blue/10 rounded cursor-pointer transition-colors duration-200 text-sm';
        dayCell.textContent = day;
        
        // Check if this is the selected date
        if (selectedDate && 
            selectedDate.getDate() === day && 
            selectedDate.getMonth() === currentCalendarDate.getMonth() && 
            selectedDate.getFullYear() === currentCalendarDate.getFullYear()) {
            dayCell.className += ' bg-clinic-blue text-white hover:bg-clinic-blue/80';
        }
        
        // Check if this is today
        const today = new Date();
        if (day === today.getDate() && 
            currentCalendarDate.getMonth() === today.getMonth() && 
            currentCalendarDate.getFullYear() === today.getFullYear()) {
            dayCell.className += ' border-2 border-clinic-tea';
        }
        
        dayCell.addEventListener('click', () => selectDate(day));
        calendarGrid.appendChild(dayCell);
    }
}

function selectDate(day) {
    selectedDate = new Date(currentCalendarDate.getFullYear(), currentCalendarDate.getMonth(), day);
    const input = document.getElementById(currentInputField);
    // Format as DD/MM/YYYY to match PHP backend expectation
    const formattedDate = `${String(day).padStart(2, '0')}/${String(currentCalendarDate.getMonth() + 1).padStart(2, '0')}/${currentCalendarDate.getFullYear()}`;
    input.value = formattedDate;
    
    // Close calendar
    document.getElementById('customCalendarModal').classList.add('hidden');
    
    // Trigger age calculation
    calculateAgeFromDate(selectedDate);
}

function calculateAgeFromDate(birthDate) {
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    const ageInput = document.getElementById('ageInput');
    if (ageInput) {
        ageInput.value = age >= 0 ? age : 0;
    }
}

// Calendar event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('customCalendarModal');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const clearDate = document.getElementById('clearDate');
    const todayDate = document.getElementById('todayDate');
    
    if (!modal || !prevMonth || !nextMonth || !monthSelect || !yearSelect || !clearDate || !todayDate) {
        console.error('Calendar elements not found on page load');
        return;
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    // Navigation buttons
    prevMonth.addEventListener('click', function() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
        updateCalendar();
    });
    
    nextMonth.addEventListener('click', function() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
        updateCalendar();
    });
    
    // Month/year select changes
    monthSelect.addEventListener('change', function() {
        currentCalendarDate.setMonth(parseInt(this.value));
        updateCalendar();
    });
    
    yearSelect.addEventListener('change', function() {
        currentCalendarDate.setFullYear(parseInt(this.value));
        updateCalendar();
    });
    
    // Clear date
    clearDate.addEventListener('click', function() {
        const input = document.getElementById(currentInputField);
        if (input) {
            input.value = '';
        }
        selectedDate = null;
        modal.classList.add('hidden');
    });
    
    // Today button
    todayDate.addEventListener('click', function() {
        const today = new Date();
        currentCalendarDate = new Date(today);
        selectedDate = new Date(today);
        updateCalendar();
    });
});
</script>
