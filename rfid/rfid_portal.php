<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// REQUIRE ADMIN AUTHENTICATION for RFID portal access
require_admin_auth();
$pdo = get_pdo();

$errors = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$rfid = sanitize_string($_POST['rfid'] ?? '');
	if ($rfid === '') {
		$errors[] = 'Please tap an ID.';
	} else {
		// Search by RFID in both students and faculty tables
		$studentStmt = $pdo->prepare('SELECT id, name, level as info, rfid, "student" as type FROM students WHERE rfid = ?');
		$studentStmt->execute([$rfid]);
		$student = $studentStmt->fetch();
		
		$facultyStmt = $pdo->prepare('SELECT id, name, department as info, rfid, "faculty" as type FROM faculty WHERE rfid = ?');
		$facultyStmt->execute([$rfid]);
		$faculty = $facultyStmt->fetch();
		
		if ($student) {
			// Log successful RFID search (authenticated admin access)
			$userId = $_SESSION['user']['id'] ?? null;
			log_activity($pdo, $userId, 'rfid_search', "Found student: {$student['name']} ({$student['info']})", 'rfid_portal');
			// Automatically redirect to patient view
			header("Location: ../patients/patient_view.php?id={$student['id']}&type=student");
			exit;
		} elseif ($faculty) {
			// Log successful RFID search (authenticated admin access)
			$userId = $_SESSION['user']['id'] ?? null;
			log_activity($pdo, $userId, 'rfid_search', "Found faculty: {$faculty['name']} ({$faculty['info']})", 'rfid_portal');
			// Automatically redirect to patient view
			header("Location: ../patients/patient_view.php?id={$faculty['id']}&type=faculty");
			exit;
		} else {
			$result = ['exists' => false];
			// Log unsuccessful RFID search (authenticated admin access)
			$userId = $_SESSION['user']['id'] ?? null;
			log_activity($pdo, $userId, 'rfid_search', "RFID not found: {$rfid}", 'rfid_portal');
		}
	}
}

?>
<?php $pageTitle = 'Patient Search Portal'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/../partials/header.php'; ?>

<!-- Full Screen Non-Scrollable Layout -->
<div class="h-screen flex items-center justify-center p-4 overflow-hidden">
    <div class="w-full max-w-4xl mx-auto h-full flex flex-col justify-center">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-comfortaa font-bold text-clinic-dark mb-3">Patient Search Portal</h1>
            <p class="text-clinic-dark/70 text-lg font-poppins mb-6">Tap a student, faculty, or visitor ID to search records or start a new registration</p>
            
            <!-- Back Button -->
            <div class="flex justify-center">
                <a href="../admin/dashboard.php" class="group px-6 py-3 bg-clinic-dark/10 border border-clinic-dark/20 text-clinic-dark rounded-2xl hover:bg-clinic-dark/20 hover:border-clinic-dark/30 hover:shadow-lg transition-all duration-300 flex items-center gap-3 font-poppins font-medium">
                    <div class="w-6 h-6 rounded-xl bg-clinic-dark/20 flex items-center justify-center group-hover:bg-clinic-dark/30 transition-colors duration-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </div>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- RFID Search Form - Compact Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-clinic-tea/20 p-8 w-full max-w-3xl mx-auto">
            <h2 class="text-3xl font-comfortaa font-bold text-clinic-dark mb-6 text-center">RFID Search</h2>
            
            <?php if ($errors): ?>
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 text-base p-4">
                    <ul class="list-disc pl-5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 text-green-700 text-base p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <strong>Registration Successful!</strong>
                            <?php if (isset($_GET['type']) && $_GET['type'] == 'student'): ?>
                                Student has been registered successfully.
                            <?php elseif (isset($_GET['type']) && $_GET['type'] == 'faculty'): ?>
                                Faculty member has been registered successfully.
                            <?php else: ?>
                                Patient has been registered successfully.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" class="space-y-6" id="rfidSearchForm">
                <div>
                    <label class="block text-clinic-dark font-poppins font-semibold mb-4 text-center text-xl">SEARCH:</label>
                    <input type="text" name="rfid" id="rfidSearchInput" autofocus 
                           class="w-full rounded-2xl bg-clinic-ivory/40 border border-clinic-tea/30 focus:ring-2 focus:ring-clinic-blue focus:border-clinic-blue px-8 py-6 text-clinic-dark placeholder-clinic-dark/50 font-poppins text-xl text-center" 
                           placeholder="Tap ID here"
                           style="background-color: #fbfcee !important; color: #343b1b !important;" />
                </div>
            </form>
            
            <!-- Always show register option -->
            <div class="mt-8 text-center">
                <button class="px-8 py-4 rounded-xl bg-clinic-blue text-white hover:bg-clinic-tea transition-colors font-poppins font-medium text-lg" id="roleChooseBtn" type="button">Register New Patient</button>
            </div>
            
            <?php if ($result && !$result['exists']): ?>
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold text-amber-800 text-xl">No Record Found</p>
                            <p class="text-slate-700 text-lg">This RFID is not registered in the system.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <!-- Role Select Modal - Large and Centered -->
    <div id="roleModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl mx-auto bg-white rounded-3xl shadow-2xl p-12">
            <div class="text-center mb-8">
                <h2 class="text-4xl font-comfortaa font-bold text-clinic-dark mb-4">Who is registering?</h2>
                <p class="text-slate-600 text-xl font-poppins">Choose the correct form for faster data entry.</p>
                <div id="rfidDisplay" class="mt-4 p-4 bg-slate-100 rounded-xl border border-slate-200">
                    <p class="text-slate-700 font-medium">RFID will be pre-filled:</p>
                    <p class="text-2xl font-mono font-bold text-slate-800" id="rfidValue">-</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Student Registration Link -->
                <div class="group px-8 py-12 rounded-2xl bg-white border border-slate-200 text-center hover:bg-slate-50 hover:border-slate-300 transition-all duration-300 shadow-sm hover:shadow-md cursor-pointer" onclick="navigateToStudentForm()">
                    <div class="flex flex-col items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-slate-200 transition-colors duration-300">
                            <svg class="w-8 h-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.083 12.083 0 01.665-6.479L12 14z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-poppins font-bold text-slate-400">Student</h3>
                            <p class="text-slate-400 text-lg">Register a new student</p>
                        </div>
                    </div>
                </div>
                
                <!-- Faculty Registration Link -->
                <div class="group px-8 py-12 rounded-2xl bg-white border border-slate-200 text-center hover:bg-slate-50 hover:border-slate-300 transition-all duration-300 shadow-sm hover:shadow-md cursor-pointer" onclick="navigateToFacultyForm()">
                    <div class="flex flex-col items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-slate-200 transition-colors duration-300">
                            <svg class="w-8 h-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-poppins font-bold text-slate-400">Faculty</h3>
                            <p class="text-slate-400 text-lg">Register a new faculty member</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button id="roleCancel" class="px-8 py-4 rounded-xl text-slate-700 hover:text-slate-900 transition-all duration-300 font-poppins font-medium text-lg">Cancel</button>
            </div>
        </div>
    </div>

