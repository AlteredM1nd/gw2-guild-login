<?php
declare(strict_types=1);
/**
 * Main plugin class
 */
class GW2_Guild_Login {

	public function __construct() {
		// Set up plugin paths - ensure we have at least the basic file path
		$this->plugin_file = defined( 'GW2_GUILD_LOGIN_FILE' ) ? GW2_GUILD_LOGIN_FILE : dirname( dirname( __FILE__ ) ) . '/gw2-guild-login.php';
		// Define constants before they're used
		$this->define_constants();
		// Now set up the rest of the paths
		$this->plugin_dir = defined( 'GW2_GUILD_LOGIN_PLUGIN_DIR' ) ? GW2_GUILD_LOGIN_PLUGIN_DIR : plugin_dir_path( $this->plugin_file );
		$this->plugin_url = defined( 'GW2_GUILD_LOGIN_PLUGIN_URL' ) ? GW2_GUILD_LOGIN_PLUGIN_URL : plugin_dir_url( $this->plugin_file );
		// Initialize
		$this->includes();
		$this->init_hooks();
		add_action('wp_logout', array($this, 'handle_logout_cache_invalidation'));
	}

	/**
	 * Invalidate user cache on logout
	 */
	public function handle_logout_cache_invalidation(): void {
		if (!is_user_logged_in()) {
			return;
		}
		$user_id = get_current_user_id();
		if ($user_id <= 0) {
			return;
		}
		// Use the existing user handler to clear cache
		if (class_exists('GW2_User_Handler') && isset($this->user_handler)) {
			$this->user_handler->clear_user_cache($user_id);
		}
	}

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	const VERSION = '2.6.2';

	/**
	 * The API handler instance
	 *
	 * @var GW2_API
	 */
	private $api;

	/**
	 * The user handler instance
	 *
	 * @var GW2_User_Handler
	 */
	private $user_handler;

	/**
	 * Admin handler instance
	 *
	 * @var GW2_Guild_Login_Admin
	 */
	private $admin;

	/**
	 * List of available templates
	 *
	 * @var array
	 */
	private $templates = array(
		'template-guild-only.php' => 'Guild Members Only',
	);

	/**
	 * The instance of the class
	 *
	 * @var GW2_Guild_Login
	 * @since 2.6.2
	 */
	protected static $instance = null;

	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Plugin directory URL
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Main instance
	 *
	 * @return GW2_Guild_Login
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define plugin constants
	 */
	private function define_constants(): void {
		// Define constants if not already defined in main plugin file
		if ( ! defined( 'GW2_GUILD_LOGIN_VERSION' ) ) {
			$this->define( 'GW2_GUILD_LOGIN_VERSION', self::VERSION );
		}

		if ( ! defined( 'GW2_GUILD_LOGIN_FILE' ) ) {
			$this->define( 'GW2_GUILD_LOGIN_FILE', $this->plugin_file );
		}

		// Define plugin directory based on the file location if not already set
		if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_DIR' ) ) {
			$plugin_dir = plugin_dir_path( $this->plugin_file );
			$this->define( 'GW2_GUILD_LOGIN_PLUGIN_DIR', $plugin_dir );
			$this->plugin_dir = $plugin_dir;
		}

