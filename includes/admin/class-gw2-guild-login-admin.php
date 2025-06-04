<?php
/**
 * The admin-specific functionality of the plugin.
 */
class GW2_Guild_Login_Admin {
	/**
	 * The single instance of this class.
	 *
	 * @var GW2_Guild_Login_Admin|null
	 */
	private static $instance = null;

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return GW2_Guild_Login_Admin
	 */
	public static function instance(): GW2_Guild_Login_Admin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->plugin_name = 'gw2-guild-login';
		$this->version     = GW2_GUILD_LOGIN_VERSION;
		$settings_mixed    = get_option( 'gw2gl_settings', array() );
		$this->settings    = is_array( $settings_mixed ) ? $settings_mixed : array(); // PHPStan: always array.

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_dark_body_class' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		// Load modern admin stylesheet
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/admin-style.css',
			array(),
			$this->version,
			'all'
		);
		// Enqueue WordPress color picker for appearance settings
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . '2.6.2/admin/js/gw2-guild-login-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name,
			'gw2gl_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gw2gl_admin_nonce' ),
				'i18n'     => array(
					'confirm_reset' => __( 'Are you sure you want to reset all settings? This cannot be undone.', 'gw2-guild-login' ),
					'saving'        => __( 'Saving...', 'gw2-guild-login' ),
					'saved'         => __( 'Settings saved!', 'gw2-guild-login' ),
					'error'         => __( 'An error occurred. Please try again.', 'gw2-guild-login' ),
				),
			)
		);
	}

	/**
	 * Generate a tooltip with helpful information
	 *
	 * @param string $content Tooltip content
	 * @return string HTML for tooltip
	 */
	private function get_tooltip( $content ) {
		return sprintf(
			'<span class="gw2-tooltip"><span class="gw2-tooltip-icon">?</span><span class="gw2-tooltip-content">%s</span></span>',
			esc_html( $content )
		);
	}

	/**
	 * Generate a field hint with helpful information
	 *
	 * @param string $content Hint content
	 * @return string HTML for field hint
	 */
	private function get_field_hint( $content ) {
		return sprintf(
			'<span class="gw2-field-hint"><span class="gw2-hint-content">%s</span></span>',
			wp_kses_post( $content )
		);
	}

	/**
	 * Add the plugin admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'GW2 Guild Login Settings', 'gw2-guild-login' ),
			__( 'GW2 Guild Login', 'gw2-guild-login' ),
			'manage_options',
			'gw2-guild-login',
			array( $this, 'display_plugin_settings_page' )
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=gw2-guild-login' ) . '">' . __( 'Settings', 'gw2-guild-login' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/**
	 * Register the plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'gw2gl_settings_group',
			'gw2gl_settings',
			array( $this, 'sanitize_settings' )
		);

		// General Settings Section
		add_settings_section(
			'gw2gl_general_section',
			__( 'General Settings', 'gw2-guild-login' ),
			array( $this, 'general_section_callback' ),
			'gw2-guild-login'
		);

		// Guild Settings Section
		add_settings_section(
			'gw2gl_guild_section',
			__( 'Guild Settings', 'gw2-guild-login' ),
			function () {
				echo '<p>' . esc_html__( 'Configure your Guild Wars 2 guild integration. Enter one or more Guild IDs (comma-separated) and a Guild API Key for guild validation.', 'gw2-guild-login' ) . '</p>';
			},
			'gw2-guild-login'
		);

		add_settings_field(
			'guild_ids',
			__( 'Guild IDs', 'gw2-guild-login' ) . $this->get_tooltip( 'Find your Guild ID by visiting https://api.guildwars2.com/v2/guild/search?name=YourGuildName or check your guild\'s API details in-game.' ),
			array( $this, 'textarea_field_callback' ),
			'gw2-guild-login',
			'gw2gl_guild_section',
			array(
				'id'          => 'guild_ids',
				'description' => __( 'Enter one or more Guild IDs, separated by commas. Example: ABC123DEF-456G-789H-012I-JKLMNOP123QR', 'gw2-guild-login' ) . $this->get_field_hint( 'Each Guild ID is a unique identifier (UUID) for your Guild Wars 2 guild. You can find it via the GW2 API or by using guild search tools. Multiple guild IDs allow users from any of the specified guilds to access your site.' ),
			)
		);

		add_settings_field(
			'guild_api_key',
			__( 'Guild API Key', 'gw2-guild-login' ) . $this->get_tooltip( 'Create an API key at https://account.arena.net/applications with "account" and "guilds" permissions. This key is used by the plugin to verify guild membership.' ),
			array( $this, 'text_field_callback' ),
			'gw2-guild-login',
			'gw2gl_guild_section',
			array(
				'id'          => 'guild_api_key',
				'description' => __( 'Enter an API key with "account" and "guilds" permissions for guild data access.', 'gw2-guild-login' ) . $this->get_field_hint( 'This API key should have the following permissions:<br/>• <strong>account</strong> - Access basic account information<br/>• <strong>guilds</strong> - Access guild membership data<br/><br/>⚠️ <strong>Important:</strong> Keep this key secure and never share it publicly. This key allows the plugin to verify which users belong to your guild.' ),
			)
		);

		add_settings_field(
			'target_guild_id',
			__( 'Target Guild IDs', 'gw2-guild-login' ) . $this->get_tooltip( 'Legacy field. Users must be members of at least one of these guilds to log in. Leave empty to allow any GW2 account access.' ),
			array( $this, 'text_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'target_guild_id',
				'description' => __( 'Enter one or more Guild IDs, separated by commas. Users must be a member of at least one to log in.', 'gw2-guild-login' ) . $this->get_field_hint( 'This setting restricts login access to members of specific guilds. If left empty, any valid GW2 API key will allow login. Use the format: <code>Guild1ID,Guild2ID,Guild3ID</code> for multiple guilds.' ),
			)
		);

		add_settings_field(
			'member_role',
			__( 'Default User Role', 'gw2-guild-login' ) . $this->get_tooltip( 'Choose the WordPress user role assigned to new users when they register through GW2 login. Higher roles have more site permissions.' ),
			array( $this, 'select_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'member_role',
				'options'     => $this->get_user_roles(),
				'description' => __( 'Select the default role for new users.', 'gw2-guild-login' ) . $this->get_field_hint( 'This determines what permissions new users will have on your site:<br/>• <strong>Subscriber</strong> - Can only read content and manage their profile<br/>• <strong>Contributor</strong> - Can write posts but not publish them<br/>• <strong>Author</strong> - Can publish and manage their own posts<br/>• <strong>Editor</strong> - Can publish and manage posts by all users<br/>• <strong>Administrator</strong> - Full site access (use with caution)<br/><br/>💡 <strong>Recommendation:</strong> Start with "Subscriber" for security, then promote trusted guild members manually.' ),
			)
		);

		add_settings_field(
			'enable_auto_register',
			__( 'Auto-register New Users', 'gw2-guild-login' ) . $this->get_tooltip( 'When enabled, users with valid GW2 API keys who aren\'t already registered will automatically get WordPress accounts created. When disabled, only existing users can log in.' ),
			array( $this, 'checkbox_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'enable_auto_register',
				'label'       => __( 'Enable automatic registration of new users', 'gw2-guild-login' ),
				'description' => __( 'If enabled, new users will be automatically registered when they log in with a valid API key.', 'gw2-guild-login' ) . $this->get_field_hint( '<strong>Enabled:</strong> Anyone with a valid GW2 API key can create an account by logging in<br/><strong>Disabled:</strong> Only users who already have WordPress accounts can use GW2 login<br/><br/>⚠️ <strong>Security Note:</strong> If you have Target Guild IDs configured, auto-registration will only work for members of those guilds. Otherwise, any GW2 player can register.' ),
			)
		);

		// API Settings Section
		add_settings_section(
			'gw2gl_api_section',
			__( 'API Settings', 'gw2-guild-login' ),
			array( $this, 'api_section_callback' ),
			'gw2-guild-login'
		);

		add_settings_field(
			'api_cache_expiry',
			__( 'API Cache Expiry', 'gw2-guild-login' ) . $this->get_tooltip( 'How long the plugin stores GW2 API responses before fetching fresh data. Longer cache times reduce API calls but may delay updates to guild membership changes.' ),
			array( $this, 'number_field_callback' ),
			'gw2-guild-login',
			'gw2gl_api_section',
			array(
				'id'          => 'api_cache_expiry',
				'min'         => 300,
				'step'        => 60,
				'description' => __( 'How long to cache API responses in seconds. Minimum 300 (5 minutes).', 'gw2-guild-login' ) . $this->get_field_hint( 'Caching improves performance by storing API responses temporarily:<br/>• <strong>300 seconds (5 minutes)</strong> - Frequent updates, more API calls<br/>• <strong>1800 seconds (30 minutes)</strong> - Balanced performance<br/>• <strong>3600 seconds (1 hour)</strong> - Fewer API calls, slower updates<br/><br/>💡 <strong>Recommendation:</strong> Use 1800 seconds unless you need real-time guild membership updates.' ),
			)
		);

		// Security Settings Section
		add_settings_section(
			'gw2gl_security_section',
			__( 'Security', 'gw2-guild-login' ),
			function () {
				echo '<p>' . esc_html__( 'Configure security-related options.', 'gw2-guild-login' ) . '</p>';
			},
			'gw2-guild-login'
		);
		add_settings_field(
			'enable_2fa',
			__( 'Require 2FA', 'gw2-guild-login' ) . $this->get_tooltip( 'Forces all users to set up two-factor authentication (TOTP) using apps like Google Authenticator or Authy. Significantly improves account security.' ),
			array( $this, 'checkbox_field_callback' ),
			'gw2-guild-login',
			'gw2gl_security_section',
			array(
				'id'          => 'enable_2fa',
				'label'       => __( 'Enable two-factor authentication for all logins', 'gw2-guild-login' ),
				'description' => $this->get_field_hint( 'When enabled, users must provide both their GW2 API key AND a time-based code from an authenticator app:<br/>• <strong>Supported apps:</strong> Google Authenticator, Authy, Microsoft Authenticator, 1Password<br/>• <strong>Setup process:</strong> Users scan a QR code on first login<br/>• <strong>Recovery:</strong> Users receive backup codes for emergency access<br/><br/>🔒 <strong>Security benefit:</strong> Even if someone steals a user\'s API key, they can\'t log in without the authenticator device.' ),
			)
		);
		add_settings_field(
			'session_timeout',
			__( 'Session Timeout (minutes)', 'gw2-guild-login' ) . $this->get_tooltip( 'How long users stay logged in before being automatically signed out. Shorter timeouts improve security but require more frequent logins.' ),
			array( $this, 'number_field_callback' ),
			'gw2-guild-login',
			'gw2gl_security_section',
			array(
				'id'          => 'session_timeout',
				'default'     => 30,
				'min'         => 1,
				'step'        => 1,
				'description' => __( 'Maximum session duration for users.', 'gw2-guild-login' ) . $this->get_field_hint( 'Balances security with user convenience:<br/>• <strong>15-30 minutes:</strong> High security for sensitive sites<br/>• <strong>60-120 minutes:</strong> Standard for most guild sites<br/>• <strong>480+ minutes (8+ hours):</strong> Convenience-focused<br/><br/>⚠️ <strong>Security tip:</strong> Shorter timeouts reduce the risk if someone accesses an unattended computer.' ),
			)
		);
		add_settings_field(
			'rate_limit',
			__( 'API Rate Limit (per hour)', 'gw2-guild-login' ) . $this->get_tooltip( 'Maximum number of GW2 API requests allowed per hour per user. Prevents abuse and ensures your site stays within GW2 API limits.' ),
			array( $this, 'number_field_callback' ),
			'gw2-guild-login',
			'gw2gl_security_section',
			array(
				'id'          => 'rate_limit',
				'default'     => 100,
				'min'         => 1,
				'step'        => 1,
				'description' => __( 'Max API requests per hour.', 'gw2-guild-login' ) . $this->get_field_hint( 'Protects against API abuse and ensures service reliability:<br/>• <strong>50-100 requests:</strong> Conservative limit for most sites<br/>• <strong>200-300 requests:</strong> Higher limit for active guild sites<br/>• <strong>500+ requests:</strong> For sites with frequent API usage<br/><br/>📊 <strong>Context:</strong> Normal users typically make 1-5 API requests per login. Higher limits accommodate power users and admin functions.' ),
			)
		);
		add_settings_field(
			'login_attempt_limit',
			__( 'Login Attempt Limit', 'gw2-guild-login' ) . $this->get_tooltip( 'Number of failed login attempts before temporarily locking out a user or IP address. Helps prevent brute force attacks.' ),
			array( $this, 'number_field_callback' ),
			'gw2-guild-login',
			'gw2gl_security_section',
			array(
				'id'          => 'login_attempt_limit',
				'default'     => 5,
				'min'         => 1,
				'step'        => 1,
				'description' => __( 'Failed logins before lockout.', 'gw2-guild-login' ) . $this->get_field_hint( 'Protects against automated attacks and password guessing:<br/>• <strong>3-5 attempts:</strong> High security, may inconvenience legitimate users<br/>• <strong>5-10 attempts:</strong> Balanced protection for most sites<br/>• <strong>10+ attempts:</strong> More lenient, allows for user error<br/><br/>🛡️ <strong>How it works:</strong> After reaching the limit, users must wait before trying again. This stops automated attacks while allowing real users to recover from typos.' ),
			)
		);

		// Appearance & Branding Section
		add_settings_section(
			'gw2gl_appearance_section',
			__( 'Appearance & Branding', 'gw2-guild-login' ),
			'__return_false',
			'gw2-appearance-branding'
		);

		// Primary Color Picker
		add_settings_field(
			'appearance_primary_color',
			__( 'Primary Color', 'gw2-guild-login' ),
			array( $this, 'color_picker_field_callback' ),
			'gw2-appearance-branding',
			'gw2gl_appearance_section',
			array(
				'id'          => 'appearance_primary_color',
				'default'     => '#1976d2',
				'description' => __( 'Primary theme color.', 'gw2-guild-login' ),
			)
		);

		// Accent Color Picker
		add_settings_field(
			'appearance_accent_color',
			__( 'Accent Color', 'gw2-guild-login' ),
			array( $this, 'color_picker_field_callback' ),
			'gw2-appearance-branding',
			'gw2gl_appearance_section',
			array(
				'id'          => 'appearance_accent_color',
				'default'     => '#26c6da',
				'description' => __( 'Accent theme color.', 'gw2-guild-login' ),
			)
		);

		// Logo Upload
		add_settings_field(
			'appearance_logo',
			__( 'Custom Logo URL', 'gw2-guild-login' ),
			array( $this, 'logo_upload_field_callback' ),
			'gw2-appearance-branding',
			'gw2gl_appearance_section',
			array(
				'id'          => 'appearance_logo',
				'description' => __( 'URL of a custom logo to display in the header.', 'gw2-guild-login' ),
			)
		);

		// Welcome Text
		add_settings_field(
			'appearance_welcome_text',
			__( 'Welcome Text', 'gw2-guild-login' ),
			array( $this, 'textarea_field_callback' ),
			'gw2-appearance-branding',
			'gw2gl_appearance_section',
			array(
				'id'          => 'appearance_welcome_text',
				'description' => __( 'Custom welcome message displayed to users.', 'gw2-guild-login' ),
			)
		);

		// Force Dark Mode Toggle
		add_settings_field(
			'appearance_force_dark',
			__( 'Force Dark Mode', 'gw2-guild-login' ),
			array( $this, 'checkbox_field_callback' ),
			'gw2-appearance-branding',
			'gw2gl_appearance_section',
			array(
				'id'    => 'appearance_force_dark',
				'label' => __( 'Enable dark theme for all users', 'gw2-guild-login' ),
			)
		);
	}

	/**
	 * Sanitize the settings before they are saved.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize Guild IDs (allow comma-separated alphanumeric/hex strings)
		if ( isset( $input['guild_ids'] ) && is_string( $input['guild_ids'] ) ) {
			$ids                    = array_map( 'trim', explode( ',', $input['guild_ids'] ) );
			$ids                    = array_filter(
				$ids,
				function ( $id ) {
					return preg_match( '/^[a-fA-F0-9]{8,}$/', $id ); // GW2 Guild IDs are usually hex
				}
			);
			$sanitized['guild_ids'] = implode( ',', $ids );
		}

		// Sanitize Guild API Key (alphanumeric, 72+ chars)
		if ( isset( $input['guild_api_key'] ) && is_string( $input['guild_api_key'] ) ) {
			$api_key = trim( $input['guild_api_key'] );
			if ( preg_match( '/^[a-zA-Z0-9-]{70,}$/', $api_key ) ) {
				$sanitized['guild_api_key'] = $api_key;
			}
		}

		// Support multiple guild IDs as array
		if ( isset( $input['target_guild_id'] ) ) {
			$ids                      = array_filter( array_map( 'trim', explode( ',', $input['target_guild_id'] ) ) );
			$input['target_guild_id'] = implode( ',', $ids ); // Store as comma-separated
		}

		// Sanitize appearance fields
		$input['appearance_primary_color'] = isset( $input['appearance_primary_color'] ) ? sanitize_hex_color( $input['appearance_primary_color'] ) : '';
		$input['appearance_accent_color']  = isset( $input['appearance_accent_color'] ) ? sanitize_hex_color( $input['appearance_accent_color'] ) : '';
		$input['appearance_logo']          = isset( $input['appearance_logo'] ) ? esc_url_raw( $input['appearance_logo'] ) : '';
		$input['appearance_welcome_text']  = isset( $input['appearance_welcome_text'] ) ? sanitize_textarea_field( $input['appearance_welcome_text'] ) : '';
		$input['appearance_force_dark']    = isset( $input['appearance_force_dark'] ) ? 1 : 0;

		$sanitized        = array();
		$current_settings = get_option( 'gw2gl_settings', array() );

		// General Settings
		$sanitized['target_guild_id']      = isset( $input['target_guild_id'] ) ? sanitize_text_field( $input['target_guild_id'] ) : '';
		$sanitized['member_role']          = isset( $input['member_role'] ) && array_key_exists( $input['member_role'], $this->get_user_roles() )
			? $input['member_role']
			: 'subscriber';
		$sanitized['enable_auto_register'] = isset( $input['enable_auto_register'] ) ? 1 : 0;

		// API Settings
		$sanitized['api_cache_expiry'] = isset( $input['api_cache_expiry'] ) ? absint( $input['api_cache_expiry'] ) : 3600;
		if ( $sanitized['api_cache_expiry'] < 300 ) {
			$sanitized['api_cache_expiry'] = 300;
		}

		// Security fields
		$sanitized['enable_2fa']          = isset( $input['enable_2fa'] ) ? 1 : 0;
		$sanitized['session_timeout']     = isset( $input['session_timeout'] ) ? absint( $input['session_timeout'] ) : 30;
		$sanitized['rate_limit']          = isset( $input['rate_limit'] ) ? absint( $input['rate_limit'] ) : 100;
		$sanitized['login_attempt_limit'] = isset( $input['login_attempt_limit'] ) ? absint( $input['login_attempt_limit'] ) : 5;

		// Preserve appearance settings across main save
		foreach ( array(
			'appearance_primary_color',
			'appearance_accent_color',
			'appearance_logo',
			'appearance_welcome_text',
			'appearance_force_dark',
		) as $app_key ) {
			if ( isset( $input[ $app_key ] ) ) {
				// Use new input when provided
				$sanitized[ $app_key ] = $input[ $app_key ];
			} elseif ( isset( $current_settings[ $app_key ] ) ) {
				// Preserve existing value when no new input
				$sanitized[ $app_key ] = $current_settings[ $app_key ];
			}
		}

		// Add admin notice for settings saved
		add_settings_error(
			'gw2gl_settings',
			'settings_updated',
			__( 'Settings saved successfully.', 'gw2-guild-login' ),
			'updated'
		);

		return $sanitized;
	}

	/**
	 * Display the plugin settings page.
	 */
	public function display_plugin_settings_page() {
		// Output admin appearance CSS variables based on settings
		$settings   = get_option( 'gw2gl_settings', array() );
		$primary    = ! empty( $settings['appearance_primary_color'] ) ? $settings['appearance_primary_color'] : '#1976d2';
		$accent     = ! empty( $settings['appearance_accent_color'] ) ? $settings['appearance_accent_color'] : '#26c6da';
		$logo       = ! empty( $settings['appearance_logo'] ) ? $settings['appearance_logo'] : '';
		$force_dark = ! empty( $settings['appearance_force_dark'] );
		$custom_css = ":root { --gw2-admin-primary: $primary; --gw2-admin-accent: $accent; }";
		if ( $force_dark ) {
			$custom_css .= ' body { background: #181c22 !important; color: #f7f9fb !important; }';
		}
		// Ensure $custom_css is always a string and properly escaped for output in <style>.
		echo '<style>' . esc_html( (string) $custom_css ) . '</style>'; // PHPStan: safe string output

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( (string) get_admin_page_title() ); // PHPStan: ensure string ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'gw2gl_settings_group' );
				do_settings_sections( 'gw2-guild-login' );
				submit_button( __( 'Save Settings', 'gw2-guild-login' ) );
				?>
			</form>
			
			<div class="gw2gl-admin-sidebar">
				<div class="gw2gl-admin-box">
					<h3><?php _e( 'About GW2 Guild Login', 'gw2-guild-login' ); ?></h3>
					<p><?php _e( 'GW2 Guild Login allows users to log in to your WordPress site using their Guild Wars 2 API key, with optional guild membership verification.', 'gw2-guild-login' ); ?></p>
					<p><?php _e( 'Version', 'gw2-guild-login' ); ?>: <?php echo esc_html( (string) $this->version ); // PHPStan: ensure string ?></p>
				</div>
				
				<div class="gw2gl-admin-box">
					<h3><?php _e( 'Need Help?', 'gw2-guild-login' ); ?></h3>
					<p><?php _e( 'Check out the documentation or contact support if you need assistance.', 'gw2-guild-login' ); ?></p>
					<p>
						<a href="https://example.com/docs/gw2-guild-login" target="_blank" class="button">
							<?php _e( 'Documentation', 'gw2-guild-login' ); ?>
						</a>
						<a href="https://example.com/support" target="_blank" class="button">
							<?php _e( 'Get Support', 'gw2-guild-login' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * General section callback.
	 */
	public function general_section_callback() {
		echo '<p>' . __( 'Configure the general settings for GW2 Guild Login.', 'gw2-guild-login' ) . '</p>';
	}

	/**
	 * API section callback.
	 */
	public function api_section_callback() {
		echo '<p>' . esc_html__( 'Configure how the plugin interacts with the Guild Wars 2 API. These settings affect performance and reliability.', 'gw2-guild-login' ) . '</p>';
	}

	/**
	 * Enqueue modern admin styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ) {
		// Load WP color picker assets
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		// Load modern admin stylesheet
		wp_enqueue_style(
			'gw2-guild-login-modern',
			plugin_dir_url( __FILE__ ) . 'css/admin-style.css',
			array(),
			$this->version
		);
	}

	/**
	 * Append a custom body class when Force Dark Mode is active.
	 *
	 * @param string $classes Existing admin body classes.
	 * @return string Modified classes.
	 */
	public function add_admin_dark_body_class( string $classes ): string {
		$settings = get_option( 'gw2gl_settings', array() );
		if ( ! empty( $settings['appearance_force_dark'] ) ) {
			$classes = trim( $classes . ' gw2-admin-dark' );
		}
		return $classes;
	}

	/**
	 * Color picker field callback.
	 *
	 * @param array $args
	 */
	public function color_picker_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) ? $this->settings[ $id ] : ( isset( $args['default'] ) ? $args['default'] : '' );
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<input type="text" class="gw2gl-color-picker" id="gw2gl_settings[' . esc_attr( $id ) . ']" name="gw2gl_settings[' . esc_attr( $id ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $args['default'] ) . '" />';
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '<script>jQuery(function($){$(".gw2gl-color-picker").wpColorPicker();});</script>';
	}

	/**
	 * Logo upload field callback.
	 *
	 * @param array $args
	 */
	public function logo_upload_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) ? $this->settings[ $id ] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<div class="gw2gl-logo-upload">';
		echo '<input type="text" id="gw2gl_settings[' . esc_attr( $id ) . ']" name="gw2gl_settings[' . esc_attr( $id ) . ']" value="' . esc_attr( $value ) . '" class="regular-text gw2gl-logo-url" /> ';
		echo '<button class="button gw2gl-upload-logo">' . __( 'Upload Logo', 'gw2-guild-login' ) . '</button>';
		if ( $value ) {
			echo '<div><img src="' . esc_url( $value ) . '" alt="Logo Preview" class="gw2-admin-custom-logo" /></div>';
		}
		echo '</div>';
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		// Add JS for media uploader
		$select_logo = esc_js( __( 'Select Logo', 'gw2-guild-login' ) );
		$use_logo    = esc_js( __( 'Use this logo', 'gw2-guild-login' ) );
		echo <<<EOT
