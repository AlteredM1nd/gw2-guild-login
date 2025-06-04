<?php
declare(strict_types=1);
use GW2GuildLogin\GW2_2FA_Handler;
use GW2GuildLogin\GW2_Login_Shortcode;

/** @var array<string,string> $plugin_data */
/** @var string $plugin_version */

/**
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
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define ABSPATH if not defined (for unit tests)
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Load WordPress helper functions if not already loaded
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Get plugin data
$plugin_data    = get_plugin_data( __FILE__ );
$plugin_version = ( is_array( $plugin_data ) && isset( $plugin_data['Version'] ) && is_string( $plugin_data['Version'] ) ) ? $plugin_data['Version'] : 'unknown';
// Define plugin version
if ( ! defined( 'GW2_GUILD_LOGIN_VERSION' ) ) {
	define( 'GW2_GUILD_LOGIN_VERSION', $plugin_version );
}

// Define plugin paths
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

// 2FA Constants
define( 'GW2_2FA_TABLE_SECRETS', 'gw2_2fa_secrets' );
define( 'GW2_2FA_TABLE_DEVICES', 'gw2_2fa_trusted_devices' );
define( 'GW2_2FA_COOKIE', 'gw2_2fa_trusted_device' );
define( 'GW2_2FA_COOKIE_EXPIRY', 30 * 24 * 60 * 60 ); // 30 days in seconds

// Backward compatibility
define( 'GW2_GUILD_LOGIN_PLUGIN_DIR', GW2_GUILD_LOGIN_DIR );
define( 'GW2_GUILD_LOGIN_PLUGIN_URL', GW2_GUILD_LOGIN_URL );

if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME' ) ) {
	define( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Backward compatibility
if ( ! defined( 'GW2_GUILD_LOGIN_ABSPATH' ) ) {
	define( 'GW2_GUILD_LOGIN_ABSPATH', plugin_dir_path( __FILE__ ) );
}

// Include required files
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-api.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-session-handler.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-user-handler.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-user-dashboard.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-password-reset.php'; // Magic link password reset

// Load the core plugin class.
require plugin_dir_path( __FILE__ ) . 'includes/class-gw2-guild-login.php';

// Load the 2FA handler class
require_once plugin_dir_path( __FILE__ ) . 'includes/GW2_2FA_Handler.php';

// Load the user dashboard
require_once plugin_dir_path( __FILE__ ) . 'includes/class-gw2-user-dashboard.php';

// Load the guild ranks functionality
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-guild-ranks.php';

// Load the admin settings class
require_once GW2_GUILD_LOGIN_DIR . 'includes/admin/class-gw2-guild-login-admin.php';

// Load the admin menu
require_once GW2_GUILD_LOGIN_DIR . 'includes/admin/class-gw2-admin-menu.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-2fa-login.php';

// Include database migration if needed
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-cli.php';
}
require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-user-dashboard.php';

// Load the main plugin class
if ( ! class_exists( 'GW2_Guild_Login' ) ) {
	require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-guild-login.php';
}

/**
 * Returns the main instance of GW2_Guild_Login
 *
 * @since 1.0.0
 * @return GW2_Guild_Login The main plugin instance
 */
function GW2_Guild_Login() {
	return GW2_Guild_Login::instance();
}

// Plugin initialization now handled in GW2_Guild_Login class.

