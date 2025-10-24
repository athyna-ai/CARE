<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - CARE System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-clinic-ivory via-white to-clinic-vanilla min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gradient-to-br from-clinic-red to-red-400 shadow-2xl flex items-center justify-center">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-6xl font-bold text-clinic-dark mb-4">500</h1>
        <h2 class="text-2xl font-semibold text-clinic-dark mb-4">Server Error</h2>
        <p class="text-clinic-dark/70 mb-8 max-w-md mx-auto">
            Something went wrong on our end. Please try again later.
        </p>
        <a href="/Care/" class="inline-block bg-clinic-blue text-white px-8 py-3 rounded-xl hover:bg-clinic-tea transition-colors">
            Return to Home
        </a>
    </div>
</body>
</html>
