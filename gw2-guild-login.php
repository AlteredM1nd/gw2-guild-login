<?php
/**
 * GW2 Guild Login Plugin
 *
 * Plugin Name:       GW2 Guild Login
 * Plugin URI:        https://github.com/AlteredM1nd/gw2-guild-login
 * Description:       Allows users to log in using their GW2 API key to verify guild membership with WordPress user integration.
 * Version:           2.6.4
 * Author:            AlteredM1nd
 * Author URI:        https://github.com/AlteredM1nd
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gw2-guild-login
 * Domain Path:       /languages
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

use GW2GuildLogin\GW2_2FA_Handler;
use GW2GuildLogin\GW2_Login_Shortcode;

/** @var array<string,string> $plugin_data */
/** @var string $plugin_version */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define ABSPATH if not defined (for unit tests).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Load WordPress helper functions if not already loaded.
if ( ! function_exists( 'get_plugin_data' ) ) {
	if ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	} else {
		error_log( 'plugin.php not found. Plugin data retrieval skipped.' );
	}
}

// Get plugin data.
$plugin_data    = get_plugin_data( __FILE__ );
$plugin_version = ( is_array( $plugin_data ) && isset( $plugin_data['Version'] ) && is_string( $plugin_data['Version'] ) ) ? $plugin_data['Version'] : 'unknown';
// Define plugin version.
if ( ! defined( 'GW2_GUILD_LOGIN_VERSION' ) ) {
	define( 'GW2_GUILD_LOGIN_VERSION', $plugin_version );
}

// Define plugin paths.
if ( ! defined( 'GW2_GUILD_LOGIN_FILE' ) ) {
	define( 'GW2_GUILD_LOGIN_FILE', __FILE__ );
}

if ( ! defined( 'GW2_GUILD_LOGIN_DIR' ) ) {
	define( 'GW2_GUILD_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'GW2_GUILD_LOGIN_URL' ) ) {
	define( 'GW2_GUILD_LOGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME' ) ) {
	define( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// 2FA Constants.
define( 'GW2_2FA_TABLE_SECRETS', 'gw2_2fa_secrets' );
define( 'GW2_2FA_TABLE_DEVICES', 'gw2_2fa_trusted_devices' );
define( 'GW2_2FA_COOKIE', 'gw2_2fa_trusted_device' );
define( 'GW2_2FA_COOKIE_EXPIRY', 30 * 24 * 60 * 60 ); // 30 days in seconds.

// Backward compatibility.
define( 'GW2_GUILD_LOGIN_PLUGIN_DIR', GW2_GUILD_LOGIN_DIR );
define( 'GW2_GUILD_LOGIN_PLUGIN_URL', GW2_GUILD_LOGIN_URL );

if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME' ) ) {
	define( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Backward compatibility.
if ( ! defined( 'GW2_GUILD_LOGIN_ABSPATH' ) ) {
	define( 'GW2_GUILD_LOGIN_ABSPATH', plugin_dir_path( __FILE__ ) );
}

// Include required files.
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-api.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-session-handler.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-user-handler.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-user-dashboard.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-password-reset.php'; // Magic link password reset.

// Load the core plugin class.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-gw2-guild-login.php' ) ) {
	require plugin_dir_path( __FILE__ ) . 'includes/class-gw2-guild-login.php';
}

// Load the 2FA handler class.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/GW2_2FA_Handler.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/GW2_2FA_Handler.php';
}

// Load the user dashboard.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-gw2-user-dashboard.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gw2-user-dashboard.php';
}

// Load the guild ranks functionality.
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-guild-ranks.php';

// Load the admin settings class.
require_once GW2_GUILD_LOGIN_DIR . 'includes/admin/class-gw2-guild-login-admin.php';

// Load the admin menu.
require_once GW2_GUILD_LOGIN_DIR . 'includes/admin/class-gw2-admin-menu.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-2fa-login.php';

// Include database migration if needed.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-cli.php';
}
require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-user-dashboard.php';

