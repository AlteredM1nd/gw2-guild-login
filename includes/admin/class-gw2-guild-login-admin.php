<?php
/**
 * The admin-specific functionality of the plugin.
 */
class GW2_Guild_Login_Admin {
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
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->plugin_name = 'gw2-guild-login';
		$this->version     = GW2_GUILD_LOGIN_VERSION;
		$settings_mixed = get_option( 'gw2gl_settings', array() );
        $this->settings = is_array($settings_mixed) ? $settings_mixed : array(); // PHPStan: always array.
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . '2.6.0/admin/css/gw2-guild-login-admin.css',
			array(),
			$this->version,
			'all'
		);
		// Enqueue WordPress color picker for appearance settings
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_media();
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . '2.6.0/admin/js/gw2-guild-login-admin.js',
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

		// Appearance & Branding Section
		add_settings_section(
			'gw2gl_appearance_section',
			__( 'Appearance & Branding', 'gw2-guild-login' ),
			array( $this, 'appearance_section_callback' ),
			'gw2-guild-login'
		);

		add_settings_field(
			'appearance_primary_color',
			__( 'Primary Color', 'gw2-guild-login' ),
			array( $this, 'color_picker_field_callback' ),
			'gw2-guild-login',
			'gw2gl_appearance_section',
			array(
				'id' => 'appearance_primary_color',
				'default' => '#1976d2',
				'description' => __( 'Choose the primary UI color.', 'gw2-guild-login' ),
			)
		);
		add_settings_field(
			'appearance_accent_color',
			__( 'Accent Color', 'gw2-guild-login' ),
			array( $this, 'color_picker_field_callback' ),
			'gw2-guild-login',
			'gw2gl_appearance_section',
			array(
				'id' => 'appearance_accent_color',
				'default' => '#26c6da',
				'description' => __( 'Choose the accent color.', 'gw2-guild-login' ),
			)
		);
		add_settings_field(
			'appearance_logo',
			__( 'Custom Logo', 'gw2-guild-login' ),
			array( $this, 'logo_upload_field_callback' ),
			'gw2-guild-login',
			'gw2gl_appearance_section',
			array(
				'id' => 'appearance_logo',
				'description' => __( 'Upload a custom logo for the login/dashboard.', 'gw2-guild-login' ),
			)
		);
		add_settings_field(
			'appearance_welcome_text',
			__( 'Custom Welcome Text', 'gw2-guild-login' ),
			array( $this, 'textarea_field_callback' ),
			'gw2-guild-login',
			'gw2gl_appearance_section',
			array(
				'id' => 'appearance_welcome_text',
				'description' => __( 'This text will appear at the top of the login and dashboard pages.', 'gw2-guild-login' ),
			)
		);
		add_settings_field(
			'appearance_force_dark',
			__( 'Force Dark Mode', 'gw2-guild-login' ),
			array( $this, 'checkbox_field_callback' ),
			'gw2-guild-login',
			'gw2gl_appearance_section',
			array(
				'id' => 'appearance_force_dark',
				'label' => __( 'Always use dark mode (override user preference)', 'gw2-guild-login' ),
				'description' => '',
			)
		);


		add_settings_field(
			'target_guild_id',
			__( 'Target Guild IDs', 'gw2-guild-login' ),
			array( $this, 'text_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'target_guild_id',
				'description' => __( 'Enter one or more Guild IDs, separated by commas. Users must be a member of at least one to log in.', 'gw2-guild-login' ),
			)
		);

		add_settings_field(
			'member_role',
			__( 'Default User Role', 'gw2-guild-login' ),
			array( $this, 'select_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'member_role',
				'options'     => $this->get_user_roles(),
				'description' => __( 'Select the default role for new users.', 'gw2-guild-login' ),
			)
		);

		add_settings_field(
			'enable_auto_register',
			__( 'Auto-register New Users', 'gw2-guild-login' ),
			array( $this, 'checkbox_field_callback' ),
			'gw2-guild-login',
			'gw2gl_general_section',
			array(
				'id'          => 'enable_auto_register',
				'label'       => __( 'Enable automatic registration of new users', 'gw2-guild-login' ),
				'description' => __( 'If enabled, new users will be automatically registered when they log in with a valid API key.', 'gw2-guild-login' ),
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
			__( 'API Cache Expiry', 'gw2-guild-login' ),
			array( $this, 'number_field_callback' ),
			'gw2-guild-login',
			'gw2gl_api_section',
			array(
				'id'          => 'api_cache_expiry',
				'min'         => 300,
				'step'        => 60,
				'description' => __( 'How long to cache API responses in seconds. Minimum 300 (5 minutes).', 'gw2-guild-login' ),
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
		// Support multiple guild IDs as array
		if ( isset( $input['target_guild_id'] ) ) {
			$ids = array_filter(array_map('trim', explode(',', $input['target_guild_id'])));
			$input['target_guild_id'] = implode(',', $ids); // Store as comma-separated
		}

		// Sanitize appearance fields
		$input['appearance_primary_color'] = isset($input['appearance_primary_color']) ? sanitize_hex_color($input['appearance_primary_color']) : '';
		$input['appearance_accent_color'] = isset($input['appearance_accent_color']) ? sanitize_hex_color($input['appearance_accent_color']) : '';
		$input['appearance_logo'] = isset($input['appearance_logo']) ? esc_url_raw($input['appearance_logo']) : '';
		$input['appearance_welcome_text'] = isset($input['appearance_welcome_text']) ? sanitize_textarea_field($input['appearance_welcome_text']) : '';
		$input['appearance_force_dark'] = isset($input['appearance_force_dark']) ? 1 : 0;

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
		$settings = get_option('gw2gl_settings', array());
		$primary = !empty($settings['appearance_primary_color']) ? $settings['appearance_primary_color'] : '#1976d2';
		$accent = !empty($settings['appearance_accent_color']) ? $settings['appearance_accent_color'] : '#26c6da';
		$logo = !empty($settings['appearance_logo']) ? $settings['appearance_logo'] : '';
		$force_dark = !empty($settings['appearance_force_dark']);
		$custom_css = ":root { --gw2-admin-primary: $primary; --gw2-admin-accent: $accent; }";
		if ( $force_dark ) {
			$custom_css .= " body { background: #181c22 !important; color: #f7f9fb !important; }";
		}
		echo '<style>'.$custom_css.'</style>';

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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
					<p><?php _e( 'Version', 'gw2-guild-login' ); ?>: <?php echo esc_html( $this->version ); ?></p>
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
	 * Appearance section callback.
	 */
	public function appearance_section_callback() {
		echo '<p>' . __( 'Customize the look and feel of the login and dashboard pages.', 'gw2-guild-login' ) . '</p>';
	}

	/**
	 * API section callback.
	 */
	public function api_section_callback() {
		echo '<p>' . __( 'Configure API-related settings.', 'gw2-guild-login' ) . '</p>';
	}

	/**
	 * Color picker field callback.
	 * @param array $args
	 */
	public function color_picker_field_callback( $args ) {
		$id = $args['id'];
		$value = isset( $this->settings[$id] ) ? $this->settings[$id] : ( isset($args['default']) ? $args['default'] : '' );
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<input type="text" class="gw2gl-color-picker" id="gw2gl_settings['.esc_attr($id).']" name="gw2gl_settings['.esc_attr($id).']" value="'.esc_attr($value).'" data-default-color="'.esc_attr($args['default']).'" />';
		if ( $description ) {
			echo '<p class="description">'.esc_html($description).'</p>';
		}
		echo '<script>jQuery(function($){$(".gw2gl-color-picker").wpColorPicker();});</script>';
	}

	/**
	 * Logo upload field callback.
	 * @param array $args
	 */
	public function logo_upload_field_callback( $args ) {
		$id = $args['id'];
		$value = isset( $this->settings[$id] ) ? $this->settings[$id] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<div class="gw2gl-logo-upload">';
		echo '<input type="text" id="gw2gl_settings['.esc_attr($id).']" name="gw2gl_settings['.esc_attr($id).']" value="'.esc_attr($value).'" class="regular-text gw2gl-logo-url" /> ';
		echo '<button class="button gw2gl-upload-logo">'.__('Upload Logo', 'gw2-guild-login').'</button>';
		if ( $value ) {
			echo '<div><img src="'.esc_url($value).'" alt="Logo Preview" class="gw2-admin-custom-logo" /></div>';
		}
		echo '</div>';
		if ( $description ) {
			echo '<p class="description">'.esc_html($description).'</p>';
		}
		// Add JS for media uploader
		$select_logo = esc_js(__('Select Logo','gw2-guild-login'));
		$use_logo = esc_js(__('Use this logo','gw2-guild-login'));
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
	 * @param array $args
	 */
	public function textarea_field_callback( $args ) {
		$id = $args['id'];
		$value = isset( $this->settings[$id] ) && is_string($this->settings[$id]) ? $this->settings[$id] : ''; // PHPStan: always string.
		$description = isset( $args['description'] ) ? $args['description'] : '';
		echo '<textarea id="gw2gl_settings['.esc_attr($id).']" name="gw2gl_settings['.esc_attr($id).']" rows="3" class="large-text">'.esc_textarea($value).'</textarea>';
		if ( $description ) {
			echo '<p class="description">'.esc_html($description).'</p>';
		}
	}

	/**
	 * Text field callback.
	 *
	 * @param array $args
	 */
	public function text_field_callback( $args ) {
		$id          = $args['id'];
		$value       = isset( $this->settings[ $id ] ) && is_string($this->settings[ $id ]) ? $this->settings[ $id ] : ''; // PHPStan: always string.
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
		$value       = isset( $this->settings[ $id ] ) && (is_string($this->settings[ $id ]) || is_numeric($this->settings[ $id ])) ? $this->settings[ $id ] : ''; // PHPStan: always string or numeric.
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
		$checked     = isset( $this->settings[ $id ] ) && ($this->settings[ $id ] === '1' || $this->settings[ $id ] === 1 || $this->settings[ $id ] === true) ? true : false; // PHPStan: always bool.
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
		$selected    = isset( $this->settings[ $id ] ) && is_string($this->settings[ $id ]) ? $this->settings[ $id ] : ''; // PHPStan: always string.
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
