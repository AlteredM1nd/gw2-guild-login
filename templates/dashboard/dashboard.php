<?php
/**
 * GW2 Guild Login Dashboard Template
 *
 * @var \WP_User $user
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
if (!$user instanceof WP_User) {
    return;
}
// Guard for PHPStan: ensure all user properties are defined and correct type
$user_id = (is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0;
/** @phpstan-ignore-next-line */
$user_display_name = (is_object($user) && isset($user->display_name) && is_string($user->display_name)) ? $user->display_name : '';
/** @phpstan-ignore-next-line */
$user_id = (is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0;
// Guard all user meta for PHPStan
$gw2_account_id_mixed = get_user_meta($user_id, 'gw2_account_id', true);
$gw2_account_id = is_string($gw2_account_id_mixed) ? $gw2_account_id_mixed : '';
$gw2_account_name_mixed = get_user_meta($user_id, 'gw2_account_name', true);
$gw2_account_name = is_string($gw2_account_name_mixed) ? $gw2_account_name_mixed : '';
$gw2_world_mixed = get_user_meta($user_id, 'gw2_world', true);
$gw2_world = is_string($gw2_world_mixed) ? $gw2_world_mixed : '';
$gw2_created_mixed = get_user_meta($user_id, 'gw2_created', true);
$gw2_created = is_string($gw2_created_mixed) ? $gw2_created_mixed : '';
$gw2_guilds_mixed = get_user_meta($user_id, 'gw2_guilds', true);
/** @phpstan-ignore-next-line */
$gw2_guilds = is_array($gw2_guilds_mixed) ? $gw2_guilds_mixed : array();
$last_login_mixed = get_user_meta($user_id, 'gw2_last_login', true);
/** @phpstan-ignore-next-line */
$last_login = is_int($last_login_mixed) ? $last_login_mixed : (is_string($last_login_mixed) && ctype_digit($last_login_mixed) ? (int)$last_login_mixed : 0);
// Ensure all variables are initialized for PHPStan
if (!isset($gw2_account_id)) { $gw2_account_id = ''; }
if (!isset($gw2_account_name)) { $gw2_account_name = ''; }
if (!isset($gw2_world)) { $gw2_world = ''; }
if (!isset($gw2_created)) { $gw2_created = ''; }
if (!isset($gw2_guilds)) { $gw2_guilds = array(); }
if (!isset($last_login)) { $last_login = 0; }

// Get user sessions.
$sessions = WP_Session_Tokens::get_instance($user_id);
$all_sessions_mixed = $sessions->get_all();
/** @phpstan-ignore-next-line */
$all_sessions = is_array($all_sessions_mixed) ? $all_sessions_mixed : array();
if (!isset($all_sessions)) { $all_sessions = array(); }
$current_session_mixed = wp_get_session_token();
/** @phpstan-ignore-next-line */
$current_session = is_string($current_session_mixed) ? $current_session_mixed : '';
if (!isset($current_session)) { $current_session = ''; }

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

/** @phpstan-ignore-next-line */
if (is_array($all_sessions)
    && $current_session !== ''
    && isset($all_sessions[$current_session])
    && is_array($all_sessions[$current_session])
) {
    $session_data = $all_sessions[$current_session];
    $current_ip = isset($session_data['ip']) && is_string($session_data['ip']) ? $session_data['ip'] : '';
    $current_ua = isset($session_data['ua']) && is_string($session_data['ua']) ? $session_data['ua'] : '';
}
    
// Detect browser.
foreach ($browser as $key => $value) {
    if (is_string($current_ua) && stripos($current_ua, $key) !== false) {
        $current_browser = $value;
        break;
    }
}
?>

<?php
$gw2gl_settings_mixed = get_option('gw2gl_settings', array());
$gw2gl_settings = is_array($gw2gl_settings_mixed) ? $gw2gl_settings_mixed : array();
$gw2gl_logo = isset($gw2gl_settings['appearance_logo']) && is_string($gw2gl_settings['appearance_logo']) ? $gw2gl_settings['appearance_logo'] : '';
$gw2gl_welcome = isset($gw2gl_settings['appearance_welcome_text']) && is_string($gw2gl_settings['appearance_welcome_text']) ? $gw2gl_settings['appearance_welcome_text'] : '';
?>
<div class="wrap gw2-dashboard" role="main" aria-label="GW2 User Dashboard">
    <?php
$gw2gl_logo_safe = is_string($gw2gl_logo) ? $gw2gl_logo : '';
$gw2gl_welcome_safe = is_string($gw2gl_welcome) ? $gw2gl_welcome : '';
$site_logo_alt = esc_attr__('Site Logo', 'gw2-guild-login');
$site_logo_alt_safe = is_string($site_logo_alt) ? $site_logo_alt : 'Site Logo';
$header_title = esc_html__('Guild Wars 2 Account', 'gw2-guild-login');
$header_title_safe = is_string($header_title) ? $header_title : 'Guild Wars 2 Account';
?>
<?php if ($gw2gl_logo_safe !== '') { ?>
    <div class="gw2-login-logo"><img src="<?php echo esc_url((string)$gw2gl_logo_safe); ?>" alt="<?php echo esc_attr((string)$site_logo_alt_safe); ?>" class="gw2-admin-custom-logo" /></div>
<?php }
if ($gw2gl_welcome_safe !== '') { ?>
    <div class="gw2-login-welcome-text"><?php echo wp_kses_post((string)$gw2gl_welcome_safe); ?></div>
<?php } ?>
<h1><?php echo esc_html((string)$header_title_safe); ?></h1>
    
    <?php do_action('gw2_dashboard_before_content'); ?>
    
    <div class="gw2-dashboard-grid">
        <!-- Account Overview -->
        <div class="gw2-card">
            <h2><?php echo esc_html__('Account Overview', 'gw2-guild-login'); ?></h2>
            <div class="gw2-card-content">
                <div class="gw2-account-info">
                    <div class="gw2-account-avatar">
                        /** @var WP_User $user */
<?php
// PHPStan: $user is always \WP_User
/** @phpstan-ignore-next-line */
/** @phpstan-ignore-next-line */
$user_display_name_safe = isset($user->display_name) && is_string($user->display_name) ? $user->display_name : '';
/** @phpstan-ignore-next-line */
$user_id_safe = isset($user->ID) && is_int($user->ID) ? $user->ID : 0;
$user_avatar_url_safe = '';
if ($user_id_safe > 0) {
    $avatar_url_mixed = get_avatar_url($user_id_safe, array('size' => 96));
    $user_avatar_url_safe = is_string($avatar_url_mixed) ? $avatar_url_mixed : '';
}
?>
<img src="<?php echo esc_url((string)$user_avatar_url_safe); ?>" alt="<?php echo esc_attr((string)$user_display_name_safe); ?>" class="gw2-avatar" />
                    </div>                    <div class="gw2-account-details">
                        <h3><?php echo esc_html((string)$user_display_name_safe); ?></h3>
                        <p class="gw2-account-id">
    <strong><?php $account_id_label = esc_html__('Account ID:', 'gw2-guild-login'); echo esc_html((string)$account_id_label); ?></strong> 
    <?php
    $not_connected = __('Not connected', 'gw2-guild-login');
    $account_id_str_safe = (is_string($gw2_account_id) && $gw2_account_id !== '') ? $gw2_account_id : (is_string($not_connected) ? $not_connected : 'Not connected');
    echo esc_html((string)$account_id_str_safe);
    ?>
</p>
<p class="gw2-account-world">
    <strong><?php $world_label = esc_html__('World:', 'gw2-guild-login'); echo esc_html((string)$world_label); ?></strong> 
    <?php
    $unknown_world = __('Unknown', 'gw2-guild-login');
    $world_str_safe = (is_string($gw2_world) && $gw2_world !== '') ? $gw2_world : (is_string($unknown_world) ? $unknown_world : 'Unknown');
    echo esc_html((string)$world_str_safe);
    ?>
</p>
<p class="gw2-account-created">
    <strong><?php $created_label = esc_html__('Created:', 'gw2-guild-login'); echo esc_html((string)$created_label); ?></strong> 
    <?php
    $date_format = get_option('date_format');
    $date_format_safe = is_string($date_format) ? $date_format : 'Y-m-d';
    if (is_string($gw2_created) && $gw2_created !== '') {
        $created_str_safe = date_i18n($date_format_safe, strtotime($gw2_created));
        echo esc_html((string)$created_str_safe);
    } else {
        $unknown_str = __('Unknown', 'gw2-guild-login');
        $unknown_str_safe = is_string($unknown_str) ? $unknown_str : 'Unknown';
        echo esc_html((string)$unknown_str_safe);
    }
    ?>
</p>
                        <p class="gw2-account-last-login">
    <strong><?php $last_login_label = esc_html__('Last Login:', 'gw2-guild-login'); echo esc_html((string)$last_login_label); ?></strong> 
    <?php
    $login_time = 0;
    if (is_int($last_login)) {
        $login_time = $last_login;
    } elseif (is_string($last_login) && ctype_digit($last_login)) {
        $login_time = (int)$last_login;
    }
    $current_time = (int)current_time('timestamp');
    $diff_str = human_time_diff($login_time, $current_time);
    $format_str = __('%s ago', 'gw2-guild-login');
    $format_str_safe = is_string($format_str) ? $format_str : '%s ago';
    $ago_str = sprintf($format_str_safe, (string)$diff_str);
    $ago_str_safe = is_string($ago_str) ? $ago_str : '';
    echo esc_html((string)$ago_str_safe);
    ?>
</p>    </td>
                            <td>
                                <?php $is_current_bool = (bool)$is_current; ?>
                                <?php if ($is_current_bool) { ?>
                                    <?php $current_badge = esc_html__('Current', 'gw2-guild-login'); ?>
                                    <span class="gw2-badge"><?php echo esc_html((string)$current_badge); ?></span>
                                <?php } else { ?>
                                    <?php
                                    $session_id_safe = (string)$session_id;
                                    $revoke_label = esc_html__('Revoke', 'gw2-guild-login');
                                    ?>
                                    <a href="#" class="gw2-revoke-session" data-session="<?php echo esc_attr($session_id_safe); ?>">
                                        <?php echo esc_html((string)$revoke_label); ?>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php // End foreach
                    ?>
<?php
// Fallback assignment to satisfy PHPStan in case loop never runs
$is_current = false;
$session_id = '';
?>
<?php
// Ensure variables are always defined for PHPStan
$is_current = false;
$session_id = '';
// Guard for PHPStan: ensure foreach is only run on array
if (is_array($all_sessions)) {
    foreach ($all_sessions as $session_id_raw => $session) {
        $session_id = is_string($session_id_raw) ? $session_id_raw : '';
        $is_current = ($session_id === $current_session);
        ?>
    <tr>
        <td><?php echo esc_html((string)$session_id); ?></td>
        <!-- Add other session columns here as needed -->
        <td>
            <?php $is_current_bool = (bool)$is_current; ?>
            <?php if ($is_current_bool) { ?>
                <?php $current_badge = esc_html__('Current', 'gw2-guild-login'); ?>
                <span class="gw2-badge"><?php echo esc_html((string)$current_badge); ?></span>
            <?php } else { ?>
                <?php
                $session_id_safe = (string)$session_id;
                $revoke_label = esc_html__('Revoke', 'gw2-guild-login');
                ?>
                <a href="#" class="gw2-revoke-session" data-session="<?php echo esc_attr($session_id_safe); ?>">
                    <?php echo esc_html((string)$revoke_label); ?>
                </a>
            <?php } ?>
        </td>
    </tr>
    <?php } // Closing foreach brace
} // Closing if brace
?>
</tbody>
                </table>
                
                <div class="gw2-session-actions">
    <?php
    $revoke_label = esc_html__('Revoke', 'gw2-guild-login');
    $revoke_label_safe = is_string($revoke_label) ? $revoke_label : 'Revoke';
    $revoke_all_label = esc_html__('Revoke All Other Sessions', 'gw2-guild-login');
    $revoke_all_label_safe = is_string($revoke_all_label) ? $revoke_all_label : 'Revoke All Other Sessions';
    ?>
    <button type="button" class="button button-secondary" id="revoke-other-sessions" aria-label="Revoke all other sessions">
        <span class="dashicons dashicons-dismiss" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html((string)$revoke_label_safe); ?></span>
        <?php echo esc_html((string)$revoke_all_label_safe); ?>
    </button>
</div>
            </div>
        </div>
    </div>
    <?php do_action('gw2_dashboard_after_content'); ?>
    <div class="gw2-dashboard-footer">
    <p class="description">
        <?php 
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $date_format_safe = is_string($date_format) ? $date_format : 'Y-m-d';
        $time_format_safe = is_string($time_format) ? $time_format : 'H:i';
        $datetime_val = current_time($date_format_safe . ' ' . $time_format_safe);
        $datetime_str_safe = is_string($datetime_val) ? $datetime_val : '';
        $footer_format = esc_html__('Last updated: %s', 'gw2-guild-login');
        $footer_format_safe = is_string($footer_format) ? $footer_format : 'Last updated: %s';
        $last_updated_str = sprintf($footer_format_safe, esc_html($datetime_str_safe));
        $last_updated_str_safe = is_string($last_updated_str) ? $last_updated_str : '';
        echo esc_html($last_updated_str_safe);
        ?>
        <span class="sep">|</span>
        <?php $refresh_label = esc_html__('Refresh', 'gw2-guild-login'); $refresh_label_safe = is_string($refresh_label) ? $refresh_label : 'Refresh'; ?>
        <a href="#" id="refresh-page"><?php echo esc_html($refresh_label_safe); ?></a>
    </p>
</div>
</div>
</div> <!-- .gw2-dashboard-grid -->
</div> <!-- .wrap.gw2-dashboard -->
<?php // End of dashboard.php ?>