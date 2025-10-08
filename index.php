<?php
declare(strict_types=1);
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/helpers.php';

// Include security breach detection
require_once __DIR__ . '/security_breach_detector.php';

// Additional SQL injection check and logging
$query_string = $_SERVER['QUERY_STRING'] ?? '';
if (!empty($query_string)) {
    $sql_patterns = [
        'union.*select', 'drop.*table', 'insert.*into', 'delete.*from',
        'update.*set', 'create.*table', 'alter.*table', 'exec.*\(',
        'load_file', 'information_schema', 'or.*1.*=.*1', 'and.*1.*=.*1'
    ];
    
    foreach ($sql_patterns as $pattern) {
        if (preg_match('/' . $pattern . '/i', $query_string)) {
            // Log the attempt
            logSecurityBreach('SQL_INJECTION_ATTEMPT', 'SQL injection attempt detected in query string', [
                'malicious_query' => $query_string,
                'pattern_matched' => $pattern,
                'url' => $_SERVER['REQUEST_URI']
            ]);
            
            // Show 403 error
            http_response_code(403);
            die('403 Forbidden - Security violation detected');
        }
    }
}

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

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla">
    <!-- Hero Section -->
    <div class="min-h-screen flex items-center justify-center p-4">
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
        </div>
    </div>

    <!-- About Us Section -->
    <div class="py-20 bg-white/50 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-comfortaa font-bold text-clinic-dark mb-6">
                    About Our School
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-clinic-blue to-clinic-tea mx-auto rounded-full"></div>
            </div>
            
            <div class="space-y-12">
                <div class="text-center">
                    <h3 class="text-3xl font-semibold text-clinic-dark mb-6">
                        Our Lady of the Sacred Heart Inc.
                    </h3>
                    <p class="text-lg text-clinic-dark/70 leading-relaxed max-w-4xl mx-auto">
                        Our school clinic plays a vital role in ensuring the health and safety of our students and faculty. The CARE (Clinic Administration of Records System) was developed to modernize and streamline our clinic operations, making healthcare management more efficient and accessible for everyone in our school community.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Vision Card -->
                    <div class="bg-gradient-to-br from-clinic-blue/10 to-clinic-tea/10 rounded-3xl p-8 shadow-xl">
                        <div class="text-center">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-clinic-blue to-clinic-tea flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </div>
                            <h4 class="text-2xl font-semibold text-clinic-dark mb-4">Vision</h4>
                            <p class="text-clinic-dark/70 italic leading-relaxed">
                                "A diocesan Catholic school community that is founded on the Oneness of Heart of Jesus and Mary dedicated to integral human development for a sustainable future."
                            </p>
                        </div>
                    </div>

                    <!-- Mission Card -->
                    <div class="bg-gradient-to-br from-clinic-tea/10 to-clinic-vanilla/20 rounded-3xl p-8 shadow-xl">
                        <div class="text-center">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-clinic-tea to-clinic-vanilla flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-2xl font-semibold text-clinic-dark mb-4">Mission</h4>
                            <div class="text-clinic-dark/70 text-left space-y-3">
                                <p class="italic leading-relaxed">
                                    "Inspired by and devoted to the Oneness of Heart of Jesus and Mary, OLSHCO is in mission to:"
                                </p>
                                <ol class="space-y-2 text-sm">
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">1.</span>
                                        <span>nurture a strong program on Christian formation;</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">2.</span>
                                        <span>foster a 21st-century learning environment; and</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">3.</span>
                                        <span>support programs and initiatives for social transformation.</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Developers Section -->
    <div class="py-20 bg-gradient-to-br from-clinic-tea/5 to-clinic-blue/5">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-comfortaa font-bold text-clinic-dark mb-6">
                    Meet Our Development Team
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-clinic-blue to-clinic-tea mx-auto rounded-full"></div>
                <p class="text-lg text-clinic-dark/70 mt-6 max-w-3xl mx-auto">
                    The talented developers behind the CARE system, dedicated to creating innovative solutions for our school's healthcare management needs.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Developer 1 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-blue to-clinic-tea flex items-center justify-center">
                        <span class="text-white font-bold text-xl">HM</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Hannah Athena A. Mauricio</h3>
                    <p class="text-clinic-dark/60 text-sm">Lead Developer</p>
                </div>

                <!-- Developer 2 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-tea to-clinic-vanilla flex items-center justify-center">
                        <span class="text-white font-bold text-xl">JL</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Jeremae L. Lalo</h3>
                    <p class="text-clinic-dark/60 text-sm">Backend Developer</p>
                </div>

                <!-- Developer 3 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-vanilla to-clinic-blue flex items-center justify-center">
                        <span class="text-white font-bold text-xl">SO</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Shanael Angelyn N. Orodio</h3>
                    <p class="text-clinic-dark/60 text-sm">Frontend Developer</p>
                </div>

                <!-- Developer 4 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-blue to-clinic-vanilla flex items-center justify-center">
                        <span class="text-white font-bold text-xl">DB</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Dhennis Jhon P. Biag</h3>
                    <p class="text-clinic-dark/60 text-sm">Full Stack Developer</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="py-12 bg-clinic-dark/5">
        <div class="max-w-4xl mx-auto text-center px-4">
            <div class="mb-8">
                <h3 class="text-2xl font-semibold text-clinic-dark mb-4">CARE System</h3>
                <p class="text-clinic-dark/60 mb-6">
                    A comprehensive clinic management solution designed specifically for Our Lady of the Sacred Heart Inc.
                </p>
            </div>
            <div class="flex justify-center items-center gap-8 text-clinic-dark/50 text-sm">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Secure
                </span>
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    HIPAA Compliant
                </span>
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    User-Friendly
                </span>
            </div>
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
/* Smooth scrolling for the entire page */
html {
    scroll-behavior: smooth;
}

/* Float animation for particles */
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

/* Fade in animation for sections */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
}

/* Hover effects for developer cards */
.developer-card {
    transition: all 0.3s ease;
}

.developer-card:hover {
    transform: translateY(-5px);
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #3b82f6, #06b6d4);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #0891b2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .developer-card {
        margin-bottom: 1rem;
    }
    
    .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4 {
        gap: 1rem;
    }
}
</style>

<?php include __DIR__ . '/partials/footer.php'; ?>