// Always register plugin settings for admin pages
if ( is_admin() ) {
	if ( class_exists( 'GW2_Guild_Login_Admin' ) ) {
		if ( method_exists( 'GW2_Guild_Login_Admin', 'instance' ) ) {
			$gw2_admin = GW2_Guild_Login_Admin::instance();
		} else {
			global $gw2_guild_login_admin_instance;
			if ( ! isset( $gw2_guild_login_admin_instance ) ) {
				$gw2_guild_login_admin_instance = new GW2_Guild_Login_Admin();
			}
			$gw2_admin = $gw2_guild_login_admin_instance;
		}
		add_action( 'admin_init', array( $gw2_admin, 'register_settings' ) );
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
// function gw2_handle_regenerate_backup_codes() {
// check_ajax_referer('gw2_2fa_nonce', 'nonce');
// if (!current_user_can('edit_users')) {
// wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'gw2-guild-login')]);
// }
// $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
// $handler = GW2_2FA_Handler::instance();
// $new_codes = $handler->generate_backup_codes();
// global $wpdb;
// $table = $wpdb->prefix . 'gw2_2fa_secrets';
// $secret_row = $wpdb->get_row($wpdb->prepare(
// "SELECT secret FROM $table WHERE user_id = %d",
// $user_id
// ));
// if (!$secret_row) {
// wp_send_json_error(['message' => __('2FA is not enabled for this user.', 'gw2-guild-login')]);
// }
// $result = $handler->enable_2fa($user_id, $handler->decrypt_secret($secret_row->secret), $new_codes);
// if (is_wp_error($result)) {
// wp_send_json_error(['message' => $result->get_error_message()]);
// }
// wp_send_json_success([
// 'codes' => $new_codes,
// 'message' => __('New backup codes have been generated. Please save them in a safe place.', 'gw2-guild-login')
// ]);
// }

// Activation hook
register_activation_hook( __FILE__, 'gw2_2fa_activate' );

/**
 * Plugin activation.
 *
 * @return void
 */
function gw2_2fa_activate(): void {
	global $wpdb; /** @var wpdb $wpdb */
	// Create database tables if they don't exist
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

	// Trusted devices table
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

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Add version to options for future updates
	add_option( 'gw2_2fa_db_version', '1.0' );
}

// Add settings link to plugin actions
// Plugin action links now handled in GW2_Guild_Login_Admin or GW2_2FA_Handler class.

/**
 * Add settings link to plugin actions
 *
 * @param array $links
 * @return array
 */
// Plugin action links logic now handled in GW2_Guild_Login_Admin or GW2_2FA_Handler class.
// function gw2_2fa_plugin_action_links($links) {
// $settings_link = '<a href="' . admin_url('profile.php#two-factor-auth') . '">' . __('2FA Settings', 'gw2-guild-login') . '</a>';
// array_unshift($links, $settings_link);
// return $links;
// }

// (Docblock removed for clarity)

// Plugin initialization logic now handled in GW2_Guild_Login class.
// function gw2_guild_login_init() {
// Load the main plugin class
// $plugin = GW2_Guild_Login();
// $GLOBALS['gw2_guild_login'] = $plugin;
// Register template hooks
// add_filter('theme_page_templates', array($plugin, 'register_page_templates'));
// add_filter('template_include', array($plugin, 'load_page_template'));
// Load text domain for translations
// load_plugin_textdomain(
// 'gw2-guild-login',
// false,
// dirname(plugin_basename(__FILE__)) . '/languages/'
// );
// Initialize core classes
// $gw2_api = new GW2_API();
// Initialize session handler (static class)
// GW2_Session_Handler::init();
// Initialize user handler with API dependency
// $gw2_user_handler = new GW2_User_Handler($gw2_api);
// Initialize shortcode handler using singleton pattern
// $gw2_login_shortcode = GW2_Login_Shortcode::instance();
// Initialize 2FA handler
// $gw2_2fa_handler = GW2_2FA_Handler::instance();
// Store instances in global for backward compatibility
// $GLOBALS['gw2_guild_login_api'] = $gw2_api;
// $GLOBALS['gw2_guild_login_session'] = 'GW2_Session_Handler';
// $GLOBALS['gw2_guild_login_user_handler'] = $gw2_user_handler;
// Initialize dashboard if we're in the admin area
// if (is_admin()) {
// $gw2_dashboard = new GW2_User_Dashboard();
// }
// }

// Shortcode registration and rendering now handled in GW2_Login_Shortcode class.


// Enqueue frontend styles and scripts
function gw2_login_enqueue_assets(): void {
	// Guild rank styles
	wp_register_style( 'gw2-guild-ranks', plugins_url( 'assets/css/guild-ranks.css', __FILE__ ), array(), GW2_GUILD_LOGIN_VERSION );
	wp_enqueue_style( 'gw2-guild-ranks' );

	// Only load on pages with the shortcode
	global $post;
	$post_content = ( is_object( $post ) && isset( $post->post_content ) && is_string( $post->post_content ) ) ? $post->post_content : '';
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post_content, 'gw2_login' ) ) {
		// Register and enqueue the stylesheet
		wp_register_style(
			'gw2-login-styles',
			plugins_url( 'assets/css/gw2-login.css', __FILE__ ),
			array(),
			GW2_GUILD_LOGIN_VERSION
		);
		wp_enqueue_style( 'gw2-login-styles' );

		// Register and enqueue the JavaScript
		wp_register_script(
			'gw2-login-script',
			plugins_url( 'assets/js/gw2-login.js', __FILE__ ),
			array( 'jquery' ),
			GW2_GUILD_LOGIN_VERSION,
			true
		);

		// Localize the script with data from PHP
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
	// Only process POST requests
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '';
	if ( 'POST' !== $request_method ) {
		return;
	}

	// Handle login form submission
	$gw2_submit_login = isset( $_POST['gw2_submit_login'] );
	$gw2_api_key_raw  = isset( $_POST['gw2_api_key'] ) ? $_POST['gw2_api_key'] : '';
	if ( $gw2_submit_login && is_string( $gw2_api_key_raw ) ) {
		// Verify nonce
		$gw2_login_nonce = isset( $_POST['gw2_login_nonce'] ) ? $_POST['gw2_login_nonce'] : '';
		if ( ! is_string( $gw2_login_nonce ) || ! wp_verify_nonce( $gw2_login_nonce, 'gw2_login_action' ) ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		$api_key         = sanitize_text_field( trim( $gw2_api_key_raw ) );
		$remember        = ! empty( $_POST['rememberme'] );
		$redirect_to_raw = isset( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : '';
		$redirect_to     = is_string( $redirect_to_raw ) ? esc_url_raw( $redirect_to_raw ) : home_url();

		if ( $api_key === '' ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		// Process the login
		$user_handler = GW2_Guild_Login()->get_user_handler();
		$result       = is_object( $user_handler ) && method_exists( $user_handler, 'process_login' ) ? $user_handler->process_login( $api_key ) : null;

		if ( is_wp_error( $result ) ) {
			// TODO: Display error message to user (handled in class-based handler).
			return;
		}

		// Set remember me cookie if needed
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

		// Set success message
		$is_new_user  = is_array( $result ) && isset( $result['is_new_user'] ) ? $result['is_new_user'] : false;
		$account_name = is_array( $result ) && isset( $result['account_name'] ) && is_string( $result['account_name'] ) ? $result['account_name'] : '';
		$message      = $is_new_user
			? sprintf( __( 'Welcome to our community, %s! Your account has been created.', 'gw2-guild-login' ), $account_name )
			: sprintf( __( 'Welcome back, %s! You have been logged in successfully.', 'gw2-guild-login' ), $account_name );
	}
}

// Add a shortcode to display a login/logout link (handled in GW2_Login_Shortcode class)
// All login/logout shortcode logic is now handled in GW2_Login_Shortcode class.
