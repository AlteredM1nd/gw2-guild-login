<?php
/**
 * Handles the admin UI for 2FA
 */
class GW2_2FA_Admin {
    /**
     * @var GW2_2FA_Handler
     */
    private $handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->handler = GW2_2FA_Handler::instance();
        
        // Add 2FA section to user profile
        add_action('show_user_profile', [$this, 'add_2fa_profile_section']);
        add_action('edit_user_profile', [$this, 'add_2fa_profile_section']);
        
        // Handle 2FA form submission
        add_action('personal_options_update', [$this, 'save_2fa_settings']);
        add_action('edit_user_profile_update', [$this, 'save_2fa_settings']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Add 2FA section to user profile
     * 
     * @param WP_User $user
     */
    public function add_2fa_profile_section($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        $is_enabled = $this->handler->is_2fa_enabled($user->ID);
        $backup_codes = get_user_meta($user->ID, 'gw2_2fa_backup_codes', true);
        
        // Generate a new secret if 2FA is being set up
        $secret = '';
        $qr_code_url = '';
        $show_setup = false;
        
        if (isset($_GET['setup-2fa']) && !$is_enabled) {
            $secret = $this->handler->generate_secret();
            $qr_code_url = $this->handler->get_qr_code_url($secret, $user->user_login);
            $show_setup = true;
        }
        ?>
        <h2><?php _e('Two-Factor Authentication', 'gw2-guild-login'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Status', 'gw2-guild-login'); ?></th>
                <td>
                    <?php if ($is_enabled) : ?>
                        <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                        <?php _e('Two-factor authentication is enabled.', 'gw2-guild-login'); ?>
                        <p class="description">
                            <a href="#" id="gw2-show-backup-codes">
                                <?php _e('View backup codes', 'gw2-guild-login'); ?>
                            </a>
                        </p>
                        <div id="gw2-backup-codes" style="display: none; margin-top: 10px;">
                            <p><?php _e('Save these backup codes in a safe place. Each code can be used only once.', 'gw2-guild-login'); ?></p>
                            <ul style="font-family: monospace; font-size: 14px; line-height: 1.8;">
                                <?php foreach ($backup_codes as $code) : ?>
                                    <li><?php echo esc_html($code); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p>
                                <button type="button" class="button button-secondary" id="gw2-regenerate-codes">
                                    <?php _e('Generate New Codes', 'gw2-guild-login'); ?>
                                </button>
                            </p>
                        </div>
                        <p>
                            <button type="submit" name="disable_2fa" class="button button-secondary">
                                <?php _e('Disable Two-Factor Authentication', 'gw2-guild-login'); ?>
                            </button>
                        </p>
                    <?php else : ?>
                        <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                        <?php _e('Two-factor authentication is not enabled.', 'gw2-guild-login'); ?>
                        <?php if (!$show_setup) : ?>
                            <p>
                                <a href="?setup-2fa=1" class="button button-primary">
                                    <?php _e('Set Up Two-Factor Authentication', 'gw2-guild-login'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            
            <?php if ($show_setup) : ?>
                <tr>
                    <th><?php _e('Scan QR Code', 'gw2-guild-login'); ?></th>
                    <td>
                        <div style="background: white; padding: 20px; border: 1px solid #ddd; display: inline-block;">
                            <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code" id="gw2-qr-code">
                        </div>
                        <p class="description">
                            <?php _e('Scan this QR code with your authenticator app.', 'gw2-guild-login'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gw2-verification-code"><?php _e('Verification Code', 'gw2-guild-login'); ?></label></th>
                    <td>
                        <input type="text" name="gw2_verification_code" id="gw2-verification-code" class="regular-text" autocomplete="off">
                        <p class="description"><?php _e('Enter the code from your authenticator app.', 'gw2-guild-login'); ?></p>
                        <input type="hidden" name="gw2_2fa_secret" value="<?php echo esc_attr($secret); ?>">
                        <p>
                            <button type="submit" name="enable_2fa" class="button button-primary">
                                <?php _e('Verify and Enable', 'gw2-guild-login'); ?>
                            </button>
                            <a href="" class="button button-secondary">
                                <?php _e('Cancel', 'gw2-guild-login'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Save 2FA settings
     * 
     * @param int $user_id
     */
    public function save_2fa_settings($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Handle enabling 2FA
        if (isset($_POST['enable_2fa']) && !empty($_POST['gw2_verification_code'])) {
            $secret = sanitize_text_field($_POST['gw2_2fa_secret']);
            $code = sanitize_text_field($_POST['gw2_verification_code']);
            
            if ($this->handler->verify_totp($secret, $code)) {
                $backup_codes = $this->handler->generate_backup_codes();
                $result = $this->handler->enable_2fa($user_id, $secret, $backup_codes);
                
                if (is_wp_error($result)) {
                    add_action('user_profile_update_errors', function($errors) use ($result) {
                        $errors->add('2fa_error', $result->get_error_message());
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . 
                             __('Two-factor authentication has been enabled.', 'gw2-guild-login') . 
                             '</p></div>';
                    });
                }
            } else {
                add_action('user_profile_update_errors', function($errors) {
                    $errors->add('2fa_error', __('Invalid verification code.', 'gw2-guild-login'));
                });
            }
        }
        // Handle disabling 2FA
        elseif (isset($_POST['disable_2fa'])) {
            $result = $this->handler->disable_2fa($user_id);
            
            if (is_wp_error($result)) {
                add_action('user_profile_update_errors', function($errors) use ($result) {
                    $errors->add('2fa_error', $result->get_error_message());
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . 
                         __('Two-factor authentication has been disabled.', 'gw2-guild-login') . 
                         '</p></div>';
                });
            }
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'profile.php' && $hook !== 'user-edit.php') {
            return;
        }

        wp_enqueue_style(
            'gw2-2fa-admin',
            plugins_url('assets/css/gw2-2fa-admin.css', dirname(__FILE__)),
            [],
            GW2_GUILD_LOGIN_VERSION
        );

        wp_enqueue_script(
            'gw2-2fa-admin',
            plugins_url('assets/js/gw2-2fa-admin.js', dirname(__FILE__)),
            ['jquery'],
            GW2_GUILD_LOGIN_VERSION,
            true
        );

        wp_localize_script('gw2-2fa-admin', 'gw22fa', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gw2_2fa_nonce'),
            'i18n' => [
                'generating' => __('Generating new codes...', 'gw2-guild-login'),
                'error' => __('An error occurred. Please try again.', 'gw2-guild-login'),
            ]
        ]);
    }
}
