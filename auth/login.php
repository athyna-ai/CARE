<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

$errors = [];
$info = [];

// Handle session timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    $info[] = 'Your session has expired due to inactivity. Please log in again.';
}

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $info[] = 'You have been successfully logged out. Thank you for using CARE CMS!';
}

// Clear any pending login session if this is a GET request (page refresh) 
// BUT NOT if we just processed a form submission (POST data still exists)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['pending_login']) && !isset($_POST['identifier'])) {
	// Clearing pending login session on GET request
	unset($_SESSION['pending_login']);
}

// Also clear if there's a cancel parameter in URL
if (isset($_GET['cancelled']) && $_GET['cancelled'] === '1') {
	if (isset($_SESSION['pending_login'])) {
		unset($_SESSION['pending_login']);
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		// IP-based rate limiting for security
		$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$pdo = get_pdo();
		
		// Check for suspicious IP activity
		$suspiciousCheck = $pdo->prepare('
			SELECT COUNT(*) as failed_count 
			FROM activity_logs 
			WHERE ip_address = ? 
			AND action = "login_failed" 
			AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
		');
		$suspiciousCheck->execute([$clientIP]);
		$failedAttempts = $suspiciousCheck->fetch()['failed_count'];
		
		// Block IP if too many failed attempts
		if ($failedAttempts >= 10) {
			$errors[] = 'Too many failed login attempts from this IP. Access temporarily blocked.';
			log_activity($pdo, null, 'ip_blocked', "IP {$clientIP} blocked due to {$failedAttempts} failed attempts in 1 hour", 'auth/login', false);
		} else {
			$identifier = sanitize_string($_POST['identifier'] ?? ''); // name or email
			$password = (string)($_POST['password'] ?? '');

			if ($identifier === '' || $password === '') {
				$errors[] = 'Please fill in all fields.';
			} else {
			try {
				$pdo = get_pdo();
				$stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_admin, rfid, failed_attempts, locked_until FROM users WHERE (email = ? OR name = ?) AND is_admin = 1 LIMIT 1');
				$stmt->execute([$identifier, $identifier]);
				$user = $stmt->fetch();
				
				// Check if account is locked
				if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
					$lockTimeRemaining = strtotime($user['locked_until']) - time();
					$minutesRemaining = ceil($lockTimeRemaining / 60);
					log_activity($pdo, null, 'account_locked', 'Attempted login to locked account: ' . $identifier . ' (locked for ' . $minutesRemaining . ' more minutes)', 'auth/login');
					$errors[] = "Account is locked due to too many failed attempts. Try again in {$minutesRemaining} minutes.";
				} else if (!$user || !password_verify($password, $user['password_hash'])) {
					// Handle failed login
					if ($user) {
						// Increment failed attempts for existing user
						$newFailedAttempts = $user['failed_attempts'] + 1;
						$stmt = $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?');
						$stmt->execute([$newFailedAttempts, $user['id']]);
						
						// Lock account if too many failed attempts
						if ($newFailedAttempts >= 5) {
							$stmt = $pdo->prepare('UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?');
							$stmt->execute([$user['id']]);
							log_activity($pdo, null, 'account_locked', 'Account locked due to 5 failed attempts: ' . $identifier, 'auth/login', false);
							$errors[] = 'Too many failed attempts. Account locked for 15 minutes.';
						} else {
							log_activity($pdo, null, 'login_failed', 'Invalid credentials for: ' . $identifier . ' (attempt ' . $newFailedAttempts . '/5)', 'auth/login', false);
							$errors[] = 'Invalid credentials.';
						}
					} else {
						// User doesn't exist
						log_activity($pdo, null, 'login_failed', 'Login attempt for non-existent account: ' . $identifier, 'auth/login', false);
						$errors[] = 'Account does not exist.';
					}
				} else {
					// Successful login - reset failed attempts and unlock account
					$stmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?');
					$stmt->execute([$user['id']]);
					
					// Check if user has RFID set up
					if (!empty($user['rfid'])) {
						// Store user data temporarily for RFID verification
						$_SESSION['pending_login'] = [
							'id' => (int)$user['id'],
							'name' => $user['name'],
							'email' => $user['email'],
							'is_admin' => (int)$user['is_admin'],
							'rfid' => $user['rfid']
						];
						// Don't log in yet, wait for RFID verification
						$info[] = 'Credentials verified. RFID verification required.';
						
					} else {
						// No RFID set up - log in directly
						// Regenerate session ID for security
						session_regenerate_id(true);
						
						$_SESSION['user'] = [
							'id' => (int)$user['id'],
							'name' => $user['name'],
							'email' => $user['email'],
							'is_admin' => (int)$user['is_admin']
						];
						$_SESSION['last_activity'] = time();
						
						log_activity($pdo, (int)$user['id'], 'login_success', 'Successful login without RFID verification', 'auth/login');
						header('Location: ../admin/dashboard.php?welcome=1');
						exit;
					}
                }
			} catch (Throwable $e) {
				error_log('Login error: ' . $e->getMessage());
				$errors[] = 'An error occurred. Please try again.';
			}
		}
		} // Close IP rate limiting else block
	}
}
?>


