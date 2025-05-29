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
        $this->version = GW2_GUILD_LOGIN_VERSION;
        $this->settings = get_option('gw2gl_settings', array());
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . '../admin/css/gw2-guild-login-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . '../admin/js/gw2-guild-login-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script(
            $this->plugin_name,
            'gw2gl_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gw2gl_admin_nonce'),
                'i18n' => array(
                    'confirm_reset' => __('Are you sure you want to reset all settings? This cannot be undone.', 'gw2-guild-login'),
                    'saving' => __('Saving...', 'gw2-guild-login'),
                    'saved' => __('Settings saved!', 'gw2-guild-login'),
                    'error' => __('An error occurred. Please try again.', 'gw2-guild-login'),
                )
            )
        );
    }

    /**
     * Add the plugin admin menu.
     */
    public function add_admin_menu() {
        add_options_page(
            __('GW2 Guild Login Settings', 'gw2-guild-login'),
            __('GW2 Guild Login', 'gw2-guild-login'),
            'manage_options',
            'gw2-guild-login',
            array($this, 'display_plugin_settings_page')
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @param array $links
     * @return array
     */
    public function add_action_links($links) {
        $settings_link = array(
            '<a href="' . admin_url('options-general.php?page=gw2-guild-login') . '">' . __('Settings', 'gw2-guild-login') . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    /**
     * Register the plugin settings.
     */
    public function register_settings() {
        register_setting(
            'gw2gl_settings_group',
            'gw2gl_settings',
            array($this, 'sanitize_settings')
        );

        // General Settings Section
        add_settings_section(
            'gw2gl_general_section',
            __('General Settings', 'gw2-guild-login'),
            array($this, 'general_section_callback'),
            'gw2-guild-login'
        );

        add_settings_field(
            'target_guild_id',
            __('Target Guild ID', 'gw2-guild-login'),
            array($this, 'text_field_callback'),
            'gw2-guild-login',
            'gw2gl_general_section',
            array(
                'id' => 'target_guild_id',
                'description' => __('Enter the Guild ID that users must be a member of to log in.', 'gw2-guild-login')
            )
        );

        add_settings_field(
            'member_role',
            __('Default User Role', 'gw2-guild-login'),
            array($this, 'select_field_callback'),
            'gw2-guild-login',
            'gw2gl_general_section',
            array(
                'id' => 'member_role',
                'options' => $this->get_user_roles(),
                'description' => __('Select the default role for new users.', 'gw2-guild-login')
            )
        );

        add_settings_field(
            'enable_auto_register',
            __('Auto-register New Users', 'gw2-guild-login'),
            array($this, 'checkbox_field_callback'),
            'gw2-guild-login',
            'gw2gl_general_section',
            array(
                'id' => 'enable_auto_register',
                'label' => __('Enable automatic registration of new users', 'gw2-guild-login'),
                'description' => __('If enabled, new users will be automatically registered when they log in with a valid API key.', 'gw2-guild-login')
            )
        );

        // API Settings Section
        add_settings_section(
            'gw2gl_api_section',
            __('API Settings', 'gw2-guild-login'),
            array($this, 'api_section_callback'),
            'gw2-guild-login'
        );

        add_settings_field(
            'api_cache_expiry',
            __('API Cache Expiry', 'gw2-guild-login'),
            array($this, 'number_field_callback'),
            'gw2-guild-login',
            'gw2gl_api_section',
            array(
                'id' => 'api_cache_expiry',
                'min' => 300,
                'step' => 60,
                'description' => __('How long to cache API responses in seconds. Minimum 300 (5 minutes).', 'gw2-guild-login')
            )
        );
    }

    /**
     * Sanitize the settings before they are saved.
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $current_settings = get_option('gw2gl_settings', array());

        // General Settings
        $sanitized['target_guild_id'] = isset($input['target_guild_id']) ? sanitize_text_field($input['target_guild_id']) : '';
        $sanitized['member_role'] = isset($input['member_role']) && array_key_exists($input['member_role'], $this->get_user_roles())
            ? $input['member_role']
            : 'subscriber';
        $sanitized['enable_auto_register'] = isset($input['enable_auto_register']) ? 1 : 0;

        // API Settings
        $sanitized['api_cache_expiry'] = isset($input['api_cache_expiry']) ? absint($input['api_cache_expiry']) : 3600;
        if ($sanitized['api_cache_expiry'] < 300) {
            $sanitized['api_cache_expiry'] = 300;
        }

        // Add admin notice for settings saved
        add_settings_error(
            'gw2gl_settings',
            'settings_updated',
            __('Settings saved successfully.', 'gw2-guild-login'),
            'updated'
        );

        return $sanitized;
    }

    /**
     * Display the plugin settings page.
     */
    public function display_plugin_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('gw2gl_settings_group');
                do_settings_sections('gw2-guild-login');
                submit_button(__('Save Settings', 'gw2-guild-login'));
                ?>
            </form>
            
            <div class="gw2gl-admin-sidebar">
                <div class="gw2gl-admin-box">
                    <h3><?php _e('About GW2 Guild Login', 'gw2-guild-login'); ?></h3>
                    <p><?php _e('GW2 Guild Login allows users to log in to your WordPress site using their Guild Wars 2 API key, with optional guild membership verification.', 'gw2-guild-login'); ?></p>
                    <p><?php _e('Version', 'gw2-guild-login'); ?>: <?php echo esc_html($this->version); ?></p>
                </div>
                
                <div class="gw2gl-admin-box">
                    <h3><?php _e('Need Help?', 'gw2-guild-login'); ?></h3>
                    <p><?php _e('Check out the documentation or contact support if you need assistance.', 'gw2-guild-login'); ?></p>
                    <p>
                        <a href="https://example.com/docs/gw2-guild-login" target="_blank" class="button">
                            <?php _e('Documentation', 'gw2-guild-login'); ?>
                        </a>
                        <a href="https://example.com/support" target="_blank" class="button">
                            <?php _e('Get Support', 'gw2-guild-login'); ?>
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
        echo '<p>' . __('Configure the general settings for GW2 Guild Login.', 'gw2-guild-login') . '</p>';
    }

    /**
     * API section callback.
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure API-related settings.', 'gw2-guild-login') . '</p>';
    }

    /**
     * Text field callback.
     *
     * @param array $args
     */
    public function text_field_callback($args) {
        $id = $args['id'];
        $value = isset($this->settings[$id]) ? $this->settings[$id] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        ?>
        <input type="text" id="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
               name="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
               value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }

    /**
     * Number field callback.
     *
     * @param array $args
     */
    public function number_field_callback($args) {
        $id = $args['id'];
        $value = isset($this->settings[$id]) ? $this->settings[$id] : '';
        $min = isset($args['min']) ? $args['min'] : 0;
        $step = isset($args['step']) ? $args['step'] : 1;
        $description = isset($args['description']) ? $args['description'] : '';
        ?>
        <input type="number" id="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
               name="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               min="<?php echo esc_attr($min); ?>" 
               step="<?php echo esc_attr($step); ?>" 
               class="small-text">
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }

    /**
     * Checkbox field callback.
     *
     * @param array $args
     */
    public function checkbox_field_callback($args) {
        $id = $args['id'];
        $label = isset($args['label']) ? $args['label'] : '';
        $checked = isset($this->settings[$id]) ? (bool) $this->settings[$id] : false;
        $description = isset($args['description']) ? $args['description'] : '';
        ?>
        <label>
            <input type="checkbox" id="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
                   name="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
                   value="1" <?php checked($checked); ?>>
            <?php echo esc_html($label); ?>
        </label>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }

    /**
     * Select field callback.
     *
     * @param array $args
     */
    public function select_field_callback($args) {
        $id = $args['id'];
        $options = isset($args['options']) ? $args['options'] : array();
        $selected = isset($this->settings[$id]) ? $this->settings[$id] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        ?>
        <select id="gw2gl_settings[<?php echo esc_attr($id); ?>]" 
                name="gw2gl_settings[<?php echo esc_attr($id); ?>]">
            <?php foreach ($options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif;
    }

    /**
     * Get all user roles.
     *
     * @return array
     */
    private function get_user_roles() {
        global $wp_roles;
        $roles = array();
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        foreach ($wp_roles->get_names() as $role => $name) {
            $roles[$role] = translate_user_role($name);
        }
        
        return $roles;
    }

    /**
     * Add a link to the settings page in the plugin action links.
     *
     * @param array $links
     * @return array
     */
    public function plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=gw2-guild-login'),
            __('Settings', 'gw2-guild-login')
        );
        
        array_unshift($links, $settings_link);
        return $links;
    }
}
