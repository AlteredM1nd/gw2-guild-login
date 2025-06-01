<?php
/**
 * GW2 User Dashboard Template
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get user data.
$user = wp_get_current_user();
$gw2_account_id = get_user_meta($user->ID, 'gw2_account_id', true);
$gw2_account_name = get_user_meta($user->ID, 'gw2_account_name', true);
$gw2_world = get_user_meta($user->ID, 'gw2_world', true);
$gw2_created = get_user_meta($user->ID, 'gw2_created', true);
$gw2_guilds = get_user_meta($user->ID, 'gw2_guilds', true);
$last_login = get_user_meta($user->ID, 'gw2_last_login', true);

// Get user sessions.
$sessions = WP_Session_Tokens::get_instance($user->ID);
$all_sessions = $sessions->get_all();
$current_session = wp_get_session_token();

// Get browser info.
$browser = array(
    'Chrome' => 'Chrome',
    'Firefox' => 'Firefox',
    'Safari' => 'Safari',
    'Opera' => 'Opera',
    'MSIE' => 'Internet Explorer',
    'Trident' => 'Internet Explorer',
    'Edge' => 'Microsoft Edge'
);

// Get current session info.
$current_ip = '';
$current_ua = '';
$current_browser = __('Unknown', 'gw2-guild-login');

if (isset($all_sessions[$current_session])) {
    $current_ip = $all_sessions[$current_session]['ip'];
    $current_ua = $all_sessions[$current_session]['ua'];
    
    // Detect browser.
    foreach ($browser as $key => $value) {
        if (stripos($current_ua, $key) !== false) {
            $current_browser = $value;
            break;
        }
    }
}
?>

<?php
$gw2gl_settings = get_option('gw2gl_settings', array());
$gw2gl_logo = !empty($gw2gl_settings['appearance_logo']) ? $gw2gl_settings['appearance_logo'] : '';
$gw2gl_welcome = !empty($gw2gl_settings['appearance_welcome_text']) ? $gw2gl_settings['appearance_welcome_text'] : '';
?>
<div class="wrap gw2-dashboard" role="main" aria-label="GW2 User Dashboard">
    <?php if ( $gw2gl_logo ) : ?>
        <div class="gw2-login-logo"><img src="<?php echo esc_url($gw2gl_logo); ?>" alt="<?php esc_attr_e('Site Logo', 'gw2-guild-login'); ?>" class="gw2-admin-custom-logo" /></div>
    <?php endif; ?>
    <?php if ( $gw2gl_welcome ) : ?>
        <div class="gw2-login-welcome-text"><?php echo wp_kses_post($gw2gl_welcome); ?></div>
    <?php endif; ?>
    <h1><?php echo esc_html__('Guild Wars 2 Account', 'gw2-guild-login'); ?></h1>
    
    <?php do_action('gw2_dashboard_before_content'); ?>
    
    <div class="gw2-dashboard-grid">
        <!-- Account Overview -->
        <div class="gw2-card">
            <h2><?php echo esc_html__('Account Overview', 'gw2-guild-login'); ?></h2>
            <div class="gw2-card-content">
                <div class="gw2-account-info">
                    <div class="gw2-account-avatar">
                        <img src="<?php echo esc_url(get_avatar_url($user->ID, array('size' => 96))); ?>" alt="<?php echo esc_attr($user->display_name); ?>" class="gw2-avatar" />
                    </div>
                    <div class="gw2-account-details">
                        <h3><?php echo esc_html($user->display_name); ?></h3>
                        <p class="gw2-account-id">
                            <strong><?php echo esc_html__('Account ID:', 'gw2-guild-login'); ?></strong> 
                            <?php echo esc_html($gw2_account_id ?: esc_html__('Not connected', 'gw2-guild-login')); ?>
                        </p>
                        <p class="gw2-account-world">
                            <strong><?php echo esc_html__('World:', 'gw2-guild-login'); ?></strong> 
                            <?php echo esc_html($gw2_world ?: esc_html__('Unknown', 'gw2-guild-login')); ?>
                        </p>
                        <p class="gw2-account-created">
                            <strong><?php echo esc_html__('Created:', 'gw2-guild-login'); ?></strong> 
                            <?php echo $gw2_created ? esc_html(date_i18n(get_option('date_format'), strtotime($gw2_created))) : esc_html__('Unknown', 'gw2-guild-login'); ?>
                        </p>
                        <p class="gw2-account-last-login">
                            <strong><?php echo esc_html__('Last Login:', 'gw2-guild-login'); ?></strong> 
                            <?php echo $last_login ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login))) : esc_html__('Never', 'gw2-guild-login'); ?>
                        </p>
                        <p class="gw2-account-current-session">
                            <strong><?php echo esc_html__('Current Session:', 'gw2-guild-login'); ?></strong> 
                            <?php echo esc_html($current_browser . ' • ' . $current_ip); ?>
                        </p>
                    </div>
                </div>
                <div class="gw2-account-actions">
                    <button type="button" class="button button-primary" id="refresh-account-data" aria-label="Refresh account data">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html__('Refresh', 'gw2-guild-login'); ?></span>
                        <?php echo esc_html__('Refresh Data', 'gw2-guild-login'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Guild Membership -->
        <?php if (!empty($gw2_guilds) && is_array($gw2_guilds)) : ?>
        <div class="gw2-card">
            <h2><?php echo esc_html__('Guild Membership', 'gw2-guild-login'); ?></h2>
            <div class="gw2-card-content">
                <ul class="gw2-guild-list">
                    <?php foreach ($gw2_guilds as $guild_id => $guild) : ?>
                    <li class="gw2-guild-item">
                        <div class="gw2-guild-avatar">
                            <img src="<?php echo esc_url($guild['emblem'] ?: 'https://render.guildwars2.com/file/0B5A9E4B7E5D4F4E9E4B7E5D4F4E9E4B7/156269.png'); ?>" alt="<?php echo esc_attr($guild['name']); ?>" />
                        </div>
                        <div class="gw2-guild-details">
                            <h4><?php echo esc_html($guild['name']); ?></h4>
                            <p class="gw2-guild-rank">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo esc_html($guild['rank']); ?>
                            </p>
                            <p class="gw2-guild-joined">
                                <span class="dashicons dashicons-calendar"></span>
                                <?php echo esc_html(sprintf(__('Joined: %s', 'gw2-guild-login'), date_i18n(get_option('date_format'), strtotime($guild['joined'])))); ?>
                            </p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Active Sessions -->
        <div class="gw2-card gw2-sessions">
            <h2><?php echo esc_html__('Active Sessions', 'gw2-guild-login'); ?></h2>
            <div class="gw2-card-content">
                <p><?php echo esc_html__('This is a list of devices that are currently logged into your account.', 'gw2-guild-login'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Browser', 'gw2-guild-login'); ?></th>
                            <th><?php echo esc_html__('IP Address', 'gw2-guild-login'); ?></th>
                            <th><?php echo esc_html__('Last Active', 'gw2-guild-login'); ?></th>
                            <th><?php echo esc_html__('Current Session', 'gw2-guild-login'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_sessions as $session_id => $session) : 
                            $browser_name = __('Unknown', 'gw2-guild-login');
                            foreach ($browser as $key => $value) {
                                if (stripos($session['ua'], $key) !== false) {
                                    $browser_name = $value;
                                    break;
                                }
                            }
                            $is_current = $session_id === $current_session;
                        ?>
                        <tr>
                            <td>
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php echo esc_html($browser_name); ?>
                            </td>
                            <td><?php echo esc_html($session['ip']); ?></td>
                            <td>
                                <?php 
                                echo esc_html(
                                    sprintf(
                                        /* translators: %s: human time difference */
                                        __('%s ago', 'gw2-guild-login'), 
                                        human_time_diff($session['login'], current_time('timestamp'))
                                    )
                                );
                                ?>
                            </td>
                            <td>
                                <?php if ($is_current) : ?>
                                    <span class="gw2-badge"><?php echo esc_html__('Current', 'gw2-guild-login'); ?></span>
                                <?php else : ?>
                                    <a href="#" class="gw2-revoke-session" data-session="<?php echo esc_attr($session_id); ?>">
                                        <?php echo esc_html__('Revoke', 'gw2-guild-login'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="gw2-session-actions">
                    <button type="button" class="button button-secondary" id="revoke-other-sessions" aria-label="Revoke all other sessions">
                        <span class="dashicons dashicons-dismiss" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html__('Revoke', 'gw2-guild-login'); ?></span>
                        <?php echo esc_html__('Revoke All Other Sessions', 'gw2-guild-login'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php do_action('gw2_dashboard_after_content'); ?>
    
    <div class="gw2-dashboard-footer">
        <p class="description">
            <?php 
            printf(
                /* translators: %s: current date and time */
                esc_html__('Last updated: %s', 'gw2-guild-login'),
                esc_html(current_time(get_option('date_format') . ' ' . get_option('time_format')))
            );
            ?>
            <span class="sep">|</span>
            <a href="#" id="refresh-page"><?php echo esc_html__('Refresh', 'gw2-guild-login'); ?></a>
        </p>
    </div>
</div>
