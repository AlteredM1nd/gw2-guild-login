<?php
/**
 * Handles Guild Rank based access control
 */
class GW2_Guild_Ranks {
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var string Guild ranks table name */
    private $table_ranks;
    
    /** @var string Guild members cache key prefix */
    private $cache_prefix = 'gw2_guild_members_';
    
    /** @var int Cache expiration in seconds (1 hour) */
    private $cache_expiration;
    
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
        global $wpdb;
        $this->table_ranks = $wpdb->prefix . 'gw2_guild_ranks';
        
        // Set cache expiration (1 hour)
        $this->cache_expiration = defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 3600;
        
        // Register activation hook
        register_activation_hook(GW2_GUILD_LOGIN_FILE, array($this, 'activate'));
        
        // Register shortcode
        add_shortcode('gw2_restricted', array($this, 'restricted_content_shortcode'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;
        
        $sql = "CREATE TABLE {$this->table_ranks} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            rank_id varchar(50) NOT NULL,
            rank_name varchar(100) NOT NULL,
            guild_id varchar(50) NOT NULL,
            permissions text,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY rank_guild (rank_id, guild_id)
        ) " . $wpdb->get_charset_collate() . ";";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'GW2 Guild Settings',
            'GW2 Guild',
            'manage_options',
            'gw2-guild-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['gw2_guild_id'])) {
            check_admin_referer('gw2_guild_settings');
            update_option('gw2_guild_id', sanitize_text_field($_POST['gw2_guild_id']));
            update_option('gw2_api_key', sanitize_text_field($_POST['gw2_api_key']));
            add_settings_error('gw2_messages', 'gw2_message', 'Settings Saved', 'updated');
        }
        
        $guild_id = get_option('gw2_guild_id', '');
        $api_key = get_option('gw2_api_key', '');
        
        ?>
        <div class="wrap">
            <h1>GW2 Guild Settings</h1>
            <?php settings_errors('gw2_messages'); ?>
            <form method="post">
                <?php wp_nonce_field('gw2_guild_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="gw2_guild_id">Guild ID</label></th>
                        <td>
                            <input type="text" id="gw2_guild_id" name="gw2_guild_id" 
                                   value="<?php echo esc_attr($guild_id); ?>" class="regular-text">
                            <p class="description">Your guild's UUID (found in guild panel URL)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gw2_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="gw2_api_key" name="gw2_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">GW2 API key with 'guild' permission</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <h2>Shortcode Usage</h2>
            <p>Use the following shortcode to restrict content by guild rank:</p>
            <pre><code>[gw2_restricted rank="Officer"]This content is only visible to officers.[/gw2_restricted]</code></pre>
            <p>You can customize the access denied message:</p>
            <pre><code>[gw2_restricted rank="Member" message="Members only! Join our guild to see this content."]...[/gw2_restricted]</code></pre>
        </div>
        <?php
    }
    
    /**
     * Fetch guild data from GW2 API
     */
    private function fetch_guild_data($guild_id) {
        $api_key = get_option('gw2_api_key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'GW2 API key is not configured');
        }
        
        $ranks_url = "https://api.guildwars2.com/v2/guild/$guild_id/ranks?access_token=$api_key";
        $members_url = "https://api.guildwars2.com/v2/guild/$guild_id/members?access_token=$api_key";
        
        $ranks_response = wp_remote_get($ranks_url);
        $members_response = wp_remote_get($members_url);
        
        if (is_wp_error($ranks_response) || is_wp_error($members_response)) {
            return new WP_Error('api_error', 'Failed to fetch guild data from GW2 API');
        }
        
        $ranks = json_decode(wp_remote_retrieve_body($ranks_response), true);
        $members = json_decode(wp_remote_retrieve_body($members_response), true);
        
        return array(
            'ranks' => $ranks,
            'members' => $members,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Check if user has required guild rank
     */
    public function check_rank_access($user_id, $required_rank) {
        $guild_id = get_user_meta($user_id, 'gw2_guild_id', true);
        $account_name = get_user_meta($user_id, 'gw2_account_name', true);
        
        if (empty($guild_id) || empty($account_name)) {
            return false;
        }
        
        $cache_key = $this->cache_prefix . $guild_id;
        $data = get_transient($cache_key);
        
        // If no cache or cache is invalid, fetch fresh data
        if (false === $data) {
            $data = $this->fetch_guild_data($guild_id);
            
            if (is_wp_error($data)) {
                error_log('GW2 Guild Ranks: ' . $data->get_error_message());
                return false;
            }
            
            set_transient($cache_key, $data, $this->cache_expiration);
        }
        
        // Find the user in members list
        foreach ($data['members'] as $member) {
            if ($member['name'] === $account_name) {
                return $member['rank'] === $required_rank;
            }
        }
        
        return false;
    }
    
    /**
     * Restricted content shortcode
     */
    public function restricted_content_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'rank' => '',
            'message' => 'You do not have permission to view this content.'
        ), $atts);
        
        if (empty($atts['rank'])) {
            return '<div class="gw2-error">Error: No rank specified in shortcode.</div>';
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="gw2-login-required">Please log in to view this content.</div>';
        }
        
        if ($this->check_rank_access($user_id, $atts['rank'])) {
            return do_shortcode($content);
        } else {
            return '<div class="gw2-access-denied">' . esc_html($atts['message']) . '</div>';
        }
    }
}

// Initialize the plugin
function gw2_guild_ranks_init() {
    return GW2_Guild_Ranks::instance();
}
add_action('plugins_loaded', 'gw2_guild_ranks_init');
