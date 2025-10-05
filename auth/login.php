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

// Clear any pending login session if this is a GET request (page refresh) 
// BUT NOT if we just processed a form submission (POST data still exists)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['pending_login']) && !isset($_POST['identifier'])) {
	error_log('Clearing pending login session on GET request (no POST data)');
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
			log_activity($pdo, null, 'ip_blocked', "IP {$clientIP} blocked due to {$failedAttempts} failed attempts in 1 hour", 'auth/login');
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
							log_activity($pdo, null, 'account_locked', 'Account locked due to 5 failed attempts: ' . $identifier, 'auth/login');
							$errors[] = 'Too many failed attempts. Account locked for 15 minutes.';
						} else {
							log_activity($pdo, null, 'login_failed', 'Invalid credentials for: ' . $identifier . ' (attempt ' . $newFailedAttempts . '/5)', 'auth/login');
							$errors[] = 'Invalid credentials.';
						}
					} else {
						// User doesn't exist
						log_activity($pdo, null, 'login_failed', 'Login attempt for non-existent account: ' . $identifier, 'auth/login');
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
						$_SESSION['user'] = [
							'id' => (int)$user['id'],
							'name' => $user['name'],
							'email' => $user['email'],
							'is_admin' => (int)$user['is_admin']
						];
						$_SESSION['last_activity'] = time();
						
						log_activity($pdo, (int)$user['id'], 'login_success', 'Successful login without RFID verification', 'auth/login');
						header('Location: ../admin/dashboard.php');
						exit;
					}
                }
			} catch (Throwable $e) {
				error_log('Login error: ' . $e->getMessage());
				$errors[] = 'Server error: ' . $e->getMessage();
			}
		}
		} // Close IP rate limiting else block
	}
}
?>


<?php
$pageTitle = 'Admin Login'; $showTopNav = false; $showSidebar = false; include __DIR__ . '/../partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:block bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla min-h-[calc(100vh-5rem)] relative overflow-hidden">
            <!-- Floating particles animation -->
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-clinic-blue/5 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-clinic-tea/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-clinic-vanilla/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
            
            <!-- Content -->
            <div class="relative z-10 flex flex-col items-center justify-center h-full px-8">
                <div class="text-center space-y-8">
                    <!-- Logo and Brand -->
                    <div class="space-y-4">
                        <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-comfortaa font-bold text-clinic-dark">CARE</h1>
                            <p class="text-sm text-clinic-dark/70 font-poppins">Clinic Administration of Records System</p>
                            <p class="text-xs text-clinic-dark/60 font-poppins italic">A School Clinic management Information system</p>
                        </div>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 gap-4">
                        <div class="bg-white/80 backdrop-blur-md rounded-xl p-4 shadow-lg border border-clinic-tea/20">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-clinic-blue/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-clinic-dark">Patient Management</span>
                            </div>
                        </div>

                        <div class="bg-white/80 backdrop-blur-md rounded-xl p-4 shadow-lg border border-clinic-tea/20">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-clinic-tea/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-clinic-dark">Medical Records</span>
                            </div>
                        </div>

                        <div class="bg-white/80 backdrop-blur-md rounded-xl p-4 shadow-lg border border-clinic-tea/20">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-clinic-vanilla/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-clinic-dark">Analytics & Logs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="pt-4 border-t border-clinic-tea/20">
                        <p class="text-xs text-clinic-dark/50">
                            Secure • HIPAA Compliant • User-Friendly
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="min-h-[calc(100vh-5rem)] flex items-center justify-center p-0 md:p-8">
            <div class="w-full max-w-2xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
			<h1 class="text-2xl font-semibold mb-2">Admin Login</h1>
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
		
		
		<form method="post" id="loginForm" novalidate class="space-y-4" autocomplete="on">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
			<div>
				<label for="identifier" class="block text-slate-700 mb-1">Name or Email</label>
				<input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800 placeholder-slate-400" placeholder="you@example.com or Admin" />
			</div>
			<div>
				<label for="password" class="block text-slate-700 mb-1">Password</label>
				<div class="relative">
					<input type="password" id="password" name="password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 pr-16 text-slate-800" />
					<button class="absolute inset-y-0 right-2 my-auto text-sky-700 text-sm px-2" data-toggle="password" data-target="password" tabindex="-1" type="button">Show</button>
				</div>
			</div>
            <button type="submit" id="loginButton" class="w-full bg-clinic-blue hover:bg-clinic-tea text-white font-semibold py-3 rounded-xl transition">Login</button>
            <?php
            try {
                $pdoTmp = get_pdo();
                $hasAdmin = admin_exists($pdoTmp);
            } catch (Throwable $e) { $hasAdmin = false; }
            if (!$hasAdmin): ?>
                <p class="text-sm text-slate-600">No admin yet? <a href="../auth/register.php" class="text-sky-700 hover:underline">Create Admin Account</a></p>
            <?php endif; ?>
		</form>
		<div id="clientNotice" class="hidden mt-4 text-sm"></div>
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
		
		// Simple form submission - let PHP handle everything
		loginForm.addEventListener('submit', function(e) {
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
					showRfidStatus('RFID verified successfully! Logging in...', 'success');
					setTimeout(() => {
						window.location.href = '../admin/dashboard.php';
					}, 1000);
				} else {
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
	});
	</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>


