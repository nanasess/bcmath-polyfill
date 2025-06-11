<?php
/**
 * Simple autoloader for PHP 5.4+ without Composer
 */

// Register autoloader
spl_autoload_register(function ($class) {
    // Check if this is a bcmath_compat class
    if (strpos($class, 'bcmath_compat\\') === 0) {
        $file = __DIR__ . '/src/' . str_replace('bcmath_compat\\', '', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Check if bcmath extension is loaded
if (!extension_loaded('bcmath')) {
    // Load our bcmath polyfill
    require_once __DIR__ . '/lib/bcmath.php';
}

// Try to load phpseclib
// First check if we have composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Already loaded by test runner, do nothing
}
// Try phpseclib 3.x structure
elseif (file_exists(__DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger.php')) {
    require_once __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/bootstrap.php';
} 
// Try phpseclib 2.x structure
elseif (file_exists(__DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger.php')) {
    // phpseclib 2.x - set up include path
    $phpseclibPath = __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib';
    set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclibPath);
    
    // Load required files for phpseclib 2.x
    require_once $phpseclibPath . '/Math/BigInteger.php';
}
// Fallback to direct file inclusion for installed packages
else {
    // Look for composer vendor directory
    $vendorDir = __DIR__ . '/vendor';
    if (is_dir($vendorDir)) {
        // Try to find phpseclib in vendor
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($vendorDir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'BigInteger.php') {
                $path = $file->getPath();
                if (strpos($path, 'phpseclib') !== false && strpos($path, 'Math') !== false) {
                    $basePath = dirname(dirname($path));
                    set_include_path(get_include_path() . PATH_SEPARATOR . $basePath);
                    
                    // Register autoloader for this path
                    spl_autoload_register(function ($class) use ($basePath) {
                        $file = $basePath . '/' . str_replace('\\', '/', $class) . '.php';
                        if (file_exists($file)) {
                            require_once $file;
                        }
                    });
                    break;
                }
            }
        }
    }
}