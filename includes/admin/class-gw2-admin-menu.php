<?php
/**
 * GW2 Admin Menu Handler.
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

/**
 * Handles the admin menu structure for GW2 Guild Login
 */
class GW2_Admin_Menu {
	/** @var self|null Singleton instance */
	private static $instance = null;

	/** @var string The slug for the main menu */
	private $menu_slug = 'gw2-guild';

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Add admin menus
	 *
	 * @return void
	 */
	public function add_admin_menus(): void {
		// Main menu.
		add_menu_page(
			__( 'GW2 Guild', 'gw2-guild-login' ),
			'GW2 Guild',
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_dashboard_page' ),
			'dashicons-groups',
			30
		);

		// Dashboard (main page).
		add_submenu_page(
			$this->menu_slug,
			__( 'Dashboard', 'gw2-guild-login' ),
			__( 'Dashboard', 'gw2-guild-login' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render_dashboard_page' )
		);

		// Guild Settings (use the same slug as the settings page).
		add_submenu_page(
			$this->menu_slug,
			__( 'Guild Settings', 'gw2-guild-login' ),
			__( 'Guild Settings', 'gw2-guild-login' ),
			'manage_options',
			'gw2-guild-login', // Use the same slug as the main settings page.
			array( $this, 'render_settings_page' )
		);

		// User Management.
		add_submenu_page(
			$this->menu_slug,
			__( 'User Management', 'gw2-guild-login' ),
			__( 'User Management', 'gw2-guild-login' ),
			'manage_options',
			'gw2-user-management',
			array( $this, 'render_user_management_page' )
		);

		// Guild Roster.
		add_submenu_page(
			$this->menu_slug,
			__( 'Guild Roster', 'gw2-guild-login' ),
			__( 'Guild Roster', 'gw2-guild-login' ),
			'manage_options',
			'gw2-guild-roster',
			array( $this, 'render_guild_roster_page' )
		);

		// Reports.
		add_submenu_page(
			$this->menu_slug,
			__( 'Reports', 'gw2-guild-login' ),
			__( 'Reports', 'gw2-guild-login' ),
			'manage_options',
			'gw2-reports',
			array( $this, 'render_reports_page' )
		);

		// Tools.
		add_submenu_page(
			$this->menu_slug,
			__( 'Tools', 'gw2-guild-login' ),
			__( 'Tools', 'gw2-guild-login' ),
			'manage_options',
			'gw2-tools',
			array( $this, 'render_tools_page' )
		);

		// Appearance & Branding.
		add_submenu_page(
			$this->menu_slug,
			__( 'Appearance & Branding', 'gw2-guild-login' ),
			__( 'Appearance & Branding', 'gw2-guild-login' ),
			'manage_options',
			'gw2-appearance-branding',
			array( $this, 'render_appearance_branding_page' )
		);
	}

	/**
	 * Enqueue admin styles
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( string $hook ): void {
		if ( strpos( $hook, 'gw2-guild' ) !== false ) {
			// Legacy admin styles.
			wp_enqueue_style(
				'gw2-admin',
				plugins_url( 'assets/css/admin.css', dirname( __DIR__ ) ),
				array(),
				GW2_GUILD_LOGIN_VERSION
			);
			// Modern admin overrides (including dark mode).
			wp_enqueue_style(
				'gw2-admin-modern',
				plugin_dir_url( __FILE__ ) . 'css/admin-style.css',
				array(),
				GW2_GUILD_LOGIN_VERSION
			);
		}
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GW2_GUILD_LOGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GW2_GUILD_LOGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render user management page
	 *
	 * @return void
	 */
	public function render_user_management_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include GW2_GUILD_LOGIN_DIR . 'admin/views/user-management.php';
	}

	/**
	 * Render guild roster page
	 *
	 * @return void
	 */
	public function render_guild_roster_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include GW2_GUILD_LOGIN_DIR . 'admin/views/guild-roster.php';
	}

	/**
	 * Render reports page
	 *
	 * @return void
	 */
	public function render_reports_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include GW2_GUILD_LOGIN_DIR . 'admin/views/reports.php';
	}

	/**
	 * Render tools page
	 *
	 * @return void
	 */
	public function render_tools_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include GW2_GUILD_LOGIN_DIR . 'admin/views/tools.php';
	}

	/**
	 * Render appearance branding page
	 *
	 * @return void
	 */
	public function render_appearance_branding_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include GW2_GUILD_LOGIN_DIR . 'admin/views/appearance-branding.php';
	}
}

/**
 * Initialize the admin menu
 *
 * @return void
 */
function gw2_admin_menu_init(): void { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	GW2_Admin_Menu::instance();
}
add_action( 'plugins_loaded', 'gw2_admin_menu_init' );
