<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$errors = [];
$info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		$identifier = sanitize_string($_POST['identifier'] ?? ''); // name or email
		$password = (string)($_POST['password'] ?? '');

		if ($identifier === '' || $password === '') {
			$errors[] = 'Please fill in all fields.';
		} else {
			try {
				$pdo = get_pdo();
				$stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_admin FROM users WHERE (email = ? OR name = ?) AND is_admin = 1 LIMIT 1');
				$stmt->execute([$identifier, $identifier]);
				$user = $stmt->fetch();
				if (!$user || !password_verify($password, $user['password_hash'])) {
					log_activity($pdo, null, 'login_failed', 'Invalid credentials for: ' . $identifier, 'auth/login');
					$errors[] = 'Invalid credentials.';
				} else {
					$_SESSION['user'] = [
						'id' => (int)$user['id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'is_admin' => (int)$user['is_admin'],
					];
					$_SESSION['last_activity'] = time();
                    log_activity($pdo, (int)$user['id'], 'login', 'Admin login', 'auth/login');
                    header('Location: dashboard.php');
					exit;
                }
			} catch (Throwable $e) {
				error_log('Login error: ' . $e->getMessage());
				$errors[] = 'Server error: ' . $e->getMessage();
			}
		}
	}
}
?>


<?php
$pageTitle = 'Admin Login'; $showTopNav = false; $showSidebar = false; include __DIR__ . '/partials/header.php'; ?>
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
                            <p class="text-sm text-clinic-dark/70 font-poppins">Clinic Administration & Records System</p>
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
		<?php if ($info): ?>
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
				<input type="text" id="identifier" name="identifier" required autofocus class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800 placeholder-slate-400" placeholder="you@example.com or Admin" />
			</div>
			<div>
				<label for="password" class="block text-slate-700 mb-1">Password</label>
				<div class="relative">
					<input type="password" id="password" name="password" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 pr-16 text-slate-800" />
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
                <p class="text-sm text-slate-600">No admin yet? <a href="register.php" class="text-sky-700 hover:underline">Create Admin Account</a></p>
            <?php endif; ?>
		</form>
		<div id="clientNotice" class="hidden mt-4 text-sm"></div>
			</div>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>


