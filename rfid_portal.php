<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();
$pdo = get_pdo();

// Note: Students table should already exist from student_form.php

$errors = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$rfid = sanitize_string($_POST['rfid'] ?? '');
	if ($rfid === '') {
		$errors[] = 'Please tap an ID.';
	} else {
		// Search by RFID (direct match since rfid is stored as plain text)
		$stmt = $pdo->prepare('SELECT id, name, level, rfid FROM students WHERE rfid = ?');
		$stmt->execute([$rfid]);
		$found = $stmt->fetch();
		if ($found) {
			$result = ['exists' => true, 'student' => $found];
			// Log successful RFID search
			log_activity($pdo, (int)$_SESSION['user']['id'], 'rfid_search', "Found student: {$found['name']} ({$found['level']})", 'rfid_portal');
		} else {
			$result = ['exists' => false];
			// Log unsuccessful RFID search
			log_activity($pdo, (int)$_SESSION['user']['id'], 'rfid_search', "RFID not found: {$rfid}", 'rfid_portal');
		}
	}
}

?>
<?php $pageTitle = 'Students RFID Portal'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>
	<div class="min-h-[calc(100vh-5rem)] w-full flex items-start md:items-center justify-center p-4 md:p-8">
		<div class="w-full max-w-4xl bg-white/80 backdrop-blur rounded-2xl border border-slate-200 shadow-xl p-6 md:p-10">
			<h1 class="text-2xl font-semibold mb-4">Students RFID Portal</h1>
			<p class="text-slate-600 mb-4">Tap a student, faculty, or visitor ID to search records or start a new registration.</p>
			<?php if ($errors): ?>
				<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm p-3">
					<ul class="list-disc pl-5"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
            <form method="post" class="space-y-4" id="rfidSearchForm">
                <label class="block text-slate-700 mb-1">Tap ID (RFID)</label>
                <input type="text" name="rfid" id="rfidSearchInput" autofocus class="w-full rounded-xl bg-white border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 px-4 py-3 text-slate-800" placeholder="Tap ID here" />
            </form>
			<?php if ($result): ?>
				<?php if ($result['exists']): $s=$result['student']; ?>
					<div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
						<p class="font-semibold text-emerald-800">Record found:</p>
						<p class="text-slate-700">Name: <?= htmlspecialchars($s['name']) ?> · Level: <?= htmlspecialchars($s['level']) ?></p>
						<div class="mt-3 flex gap-2">
							<a class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition-colors" href="patient_view.php?id=<?= (int)$s['id'] ?>&type=student">View Patient</a>
							<a class="px-4 py-2 rounded-lg bg-slate-600 text-white hover:bg-slate-700 transition-colors" href="student_form.php?id=<?= (int)$s['id'] ?>">Edit Record</a>
						</div>
					</div>
				<?php else: ?>
                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
						<p class="font-semibold text-amber-800">No record found for this RFID.</p>
                        <button class="mt-2 inline-block px-4 py-2 rounded-lg bg-emerald-600 text-white" id="roleChooseBtn" type="button">Register</button>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
    <!-- Role Select Modal -->
    <div id="roleModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-semibold">Who is registering?</h2>
            <p class="text-slate-600 mb-3">Choose the correct form for faster data entry.</p>
            <div class="grid grid-cols-2 gap-3">
                <a id="roleStudent" class="px-4 py-3 rounded-lg bg-sky-600 text-white text-center" href="#">Student</a>
                <a id="roleFaculty" class="px-4 py-3 rounded-lg bg-indigo-600 text-white text-center" href="#">Faculty</a>
            </div>
            <div class="mt-4 text-right">
                <button id="roleCancel" class="px-4 py-2 rounded-lg border border-slate-300">Cancel</button>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/partials/footer.php'; ?>