<script>
jQuery(function($){
	$(".gw2gl-upload-logo").on("click", function(e){
		e.preventDefault();
		var button = $(this);
		var custom_uploader = wp.media({
			title: "$select_logo",
			button: { text: "$use_logo" },
			multiple: false
		}).on("select", function(){
			var attachment = custom_uploader.state().get("selection").first().toJSON();
			button.prev(".gw2gl-logo-url").val(attachment.url);
			button.parent().find("img").remove();
			button.parent().append('<div><img src="'+attachment.url+'" alt="Logo Preview" class="gw2-admin-custom-logo" /></div>');
		});
		custom_uploader.open();
	});
});
</script>
EOT;
	}

	/**
	 * Textarea field callback.
	 *
	 * @param array $args
	 */
	public function textarea_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) && is_string( $this->settings[ $id ] ) ? $this->settings[ $id ] : ''; // PHPStan: always string.
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<textarea id="gw2gl_settings[' . esc_attr( $id ) . ']" name="gw2gl_settings[' . esc_attr( $id ) . ']" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
	}

	/**
	 * Text field callback.
	 *
	 * @param array $args
	 */
	public function text_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) && is_string( $this->settings[ $id ] ) ? $this->settings[ $id ] : ''; // PHPStan: always string.
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<input type="text" id="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
				name="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
				value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php
		endif;
	}

	/**
	 * Number field callback.
	 *
	 * @param array $args
	 */
	public function number_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) && ( is_string( $this->settings[ $id ] ) || is_numeric( $this->settings[ $id ] ) ) ? $this->settings[ $id ] : ''; // PHPStan: always string or numeric.
		$min         = isset( $args['min'] ) ? $args['min'] : 0;
		$step        = isset( $args['step'] ) ? $args['step'] : 1;
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<input type="number" id="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
				name="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
				value="<?php echo esc_attr( $value ); ?>" 
				min="<?php echo esc_attr( $min ); ?>" 
				step="<?php echo esc_attr( $step ); ?>" 
				class="small-text">
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php
		endif;
	}

	/**
	 * Checkbox field callback.
	 *
	 * @param array $args
	 */
	public function checkbox_field_callback( $args ) {
		$id          = $args['id'];
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$checked     = isset( $this->settings[ $id ] ) && ( $this->settings[ $id ] === '1' || $this->settings[ $id ] === 1 || $this->settings[ $id ] === true ) ? true : false; // PHPStan: always bool.
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<label>
			<input type="checkbox" id="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
					name="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
					value="1" <?php checked( $checked ); ?>>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php
		endif;
	}

	/**
	 * Select field callback.
	 *
	 * @param array $args
	 */
	public function select_field_callback( $args ) {
		$id          = $args['id'];
		$options     = isset( $args['options'] ) ? $args['options'] : array();
		$selected    = isset( $this->settings[ $id ] ) && is_string( $this->settings[ $id ] ) ? $this->settings[ $id ] : ''; // PHPStan: always string.
		$description = isset( $args['description'] ) ? $args['description'] : '';
		?>
		<select id="gw2gl_settings[<?php echo esc_attr( $id ); ?>]" 
				name="gw2gl_settings[<?php echo esc_attr( $id ); ?>]">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php
		endif;
	}

	/**
	 * Get all user roles.
	 *
	 * @return array
	 */
	private function get_user_roles() {
		global $wp_roles;
		$roles = array();

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		foreach ( $wp_roles->get_names() as $role => $name ) {
			$roles[ $role ] = translate_user_role( $name );
		}

		return $roles;
	}

	/**
	 * Add a link to the settings page in the plugin action links.
	 *
	 * @param array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=gw2-guild-login' ),
			__( 'Settings', 'gw2-guild-login' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}
}
