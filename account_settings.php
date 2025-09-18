<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();
$user = $_SESSION['user'];
$errors = [];
$info = [];
$showRegisterForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!verify_csrf($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Invalid request.';
	} else {
		// Check if this is a register new account request
		if (isset($_POST['register_new'])) {
			$showRegisterForm = true;
		} else if (isset($_POST['create_account'])) {
			// Handle new account creation
			$newName = sanitize_string($_POST['new_name'] ?? '');
			$newEmail = sanitize_string($_POST['new_email'] ?? '');
			$newPassword = (string)($_POST['new_account_password'] ?? '');
			$newRfid = sanitize_string($_POST['new_rfid'] ?? '');
			$isAdmin = isset($_POST['is_admin']) ? 1 : 0;

			if ($newName === '' || $newEmail === '' || $newPassword === '') {
				$errors[] = 'Please fill in all required fields.';
			} else if (!is_strong_password($newPassword)) {
				$errors[] = 'Password must be strong.';
			} else {
				try {
					// Check if email already exists
					$check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR rfid = ? LIMIT 1');
					$check->execute([$newEmail, $newRfid]);
					if ($check->fetch()) {
						$errors[] = 'Email or RFID already in use.';
					} else {
						$hash = password_hash($newPassword, PASSWORD_DEFAULT);
						$rfidHash = password_hash($newRfid, PASSWORD_DEFAULT);
						$ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, rfid, is_admin) VALUES (?, ?, ?, ?, ?)');
						$ins->execute([$newName, $newEmail, $hash, $rfidHash, $isAdmin]);
						$info[] = 'New account created successfully.';
						$showRegisterForm = false;
					}
				} catch (Throwable $e) {
					$errors[] = 'Server error.';
				}
			}
		} else {
			// Handle profile update
			$name = sanitize_string($_POST['name'] ?? $user['name']);
			$email = sanitize_string($_POST['email'] ?? $user['email']);
			$newPassword = (string)($_POST['new_password'] ?? '');
			$rfid = sanitize_string($_POST['rfid'] ?? '');

			try {
				// Update name/email
				$upd = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
				$upd->execute([$name, $email, (int)$user['id']]);
				$info[] = 'Profile updated.';
				
				// Log profile update
				log_activity($pdo, (int)$user['id'], 'profile_update', "Updated profile: {$name} ({$email})", 'account_settings');

				// Update password if provided
				if ($newPassword !== '') {
					if (!is_strong_password($newPassword)) {
						$errors[] = 'New password must be strong.';
					} else {
						$hash = password_hash($newPassword, PASSWORD_DEFAULT);
						$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, (int)$user['id']]);
						$info[] = 'Password updated.';
						
						// Log password update
						log_activity($pdo, (int)$user['id'], 'password_update', "Updated password", 'account_settings');
					}
				}

			// Update RFID (store hashed)
			if ($rfid !== '') {
				$rfidHash = password_hash($rfid, PASSWORD_DEFAULT);
				$pdo->prepare('UPDATE users SET rfid = ? WHERE id = ?')->execute([$rfidHash, (int)$user['id']]);
				$info[] = 'RFID updated.';
				
				// Log RFID update
				log_activity($pdo, (int)$user['id'], 'rfid_update', "Updated RFID", 'account_settings');
			}

				// Refresh session
				$_SESSION['user']['name'] = $name;
				$_SESSION['user']['email'] = $email;
			} catch (Throwable $e) {
				$errors[] = 'Server error.';
			}
		}
	}
}

?>
<?php $pageTitle = 'Account Settings'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>
	<div class="min-h-[calc(100vh-5rem)] flex items-start md:items-center justify-center p-4 md:p-8">
		<div class="w-full max-w-3xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
			<div class="flex justify-between items-center mb-4">
				<h1 class="text-2xl font-semibold">Account Settings</h1>
				<?php if (!$showRegisterForm): ?>
					<form method="post" class="inline">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
						<button type="submit" name="register_new" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
							+ Register New Account
						</button>
					</form>
				<?php else: ?>
					<form method="post" class="inline">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
						<button type="submit" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
							← Back to Settings
						</button>
					</form>
				<?php endif; ?>
			</div>
			<?php if ($errors): ?>
				<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm p-3">
					<ul class="list-disc pl-5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
			<?php if ($info): ?>
				<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm p-3">
					<ul class="list-disc pl-5"><?php foreach ($info as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
			
			<?php if ($showRegisterForm): ?>
				<!-- Register New Account Form -->
				<div class="bg-slate-50 rounded-xl p-6 mb-6">
					<h2 class="text-xl font-semibold mb-4 text-slate-800">Register New Account</h2>
					<form method="post" class="grid md:grid-cols-2 gap-4" autocomplete="on">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
						<div>
							<label class="block text-slate-700 mb-1">Name *</label>
							<input type="text" name="new_name" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
						</div>
						<div>
							<label class="block text-slate-700 mb-1">Email *</label>
							<input type="email" name="new_email" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
						</div>
						<div>
							<label class="block text-slate-700 mb-1">Password *</label>
							<input type="password" name="new_account_password" required class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
							<small class="block text-slate-500">Must be strong password (8+ chars, upper, lower, number, special)</small>
						</div>
						<div>
							<label class="block text-slate-700 mb-1">RFID</label>
							<input type="text" name="new_rfid" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
							<small class="block text-slate-500">Optional - for RFID login</small>
						</div>
						<div class="md:col-span-2">
							<label class="flex items-center">
								<input type="checkbox" name="is_admin" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
								<span class="ml-2 text-slate-700">Admin privileges</span>
							</label>
						</div>
						<div class="md:col-span-2">
							<button type="submit" name="create_account" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-xl transition">
								Create Account
							</button>
						</div>
					</form>
				</div>
			<?php else: ?>
				<!-- Profile Settings Form -->
				<form method="post" class="grid md:grid-cols-2 gap-4" autocomplete="on">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
				<div>
					<label class="block text-slate-700 mb-1">Name</label>
					<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">Email</label>
					<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
				</div>
				<div>
					<label class="block text-slate-700 mb-1">New Password</label>
					<input type="password" name="new_password" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
					<small class="block text-slate-500">Leave blank to keep current password.</small>
				</div>
				<div>
					<label class="block text-slate-700 mb-1">RFID (Tap to update)</label>
					<input type="text" name="rfid" class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" />
					<small class="block text-slate-500">Leave blank to keep current RFID.</small>
				</div>
				<div class="md:col-span-2">
					<button class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 rounded-xl transition">Save Changes</button>
				</div>
			</form>
			<?php endif; ?>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>


