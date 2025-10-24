<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - CARE System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-clinic-blue to-clinic-tea shadow-2xl flex items-center justify-center">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h1 class="text-6xl font-bold text-clinic-dark mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-clinic-dark mb-4">Page Not Found</h2>
        <p class="text-clinic-dark/70 mb-8 max-w-md mx-auto">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <a href="/Care/" class="inline-block bg-clinic-blue text-white px-8 py-3 rounded-xl hover:bg-clinic-tea transition-colors">
            Return to Home
        </a>
    </div>
</body>
</html>
