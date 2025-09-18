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
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
	$info[] = 'Registration successful. You can now log in.';
}

$pageTitle = 'Admin Login'; $showTopNav = false; $showSidebar = false; include __DIR__ . '/partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:block bg-gradient-to-br from-sky-100 to-blue-100 min-h-[calc(100vh-5rem)]"></div>
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
            <button type="submit" id="loginButton" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 rounded-xl transition">Login</button>
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


