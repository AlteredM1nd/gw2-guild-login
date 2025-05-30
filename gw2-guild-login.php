<?php
/**
 * Plugin Name:       GW2 Guild Login
 * Plugin URI:        https://github.com/AlteredM1nd/gw2-guild-login
 * Description:       Allows users to log in using their GW2 API key to verify guild membership with WordPress user integration.
 * Version:           2.4.0
 * Author:            AlteredM1nd
 * Author URI:        https://github.com/AlteredM1nd
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gw2-guild-login
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define ABSPATH if not defined (for unit tests)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Load WordPress helper functions if not already loaded
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Get plugin data
$plugin_data = get_plugin_data(__FILE__);

// Define plugin version
if (!defined('GW2_GUILD_LOGIN_VERSION')) {
    define('GW2_GUILD_LOGIN_VERSION', $plugin_data['Version']);
}

// Define plugin paths
if (!defined('GW2_GUILD_LOGIN_FILE')) {
    define('GW2_GUILD_LOGIN_FILE', __FILE__);
}

if (!defined('GW2_GUILD_LOGIN_DIR')) {
    define('GW2_GUILD_LOGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('GW2_GUILD_LOGIN_URL')) {
    define('GW2_GUILD_LOGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('GW2_GUILD_LOGIN_PLUGIN_BASENAME')) {
    define('GW2_GUILD_LOGIN_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// 2FA Constants
define('GW2_2FA_TABLE_SECRETS', 'gw2_2fa_secrets');
define('GW2_2FA_TABLE_DEVICES', 'gw2_2fa_trusted_devices');
define('GW2_2FA_COOKIE', 'gw2_2fa_trusted_device');
define('GW2_2FA_COOKIE_EXPIRY', 30 * 24 * 60 * 60); // 30 days in seconds

// Backward compatibility
define('GW2_GUILD_LOGIN_PLUGIN_DIR', GW2_GUILD_LOGIN_DIR);
define('GW2_GUILD_LOGIN_PLUGIN_URL', GW2_GUILD_LOGIN_URL);

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

// Load the core plugin class.
require plugin_dir_path(__FILE__) . 'includes/class-gw2-guild-login.php';

// Load the 2FA functionality
require_once plugin_dir_path(__FILE__) . 'includes/class-gw2-2fa.php';

// Load the user dashboard
require_once plugin_dir_path(__FILE__) . 'includes/class-gw2-user-dashboard.php';

// Load the guild ranks functionality
require_once plugin_dir_path(__FILE__) . 'includes/class-gw2-guild-ranks.php';

// Load the admin menu
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-gw2-admin-menu.php';
require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-2fa-login.php'; 

// Include database migration if needed
if (defined('WP_CLI') && WP_CLI) {
    require_once GW2_GUILD_LOGIN_DIR . 'includes/class-gw2-cli.php';
}
require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/class-gw2-user-dashboard.php';

// Load the main plugin class
if (!class_exists('GW2_Guild_Login')) {
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

// Initialize the plugin
add_action('plugins_loaded', 'gw2_guild_login_init', 15);

// Initialize 2FA
add_action('init', 'gw2_2fa_init');

/**
 * Initialize 2FA functionality
 */
function gw2_2fa_init() {
    // Initialize 2FA admin for logged-in users with appropriate capabilities
    if (is_admin() && current_user_can('edit_users')) {
        new GW2_2FA_Admin();
    }
    
    // Always initialize 2FA login handling
    // It will handle both logged-in and non-logged-in users appropriately
    new GW2_2FA_Login();
}

// Handle AJAX requests for 2FA
add_action('wp_ajax_gw2_regenerate_backup_codes', 'gw2_handle_regenerate_backup_codes');

/**
 * Handle AJAX request to regenerate backup codes
 */
function gw2_handle_regenerate_backup_codes() {
    check_ajax_referer('gw2_2fa_nonce', 'nonce');
    
    if (!current_user_can('edit_users')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'gw2-guild-login')]);
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $handler = GW2_2FA_Handler::instance();
    $new_codes = $handler->generate_backup_codes();
    
    // Get the current secret
    global $wpdb;
    $table = $wpdb->prefix . 'gw2_2fa_secrets';
    $secret_row = $wpdb->get_row($wpdb->prepare(
        "SELECT secret FROM $table WHERE user_id = %d",
        $user_id
    ));
    
    if (!$secret_row) {
        wp_send_json_error(['message' => __('2FA is not enabled for this user.', 'gw2-guild-login')]);
    }
    
    // Update the backup codes
    $result = $handler->enable_2fa($user_id, $handler->decrypt_secret($secret_row->secret), $new_codes);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    wp_send_json_success([
        'codes' => $new_codes,
        'message' => __('New backup codes have been generated. Please save them in a safe place.', 'gw2-guild-login')
    ]);
}

