<?php
// Define WordPress constants for testing
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content' );
}

// Polyfill for get_current_user_id() when running PHPUnit outside WordPress
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return 1; // Test user ID
	}
}

// Polyfill for WordPress options functions
if ( ! function_exists( 'get_option' ) ) {
	/** @var array<string, mixed> $gw2gl_test_options */
	global $gw2gl_test_options;
	$gw2gl_test_options = array();

	function get_option( string $option, mixed $default = false ): mixed {
		global $gw2gl_test_options;
		return isset( $gw2gl_test_options[ $option ] ) ? $gw2gl_test_options[ $option ] : $default;
	}

	function update_option( string $option, mixed $value ): bool {
		global $gw2gl_test_options;
		$gw2gl_test_options[ $option ] = $value;
		return true;
	}

	function delete_option( string $option ): bool {
		global $gw2gl_test_options;
		unset( $gw2gl_test_options[ $option ] );
		return true;
	}
}

// PHPUnit bootstrap file for GW2 Guild Login

// Ensure tests run in a safe environment
define( 'WP_ENV', 'testing' );

// Load Composer autoloader if available
if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require __DIR__ . '/../vendor/autoload.php';
}

// Set up WordPress test environment if needed
// (Assumes WP test suite is installed for integration tests)

// Polyfill set_transient/get_transient/delete_transient for non-WordPress environments
if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Simple in-memory cache for PHPUnit tests.
	 */
	global $gw2gl_test_transients;
	/** @var array<string, mixed> $gw2gl_test_transients */
	$gw2gl_test_transients = array();
	function set_transient( string $key, mixed $value, int $expiration = 0 ): bool {
		global $gw2gl_test_transients;
		/** @var array<string, mixed> $gw2gl_test_transients */
		global $gw2gl_test_transients;
		$gw2gl_test_transients[ $key ] = $value;
		return true;
	}
	function get_transient( string $key ): mixed {
		global $gw2gl_test_transients;
		/** @var array<string, mixed> $gw2gl_test_transients */
		global $gw2gl_test_transients;
		return isset( $gw2gl_test_transients[ $key ] ) ? $gw2gl_test_transients[ $key ] : false;
	}
	function delete_transient( string $key ): bool {
		global $gw2gl_test_transients;
		/** @var array<string, mixed> $gw2gl_test_transients */
		global $gw2gl_test_transients;
		unset( $gw2gl_test_transients[ $key ] );
		return true;
	}
}

// Polyfill wp_rand for non-WordPress environments
if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( int $min = 0, ?int $max = null ): int {
		if ( $max === null ) {
			$max = mt_getrandmax();
		}
		return random_int( $min, $max );
	}
}

// Polyfill wp_generate_password for non-WordPress environments
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars ) {
			$chars .= '!@#$%^&*()';
		}
		if ( $extra_special_chars ) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}

		$password = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$password .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
		}
		return $password;
	}
}

require_once __DIR__ . '/../includes/class-gw2-api-cache-utils.php';
