<?php
// PHPUnit bootstrap file for GW2 Guild Login

// Ensure tests run in a safe environment
define('WP_ENV', 'testing');

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Set up WordPress test environment if needed
// (Assumes WP test suite is installed for integration tests)

// Polyfill wp_rand for non-WordPress environments
if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = null) {
        if ($max === null) {
            $max = mt_getrandmax();
        }
        return random_int($min, $max);
    }
}
