<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

if (empty($_SESSION['user'])) { return; }
?>
<aside id="appSidebar" class="fixed top-20 left-0 z-30 h-[calc(100vh-5rem)] w-72 sm:w-80 bg-white/90 backdrop-blur-md border-r border-clinic-tea/20 shadow-xl transition-transform duration-300 ease-in-out -translate-x-full" data-sidebar-state="hidden">
	<nav class="p-4 sm:p-6 space-y-2 sm:space-y-3">
		<div class="mb-6">
			<h3 class="text-sm font-comfortaa font-semibold text-clinic-dark/60 uppercase tracking-wider">Navigation</h3>
		</div>
		
		<!-- DASHBOARD Section -->
		<div class="mb-4">
			<div class="space-y-2">
				<a href="../admin/dashboard.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-blue/10 border border-clinic-blue/20 text-clinic-blue hover:bg-clinic-blue/20 hover:border-clinic-blue/40 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-blue/20 flex items-center justify-center group-hover:bg-clinic-blue/30 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Dashboard</span>
				</a>
			</div>
		</div>
		
		<!-- PATIENTS Section -->
		<div class="mb-4">
			<h4 class="text-xs font-comfortaa font-semibold text-clinic-dark/50 uppercase tracking-wider mb-3">PATIENTS</h4>
			<div class="space-y-2">
				<a href="../rfid/rfid_portal.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-blue/10 hover:border-clinic-blue/30 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-blue/10 flex items-center justify-center group-hover:bg-clinic-blue/20 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Search</span>
				</a>
			</div>
		</div>
		
		<!-- REGISTRATION Section -->
		<div class="mb-4">
			<h4 class="text-xs font-comfortaa font-semibold text-clinic-dark/50 uppercase tracking-wider mb-3">REGISTRATION</h4>
			<div class="space-y-2">
				<a href="../patients/student_form.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-tea/20 hover:border-clinic-tea/40 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-tea/20 flex items-center justify-center group-hover:bg-clinic-tea/30 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Register Student</span>
				</a>
				<a href="../patients/faculty_form.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-vanilla/30 hover:border-clinic-vanilla/40 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-vanilla/30 flex items-center justify-center group-hover:bg-clinic-vanilla/40 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Register Faculty</span>
				</a>
			</div>
		</div>
		
		<!-- MANAGEMENT Section -->
		<div class="mb-4">
			<h4 class="text-xs font-comfortaa font-semibold text-clinic-dark/50 uppercase tracking-wider mb-3">MANAGEMENT</h4>
			<div class="space-y-2">
				<a href="../admin/archive_management.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-dark/5 hover:border-clinic-dark/20 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-dark/5 flex items-center justify-center group-hover:bg-clinic-dark/10 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Archive</span>
				</a>
				<a href="../logs/visitation_logs.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-green/5 hover:border-clinic-green/20 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-green/5 flex items-center justify-center group-hover:bg-clinic-green/10 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Visitation Logs</span>
				</a>
				<a href="../logs/logs.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-purple/5 hover:border-clinic-purple/20 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-purple/5 flex items-center justify-center group-hover:bg-clinic-purple/10 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Activity Logs</span>
				</a>
				<a href="../reports/analytics.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-orange/5 hover:border-clinic-orange/20 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-orange/5 flex items-center justify-center group-hover:bg-clinic-orange/10 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Reports & Analytics</span>
				</a>
				<a href="../admin/settings.php" class="group flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-2 sm:py-3 rounded-2xl bg-clinic-ivory/60 border border-clinic-tea/20 text-clinic-blue hover:bg-clinic-blue/5 hover:border-clinic-blue/20 hover:shadow-lg transition-all duration-300">
					<div class="w-6 h-6 sm:w-8 sm:h-8 rounded-xl bg-clinic-blue/5 flex items-center justify-center group-hover:bg-clinic-blue/10 transition-colors duration-200">
						<svg class="w-3 h-3 sm:w-4 sm:h-4 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
						</svg>
					</div>
					<span class="font-poppins font-medium text-sm sm:text-base">Settings</span>
				</a>
			</div>
		</div>
	</nav>
</aside>