// Activation hook
register_activation_hook(__FILE__, 'gw2_2fa_activate');

/**
 * Plugin activation
 */
function gw2_2fa_activate() {
    // Create database tables if they don't exist
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // 2FA Secrets table
    $table_name = $wpdb->prefix . 'gw2_2fa_secrets';
    $sql = "CREATE TABLE $table_name (
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
    $sql .= "CREATE TABLE $table_name (
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
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add version to options for future updates
    add_option('gw2_2fa_db_version', '1.0');
}

// Add settings link to plugin actions
add_filter('plugin_action_links_' . GW2_GUILD_LOGIN_PLUGIN_BASENAME, 'gw2_2fa_plugin_action_links');

/**
 * Add settings link to plugin actions
 * 
 * @param array $links
 * @return array
 */
function gw2_2fa_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('profile.php#two-factor-auth') . '">' . __('2FA Settings', 'gw2-guild-login') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 */
function gw2_guild_login_init() {
    // Load the main plugin class
    $plugin = GW2_Guild_Login();
    $GLOBALS['gw2_guild_login'] = $plugin;

    // Register template hooks
    add_filter('theme_page_templates', array($plugin, 'register_page_templates'));
    add_filter('template_include', array($plugin, 'load_page_template'));

    // Load text domain for translations
    load_plugin_textdomain(
        'gw2-guild-login',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
    
    // Initialize core classes
    $gw2_api = new GW2_API();
    
    // Initialize session handler (static class)
    GW2_Session_Handler::init();
    
    // Initialize user handler with API dependency
    $gw2_user_handler = new GW2_User_Handler($gw2_api);
    
    // Initialize shortcode handler using singleton pattern
    $gw2_login_shortcode = GW2_Login_Shortcode::instance();
    
    // Initialize 2FA handler
    $gw2_2fa_handler = GW2_2FA_Handler::instance();
    
    // Store instances in global for backward compatibility
    $GLOBALS['gw2_guild_login_api'] = $gw2_api;
    $GLOBALS['gw2_guild_login_session'] = 'GW2_Session_Handler';
    $GLOBALS['gw2_guild_login_user_handler'] = $gw2_user_handler;
    $GLOBALS['gw2_2fa_handler'] = $gw2_2fa_handler;

    // Initialize dashboard if we're in the admin area
    if (is_admin()) {
        $gw2_dashboard = new GW2_User_Dashboard();
    }
}

// Shortcode for displaying the login form
function gw2_login_form_shortcode() {
    // Don't show the form to logged-in users
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $gw2_account = get_user_meta($current_user->ID, 'gw2_account_name', true);

        ob_start();
        ?>
        <div class="gw2-login-status">
            <p>
                <?php
                printf(
                    __('Logged in as %1$s (GW2: %2$s)', 'gw2-guild-login'),
                    esc_html($current_user->display_name),
                    esc_html($gw2_account ?: __('No GW2 account linked', 'gw2-guild-login'))
                );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('users.php?page=gw2-account')); ?>" class="button">
                    <?php _e('My GW2 Account', 'gw2-guild-login'); ?>
                </a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="button">
                    <?php _e('Logout', 'gw2-guild-login'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

	// Show login form for non-logged-in users
	ob_start();

	// Display messages (errors or success)
	if ( isset( $_SESSION['gw2_login_message'] ) ) {
		$message_type = isset( $_SESSION['gw2_login_message_type'] ) ?
			esc_attr( $_SESSION['gw2_login_message_type'] ) : 'info';
		?>
		<div class="notice notice-<?php echo $message_type; ?> is-dismissible">
			<p><?php echo esc_html( $_SESSION['gw2_login_message'] ); ?></p>
		</div>
		<?php
		unset( $_SESSION['gw2_login_message'] );
		unset( $_SESSION['gw2_login_message_type'] );
	}

	?>
	<form id="gw2-login-form" method="post" class="gw2-login-form">
		<p class="form-row">
			<label for="gw2_api_key"><?php _e( 'GW2 API Key:', 'gw2-guild-login' ); ?></label>
			<input type="password" name="gw2_api_key" id="gw2_api_key" class="input-text" required 
					placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxxxxxxxxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
			<small class="description">
				<?php _e( 'Requires "account" and "guilds" permissions. ', 'gw2-guild-login' ); ?>
				<a href="https://account.arena.net/applications" target="_blank" rel="noopener noreferrer">
					<?php _e( 'Get an API key', 'gw2-guild-login' ); ?>
				</a>
			</small>
		</p>
		
		<p class="form-row remember-me">
			<label>
				<input name="rememberme" type="checkbox" id="rememberme" value="forever">
				<?php _e( 'Remember Me', 'gw2-guild-login' ); ?>
			</label>
		</p>
		
		<?php wp_nonce_field( 'gw2_login_action', 'gw2_login_nonce' ); ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : home_url() ); ?>">
		
		<p class="form-submit">
			<button type="submit" name="gw2_submit_login" class="button button-primary">
				<?php _e( 'Login with GW2', 'gw2-guild-login' ); ?>
			</button>
		</p>
		
		<?php if ( get_option( 'users_can_register' ) ) : ?>
		<p class="register-link">
			<?php _e( "Don't have an account?", 'gw2-guild-login' ); ?> 
			<a href="<?php echo esc_url( wp_registration_url() ); ?>">
				<?php _e( 'Register', 'gw2-guild-login' ); ?>
			</a>
		</p>
		<?php endif; ?>
	</form>
	
	<?php
	return ob_get_clean();
}
add_shortcode( 'gw2_login', 'gw2_login_form_shortcode' );

