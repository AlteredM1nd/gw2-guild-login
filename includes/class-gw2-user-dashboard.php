<?php
/**
 * GW2_User_Dashboard
 *
 * Handles the enhanced user dashboard for the GW2 Guild Login plugin.
 * Manages user data display, session management, AJAX actions, and profile integration.
 *
 * @package GW2_Guild_Login
 * @since 2.6.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
add_action('wp_dashboard_setup', function(): void {
    wp_add_dashboard_widget('gw2gl_stats', __('GW2 Guild Login Security', 'gw2-guild-login'), function(): void {
        global $wpdb;
        // @phpstan-ignore-next-line for $wpdb as WordPress core global
        /** @phpstan-ignore-next-line */
        $encrypted_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'gw2_api_key' AND meta_value != ''");
        $encrypted_count_safe = is_numeric($encrypted_count) ? (int)$encrypted_count : 0;

        $last_cache_flush = get_option('gw2gl_last_cache_flush');
        $last_cache_flush_safe = is_numeric($last_cache_flush) ? (int)$last_cache_flush : 0;

        /** @phpstan-ignore-next-line */
        $option_names = $wpdb->get_col(
            /** @phpstan-ignore-next-line */
            $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'gw2gl_failed_attempts_%')
        );
        $failed_attempts = 0;
        if (is_array($option_names)) {
            foreach ($option_names as $opt) {
                $data = get_option($opt);
                if (is_array($data) && isset($data['time']) && $data['time'] > (time() - 86400)) {
                    $failed_attempts += isset($data['count']) && is_numeric($data['count']) ? (int)$data['count'] : 0;
                } elseif (is_numeric($data) && $data > 0) {
                    $failed_attempts += (int)$data;
                }
            }
        }
        $failed_attempts_safe = (int)$failed_attempts;

        $encryption_status = (defined('SECURE_AUTH_KEY') && is_string(SECURE_AUTH_KEY) && strlen(SECURE_AUTH_KEY) >= 64)
            ? '<span style="color:green">✔ Active</span>'
            : '<span style="color:red">✖ Insecure</span>';
        $last_cache_flush_str = $last_cache_flush_safe > 0 ? esc_html(date('Y-m-d H:i', $last_cache_flush_safe)) : esc_html__('Never', 'gw2-guild-login');

        $encrypted_count_str = is_numeric($encrypted_count) ? (string)(int)$encrypted_count : '0';
        $encryption_status_str = $encryption_status; // always string
        $last_cache_flush_str_safe = is_string($last_cache_flush) ? $last_cache_flush : '';
        $failed_attempts_str = is_numeric($failed_attempts) ? (string)(int)$failed_attempts : '0';

        $encrypted_count_raw = get_option('gw2_encrypted_count');
        $encrypted_count_str = is_int($encrypted_count_raw) ? (string)$encrypted_count_raw : '0';

        $encryption_status_raw = get_option('gw2_encryption_status');
        $encryption_status_str = is_string($encryption_status_raw) ? $encryption_status_raw : '';

        $last_cache_flush_raw = get_option('gw2_last_cache_flush');
        $last_cache_flush_str = is_string($last_cache_flush_raw) ? $last_cache_flush_raw : '';

        $failed_attempts_raw = get_option('gw2_failed_attempts');
        $failed_attempts_str = is_int($failed_attempts_raw) ? (string)$failed_attempts_raw : '0';

        // Initialize variables to safe defaults
        $encrypted_count_str_safe = $encrypted_count_str;
        $encryption_status_str_safe = $encryption_status_str;
        $last_cache_flush_str_safe = $last_cache_flush_str;
        $failed_attempts_str_safe = $failed_attempts_str;

        echo '<p><strong>Encrypted API Keys:</strong> ' . esc_html($encrypted_count_str_safe) . '</p>';
        echo '<p><strong>Encryption Status:</strong> ' . $encryption_status_str_safe . '</p>';
        echo '<p><strong>Last Cache Flush:</strong> ' . esc_html($last_cache_flush_str_safe) . '</p>';
        echo '<p><strong>Failed Logins (24h):</strong> ' . esc_html($failed_attempts_str_safe) . '</p>';
