<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

if (empty($_SESSION['user'])) { return; }
?>
<aside id="appSidebar" class="fixed top-16 left-0 z-30 h-[calc(100vh-4rem)] w-72 bg-white border-r border-slate-200 shadow-sm transition-transform duration-300 ease-in-out -translate-x-full" data-sidebar-state="hidden">
	<nav class="p-4 space-y-2">
		<a href="rfid_portal.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100">
			<span class="font-semibold">Search</span>
		</a>
		<a href="student_form.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
			<span class="font-semibold">Register Student</span>
		</a>
		<a href="faculty_form.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
			<span class="font-semibold">Register Faculty</span>
		</a>
		<a href="archive_management.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-orange-50 text-orange-700 hover:bg-orange-100">
			<span class="font-semibold">Archive</span>
		</a>
		<a href="account_settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 text-slate-700 hover:bg-slate-100">
			<span class="font-semibold">Settings</span>
		</a>
	</nav>
</aside>
