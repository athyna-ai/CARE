<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$errors = [];
$success = false;

// Restrict registration: if an admin exists, only allow when logged-in admin is accessing
try {
	$pdoCheck = get_pdo();
	if (admin_exists($pdoCheck) && !is_logged_in_admin()) {
		header('Location: login.php');
		exit;
	}
} catch (Throwable $e) {
	// if DB not ready, allow first-time setup
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request. Please refresh and try again.';
	} else {
		$name = sanitize_string($_POST['name'] ?? '');
		$email = sanitize_string($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');
		$accept_policy = isset($_POST['accept_policy']);

		if ($name === '') { $errors[] = 'Name is required.'; }
		if ($email === '' || !is_valid_email($email)) { $errors[] = 'Valid email is required.'; }
		if (!is_strong_password($password)) { $errors[] = 'Password must be at least 8 chars with upper, lower, number, and special.'; }
		if (!$accept_policy) { $errors[] = 'You must accept the policy.'; }

		if (!$errors) {
			try {
				$pdo = get_pdo();
				// Only allow admin accounts to be created (flag is_admin=1)
				// Ensure unique email
				$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
				$check->execute([$email]);
				if ($check->fetch()) {
					$errors[] = 'Email already in use.';
				} else {
					$hash = password_hash($password, PASSWORD_DEFAULT);
					$ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)');
					$ins->execute([$name, $email, $hash]);
					$userId = (int)$pdo->lastInsertId();
					log_activity($pdo, $userId, 'register', 'Admin account created', 'auth/register');
					// Redirect to login after successful registration
					header('Location: login.php?registered=1');
					exit;
				}
			} catch (Throwable $e) {
				$errors[] = 'Server error. Please try again later.';
			}
		}
	}
}

?>
<?php $pageTitle = 'Register Admin'; $showTopNav = false; $showSidebar = false; include __DIR__ . '/partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:block bg-gradient-to-br from-sky-100 to-blue-100 min-h-[calc(100vh-5rem)]"></div>
        <div class="min-h-[calc(100vh-5rem)] flex items-center justify-center p-0 md:p-8">
            <div class="w-full max-w-2xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
			<h1 class="text-2xl font-semibold mb-2">Register Admin</h1>
		<?php if ($success): ?>
			<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm p-3">Registration successful. <a href="/login.php" class="underline">Go to Login</a></div>
		<?php endif; ?>
		<?php if ($errors): ?>
			<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm p-3" id="serverErrors">
				<ul class="list-disc pl-5">
					<?php foreach ($errors as $err): ?>
						<li><?= htmlspecialchars($err) ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<form method="post" id="registerForm" novalidate class="space-y-4" autocomplete="on">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
			<div>
				<label for="name" class="block text-slate-700 mb-1">Name</label>
				<input type="text" id="name" name="name" required autofocus class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
			</div>
			<div>
				<label for="email" class="block text-slate-700 mb-1">Email</label>
				<input type="email" id="email" name="email" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
			</div>
			<div>
				<label for="password" class="block text-slate-700 mb-1">Password</label>
				<div class="relative">
					<input type="password" id="password" name="password" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 pr-16 text-slate-800" />
					<button class="absolute inset-y-0 right-2 my-auto text-sky-700 text-sm px-2" data-toggle="password" data-target="password" tabindex="-1" type="button">Show</button>
				</div>
				<small class="block text-slate-500">At least 8 chars, include uppercase, lowercase, number, special.</small>
			</div>
			<div class="flex items-center gap-2">
				<input type="checkbox" id="accept_policy" name="accept_policy" class="h-4 w-4 rounded border-slate-300 bg-white">
				<label for="accept_policy" class="text-slate-700">I accept the policy</label>
			</div>
			<button type="submit" id="registerButton" disabled class="w-full bg-clinic-blue hover:bg-clinic-tea text-white font-semibold py-3 rounded-xl transition">Create Admin Account</button>
			<p class="text-sm text-slate-600"><a href="login.php" class="text-sky-700 hover:underline">Back to Login</a></p>
		</form>
			</div>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>


