<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'CARE: Clinic Administration & Records System' ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			theme: {
				extend: {
					colors: {
						brand: {
							light: '#93c5fd',
							DEFAULT: '#3b82f6',
							dark: '#1d4ed8'
						},
						accent: {
							yellow: '#f59e0b',
							red: '#ef4444'
						}
					}
				}
			}
		};
	</script>
	<link rel="stylesheet" href="/styles.css" />
</head>
<body class="min-h-screen bg-gradient-to-br from-white via-sky-50 to-blue-50 text-slate-800">
    <header class="fixed top-0 inset-x-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
        <div class="w-full px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <?php $enableSidebar = (!empty($_SESSION['user']) && ($GLOBALS['showSidebar'] ?? false)); if ($enableSidebar): ?>
                    <button id="sidebarToggle" class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 shadow-sm transition-all duration-200" aria-label="Toggle navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                <?php endif; ?>
				<div class="h-8 w-8 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 ring-2 ring-sky-200/60"></div>
				<div class="font-semibold tracking-wide">CARE<span class="ml-2 text-xs text-slate-500 font-normal">Clinic Administration &amp; Records System</span></div>
			</div>
            <?php $showTopNav = $GLOBALS['showTopNav'] ?? false; if (!empty($_SESSION['user']) && $showTopNav): ?>
                <nav class="hidden md:flex items-center gap-4 text-sm">
                    <a href="dashboard.php" class="hover:text-sky-700 transition">Dashboard</a>
                    <a href="logs.php" class="hover:text-sky-700 transition">Logs</a>
                    <form action="logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <button class="text-red-600 hover:underline">Logout</button>
                    </form>
                </nav>
            <?php endif; ?>
		</div>
	</header>
    <main class="pt-20 pr-0 min-h-screen transition-all duration-300" id="mainContent">
        <?php if ($enableSidebar) { include __DIR__ . '/sidebar.php'; } ?>
        <div class="w-full">

