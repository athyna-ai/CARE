<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();

$user = $_SESSION['user'];
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
?>
<?php $pageTitle = 'Dashboard'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] flex flex-col gap-8 md:gap-16 px-4 md:px-6 lg:px-8">
		<!-- Popup notifications container -->
		<div id="notificationContainer" class="fixed top-32 right-6 z-50 space-y-3"></div>

		<!-- Hero: centered at the top -->
		<div class="pt-8 md:pt-16 text-center">
			<div class="inline-flex items-center gap-3 mb-4 md:mb-6">
				<div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg"></div>
				<h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-comfortaa font-bold text-clinic-dark">WELCOME</h1>
			</div>
			<h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-comfortaa font-semibold text-clinic-blue mb-4"><?= htmlspecialchars(strtoupper($user['name'])) ?></h2>
			<p class="mt-4 text-clinic-dark/70 text-base sm:text-lg md:text-xl font-poppins max-w-2xl mx-auto px-4">We Care, You Care — enabling secure, efficient, and compassionate clinic operations.</p>
		</div>

		<!-- Section title and description -->
        <div class="w-full px-0 md:px-6">
			<h2 class="text-xl sm:text-2xl md:text-3xl font-comfortaa font-semibold text-clinic-dark mb-3">What would you like to manage today?</h2>
			<p class="text-clinic-dark/60 text-base md:text-lg font-poppins">Choose a category below to access records and tools.</p>
		</div>


		<!-- Glassy, larger cards in a single row on large screens (wrap on small screens) -->
		<div class="w-full">
			<div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6 lg:gap-6 xl:gap-8 max-w-none">
				<a href="school_listing.php?level=Pre-school&type=students" class="group w-full h-48 md:h-56 lg:h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🎒</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Pre‑school</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="school_listing.php?level=Elementary&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">📚</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Elementary</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="school_listing.php?level=High School&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🏫</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">High School</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="school_listing.php?level=Senior High School&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🎓</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Senior HS</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="school_listing.php?level=College&type=students" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">🏛️</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">College</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Students</div>
				</a>
				<a href="faculty_listing.php" class="group w-full h-48 md:h-56 lg:h-64 rounded-3xl border border-clinic-tea/20 bg-white/70 backdrop-blur-md shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 flex flex-col items-center justify-center relative overflow-hidden">
					<div class="absolute inset-0 bg-gradient-to-br from-clinic-ivory/50 to-clinic-vanilla/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
					<div class="relative z-10 text-5xl group-hover:scale-110 transition-transform duration-300">👩‍🏫</div>
					<div class="relative z-10 mt-4 text-2xl font-comfortaa font-semibold text-clinic-dark group-hover:text-clinic-blue transition-colors duration-300">Faculty</div>
					<div class="relative z-10 mt-2 text-sm font-poppins text-clinic-dark/60 uppercase tracking-wider">Staff</div>
				</a>
			</div>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// Popup notification system
function showNotification(message, type = 'success', duration = 2000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    notification.className = `transform transition-all duration-500 ease-out translate-x-full opacity-0 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-clinic-tea/20 p-6 ${
        type === 'success' ? 'border-l-4 border-l-clinic-tea' : 
        type === 'error' ? 'border-l-4 border-l-red-500' : 
        type === 'warning' ? 'border-l-4 border-l-clinic-vanilla' : 
        'border-l-4 border-l-clinic-blue'
    }`;
    
    // Create content
    notification.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-xl bg-clinic-ivory/60 flex items-center justify-center text-lg">
                    ${type === 'success' ? '✅' : 
                      type === 'error' ? '❌' : 
                      type === 'warning' ? '⚠️' : 
                      'ℹ️'}
                </div>
            </div>
            <div class="flex-1">
                <p class="text-clinic-dark font-poppins font-medium">${message}</p>
            </div>
            <div class="flex-shrink-0">
                <button class="close-btn w-8 h-8 rounded-xl bg-clinic-dark/5 hover:bg-clinic-dark/10 text-clinic-dark/60 hover:text-clinic-dark transition-all duration-200 flex items-center justify-center">
                    <span class="sr-only">Close</span>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Add event listener to close button
    const closeBtn = notification.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        closeNotification(closeBtn);
    });
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(closeBtn);
        }, duration);
    }
}

function closeNotification(button) {
    // Find the notification container (the outermost div with transform class)
    let notification = button;
    while (notification && !notification.classList.contains('transform')) {
        notification = notification.parentElement;
    }
    
    if (!notification) {
        console.log('Notification not found');
        return;
    }
    
    console.log('Closing notification:', notification.id);
    
    // Animate out
    notification.style.transition = 'all 0.3s ease-in-out';
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    // Remove after animation
    setTimeout(() => {
        if (notification && notification.parentNode) {
            console.log('Removing notification:', notification.id);
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

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
</script>


