<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: auth/login.php');
    exit;
}

// Handle logout confirmation
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Get PDO connection
    $pdo = get_pdo();
    
    // Log the logout activity
    log_activity($pdo, $_SESSION['user']['id'] ?? null, 'User logout', 'User logged out successfully');
    
    // Destroy session
    session_destroy();
    
    // Redirect to login with logout message
    header('Location: auth/login.php?logout=1');
    exit;
}

$pageTitle = "Logout Confirmation";
$showTopNav = false;
$showSidebar = false;
include __DIR__ . '/partials/header.php';
?>

<style>
/* Full screen logout styling */
body {
    margin: 0;
    padding: 0;
    overflow: hidden;
}

/* Hide header completely */
header {
    display: none !important;
}

/* Full screen container */
.full-screen-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f0f4f8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* Ensure content is centered and responsive */
.logout-content {
    width: 100%;
    max-width: 400px;
    padding: 0 1rem;
}
</style>

<div class="full-screen-container">
    <div class="logout-content">
        <!-- Logout Confirmation Modal -->
        <div id="logoutModal" class="bg-white rounded-2xl shadow-2xl border border-clinic-tea/20 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-clinic-red/10 to-clinic-red/5 px-6 py-4 border-b border-clinic-tea/20">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-clinic-red/10 rounded-lg">
                        <svg class="w-6 h-6 text-clinic-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-clinic-dark">Confirm Logout</h2>
                        <p class="text-sm text-clinic-dark/70">Are you sure you want to log out?</p>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-clinic-red/10 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-clinic-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-clinic-dark mb-2">Ready to Sign Out?</h3>
                    <p class="text-clinic-dark/70 text-sm leading-relaxed">
                        You will be logged out of the CARE Clinic Administration System. 
                        Make sure to save any unsaved work before proceeding.
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button onclick="goBack()" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors duration-200">
                        Cancel
                    </button>
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="confirm_logout" value="yes">
                        <button type="submit" class="w-full px-4 py-3 bg-clinic-red hover:bg-clinic-red/80 text-white rounded-lg font-medium transition-colors duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Loading Screen (Hidden by default) -->
        <div id="loadingScreen" class="hidden fixed inset-0 bg-black/90 backdrop-blur-md z-[10000] flex items-center justify-center">
            <div class="bg-white rounded-2xl shadow-2xl p-8 text-center max-w-sm mx-4">
                <!-- Loading Animation -->
                <div class="mb-6">
                    <div class="relative w-20 h-20 mx-auto">
                        <!-- Spinning Circle -->
                        <div class="absolute inset-0 border-4 border-clinic-red/20 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-transparent border-t-clinic-red rounded-full animate-spin"></div>
                        
                        <!-- Center Icon -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-8 h-8 text-clinic-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Text -->
                <h3 class="text-xl font-bold text-clinic-dark mb-2">Logging Out...</h3>
                <p class="text-clinic-dark/70 text-sm mb-4">Please wait while we sign you out securely</p>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div id="progressBar" class="bg-clinic-red h-2 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
                </div>
                
                <!-- Goodbye Message -->
                <div id="goodbyeMessage" class="hidden">
                    <div class="text-clinic-tea text-sm font-medium">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        Thank you for using CARE CMS
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function goBack() {
    // Go back to previous page or dashboard
    if (document.referrer && document.referrer !== window.location.href) {
        window.history.back();
    } else {
        window.location.href = 'admin/dashboard.php';
    }
}

// Handle form submission with loading screen
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loadingScreen = document.getElementById('loadingScreen');
    const progressBar = document.getElementById('progressBar');
    const goodbyeMessage = document.getElementById('goodbyeMessage');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading screen
            loadingScreen.classList.remove('hidden');
            
            // Animate progress bar
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 200);
            
            // Show goodbye message after 2 seconds
            setTimeout(() => {
                goodbyeMessage.classList.remove('hidden');
            }, 2000);
            
            // Complete progress and submit after 3 seconds
            setTimeout(() => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                // Submit the form after showing loading
                setTimeout(() => {
                    form.submit();
                }, 500);
            }, 3000);
        });
    }
});

// Add some visual effects
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animation
    const modal = document.getElementById('logoutModal');
    modal.style.opacity = '0';
    modal.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        modal.style.transition = 'all 0.3s ease-out';
        modal.style.opacity = '1';
        modal.style.transform = 'translateY(0)';
    }, 100);
});
</script>

<style>
/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeInUp {
    animation: fadeInUp 0.3s ease-out;
}

/* Loading screen backdrop blur */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Progress bar animation */
#progressBar {
    transition: width 0.3s ease-out;
}

/* Pulse animation for loading icon */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s infinite;
}
</style>

    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