		if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_URL' ) ) {
			$plugin_url = plugin_dir_url( $this->plugin_file );
			$this->define( 'GW2_GUILD_LOGIN_PLUGIN_URL', $plugin_url );
			$this->plugin_url = $plugin_url;
		}

		if ( ! defined( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME' ) ) {
			$basename = plugin_basename( $this->plugin_file );
			$this->define( 'GW2_GUILD_LOGIN_PLUGIN_BASENAME', $basename );
		}

		// Backward compatibility
		if ( ! defined( 'GW2_GUILD_LOGIN_ABSPATH' ) ) {
			$this->define( 'GW2_GUILD_LOGIN_ABSPATH', $this->plugin_dir );
		}
	}

	/**
	 * Define constant if not already set
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required files
	 */
	public function includes(): void {
		// Ensure constants are defined
		$this->define_constants();

		// Include required files
		require_once $this->plugin_dir . 'includes/class-gw2-api.php';
		require_once $this->plugin_dir . 'includes/class-gw2-user-handler.php';

		// Initialize components
		$this->init_components();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks(): void {
		// Activation and deactivation hooks
		register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );

		// Initialize plugin
		add_action( 'init', array( $this, 'init' ), 0 );

		// Load text domain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Initialize admin
		add_action( 'admin_init', array( $this, 'init_admin' ) );

		// Register page templates
		add_filter( 'theme_page_templates', array( $this, 'register_page_templates' ) );
		add_filter( 'template_include', array( $this, 'load_page_template' ) );
	}

	/**
	 * Initialize the plugin
	 */
	public function init(): void {
		// Initialize session if not already started
		if ( ! session_id() ) {
			session_start();
		}

		// Load required files
		$this->load_dependencies();

		// Initialize components
		$this->init_components();
	}

	/**
	 * Load plugin text domain
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'gw2-guild-login',
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages/'
		);
	}

	/**
	 * Add our template to the page template dropdown
	 *
	 * @param array<string,string> $templates
	 * @return array<string,string>
	 */
	public function register_page_templates(array $templates): array {
		$templates = array_merge( $templates, $this->templates );
		return $templates;
	}

	/**
	 * Load the template if it's set
	 *
	 * @param string $template
	 * @return string
	 */
	public function load_page_template(string $template): string {
		global $post;

		// Return the template if it's not a page
		if ( ! $post ) {
			return $template;
		}

		// Get the template name from post meta
		$template_name = get_post_meta( $post->ID, '_wp_page_template', true );

		// Return default template if we don't have a custom one
		if ( ! isset( $this->templates[ $template_name ] ) ) {
			return $template;
		}

		// Check if the template file exists
		$template_file = GW2_GUILD_LOGIN_PLUGIN_DIR . 'templates/' . $template_name;

		// Return the template file if it exists
		if ( file_exists( $template_file ) ) {
			return $template_file;
		}

		// Return the default template if our custom one doesn't exist
		return $template;
	}

	/**
	 * Load required dependencies
	 */
	private function load_dependencies() {
		// Load required files
		require_once $this->plugin_dir . 'includes/class-gw2-api.php';
		require_once $this->plugin_dir . 'includes/class-gw2-user-handler.php';
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components() {
		// Initialize API handler
		$this->api = new GW2_API();

		// Initialize user handler
		$this->user_handler = new GW2_User_Handler( $this->api );
	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		// Add default options
		$default_options = array(
			'target_guild_id'      => '',
			'member_role'          => 'subscriber',
			'enable_auto_register' => true,
			'api_cache_expiry'     => 3600, // 1 hour
		);

		add_option( 'gw2gl_settings', $default_options );

		// Create required database tables if needed
		self::create_tables();

		// Schedule cron jobs
		self::schedule_events();
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate() {
		// Clear scheduled events
		wp_clear_scheduled_hook( 'gw2gl_daily_sync' );
	}

	/**
	 * Create required database tables
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb; /** @var \wpdb $wpdb */
		if (! ($wpdb instanceof \wpdb)) {
			return;
		}
		$charset_collate = (string) $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'gw2gl_api_keys';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,\n            user_id bigint(20) NOT NULL,\n            api_key varchar(100) NOT NULL,\n            permissions text NOT NULL,\n            created_at datetime DEFAULT CURRENT_TIMESTAMP,\n            last_used datetime DEFAULT NULL,\n            is_active tinyint(1) DEFAULT 1,\n            PRIMARY KEY (id),\n            UNIQUE KEY user_id (user_id),\n            KEY api_key (api_key)\n        ) $charset_collate;";

		if ( ! defined( 'ABSPATH' ) ) {
			require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-admin/includes/upgrade.php';
		} else {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( $sql );
	}

	/**
	 * Schedule cron events
	 *
	 * @return void
	 */
	private static function schedule_events(): void {
		// Schedule daily sync
		if ( ! wp_next_scheduled( 'gw2gl_daily_sync' ) ) {
			wp_schedule_event( time(), 'daily', 'gw2gl_daily_sync' );
		}
	}

	/**
	 * Get the API handler
	 *
	 * @return GW2_API
	 */
	public function get_api() {
		return $this->api;
	}

	/**
	 * Get the user handler
	 *
	 * @return GW2_User_Handler
	 */
	public function get_user_handler() {
		return $this->user_handler;
	}



	/**
	 * Initialize admin functionality
	 *
	 * @return void
	 */
	public function init_admin(): void {
		// Only load in admin area and if not already loaded
		if ( ! is_admin() || $this->admin ) {
			return;
		}

		// Include the admin class if not already loaded
		if ( ! class_exists( 'GW2_Guild_Login_Admin' ) ) {
			require_once GW2_GUILD_LOGIN_PLUGIN_DIR . 'includes/admin/class-gw2-guild-login-admin.php';
		}

		// Initialize admin class
		$this->admin = new GW2_Guild_Login_Admin();

		// Register admin hooks
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );

		// Run API key migration and show encryption key warning if needed
		add_action( 'admin_init', array( __CLASS__, 'maybe_migrate_api_keys_and_warn' ) );

		// Add plugin action links
		add_filter( 'plugin_action_links_' . GW2_GUILD_LOGIN_PLUGIN_BASENAME, array( $this->admin, 'plugin_action_links' ) );
	}

	/**
	 * Migrate API keys and warn about encryption key
	 *
	 * @return void
	 */
	public static function maybe_migrate_api_keys_and_warn(): void {
		// Migrate legacy API keys
		if ( class_exists( 'GW2_User_Handler' ) ) {
			GW2_User_Handler::maybe_migrate_api_keys();
		}
		// Warn if encryption key is missing/weak
		if ( class_exists( 'GW2_User_Handler' ) && GW2_User_Handler::is_encryption_key_weak() ) {
			add_action( 'admin_notices', function() {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'GW2 Guild Login: Your encryption key is missing or too short. Please set a strong key (32+ chars) in wp-config.php for secure API key storage.', 'gw2-guild-login' )
				);
			} );
		}
	}

	/**
	 * Get the admin instance
	 *
	 * @return GW2_Guild_Login_Admin|null
	 */
	public function get_admin(): ?GW2_Guild_Login_Admin {
		return $this->admin;
	}
}

/**
 * Returns the main instance of GW2_Guild_Login
 *
 * @return GW2_Guild_Login
 */
function GW2_Guild_Login() {
	return GW2_Guild_Login::instance();
}

// Initialize the plugin
$GLOBALS['gw2_guild_login'] = GW2_Guild_Login();
