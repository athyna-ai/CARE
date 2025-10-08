<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CARE: Clinic Administration of Records System') ?></title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="geolocation=(), microphone=(), camera=()">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'clinic-blue': '#3971b8',
                        'clinic-tea': '#c8d69b',
                        'clinic-ivory': '#fbfcee',
                        'clinic-vanilla': '#f6e6a5',
                        'clinic-dark': '#343b1b',
                        'clinic-green': '#22c55e',
                        'clinic-purple': '#a855f7',
                        'clinic-red': '#e74c3c'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'comfortaa': ['Comfortaa', 'cursive']
                    }
                }
            }
        }
        
        // Global user state for security monitor
        window.CurrentUser = {
            isLoggedIn: <?= isset($_SESSION['user']) && !empty($_SESSION['user']) ? 'true' : 'false' ?>,
            userId: <?= isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'null' ?>,
            username: <?= isset($_SESSION['user']['username']) ? '"' . addslashes($_SESSION['user']['username']) . '"' : 'null' ?>
        };
        
        // Global CSRF token for session monitor
        window.csrfToken = '<?= csrf_token() ?>';
    </script>
    <!-- Security Live Monitor -->
    <script src="../assets/js/security-live-monitor.js"></script>
    <!-- Session Timeout Monitor -->
    <script src="../assets/js/session-timeout-monitor.js"></script>
    
    <!-- Logout Confirmation Script -->
    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout? You will need to log in again to access the system.');
        }
    </script>
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .font-comfortaa { font-family: 'Comfortaa', cursive; }
    </style>
</head>
<body class="bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla min-h-screen">
    <!-- Top Navigation Bar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-clinic-tea/20 shadow-lg">
        <div class="flex items-center justify-between px-4 py-3 h-20">
            <!-- Left side - Hamburger menu and logo -->
            <div class="flex items-center gap-4">
                <!-- Hamburger Menu Button - Only show when sidebar is enabled -->
                <?php if ($showSidebar ?? false): ?>
                <button id="sidebarToggle" class="p-2 rounded-xl bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue transition-all duration-200 hover:scale-105">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <?php endif; ?>
                
                <!-- Logo and Title -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-lg"></div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-comfortaa font-bold text-clinic-dark">CARE</h1>
                        <p class="text-xs text-clinic-dark/60 font-poppins">Clinic Administration of Records System</p>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Navigation and logout -->
            <div class="flex items-center gap-4">
                <!-- Index button - only show on login page -->
                <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                <a href="../index.php" class="px-4 py-2 bg-clinic-ivory/60 hover:bg-clinic-ivory/80 text-clinic-dark rounded-xl font-poppins font-medium transition-all duration-200 hover:scale-105">
                    Index
                </a>
                <?php endif; ?>
                
                <!-- Dashboard and logout - only show if user is logged in -->
                <?php if (isset($_SESSION['user']) && !empty($_SESSION['user'])): ?>
                <a href="../admin/dashboard.php" class="px-4 py-2 bg-clinic-tea/20 hover:bg-clinic-tea/30 text-clinic-dark rounded-xl font-poppins font-medium transition-all duration-200 hover:scale-105">
                    Dashboard
                </a>
                <a href="../auth/logout.php" onclick="return confirmLogout()" class="px-4 py-2 bg-clinic-blue/10 hover:bg-clinic-blue/20 text-clinic-blue rounded-xl font-poppins font-medium transition-all duration-200 hover:scale-105">
                    Logout
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <?php if ($showSidebar ?? false): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main id="mainContent" class="transition-all duration-300 ease-in-out pt-20 min-h-screen <?= ($showSidebar ?? false) ? 'lg:pl-80' : '' ?>">
        <div class="w-full h-full">
