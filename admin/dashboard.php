<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

require_admin_auth();

// Include security breach detection AFTER authentication
require_once __DIR__ . '/../security_breach_detector.php';

$user = $_SESSION['user'];
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$welcome = isset($_GET['welcome']) ? (int)$_GET['welcome'] : 0;
?>
<?php $pageTitle = 'Dashboard'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/../partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] flex flex-col gap-8 md:gap-16 px-4 md:px-6 lg:px-8">
		<!-- Popup notifications container -->
		<div id="notificationContainer" class="fixed top-20 right-6 z-50 space-y-3"></div>

		<!-- Welcome Message -->
		<?php if ($welcome): ?>
		<div id="welcomeMessage" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 bg-white rounded-xl shadow-2xl border border-clinic-tea/20 p-6 max-w-md mx-4 animate-fadeInUp">
			<div class="flex items-center gap-4">
				<div class="p-3 bg-clinic-green/10 rounded-lg">
					<svg class="w-6 h-6 text-clinic-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
					</svg>
				</div>
				<div class="flex-1">
					<h3 class="text-lg font-semibold text-clinic-dark">Welcome back!</h3>
					<p class="text-sm text-clinic-dark/70">You have successfully logged into CARE CMS</p>
				</div>
				<button onclick="closeWelcomeMessage()" class="p-1 hover:bg-gray-100 rounded-lg transition-colors duration-200">
					<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>
				</button>
			</div>
		</div>
		<?php endif; ?>

		<!-- Hero: centered at the top -->
		<div class="pt-8 md:pt-16 text-center">
			<div class="inline-flex items-center gap-3 mb-4 md:mb-6">
				<div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg"></div>
				<h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-comfortaa font-bold text-clinic-dark">WELCOME</h1>
			</div>
			<h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-comfortaa font-semibold text-clinic-blue mb-4"><?= htmlspecialchars(strtoupper($user['name'])) ?></h2>
			<p class="mt-4 text-clinic-dark/70 text-base sm:text-lg md:text-xl font-poppins max-w-2xl mx-auto px-4">Empowering School Health & Wellness — enabling secure, efficient, and compassionate clinic operations.</p>
		</div>

		<!-- Section title and description -->
        <div class="w-full px-0 md:px-6">
			<h2 class="text-xl sm:text-2xl md:text-3xl font-comfortaa font-semibold text-clinic-dark mb-3">What would you like to manage today?</h2>
			<p class="text-clinic-dark/60 text-base md:text-lg font-poppins">Choose a category below to access records and tools.</p>
		</div>


		<!-- Glassy, larger cards in a single row on large screens (wrap on small screens) -->
		<div class="w-full">
			<div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6 lg:gap-6 xl:gap-8 max-w-none">
				<a href="../patients/school_listing.php?level=Pre-school&type=students" class="group w-full h-48 md:h-56 lg:h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🎒</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Pre‑school</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="../patients/school_listing.php?level=Elementary&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">📚</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Elementary</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="../patients/school_listing.php?level=High School&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🏫</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">High School</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="../patients/school_listing.php?level=Senior High School&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🎓</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Senior HS</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="../patients/school_listing.php?level=College&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🏛️</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">College</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="../patients/faculty_listing.php" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">👩‍🏫</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Faculty</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Staff</div>
				</a>
			</div>
		</div>
		
	</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>
// Notification system - now uses global system from header.php
// The showNotification function is now globally available

// Show success notification if redirected from form
<?php if ($success): ?>
document.addEventListener('DOMContentLoaded', () => {
    showNotification('Registration completed successfully! The record has been saved.', 'success', 2000);
    
    // Clear the success parameter from URL to prevent notification on reload
    const url = new URL(window.location);
    url.searchParams.delete('success');
    window.history.replaceState({}, '', url);
});
<?php endif; ?>

// Test function - you can call this from browser console
window.testNotification = function() {
    showNotification('Test notification - click X to close', 'success', 0);
};

// Welcome message functions
function closeWelcomeMessage() {
    const welcomeMessage = document.getElementById('welcomeMessage');
    if (welcomeMessage) {
        welcomeMessage.style.transition = 'all 0.3s ease-out';
        welcomeMessage.style.opacity = '0';
        welcomeMessage.style.transform = 'translate(-50%, -20px)';
        setTimeout(() => {
            welcomeMessage.remove();
        }, 300);
    }
}

// Auto-hide welcome message after 5 seconds and clean URL
document.addEventListener('DOMContentLoaded', function() {
    const welcomeMessage = document.getElementById('welcomeMessage');
    if (welcomeMessage) {
        // Clean the URL by removing the welcome parameter
        if (window.location.search.includes('welcome=1')) {
            const url = new URL(window.location);
            url.searchParams.delete('welcome');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
        
        setTimeout(() => {
            closeWelcomeMessage();
        }, 5000);
    }
});
</script>