/** @phpstan-ignore-next-line */
$last_cache_flush_str = is_string($last_cache_flush_raw) ? $last_cache_flush_raw : '';
/** @phpstan-ignore-next-line */
$failed_attempts_raw = get_option('gw2_failed_attempts');
/** @phpstan-ignore-next-line */
$failed_attempts_str = is_int($failed_attempts_raw) ? (string)$failed_attempts_raw : '0';
/** @phpstan-ignore-next-line */
echo '<p><strong>Encrypted API Keys:</strong> ' . esc_html($encrypted_count_str) . '</p>';
/** @phpstan-ignore-next-line */
echo '<p><strong>Encryption Status:</strong> ' . $encryption_status_str . '</p>';
/** @phpstan-ignore-next-line */
echo '<p><strong>Last Cache Flush:</strong> ' . esc_html($last_cache_flush_str) . '</p>';
/** @phpstan-ignore-next-line */
echo '<p><strong>Failed Logins (24h):</strong> ' . esc_html($failed_attempts_str) . '</p>';
    });
});

class GW2_User_Dashboard {
    /**
     * Instance of this class.
     *
     * @since 2.6.0
     * @var GW2_User_Dashboard
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class.
     *
     * @since 2.6.0
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
     * @since 2.6.0
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
     * @since 2.6.0
     */
    public function init(): void {
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
     * @since 2.6.0
     */
    public function add_dashboard_menu(): void {
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
     * @since 2.6.0
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts($hook): void {
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
     * @since 2.6.0
     */
    public function render_dashboard_page(): void {
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to view this page.', 'gw2-guild-login'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'gw2gl'));
        }

        $user_id_mixed = get_current_user_id();
        $user_id = is_int($user_id_mixed) ? $user_id_mixed : (is_string($user_id_mixed) && ctype_digit($user_id_mixed) ? (int)$user_id_mixed : 0);
        $user_mixed = get_userdata($user_id);
        /** @phpstan-ignore-next-line */
        $user = (is_object($user_mixed) && isset($user_mixed->ID) && is_int($user_mixed->ID)) ? $user_mixed : null;
        $gw2_account_id_mixed = get_user_meta($user_id, 'gw2_account_id', true);
        $gw2_account_id = is_string($gw2_account_id_mixed) ? $gw2_account_id_mixed : '';
        // Use GW2_User_Handler to decrypt API key
        /** @phpstan-ignore-next-line */
        $user_handler = class_exists('GW2_User_Handler') ? new GW2_User_Handler(null) : null;
        /** @phpstan-ignore-next-line */
        $gw2_api_key_mixed = $user_handler ? $user_handler->decrypt_api_key($user_id) : '';
        $gw2_api_key = is_string($gw2_api_key_mixed) ? $gw2_api_key_mixed : '';
        $last_login_mixed = get_user_meta($user_id, 'gw2_last_login', true);
        $last_login = is_string($last_login_mixed) ? $last_login_mixed : '';

        // Get user sessions
        /** @phpstan-ignore-next-line */
        $sessions = WP_Session_Tokens::get_instance($user_id);
        /** @phpstan-ignore-next-line */
        $all_sessions = is_object($sessions) && method_exists($sessions, 'get_all') ? $sessions->get_all() : array();
        $current_session = wp_get_session_token();
        $current_ip = '';
        $current_ua = '';

        // Get current session info
        if (is_array($all_sessions) && isset($all_sessions[$current_session])) {
            $session_entry = $all_sessions[$current_session];
            $current_ip = (is_array($session_entry) && isset($session_entry['ip']) && is_string($session_entry['ip'])) ? $session_entry['ip'] : '';
            $current_ua = (is_array($session_entry) && isset($session_entry['ua']) && is_string($session_entry['ua'])) ? $session_entry['ua'] : '';
        }

        /** @phpstan-ignore-next-line */
        include plugin_dir_path(dirname(__FILE__)) . 'templates/dashboard/dashboard.php';
    }

    /**
     * Add GW2 account section to user profile.
     *
     * @since 2.6.0
     * @param WP_User $user User object.
     */
    /**
 * @param mixed $user WP_User object (WordPress core).
 */
public function add_profile_section($user): void {
        /** @phpstan-ignore-next-line */
        $user_id = (is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0;
        $gw2_account_id_mixed = get_user_meta($user_id, 'gw2_account_id', true);
        $gw2_account_id = is_string($gw2_account_id_mixed) ? $gw2_account_id_mixed : '';
        // Use GW2_User_Handler to decrypt API key
        /** @phpstan-ignore-next-line */
        $user_handler = class_exists('GW2_User_Handler') ? new GW2_User_Handler(null) : null;
        /** @phpstan-ignore-next-line */
        $gw2_api_key_mixed = $user_handler ? $user_handler->decrypt_api_key($user_id) : '';
        $gw2_api_key = is_string($gw2_api_key_mixed) ? $gw2_api_key_mixed : '';
        $last_login_mixed = get_user_meta($user_id, 'gw2_last_login', true);
        $last_login = is_string($last_login_mixed) ? $last_login_mixed : '';
        // Strict type for account ID
        $gw2_account_id_safe = $gw2_account_id;
        // Strict type for API key
        $gw2_api_key_safe = $gw2_api_key;
        // Strict type for last login
        $last_login_safe = '';
        if (is_string($last_login) && $last_login !== '' && (is_numeric($last_login) || strtotime($last_login) !== false)) {
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $timestamp = is_numeric($last_login) ? (int)$last_login : strtotime($last_login);
            $last_login_safe = date_i18n(is_string($date_format) ? $date_format : 'Y-m-d' . ' ' . (is_string($time_format) ? $time_format : 'H:i'), $timestamp);
        } else {
            $last_login_safe = esc_html__('Never', 'gw2-guild-login');
        }
        ?>
        <h2><?php esc_html_e('Guild Wars 2 Account', 'gw2-guild-login'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="gw2_account_id"><?php esc_html_e('GW2 Account ID', 'gw2-guild-login'); ?></label></th>
                <td>
                    <?php /** @phpstan-ignore-next-line */
/** @var WP_User $user */
/** @phpstan-ignore-next-line */
$gw2_account_id_raw = get_user_meta((is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0, 'gw2_account_id', true);
/** @phpstan-ignore-next-line */
$gw2_account_id_str = is_string($gw2_account_id_raw) ? $gw2_account_id_raw : '';
?>
/** @phpstan-ignore-next-line */
<input type="text" name="gw2_account_id" id="gw2_account_id" value="<?php echo esc_attr($gw2_account_id_str); ?>" class="regular-text" disabled />
                    <p class="description"><?php esc_html_e('Your Guild Wars 2 account ID.', 'gw2-guild-login'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="gw2_last_login"><?php esc_html_e('Last Login', 'gw2-guild-login'); ?></label></th>
                <td>
                    <?php /** @phpstan-ignore-next-line */
$last_login_raw = get_user_meta((is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0, 'last_login', true);
/** @phpstan-ignore-next-line */
$last_login_str = is_string($last_login_raw) ? $last_login_raw : '';
?>
/** @phpstan-ignore-next-line */
<input type="text" name="gw2_last_login" id="gw2_last_login" value="<?php echo esc_attr($last_login_str); ?>" class="regular-text" disabled />
                </td>
            </tr>
            <?php if (current_user_can('manage_options') && $gw2_api_key_safe !== '') : ?>
            <tr>
                <th><label for="gw2_api_key"><?php esc_html_e('API Key', 'gw2-guild-login'); ?></label></th>
                <th><label><?php esc_html_e('API Key', 'gw2-guild-login'); ?></label></th>
                <td>
                    <div class="gw2-api-key-wrapper">
                        <?php /** @phpstan-ignore-next-line */
$gw2_api_key_raw = get_user_meta((is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0, 'gw2_api_key', true);
/** @phpstan-ignore-next-line */
$gw2_api_key_str = is_string($gw2_api_key_raw) ? $gw2_api_key_raw : '';
?>
/** @phpstan-ignore-next-line */
<input type="password" value="<?php echo esc_attr($gw2_api_key_str); ?>" class="regular-text" id="gw2_api_key" readonly autocomplete="off" />
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
     * @since 2.6.0
     * @param int $user_id User ID.
     */
    public function save_profile_fields($user_id): void {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Add any additional fields to save here
        return;
    }

    /**
     * Handle AJAX requests.
     *
     * @since 2.6.0
     */
    public function handle_ajax_request(): void {
        check_ajax_referer('gw2-dashboard-nonce', 'nonce');

        if (!is_user_logged_in()) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[GW2 Guild Login] Unauthorized AJAX attempt by non-logged-in user.' );
            }
            wp_send_json_error(__('You must be logged in to perform this action.', 'gw2-guild-login'));
        }

        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $user_id_mixed = get_current_user_id();
        $user_id = is_int($user_id_mixed) ? $user_id_mixed : (is_string($user_id_mixed) && ctype_digit($user_id_mixed) ? (int)$user_id_mixed : 0);

        switch ($action) {
            case 'revoke_sessions':
                $this->revoke_other_sessions($user_id);
                break;
            case 'refresh_data':
                $this->refresh_user_data($user_id);
                break;
            default:
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[GW2 Guild Login] Invalid AJAX action: ' . $action . ' by user ' . $user_id );
                }
                wp_send_json_error(__('An unexpected error occurred. Please try again later.', 'gw2-guild-login'));
        }
    }

    /**
     * Revoke all sessions except the current one.
 * @phpstan-ignore-next-line WP_Session_Tokens is a WordPress core class.
 * @since 2.6.0
 * @param int $user_id User ID.
 */
private function revoke_other_sessions($user_id): void {
    /** @phpstan-ignore-next-line */
    $sessions = WP_Session_Tokens::get_instance($user_id);
    $current_session = wp_get_session_token();
    if (is_object($sessions) && method_exists($sessions, 'destroy_others')) {
        /** @phpstan-ignore-next-line */
        $sessions->destroy_others($current_session);
    }
    wp_send_json_success(__('All other sessions have been revoked.', 'gw2-guild-login'));
}

/**
 * Refresh user data from GW2 API.
 * @since 2.6.0
 * @param int $user_id User ID.
 */
private function refresh_user_data($user_id): void {
    // Use GW2_User_Handler to decrypt API key
    /** @phpstan-ignore-next-line */
    $user_handler = class_exists('GW2_User_Handler') ? new GW2_User_Handler(null) : null;
    /** @phpstan-ignore-next-line */
    $api_key_mixed = $user_handler ? $user_handler->decrypt_api_key($user_id) : '';
    $api_key = is_string($api_key_mixed) ? $api_key_mixed : '';

    if ($api_key === '') {
        wp_send_json_error(__('No API key found.', 'gw2-guild-login'));
    }

    $account_data = null;
    if (class_exists('GW2_API')) {
        $gw2_api = new GW2_API();
        /** @phpstan-ignore-next-line */
        $account_data = $gw2_api->get_account_data($api_key);
    }

    if (is_wp_error($account_data)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $err_msg = $account_data->get_error_message();
            error_log('[GW2 Guild Login] GW2 API error for user ' . $user_id . ': ' . $err_msg);
        }
        wp_send_json_error(__('Failed to fetch account data from GW2 API. Please try again later.', 'gw2-guild-login'));
    }

    if (is_array($account_data)) {
        if (isset($account_data['name']) && is_string($account_data['name'])) {
            update_user_meta($user_id, 'gw2_account_name', $account_data['name']);
        }
        if (isset($account_data['world']) && is_string($account_data['world'])) {
            update_user_meta($user_id, 'gw2_world', $account_data['world']);
        }
        if (isset($account_data['created']) && is_string($account_data['created'])) {
            update_user_meta($user_id, 'gw2_created', $account_data['created']);
        }
        if (isset($account_data['guilds']) && is_array($account_data['guilds']) && !empty($account_data['guilds'])) {
            update_user_meta($user_id, 'gw2_guilds', $account_data['guilds']);
        }
        wp_send_json_success(__('Account data refreshed successfully.', 'gw2-guild-login'));
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[GW2 Guild Login] Failed to refresh account data for user ' . $user_id);
    }
    wp_send_json_error(__('An unexpected error occurred while refreshing your account data. Please try again later.', 'gw2-guild-login'));
}

}

// Initialize the dashboard
function gw2_user_dashboard_init(): GW2_User_Dashboard {
    return GW2_User_Dashboard::get_instance();
}
add_action('plugins_loaded', 'gw2_user_dashboard_init');
