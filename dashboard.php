<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

require_admin_auth();

$user = $_SESSION['user'];
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
?>
<?php $pageTitle = 'Dashboard'; $showTopNav = true; $showSidebar = true; include __DIR__ . '/partials/header.php'; ?>
    <div class="min-h-[calc(100vh-5rem)] flex flex-col gap-12 px-4 md:px-8">
		<!-- Popup notifications container -->
		<div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

		<!-- Hero: centered at the top -->
		<div class="pt-10 text-center">
			<h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">WELCOME <?= htmlspecialchars(strtoupper($user['name'])) ?></h1>
			<p class="mt-2 text-slate-600 text-base sm:text-lg">We Care, You Care — enabling secure, efficient, and compassionate clinic operations.</p>
		</div>

		<!-- Section title and description -->
        <div class="w-full px-0 md:px-6">
			<h2 class="text-2xl font-semibold">What would you like to manage today?</h2>
			<p class="mt-1 text-slate-600">Choose a category below to access records and tools.</p>
		</div>


		<!-- Glassy, larger cards in a single row on large screens (wrap on small screens) -->
		<div class="w-full">
			<div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5">
				<a href="school_listing.php?level=Pre-school&type=students" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">🎒</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">Pre‑school</div>
					<div class="mt-1 text-sm text-slate-600">Students</div>
				</a>
				<a href="school_listing.php?level=Elementary&type=students" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">📚</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">Elementary</div>
					<div class="mt-1 text-sm text-slate-600">Students</div>
				</a>
				<a href="school_listing.php?level=High School&type=students" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">🏫</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">High School</div>
					<div class="mt-1 text-sm text-slate-600">Students</div>
				</a>
				<a href="school_listing.php?level=Senior High School&type=students" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">🎓</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">Senior HS</div>
					<div class="mt-1 text-sm text-slate-600">Students</div>
				</a>
				<a href="school_listing.php?level=College&type=students" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">🏛️</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">College</div>
					<div class="mt-1 text-sm text-slate-600">Students</div>
				</a>
				<a href="faculty_listing.php" class="w-full h-56 rounded-3xl border border-white/40 bg-white/30 backdrop-blur shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 flex flex-col items-center justify-center group">
					<div class="text-3xl group-hover:scale-110 transition-transform">👩‍🏫</div>
					<div class="mt-2 text-xl font-semibold text-slate-800">Faculty</div>
					<div class="mt-1 text-sm text-slate-600">Staff</div>
				</a>
			</div>
		</div>
	</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
// Popup notification system
function showNotification(message, type = 'success', duration = 5000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'notification-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    notification.className = `transform transition-all duration-300 ease-in-out translate-x-full opacity-0 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 p-4 ${
        type === 'success' ? 'border-emerald-500' : 
        type === 'error' ? 'border-red-500' : 
        type === 'warning' ? 'border-yellow-500' : 
        'border-blue-500'
    }`;
    
    // Create content
    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="text-lg">
                    ${type === 'success' ? '✅' : 
                      type === 'error' ? '❌' : 
                      type === 'warning' ? '⚠️' : 
                      'ℹ️'}
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-slate-800">${message}</p>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button class="close-btn text-slate-400 hover:text-slate-600 focus:outline-none">
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
    showNotification('Registration completed successfully! The record has been saved.', 'success', 5000);
});
<?php endif; ?>

// Test function - you can call this from browser console
window.testNotification = function() {
    showNotification('Test notification - click X to close', 'success', 0);
};
</script>


