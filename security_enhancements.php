<?php
/**
 * Enhanced Security Headers
 * Add these to your .htaccess or implement in PHP
 */

// Enhanced Security Headers
function setEnhancedSecurityHeaders() {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    
    // HSTS (only for HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Content Security Policy (Enhanced)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: blob:; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none'; " .
           "base-uri 'self'; " .
           "form-action 'self'; " .
           "object-src 'none'; " .
           "upgrade-insecure-requests;";
    
    header("Content-Security-Policy: $csp");
    
    // Remove server information
    header_remove('X-Powered-By');
    header_remove('Server');
}

// Call this function at the start of each page
setEnhancedSecurityHeaders();
?>
