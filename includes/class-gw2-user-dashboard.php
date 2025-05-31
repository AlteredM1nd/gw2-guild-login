<?php
/**
 * GW2_User_Dashboard
 *
 * Handles the enhanced user dashboard for the GW2 Guild Login plugin.
 * Manages user data display, session management, AJAX actions, and profile integration.
 *
 * @package GW2_Guild_Login
 * @since 2.4.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class GW2_User_Dashboard {
    /**
     * Instance of this class.
     *
     * @since 2.4.1
     * @var GW2_User_Dashboard
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class.
     *
     * @since 2.4.1
     * @return GW2_User_Dashboard
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 2.4.1
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_dashboard_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_gw2_dashboard_action', array($this, 'handle_ajax_request'));
    }

    /**
     * Initialize the dashboard.
     *
     * @since 2.4.1
     */
    public function init() {
        // Add user profile fields
        add_action('show_user_profile', array($this, 'add_profile_section'));
        add_action('edit_user_profile', array($this, 'add_profile_section'));
        
        // Save profile fields
        add_action('personal_options_update', array($this, 'save_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_profile_fields'));
    }

    /**
     * Add dashboard menu item.
     *
     * @since 2.4.1
     */
    public function add_dashboard_menu() {
        add_users_page(
            esc_html__('GW2 Account', 'gw2-guild-login'),
            esc_html__('GW2 Account', 'gw2-guild-login'),
            'read',
            'gw2-account',
            array($this, 'render_dashboard_page')
        );
    }

    /**
     * Enqueue dashboard scripts and styles.
     *
     * @since 2.4.1
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts($hook) {
        if ('users_page_gw2-account' !== $hook && 'profile.php' !== $hook && 'user-edit.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'gw2-dashboard-css',
            plugins_url('../assets/css/gw2-dashboard.css', __FILE__),
            array(),
            '2.4.0'
        );

        wp_enqueue_script(
            'gw2-dashboard-js',
            plugins_url('../assets/js/gw2-dashboard.js', __FILE__),
            array('jquery'),
            '2.4.0',
            true
        );

        wp_localize_script('gw2-dashboard-js', 'gw2Dashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gw2-dashboard-nonce'),
            'i18n' => array(
                'confirmLogoutAll' => __('Are you sure you want to log out of all other devices?', 'gw2-guild-login'),
                'confirmRevokeKey' => __('Are you sure you want to revoke your API key? This will log you out.', 'gw2-guild-login'),
                'error' => __('An error occurred. Please try again.', 'gw2-guild-login'),
            ),
        ));
    }

    /**
     * Render the dashboard page.
     *
     * @since 2.4.1
     */
    public function render_dashboard_page() {
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to view this page.', 'gw2-guild-login'));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $gw2_account_id = get_user_meta($user_id, 'gw2_account_id', true);
        $gw2_api_key = get_user_meta($user_id, 'gw2_api_key', true);
        $last_login = get_user_meta($user_id, 'gw2_last_login', true);

        // Get user sessions
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $all_sessions = $sessions->get_all();
        $current_session = wp_get_session_token();
        $current_ip = '';
        $current_ua = '';

        // Get current session info
        if (isset($all_sessions[$current_session])) {
            $current_ip = $all_sessions[$current_session]['ip'];
            $current_ua = $all_sessions[$current_session]['ua'];
        }

        // Include the dashboard template
        include plugin_dir_path(dirname(__FILE__)) . 'templates/dashboard/dashboard.php';
    }

    /**
     * Add GW2 account section to user profile.
     *
     * @since 2.4.1
     * @param WP_User $user User object.
     */
    public function add_profile_section($user) {
        $gw2_account_id = get_user_meta($user->ID, 'gw2_account_id', true);
        $gw2_api_key = get_user_meta($user->ID, 'gw2_api_key', true);
        $last_login = get_user_meta($user->ID, 'gw2_last_login', true);
        ?>
        <h2><?php esc_html_e('Guild Wars 2 Account', 'gw2-guild-login'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="gw2_account_id"><?php esc_html_e('GW2 Account ID', 'gw2-guild-login'); ?></label></th>
                <td>
                    <input type="text" name="gw2_account_id" id="gw2_account_id" value="<?php echo esc_attr($gw2_account_id); ?>" class="regular-text" disabled />
                    <p class="description"><?php esc_html_e('Your Guild Wars 2 account ID.', 'gw2-guild-login'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="gw2_last_login"><?php esc_html_e('Last Login', 'gw2-guild-login'); ?></label></th>
                <td>
                    <input type="text" name="gw2_last_login" id="gw2_last_login" value="<?php echo esc_attr($last_login ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login)) : esc_html__('Never', 'gw2-guild-login')); ?>" class="regular-text" disabled />
                </td>
            </tr>
            <?php if (current_user_can('manage_options') && !empty($gw2_api_key)) : ?>
            <tr>
                <th><label for="gw2_api_key"><?php esc_html_e('API Key', 'gw2-guild-login'); ?></label></th>
                <th><label><?php esc_html_e('API Key', 'gw2-guild-login'); ?></label></th>
                <td>
                    <div class="gw2-api-key-wrapper">
                        <input type="password" value="<?php echo esc_attr($gw2_api_key); ?>" class="regular-text" id="gw2_api_key" readonly />
                        <button type="button" class="button button-secondary" id="toggle-api-key"><?php esc_html_e('Show', 'gw2-guild-login'); ?></button>
                        <button type="button" class="button button-secondary" id="copy-api-key"><?php esc_html_e('Copy', 'gw2-guild-login'); ?></button>
                    </div>
                    <p class="description"><?php esc_html_e('Keep your API key secure and do not share it with anyone.', 'gw2-guild-login'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Save profile fields.
     *
     * @since 2.4.1
     * @param int $user_id User ID.
     */
    public function save_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Add any additional fields to save here
        return true;
    }

    /**
     * Handle AJAX requests.
     *
     * @since 2.4.1
     */
    public function handle_ajax_request() {
        check_ajax_referer('gw2-dashboard-nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform this action.', 'gw2-guild-login'));
        }

        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $user_id = get_current_user_id();

        switch ($action) {
            case 'revoke_sessions':
                $this->revoke_other_sessions($user_id);
                break;
            case 'refresh_data':
                $this->refresh_user_data($user_id);
                break;
            default:
                wp_send_json_error(__('Invalid action.', 'gw2-guild-login'));
        }
    }

    /**
     * Revoke all sessions except the current one.
     *
     * @since 2.4.1
     * @param int $user_id User ID.
     */
    private function revoke_other_sessions($user_id) {
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $current_session = wp_get_session_token();
        $sessions->destroy_others($current_session);
        
        wp_send_json_success(__('All other sessions have been revoked.', 'gw2-guild-login'));
    }

    /**
     * Refresh user data from GW2 API.
     *
     * @since 2.4.1
     * @param int $user_id User ID.
     */
    private function refresh_user_data($user_id) {
        $api_key = get_user_meta($user_id, 'gw2_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(__('No API key found.', 'gw2-guild-login'));
        }

        // Use existing GW2 API class to refresh data
        if (class_exists('GW2_API')) {
            $gw2_api = new GW2_API();
            $account_data = $gw2_api->get_account_data($api_key);
            
            if (is_wp_error($account_data)) {
                wp_send_json_error($account_data->get_error_message());
            }
            
            // Update user meta with fresh data
            update_user_meta($user_id, 'gw2_account_name', $account_data['name']);
            update_user_meta($user_id, 'gw2_world', $account_data['world']);
            update_user_meta($user_id, 'gw2_created', $account_data['created']);
            
            // Get guild data if available
            if (isset($account_data['guilds']) && !empty($account_data['guilds'])) {
                update_user_meta($user_id, 'gw2_guilds', $account_data['guilds']);
            }
            
            wp_send_json_success(__('Account data refreshed successfully.', 'gw2-guild-login'));
        }
        
        wp_send_json_error(__('Failed to refresh account data.', 'gw2-guild-login'));
    }
}

// Initialize the dashboard
function gw2_user_dashboard_init() {
    return GW2_User_Dashboard::get_instance();
}
add_action('plugins_loaded', 'gw2_user_dashboard_init');
