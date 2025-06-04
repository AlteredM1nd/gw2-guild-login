<?php
/**
 * PHPStan bootstrap file to define constants for static analysis
 */

// Define plugin constants that PHPStan needs
if (!defined('GW2_GUILD_LOGIN_VERSION')) {
    define('GW2_GUILD_LOGIN_VERSION', '2.6.4');
}

if (!defined('GW2_GUILD_LOGIN_FILE')) {
    define('GW2_GUILD_LOGIN_FILE', __DIR__ . '/gw2-guild-login.php');
}

if (!defined('GW2_GUILD_LOGIN_DIR')) {
    define('GW2_GUILD_LOGIN_DIR', __DIR__ . '/');
}

if (!defined('GW2_GUILD_LOGIN_URL')) {
    define('GW2_GUILD_LOGIN_URL', 'https://example.com/wp-content/plugins/gw2-guild-login/');
}

// Define WordPress constants that might be missing during analysis
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}