// Enqueue frontend styles and scripts
function gw2_login_enqueue_assets() {
    // Guild rank styles
    wp_register_style('gw2-guild-ranks', plugins_url('assets/css/guild-ranks.css', __FILE__), array(), GW2_GUILD_LOGIN_VERSION);
    wp_enqueue_style('gw2-guild-ranks');
    
	// Only load on pages with the shortcode
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'gw2_login' ) ) {
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
add_action( 'wp_enqueue_scripts', 'gw2_login_enqueue_assets' );

/**
 * Handle the login form submission
 */
function gw2_handle_login_submission() {
	// Only process POST requests
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	// Handle login form submission
	if ( isset( $_POST['gw2_submit_login'] ) && isset( $_POST['gw2_api_key'] ) ) {
		// Verify nonce
		if ( ! isset( $_POST['gw2_login_nonce'] ) || ! wp_verify_nonce( $_POST['gw2_login_nonce'], 'gw2_login_action' ) ) {
			gw2_set_message( __( 'Security check failed. Please try again.', 'gw2-guild-login' ), 'error' );
			return;
		}

		$api_key     = sanitize_text_field( trim( $_POST['gw2_api_key'] ) );
		$remember    = ! empty( $_POST['rememberme'] );
		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : home_url();

		if ( empty( $api_key ) ) {
			gw2_set_message( __( 'API Key cannot be empty.', 'gw2-guild-login' ), 'error' );
			return;
		}

		// Process the login
		$result = GW2_Guild_Login()->get_user_handler()->process_login( $api_key );

		if ( is_wp_error( $result ) ) {
			gw2_set_message( $result->get_error_message(), 'error' );
			return;
		}

		// Set remember me cookie if needed
		if ( $remember && ! empty( $result['user_id'] ) ) {
			$user = get_user_by( 'id', $result['user_id'] );
			if ( $user && is_a( $user, 'WP_User' ) ) {
				wp_set_auth_cookie( $user->ID, $remember );
				wp_set_current_user( $user->ID, $user->user_login );
				do_action( 'wp_login', $user->user_login, $user );
			} else {
				gw2_set_message( __( 'Error: Could not retrieve user information.', 'gw2-guild-login' ), 'error' );
				return;
			}
		}

		// Set success message
		$message = $result['is_new_user']
			? sprintf( __( 'Welcome to our community, %s! Your account has been created.', 'gw2-guild-login' ), $result['account_name'] )
			: sprintf( __( 'Welcome back, %s! You have been logged in successfully.', 'gw2-guild-login' ), $result['account_name'] );

		gw2_set_message( $message, 'success' );

		// Redirect to the requested page or home
		wp_safe_redirect( $redirect_to );
		exit;
	}
}
add_action( 'init', 'gw2_handle_login_submission' );

/**
 * Set a message to be displayed to the user
 *
 * @param string $message The message to display
 * @param string $type    The type of message (error, success, warning, info)
 */
function gw2_set_message( $message, $type = 'info' ) {
	if ( ! session_id() ) {
		session_start();
	}

	$_SESSION['gw2_login_message']      = $message;
	$_SESSION['gw2_login_message_type'] = $type;
}

/**
 * Shortcode to protect content for guild members only
 */
function gw2_guild_content_shortcode( $atts, $content = null ) {
	// Parse attributes
	$atts = shortcode_atts(
		array(
			'capability'      => 'read',
			'show_greeting'   => 'yes',
			'show_login_form' => 'yes',
			'message'         => '',
		),
		$atts,
		'gw2_guild_only'
	);

	// Allow filtering the capability required to view the content
	$required_cap = apply_filters( 'gw2_required_capability', $atts['capability'] );

	// Check if user is logged in and has the required capability
	if ( is_user_logged_in() && current_user_can( $required_cap ) ) {
		$current_user = wp_get_current_user();
		$gw2_account  = get_user_meta( $current_user->ID, 'gw2_account_name', true );

		// Add a greeting if desired
		$greeting = '';
		if ( 'yes' === $atts['show_greeting'] && apply_filters( 'gw2_show_greeting', true ) ) {
			$greeting = sprintf(
				'<div class="gw2-greeting">%s %s</div>',
				esc_html__( 'Welcome,', 'gw2-guild-login' ),
				esc_html( $gw2_account ?: $current_user->display_name )
			);
		}

		// Process the content and apply filters
		$content = do_shortcode( $content );
		$content = apply_filters( 'gw2_protected_content', $content, $current_user );

		return $greeting . $content;
	} else {
		// Show custom message if provided, otherwise use default
		$message = ! empty( $atts['message'] )
			? '<div class="gw2-login-required">' . esc_html( $atts['message'] ) . '</div>'
			: apply_filters(
				'gw2_login_required_message',
				sprintf(
					'<div class="gw2-login-required">%s</div>',
					esc_html__( 'You must be logged in as a guild member to view this content.', 'gw2-guild-login' )
				)
			);

		// Show login form if enabled
		if ( 'yes' === $atts['show_login_form'] && apply_filters( 'gw2_show_login_form', true ) ) {
			$message .= do_shortcode( '[gw2_login]' );
		}

		return $message;
	}
}
add_shortcode( 'gw2_guild_only', 'gw2_guild_content_shortcode' );

/**
 * Add a shortcode to display a login/logout link
 */
function gw2_login_logout_shortcode( $atts ) {
	// Parse attributes with defaults
	$atts = shortcode_atts(
		array(
			'login_text'      => __( 'Login', 'gw2-guild-login' ),
			'logout_text'     => __( 'Logout', 'gw2-guild-login' ),
			'redirect'        => '',
			'show_avatar'     => 'yes',
			'show_name'       => 'yes',
			'show_gw2_name'   => 'yes',
			'avatar_size'     => 32,
			'container_class' => 'gw2-login-status',
			'login_class'     => 'login-link',
			'logout_class'    => 'logout-link',
			'greeting'        => '',
		),
		$atts,
		'gw2_loginout'
	);

	// Build redirect URL
	$redirect = ! empty( $atts['redirect'] ) ? $atts['redirect'] : ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$greeting     = '';

		// Add avatar if enabled
		if ( 'yes' === $atts['show_avatar'] ) {
			$greeting .= get_avatar( $current_user->ID, absint( $atts['avatar_size'] ) ) . ' ';
		}

		// Add greeting text if provided
		if ( ! empty( $atts['greeting'] ) ) {
			$greeting .= '<span class="greeting-text">' . esc_html( $atts['greeting'] ) . ' </span>';
		}

		// Add display name if enabled
		if ( 'yes' === $atts['show_name'] ) {
			$display_name = $current_user->display_name;

			// Show GW2 account name if available and enabled
			if ( 'yes' === $atts['show_gw2_name'] ) {
				$gw2_name = get_user_meta( $current_user->ID, 'gw2_account_name', true );
				if ( ! empty( $gw2_name ) ) {
					$display_name = $gw2_name;
				}
			}

			$greeting .= sprintf(
				'<span class="display-name">%s</span>',
				esc_html( $display_name )
			);
		}

		// Build logout URL with redirect
		$logout_url = wp_logout_url( $redirect );

		// Build the output
		$output = sprintf(
			'<div class="%s">%s <a href="%s" class="%s">%s</a></div>',
			esc_attr( $atts['container_class'] ),
			$greeting,
			esc_url( $logout_url ),
			esc_attr( $atts['logout_class'] ),
			esc_html( $atts['logout_text'] )
		);

		return apply_filters( 'gw2_loginout_shortcode_logged_in', $output, $current_user, $atts );
	} else {
		// Build login URL with redirect
		$login_url = wp_login_url( $redirect );

		// Build the output
		$output = sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $login_url ),
			esc_attr( $atts['login_class'] ),
			esc_html( $atts['login_text'] )
		);

		return apply_filters( 'gw2_loginout_shortcode_logged_out', $output, $atts );
	}
}
add_shortcode( 'gw2_loginout', 'gw2_login_logout_shortcode' );