// Load the main plugin class.
if ( ! class_exists( 'GW2_Guild_Login' ) ) {
	if ( file_exists( GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-guild-login.php' ) ) {
		require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-guild-login.php';
	}
}

/**
 * Returns the main instance of GW2_Guild_Login
 *
 * @since 1.0.0
 * @return GW2_Guild_Login The main plugin instance
 */
function GW2_Guild_Login(): GW2_Guild_Login { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return GW2_Guild_Login::instance();
}

// Plugin initialization now handled in GW2_Guild_Login class.

// Always register plugin settings for admin pages.
if ( is_admin() ) {
	if ( class_exists( 'GW2_Guild_Login_Admin' ) ) {
		$gw2_admin = GW2_Guild_Login_Admin::instance();
		if ( is_object( $gw2_admin ) ) {
			add_action( 'admin_init', array( $gw2_admin, 'register_settings' ) );
		}
	}
}

// Initialize 2FA
// 2FA initialization now handled in GW2_2FA_Handler class.

/**
 * Initialize 2FA functionality
 */
// 2FA initialization logic now handled in GW2_2FA_Handler class.
// function gw2_2fa_init() {
// Initialize 2FA admin for logged-in users with appropriate capabilities
// if (is_admin() && current_user_can('edit_users')) {
// new GW2_2FA_Admin();
// }
// Always initialize 2FA login handling
// It will handle both logged-in and non-logged-in users appropriately
// new GW2_2FA_Login();
// }

// Handle AJAX requests for 2FA
// AJAX handler for backup codes now handled in GW2_2FA_Handler class.

/**
 * Handle AJAX request to regenerate backup codes
 */
// AJAX handler logic now handled in GW2_2FA_Handler class.

// Activation hook.
register_activation_hook( __FILE__, 'gw2_2fa_activate' );

/**
 * Plugin activation.
 *
 * @return void
 */
function gw2_2fa_activate(): void {
	global $wpdb; /** @var wpdb $wpdb */
	// Create database tables if they don't exist.
	$charset_collate = $wpdb->get_charset_collate();

	// 2FA Secrets table
	$table_name = $wpdb->prefix . 'gw2_2fa_secrets';
	$sql        = "CREATE TABLE $table_name (
        user_id bigint(20) UNSIGNED NOT NULL,
        secret varchar(255) NOT NULL,
        backup_codes text,
        is_enabled tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (user_id)
    ) $charset_collate;";

	// Trusted devices table.
	$table_name = $wpdb->prefix . 'gw2_2fa_trusted_devices';
	$sql       .= "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        device_name varchar(100) NOT NULL,
        device_token varchar(64) NOT NULL,
        last_used datetime NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        UNIQUE KEY device_token (device_token)
    ) $charset_collate;";

	if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// Add version to options for future updates.
	add_option( 'gw2_2fa_db_version', '1.0' );
}

// Add settings link to plugin actions.
// Plugin action links now handled in GW2_Guild_Login_Admin or GW2_2FA_Handler class.

// Plugin initialization logic now handled in GW2_Guild_Login class.

// Shortcode registration and rendering now handled in GW2_Login_Shortcode class.


/**
 * Enqueue frontend styles and scripts
 *
 * @return void
 */
function gw2_login_enqueue_assets(): void {
	// Guild rank styles.
	wp_register_style( 'gw2-guild-ranks', plugins_url( 'assets/css/guild-ranks.css', __FILE__ ), array(), GW2_GUILD_LOGIN_VERSION );
	wp_enqueue_style( 'gw2-guild-ranks' );

	// Only load on pages with the shortcode.
	global $post;
	$post_content = ( is_object( $post ) && isset( $post->post_content ) && is_string( $post->post_content ) ) ? $post->post_content : '';
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post_content, 'gw2_login' ) ) {
		// Register and enqueue the stylesheet.
		wp_register_style(
			'gw2-login-styles',
			plugins_url( 'assets/css/gw2-login.css', __FILE__ ),
			array(),
			GW2_GUILD_LOGIN_VERSION
		);
		wp_enqueue_style( 'gw2-login-styles' );

		// Register and enqueue the JavaScript.
		wp_register_script(
			'gw2-login-script',
			plugins_url( 'assets/js/gw2-login.js', __FILE__ ),
			array( 'jquery' ),
			GW2_GUILD_LOGIN_VERSION,
			true
		);

		// Localize the script with data from PHP.
		wp_localize_script(
			'gw2-login-script',
			'gw2LoginVars',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gw2-ajax-nonce' ),
				'i18n'    => array(
					'error'      => __( 'An error occurred. Please try again.', 'gw2-guild-login' ),
					'connecting' => __( 'Connecting...', 'gw2-guild-login' ),
				),
			)
		);

		wp_enqueue_script( 'gw2-login-script' );
	}
}