<script>
// Handle ESC key to redirect to dashboard (outside DOMContentLoaded)
function handleEscKey(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        // Check if any form, modal, or action element is active
        const activeElement = document.activeElement;
        const isFormElement = activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.tagName === 'SELECT' ||
            activeElement.tagName === 'BUTTON' ||
            activeElement.closest('form') ||
            activeElement.closest('[role="dialog"]') ||
            activeElement.closest('.modal')
        );
        
        // Check if any form is currently being submitted
        const forms = document.querySelectorAll('form');
        const isFormSubmitting = Array.from(forms).some(form => {
            const submitButton = form.querySelector('button[type="submit"]:focus, input[type="submit"]:focus');
            return submitButton !== null;
        });
        
        // Only redirect if no form/modal is active and no form is being submitted
        if (!isFormElement && !isFormSubmitting) {
            e.preventDefault();
            e.stopPropagation();
            // Add a small delay to prevent interference with form submissions
            setTimeout(() => {
                window.location.replace('../admin/dashboard.php');
            }, 100);
            return false;
        }
    }
}

// Add ESC key listener only for this page
document.addEventListener('keydown', handleEscKey);


document.addEventListener('DOMContentLoaded', function() {
    // Handle role selection modal
    const roleModal = document.getElementById('roleModal');
    const roleChooseBtn = document.getElementById('roleChooseBtn');
    const roleCancel = document.getElementById('roleCancel');
    
    if (roleChooseBtn) {
        roleChooseBtn.addEventListener('click', function() {
            console.log('Role choose button clicked');
            // Update RFID display in modal
            const rfidValue = document.getElementById('rfidSearchInput').value;
            const rfidDisplay = document.getElementById('rfidValue');
            console.log('RFID value for display:', rfidValue);
            if (rfidDisplay) {
                rfidDisplay.textContent = rfidValue || 'No RFID entered';
            }
            
            roleModal.classList.remove('hidden');
            roleModal.classList.add('flex');
            console.log('Modal opened');
        });
    }
    
    if (roleCancel) {
        roleCancel.addEventListener('click', function() {
            roleModal.classList.add('hidden');
            roleModal.classList.remove('flex');
        });
    }
    
    // Close modal when clicking outside
    if (roleModal) {
        roleModal.addEventListener('click', function(e) {
            if (e.target === roleModal) {
                roleModal.classList.add('hidden');
                roleModal.classList.remove('flex');
            }
        });
    }
    
    // Navigation functions for role selection
    window.navigateToStudentForm = function() {
        console.log('Student form navigation triggered');
        const rfidValue = document.getElementById('rfidSearchInput').value;
        console.log('RFID value:', rfidValue);
        
        let url = '../patients/student_form.php';
        if (rfidValue) {
            url += `?rfid=${encodeURIComponent(rfidValue)}`;
        }
        
        console.log('Navigating to:', url);
        window.location.href = url;
    };
    
    window.navigateToFacultyForm = function() {
        console.log('Faculty form navigation triggered');
        const rfidValue = document.getElementById('rfidSearchInput').value;
        console.log('RFID value:', rfidValue);
        
        let url = '../patients/faculty_form.php';
        if (rfidValue) {
            url += `?rfid=${encodeURIComponent(rfidValue)}`;
        }
        
        console.log('Navigating to:', url);
        window.location.href = url;
    };
    
    // Handle Enter key on RFID input
    const rfidInput = document.getElementById('rfidSearchInput');
    if (rfidInput) {
        rfidInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Submit the form to search for existing RFID
                document.getElementById('rfidSearchForm').submit();
            }
        });
    }
    
    // Auto-focus RFID input on page load
    if (rfidInput) {
        rfidInput.focus();
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>



