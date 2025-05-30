<?php
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('GW2 Guild', 'gw2-guild-login'),
            'GW2 Guild',
            'manage_options',
            $this->menu_slug,
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            30
        );
        
        // Dashboard (main page)
        add_submenu_page(
            $this->menu_slug,
            __('Dashboard', 'gw2-guild-login'),
            __('Dashboard', 'gw2-guild-login'),
            'manage_options',
            $this->menu_slug,
            array($this, 'render_dashboard_page')
        );
        
        // Guild Settings
        add_submenu_page(
            $this->menu_slug,
            __('Guild Settings', 'gw2-guild-login'),
            __('Guild Settings', 'gw2-guild-login'),
            'manage_options',
            'gw2-guild-settings',
            array($this, 'render_settings_page')
        );
        
        // Rank Access
        add_submenu_page(
            $this->menu_slug,
            __('Rank Access', 'gw2-guild-login'),
            __('Rank Access', 'gw2-guild-login'),
            'manage_options',
            'gw2-rank-access',
            array($this, 'render_rank_access_page')
        );
        
        // User Management
        add_submenu_page(
            $this->menu_slug,
            __('User Management', 'gw2-guild-login'),
            __('User Management', 'gw2-guild-login'),
            'manage_options',
            'gw2-user-management',
            array($this, 'render_user_management_page')
        );
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'gw2-guild') !== false) {
            wp_enqueue_style(
                'gw2-admin',
                plugins_url('assets/css/admin.css', dirname(__DIR__)),
                array(),
                GW2_GUILD_LOGIN_VERSION
            );
        }
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include GW2_GUILD_LOGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include GW2_GUILD_LOGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render rank access page
     */
    public function render_rank_access_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submission
        if (isset($_POST['gw2_guild_id'])) {
            check_admin_referer('gw2_guild_settings');
            update_option('gw2_guild_id', sanitize_text_field($_POST['gw2_guild_id']));
            update_option('gw2_api_key', sanitize_text_field($_POST['gw2_api_key']));
            add_settings_error('gw2_messages', 'gw2_message', 'Settings Saved', 'updated');
        }
        
        $guild_id = get_option('gw2_guild_id', '');
        $api_key = get_option('gw2_api_key', '');
        
        include GW2_GUILD_LOGIN_DIR . 'admin/views/rank-access.php';
    }
    
    /**
     * Render user management page
     */
    public function render_user_management_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include GW2_GUILD_LOGIN_DIR . 'admin/views/user-management.php';
    }
}

// Initialize the admin menu
function gw2_admin_menu_init() {
    return GW2_Admin_Menu::instance();
}
add_action('plugins_loaded', 'gw2_admin_menu_init');