<?php
$pageTitle = 'Admin Login'; $showTopNav = false; $showSidebar = false; include __DIR__ . '/../partials/header.php'; ?>
    <!-- Background with floating particles -->
    <div class="h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla relative overflow-hidden flex items-center justify-center p-4" style="overflow: hidden;">
        <!-- Floating particles animation -->
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-clinic-blue/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-clinic-tea/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-clinic-vanilla/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
        
        <!-- Single panel container -->
        <div class="relative z-10 flex justify-center w-full -mt-2 sm:-mt-4">
            <div class="flex flex-col lg:flex-row max-w-4xl w-full rounded-2xl overflow-hidden shadow-2xl">
                <!-- Left Side - Login Form (with gradient background) -->
                <div class="w-full lg:w-2/3 bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla p-6 sm:p-8 lg:p-12">
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-3xl font-bold text-clinic-dark mb-2">LOGIN</h1>
                        </div>
                        
                        <?php if ($errors): ?>
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm p-3" id="serverErrors">
                                <ul class="list-disc pl-5">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if ($info && empty($errors)): ?>
                            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm p-3" id="infoBox">
                                <ul class="list-disc pl-5">
                                    <?php foreach ($info as $msg): ?>
                                        <li><?= htmlspecialchars($msg) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" id="loginForm" novalidate class="space-y-6" autocomplete="on">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                            <div>
                                <label for="identifier" class="block text-clinic-dark font-medium mb-2">USER NAME</label>
                                <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus class="w-full rounded-xl bg-white border-2 border-clinic-tea/30 focus:border-clinic-blue focus:ring-2 focus:ring-clinic-blue/20 px-4 py-4 text-clinic-dark placeholder-clinic-dark/60 font-medium" placeholder="Enter your username or email" />
                            </div>
                            <div>
                                <label for="password" class="block text-clinic-dark font-medium mb-2">PASSWORD</label>
                                <div class="relative">
                                    <input type="password" id="password" name="password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" required class="w-full rounded-xl bg-white border-2 border-clinic-tea/30 focus:border-clinic-blue focus:ring-2 focus:ring-clinic-blue/20 px-4 py-4 pr-16 text-clinic-dark placeholder-clinic-dark/60 font-medium" placeholder="Enter your password" />
                                    <button class="absolute inset-y-0 right-2 my-auto text-clinic-blue text-sm px-2 font-medium" data-toggle="password" data-target="password" tabindex="-1" type="button">Show</button>
                                </div>
                            </div>
                            <button type="submit" id="loginButton" class="w-full bg-clinic-blue hover:bg-clinic-tea text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">LOGIN</button>
                            <?php
                            try {
                                $pdoTmp = get_pdo();
                                $hasAdmin = admin_exists($pdoTmp);
                            } catch (Throwable $e) { $hasAdmin = false; }
                            if (!$hasAdmin): ?>
                                <div class="mt-6 text-center">
                                    <a href="../auth/register.php" class="inline-block bg-white text-clinic-blue font-bold py-3 px-8 rounded-xl hover:bg-clinic-blue hover:text-white transition-all duration-300 shadow-lg">SIGN UP</a>
                                    <p class="text-clinic-dark/70 text-sm mt-3">Don't have an account?</p>
                                </div>
                            <?php endif; ?>
                        </form>
                        <div id="clientNotice" class="hidden mt-4 text-sm"></div>
                    </div>
                </div>

                <!-- Right Side - Branding/Info (plain white) -->
                <div class="w-full lg:w-1/3 bg-white p-6 sm:p-8 lg:p-12 flex flex-col items-center justify-center text-center">
                        <!-- Logo and Brand -->
                        <div class="space-y-4">
                            <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-2xl flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-comfortaa font-bold text-clinic-dark mb-2">CARE</h1>
                                <p class="text-sm text-clinic-dark/80 font-poppins mb-1">Clinic Administration of Records System</p>
                                <p class="text-xs text-clinic-dark/60 font-poppins italic">A School Clinic management Information system</p>
                            </div>
                        </div>

                        <!-- Security Icon -->
                        <div class="mt-8 mb-6">
                            <div class="w-20 h-20 mx-auto rounded-2xl bg-clinic-tea/20 flex items-center justify-center">
                                <svg class="w-10 h-10 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Features List -->
                        <div class="space-y-3 text-sm text-clinic-dark/80">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-clinic-blue"></div>
                                <span>Secure Patient Management</span>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-clinic-tea"></div>
                                <span>Digital Medical Records</span>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-clinic-vanilla"></div>
                                <span>Comprehensive Analytics</span>
                            </div>
                        </div>

                    <!-- Footer Info -->
                    <div class="mt-8 pt-6 border-t border-clinic-tea/20">
                        <p class="text-xs text-clinic-dark/60">
                            Our Lady of the Sacred Heart Inc.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

	<!-- RFID Verification Modal -->
	<div id="rfidModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
		<div class="bg-white rounded-xl shadow-2xl w-80 p-4">
			<div class="text-center">
				<!-- RFID Icon -->
				<div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg flex items-center justify-center">
					<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
					</svg>
				</div>
				
				<h3 class="text-lg font-semibold text-clinic-dark mb-1">RFID Verification</h3>
				<p class="text-clinic-dark/70 mb-4 text-sm">Tap your RFID card or enter manually</p>
				
				<!-- RFID Input -->
				<div class="mb-3">
					<input type="text" id="rfidInput" placeholder="Tap RFID card or enter manually" 
						   class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-clinic-blue focus:ring-2 focus:ring-clinic-blue/20 text-center text-sm tracking-wider" 
						   autofocus />
				</div>
				
				<!-- Status Message -->
				<div id="rfidStatus" class="hidden mb-3 p-2 rounded-lg text-xs"></div>
				
				<!-- Action Buttons -->
				<div class="flex gap-2">
					<button id="verifyRfid" class="flex-1 bg-clinic-blue hover:bg-clinic-blue/90 text-white font-medium py-2 px-3 rounded-lg transition text-sm">
						Verify
					</button>
					<button id="cancelRfid" type="button" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-3 rounded-lg transition text-sm cursor-pointer">
						Cancel
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Loading Screen (Hidden by default) -->
	<div id="loginLoadingScreen" class="hidden fixed inset-0 bg-black/90 backdrop-blur-md z-[10000] flex items-center justify-center">
		<div class="bg-white rounded-2xl shadow-2xl p-8 text-center max-w-sm mx-4">
			<!-- Loading Animation -->
			<div class="mb-6">
				<div class="relative w-20 h-20 mx-auto">
					<!-- Spinning Circle -->
					<div class="absolute inset-0 border-4 border-clinic-blue/20 rounded-full"></div>
					<div class="absolute inset-0 border-4 border-transparent border-t-clinic-blue rounded-full animate-spin"></div>
					
					<!-- Center Icon -->
					<div class="absolute inset-0 flex items-center justify-center">
						<svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
						</svg>
					</div>
				</div>
			</div>
			
			<!-- Loading Text -->
			<h3 class="text-xl font-bold text-clinic-dark mb-2">Logging In...</h3>
			<p class="text-clinic-dark/70 text-sm mb-4">Please wait while we verify your credentials</p>
			
			<!-- Progress Bar -->
			<div class="w-full bg-gray-200 rounded-full h-2 mb-4">
				<div id="loginProgressBar" class="bg-clinic-blue h-2 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
			</div>
			
			<!-- Welcome Message -->
			<div id="loginWelcomeMessage" class="hidden">
				<div class="text-clinic-green text-sm font-medium">
					<svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
					</svg>
					Welcome to CARE CMS!
				</div>
			</div>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const loginForm = document.getElementById('loginForm');
		const rfidModal = document.getElementById('rfidModal');
		const rfidInput = document.getElementById('rfidInput');
		const verifyRfidBtn = document.getElementById('verifyRfid');
		const cancelRfidBtn = document.getElementById('cancelRfid');
		const rfidStatus = document.getElementById('rfidStatus');
		
		// Check if there's a pending login session (credentials verified, RFID needed)
		const hasPendingLogin = <?= isset($_SESSION['pending_login']) && !empty($_SESSION['pending_login']) ? 'true' : 'false' ?>;
		
		if (hasPendingLogin) {
			// Show RFID modal - credentials were verified
			rfidModal.style.display = 'flex';
			rfidModal.classList.remove('hidden');
			rfidModal.classList.add('flex');
			
			setTimeout(() => {
				rfidInput.focus();
			}, 100);
		} else {
			// Hide modal - no pending login
			rfidModal.style.display = 'none';
			rfidModal.classList.add('hidden');
		}
		
		// Form submission with loading screen
		loginForm.addEventListener('submit', function(e) {
			// Show loading screen
			showLoginLoadingScreen();
			
			// Allow normal form submission
			// PHP will handle the logic and set session if needed
		});
		
		// Handle RFID verification
		verifyRfidBtn.addEventListener('click', function() {
			const rfid = rfidInput.value.trim();
			
			if (!rfid) {
				showRfidStatus('Please enter or tap your RFID card', 'error');
				return;
			}
			
			verifyRfidBtn.disabled = true;
			verifyRfidBtn.textContent = 'Verifying...';
			
			// Show loading screen for RFID verification
			showLoginLoadingScreen();
			
			fetch('verify_rfid_login.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'rfid=' + encodeURIComponent(rfid)
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Ensure header stays hidden during verification success
					const header = document.querySelector('header');
					const topNav = document.querySelector('.top-nav');
					if (header) header.style.display = 'none';
					if (topNav) topNav.style.display = 'none';
					
					// Update loading screen message
					const loadingText = document.querySelector('#loginLoadingScreen h3');
					const loadingSubtext = document.querySelector('#loginLoadingScreen p');
					if (loadingText) loadingText.textContent = 'RFID Verified!';
					if (loadingSubtext) loadingSubtext.textContent = 'Redirecting to dashboard...';
					
					setTimeout(() => {
						window.location.href = '../admin/dashboard.php?welcome=1';
					}, 1500);
				} else {
					hideLoginLoadingScreen();
					showRfidStatus(data.message || 'Invalid RFID card', 'error');
					rfidInput.value = '';
					rfidInput.focus();
					
					// If no pending session, close modal and reload
					if (data.message && data.message.includes('No pending login session')) {
						setTimeout(() => {
							rfidModal.classList.add('hidden');
							location.reload();
						}, 2000);
					}
				}
			})
			.catch(error => {
				console.error('Error:', error);
				hideLoginLoadingScreen();
				showRfidStatus('Error verifying RFID. Please try again.', 'error');
			})
			.finally(() => {
				verifyRfidBtn.disabled = false;
				verifyRfidBtn.textContent = 'Verify RFID';
			});
		});
		
		// Handle cancel
		cancelRfidBtn.addEventListener('click', function() {
			console.log('Cancel button clicked');
			
			// Hide modal immediately
			rfidModal.style.display = 'none';
			rfidModal.classList.add('hidden');
			rfidInput.value = '';
			rfidStatus.classList.add('hidden');
			
			// Redirect to login page with cancel parameter
			window.location.href = 'login.php?cancelled=1';
		});
		
		// Handle Enter key in RFID input
		rfidInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				verifyRfidBtn.click();
			}
		});
		
		// Auto-focus RFID input when modal opens
		rfidInput.addEventListener('focus', function() {
			this.select();
		});
		
		// Close modal when clicking outside
		rfidModal.addEventListener('click', function(e) {
			if (e.target === rfidModal) {
				cancelRfidBtn.click();
			}
		});
		
		// Close modal with Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && !rfidModal.classList.contains('hidden')) {
				cancelRfidBtn.click();
			}
		});
		
		function showRfidStatus(message, type) {
			rfidStatus.textContent = message;
			rfidStatus.className = 'mb-4 p-3 rounded-lg text-sm ' + (type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200');
			rfidStatus.classList.remove('hidden');
		}
		
		// Login loading screen functions
		function showLoginLoadingScreen() {
			const loadingScreen = document.getElementById('loginLoadingScreen');
			const progressBar = document.getElementById('loginProgressBar');
			const welcomeMessage = document.getElementById('loginWelcomeMessage');
			
			if (loadingScreen) {
				// Hide main header and navigation elements
				const header = document.querySelector('header');
				const topNav = document.querySelector('.top-nav');
				if (header) header.style.display = 'none';
				if (topNav) topNav.style.display = 'none';
				
				loadingScreen.classList.remove('hidden');
				
				// Animate progress bar
				let progress = 0;
				const progressInterval = setInterval(() => {
					progress += Math.random() * 15;
					if (progress > 90) progress = 90;
					progressBar.style.width = progress + '%';
				}, 200);
				
				// Show welcome message after 2 seconds
				setTimeout(() => {
					welcomeMessage.classList.remove('hidden');
				}, 2000);
				
				// Complete progress after 3 seconds (form should be submitted by then)
				setTimeout(() => {
					clearInterval(progressInterval);
					progressBar.style.width = '100%';
				}, 3000);
			}
		}
		
		function hideLoginLoadingScreen() {
			const loadingScreen = document.getElementById('loginLoadingScreen');
			if (loadingScreen) {
				loadingScreen.classList.add('hidden');
				
				// Restore main header and navigation elements
				const header = document.querySelector('header');
				const topNav = document.querySelector('.top-nav');
				if (header) header.style.display = '';
				if (topNav) topNav.style.display = '';
			}
		}
	});
	</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>


