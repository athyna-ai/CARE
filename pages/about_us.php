<?php
// =============================================================================
// ABOUT US PAGE - CONTENT EDITING GUIDE
// =============================================================================
// 
// ⚠️  DO NOT EDIT THE PHP CODE BELOW (Lines 1-16) ⚠️
// This section handles security, authentication, and page setup
// 
// ✅ SAFE TO EDIT: HTML CONTENT SECTION (Lines 18-45)
// You can safely edit the content between the <div> tags
// 
// ⚠️  DO NOT EDIT: FOOTER INCLUDE (Line 47) ⚠️
// This includes the page footer
//
// =============================================================================

declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Include security breach detection
require_once __DIR__ . '/../security_breach_detector.php';

// REQUIRE ADMIN AUTHENTICATION
require_admin_auth();

$pageTitle = 'About Us';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<!-- ============================================================================= -->
<!-- ✅ SAFE TO EDIT: HTML CONTENT SECTION STARTS HERE -->
<!-- You can edit everything between this comment and the "ENDS HERE" comment below -->
<!-- ============================================================================= -->

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory/30 via-white to-clinic-tea/20 pt-24 pb-8 px-4">
    <div class="max-w-2xl mx-auto text-center flex flex-col justify-center min-h-[calc(100vh-8rem)]">
        <!-- Coming Soon Card -->
        <div class="bg-white/80 backdrop-blur rounded-2xl border border-clinic-tea/20 shadow-xl p-12">
            <!-- Coming Soon Icon -->
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-clinic-blue/10 to-clinic-tea/10 rounded-3xl mb-6">
                <svg class="w-10 h-10 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Coming Soon Text -->
            <h1 class="text-3xl font-bold text-clinic-dark mb-4">About Us</h1>
            <h2 class="text-2xl font-semibold text-clinic-blue mb-6">Coming Soon</h2>
            <p class="text-clinic-blue/70 text-lg mb-8">
                This page is under construction. Please check back later.
            </p>

            <!-- Back Button -->
            <a href="../admin/dashboard.php" class="inline-flex items-center gap-2 px-6 py-3 bg-clinic-blue text-white rounded-xl hover:bg-clinic-tea transition-colors duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- ============================================================================= -->
<!-- ✅ SAFE TO EDIT: HTML CONTENT SECTION ENDS HERE -->
<!-- ============================================================================= -->

<!-- ⚠️  DO NOT EDIT: FOOTER INCLUDE BELOW ⚠️ -->
<?php include __DIR__ . '/../partials/footer.php'; ?>