/**
 * Handle the login form submission.
 *
 * @return void
 */
function gw2_handle_login_submission(): void {
	// Only process POST requests.
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
	if ( 'POST' !== $request_method ) {
		return;
	}

	// Handle login form submission.
	$gw2_submit_login = isset( $_POST['gw2_submit_login'] );
	$gw2_api_key_raw  = isset( $_POST['gw2_api_key'] ) && is_string( $_POST['gw2_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gw2_api_key'] ) ) : '';
	if ( $gw2_submit_login && is_string( $gw2_api_key_raw ) ) {
		// Verify nonce.
		$gw2_login_nonce = isset( $_POST['gw2_login_nonce'] ) && is_string( $_POST['gw2_login_nonce'] ) ? sanitize_key( wp_unslash( $_POST['gw2_login_nonce'] ) ) : '';
		if ( ! is_string( $gw2_login_nonce ) || ! wp_verify_nonce( $gw2_login_nonce, 'gw2_login_action' ) ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		$api_key         = sanitize_text_field( trim( $gw2_api_key_raw ) );
		$remember        = ! empty( $_POST['rememberme'] );
		$redirect_to_raw = isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) ? sanitize_url( wp_unslash( $_POST['redirect_to'] ) ) : '';
		$redirect_to     = is_string( $redirect_to_raw ) ? esc_url_raw( $redirect_to_raw ) : home_url();

		if ( '' === $api_key ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		// Process the login.
		$user_handler = GW2_Guild_Login()->get_user_handler();
		$result       = is_object( $user_handler ) && method_exists( $user_handler, 'process_login' ) ? $user_handler->process_login( $api_key ) : null;

		if ( is_wp_error( $result ) ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		// Set remember me cookie if needed.
		$user_id = is_array( $result ) && isset( $result['user_id'] ) && ( is_int( $result['user_id'] ) || ( is_string( $result['user_id'] ) && ctype_digit( $result['user_id'] ) ) ) ? (int) $result['user_id'] : 0;
		if ( $remember && $user_id > 0 ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user && is_a( $user, 'WP_User' ) ) {
				/** @phpstan-ignore-next-line */
				wp_set_auth_cookie( $user->ID, $remember );
				/** @phpstan-ignore-next-line */
				wp_set_current_user( $user->ID, $user->user_login );
				/** @phpstan-ignore-next-line */
				do_action( 'wp_login', $user->user_login, $user );
			} else {
				// TODO: Display error message to user (handled in class-based handler).
				return;
			}
		}

		// Set success message.
		$is_new_user  = is_array( $result ) && isset( $result['is_new_user'] ) ? $result['is_new_user'] : false;
		$account_name = is_array( $result ) && isset( $result['account_name'] ) && is_string( $result['account_name'] ) ? $result['account_name'] : '';
		$message      = $is_new_user
			? sprintf( __( 'Welcome to our community, %s! Your account has been created.', 'gw2-guild-login' ), $account_name )
			: sprintf( __( 'Welcome back, %s! You have been logged in successfully.', 'gw2-guild-login' ), $account_name );
	}
}

// Add a shortcode to display a login/logout link (handled in GW2_Login_Shortcode class)
// All login/logout shortcode logic is now handled in GW2_Login_Shortcode class.
