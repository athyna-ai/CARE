<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$pageTitle = 'Welcome';
$showTopNav = false;
$showSidebar = false;
include __DIR__ . '/partials/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla flex items-center justify-center p-4">
    <div class="max-w-4xl mx-auto text-center">
        <!-- Logo and Brand -->
        <div class="mb-12">
            <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-2xl flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-comfortaa font-bold text-clinic-dark mb-4">
                CARE
            </h1>
            <p class="text-xl md:text-2xl text-clinic-dark/70 font-poppins max-w-2xl mx-auto mb-2">
                Clinic Administration of Records System
            </p>
            <p class="text-lg text-clinic-dark/60 font-poppins italic">
                A School Clinic management Information system
            </p>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-clinic-blue/10 flex items-center justify-center">
                    <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-clinic-dark mb-2">Patient Management</h3>
                <p class="text-clinic-dark/60">Comprehensive patient registration and medical record management</p>
            </div>

            <div class="bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-clinic-tea/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-clinic-dark mb-2">Medical Records</h3>
                <p class="text-clinic-dark/60">Digital medical history and form management system</p>
            </div>

            <div class="bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-clinic-vanilla/30 flex items-center justify-center">
                    <svg class="w-8 h-8 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-clinic-dark mb-2">Analytics & Logs</h3>
                <p class="text-clinic-dark/60">Comprehensive reporting and activity tracking</p>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="space-y-6">
            <h2 class="text-2xl md:text-3xl font-semibold text-clinic-dark mb-4">
                Ready to access the system?
            </h2>
            <p class="text-lg text-clinic-dark/60 mb-8">
                Sign in to manage patient records, medical forms, and clinic operations
            </p>
            
            <div class="flex justify-center">
                <a href="auth/login.php" class="inline-block px-12 py-5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold text-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 flex items-center gap-4 border-2 border-blue-500">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="text-white font-bold">ENTER SYSTEM</span>
                </a>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-16 pt-8 border-t border-clinic-tea/20">
            <p class="text-clinic-dark/50 text-sm">
                Secure • HIPAA Compliant • User-Friendly
            </p>
        </div>
    </div>
</div>

<!-- Floating particles animation -->
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-clinic-blue/5 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-clinic-tea/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-clinic-vanilla/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float:nth-child(2) {
    animation-delay: 2s;
}

.animate-float:nth-child(3) {
    animation-delay: 4s;
}
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
