<?php
/**
 * File Permission Security Script
 * Sets proper file permissions for security
 */

echo "🔒 Setting secure file permissions...\n\n";

// Define secure permissions
$filePermissions = 0644;  // rw-r--r--
$dirPermissions = 0755;   // rwxr-xr-x
$secureFilePermissions = 0600;  // rw-------
$secureDirPermissions = 0700;   // rwx------

// Files that need secure permissions
$secureFiles = [
    'core/config.php',
    'care_cms.sql',
    '.htaccess',
    'security_breach_detector.php',
    'security_logger.php'
];

// Directories that need secure permissions
$secureDirs = [
    'logs',
    'core',
    'config'
];

// Set secure file permissions
foreach ($secureFiles as $file) {
    if (file_exists($file)) {
        if (chmod($file, $secureFilePermissions)) {
            echo "✅ Secured file: $file\n";
        } else {
            echo "❌ Failed to secure file: $file\n";
        }
    }
}

// Set secure directory permissions
foreach ($secureDirs as $dir) {
    if (is_dir($dir)) {
        if (chmod($dir, $secureDirPermissions)) {
            echo "✅ Secured directory: $dir\n";
        } else {
            echo "❌ Failed to secure directory: $dir\n";
        }
    }
}

// Set standard permissions for other files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Skip already secured files
        if (in_array($path, $secureFiles)) continue;
        
        // Set appropriate permissions based on file type
        if (in_array($ext, ['php', 'html', 'css', 'js', 'json', 'txt', 'md'])) {
            chmod($path, $filePermissions);
        }
    } elseif ($file->isDir()) {
        $path = $file->getPathname();
        if (!in_array($path, $secureDirs)) {
            chmod($path, $dirPermissions);
        }
    }
}

echo "\n🔒 File permissions secured!\n";
echo "📋 Summary:\n";
echo "   - Config files: 600 (rw-------)\n";
echo "   - Log directories: 700 (rwx------)\n";
echo "   - Other files: 644 (rw-r--r--)\n";
echo "   - Other directories: 755 (rwxr-xr-x)\n";
?>
