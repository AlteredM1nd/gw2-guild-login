<?php
/**
 * Handles 2FA during the login process
 */
class GW2_2FA_Login {
    /**
     * @var GW2_2FA_Handler
     */
    private $handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->handler = GW2_2FA_Handler::instance();
        
        // Add 2FA form to login page
        add_action('login_form', [$this, 'add_2fa_field']);
        
        // Verify 2FA code during authentication
        add_filter('authenticate', [$this, 'verify_2fa'], 30, 3);
        
        // Handle 2FA verification form submission
        add_action('login_form_2fa_verify', [$this, 'handle_2fa_verification']);
        
        // Enqueue login page scripts
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_scripts']);
    }

    /**
     * Add 2FA field to login form
     */
    public function add_2fa_field() {
        // Only show if user exists and 2FA is enabled
        $user = wp_get_current_user();
        if ($user && $user->exists() && $this->handler->is_2fa_enabled($user->ID)) {
            ?>
            <p>
                <label for="gw2-2fa-code"><?php _e('Authentication Code', 'gw2-guild-login'); ?><br>
                <input type="text" name="gw2_2fa_code" id="gw2-2fa-code" class="input" value="" size="20" autocomplete="off" autofocus>
                </label>
            </p>
            <p class="gw2-2fa-actions">
                <a href="#" id="gw2-use-backup-code"><?php _e('Use a backup code', 'gw2-guild-login'); ?></a>
            </p>
            <?php
        }
    }

    /**
     * Verify 2FA code during authentication
     * 
     * @param WP_User|WP_Error $user
     * @param string $username
     * @param string $password
     * @return WP_User|WP_Error
     */
    public function verify_2fa($user, $username, $password) {
        // Don't interfere with other authentication methods
        if (!is_a($user, 'WP_User') || !$user->exists()) {
            return $user;
        }

        // Check if 2FA is enabled for this user
        if (!$this->handler->is_2fa_enabled($user->ID)) {
            return $user;
        }

        // Verify the 2FA code
        if (empty($_POST['gw2_2fa_code'])) {
            return new WP_Error(
                '2fa_required',
                __('<strong>Error</strong>: Two-factor authentication code is required.', 'gw2-guild-login')
            );
        }

        $code = sanitize_text_field($_POST['gw2_2fa_code']);
        
        // Get the user's 2FA secret
        global $wpdb;
        $table = $wpdb->prefix . 'gw2_2fa_secrets';
        $secret_row = $wpdb->get_row($wpdb->prepare(
            "SELECT secret FROM $table WHERE user_id = %d",
            $user->ID
        ));

        if (!$secret_row) {
            return new WP_Error(
                '2fa_error',
                __('<strong>Error</strong>: Two-factor authentication is not properly configured for your account.', 'gw2-guild-login')
            );
        }

        $secret = $this->handler->decrypt_secret($secret_row->secret);
        
        // Verify the code
        if (!$this->handler->verify_totp($secret, $code)) {
            // Check backup codes
            $backup_codes = get_user_meta($user->ID, 'gw2_2fa_backup_codes', true);
            if (empty($backup_codes) || !in_array($code, $backup_codes)) {
                return new WP_Error(
                    '2fa_invalid_code',
                    __('<strong>Error</strong>: Invalid authentication code.', 'gw2-guild-login')
                );
            }
            
            // Remove used backup code
            $backup_codes = array_values(array_diff($backup_codes, [$code]));
            update_user_meta($user->ID, 'gw2_2fa_backup_codes', $backup_codes);
            
            // If this was the last backup code, generate new ones
            if (empty($backup_codes)) {
                $new_codes = $this->handler->generate_backup_codes();
                $this->handler->enable_2fa($user->ID, $secret, $new_codes);
                
                // Send email to user about new backup codes
                $this->send_backup_codes_email($user, $new_codes);
            }
        }

        return $user;
    }

    /**
     * Handle 2FA verification form submission
     */
    public function handle_2fa_verification() {
        if (empty($_POST['log']) || empty($_POST['pwd'])) {
            wp_safe_redirect(wp_login_url());
            exit;
        }

        $user = wp_authenticate_username_password(
            null,
            sanitize_user($_POST['log']),
            $_POST['pwd']
        );

        if (is_wp_error($user)) {
            wp_safe_redirect(add_query_arg('login', 'failed', wp_login_url()));
            exit;
        }

        // If 2FA is not enabled, log the user in
        if (!$this->handler->is_2fa_enabled($user->ID)) {
            wp_set_auth_cookie($user->ID, !empty($_POST['rememberme']));
            
            $redirect_to = apply_filters('login_redirect', 
                isset($_POST['redirect_to']) ? $_POST['redirect_to'] : admin_url(),
                isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '',
                $user
            );
            
            wp_safe_redirect($redirect_to);
            exit;
        }

        // Show 2FA verification form
        $this->show_2fa_form($user);
        exit;
    }

    /**
     * Display the 2FA verification form
     * 
     * @param WP_User $user
     */
    /**
     * Display the 2FA verification form
     * 
     * @param WP_User $user
     */
    private function show_2fa_form($user) {
        // Load the login header with empty error message
        login_header(
            __('Two-Factor Authentication', 'gw2-guild-login'),
            '',
            null // No error message by default
        );
        ?>
        <form name="2faform" id="2faform" action="<?php echo esc_url(site_url('wp-login.php?action=2fa_verify', 'login_post')); ?>" method="post" autocomplete="off">
            <p><?php _e('Please enter the verification code from your authenticator app.', 'gw2-guild-login'); ?></p>
            
            <p>
                <label for="gw2-2fa-code"><?php _e('Verification Code', 'gw2-guild-login'); ?><br>
                <input type="text" name="gw2_2fa_code" id="gw2-2fa-code" class="input" value="" size="20" autocomplete="off" autofocus>
                </label>
            </p>
            
            <p class="gw2-2fa-actions">
                <a href="#" id="gw2-use-backup-code"><?php _e('Use a backup code', 'gw2-guild-login'); ?></a>
            </p>
            
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Verify', 'gw2-guild-login'); ?>">
                <input type="hidden" name="log" value="<?php echo esc_attr($user->user_login); ?>">
                <input type="hidden" name="pwd" value="<?php echo esc_attr($_POST['pwd']); ?>">
                <input type="hidden" name="rememberme" value="<?php echo !empty($_POST['rememberme']) ? '1' : '0'; ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_POST['redirect_to']) ? $_POST['redirect_to'] : ''); ?>">
                <input type="hidden" name="testcookie" value="1">
                <?php wp_nonce_field('2fa_verify', '_2fa_nonce'); ?>
            </p>
        </form>
        
        <p id="backtoblog">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php _e('← Go to site', 'gw2-guild-login'); ?>
            </a>
        </p>
        
        <script type="text/javascript">
        document.getElementById('gw2-use-backup-code').addEventListener('click', function(e) {
            e.preventDefault();
            var codeField = document.getElementById('gw2-2fa-code');
            codeField.placeholder = '<?php echo esc_js(__('Enter backup code', 'gw2-guild-login')); ?>';
            codeField.focus();
            this.parentNode.removeChild(this);
        });
        document.getElementById('gw2-2fa-code').focus();
        </script>
        <?php
        login_footer();
    }

    /**
     * Send backup codes to user's email
     * 
     * @param WP_User $user
     * @param array $codes
     * @return bool
     */
    private function send_backup_codes_email($user, $codes) {
        $blog_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $subject = sprintf(__('[%s] New Backup Codes', 'gw2-guild-login'), $blog_name);
        
        $message = sprintf(__('Hello %s,', 'gw2-guild-login'), $user->display_name) . "\r\n\r\n";
        $message .= __('You have used your last backup code for two-factor authentication. Here are your new backup codes:', 'gw2-guild-login') . "\r\n\r\n";
        
        foreach ($codes as $code) {
            $message .= $code . "\r\n";
        }
        
        $message .= "\r\n" . __('Each code can only be used once. Save these codes in a safe place.', 'gw2-guild-login') . "\r\n\r\n";
        $message .= sprintf(__('If you did not request new backup codes, please secure your account immediately by visiting %s', 'gw2-guild-login'), admin_url('profile.php')) . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Enqueue login page scripts
     */
    public function enqueue_login_scripts() {
        wp_enqueue_style(
            'gw2-2fa-login',
            plugins_url('assets/css/gw2-2fa-login.css', dirname(__FILE__)),
            [],
            GW2_GUILD_LOGIN_VERSION
        );
        
        wp_enqueue_script(
            'gw2-2fa-login',
            plugins_url('assets/js/gw2-2fa-login.js', dirname(__FILE__)),
            ['jquery'],
            GW2_GUILD_LOGIN_VERSION,
            true
        );
    }
}
