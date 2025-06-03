<?php
declare(strict_types=1);
if (!current_user_can('manage_options')) {
    return;
}

// Prepare data sources
global $wpdb;
// 1. Login activity: count unique users logged in per day for past 7 days
$login_activity = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key='gw2_last_login' AND DATE(FROM_UNIXTIME(CAST(meta_value AS SIGNED))) = %s",
        $day
    ));
    $login_activity[$day] = $count;
}

// 2. Failed login attempts: sum counts in last 7 days
$failed = 0;
$option_names = $wpdb->get_col(
    $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'gw2gl_failed_attempts_%')
);
$week_ago = time() - 7 * DAY_IN_SECONDS;
foreach ($option_names as $opt) {
    $data = get_option($opt);
    if (is_array($data) && isset($data['count'], $data['time']) && (int)$data['time'] >= $week_ago) {
        $failed += (int) $data['count'];
    }
}

// 3. User engagement metrics
$all_counts = count_users();
$total_users = $all_counts['total_users'];
$guild_users = count(get_users([ 'meta_key' => 'gw2_account_name', 'meta_compare' => 'EXISTS' ]));
$active_users = count(get_users([
    'meta_key' => 'gw2_last_login',
    'meta_value' => strtotime('-30 days'),
    'meta_compare' => '>',
    'meta_type' => 'NUMERIC',
]));

// 4. Security events: 2FA enabled users
// Use the 2FA secrets table for Security Events
$secrets_table = $wpdb->prefix . 'gw2_2fa_secrets';
$twofa_users = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$secrets_table}");

?>
<div class="wrap gw2-admin-reports">
    <h1><?php esc_html_e('Reports', 'gw2-guild-login'); ?></h1>

    <h2><?php esc_html_e('Login Activity (Last 7 Days)', 'gw2-guild-login'); ?></h2>
    <ul>
        <?php foreach ($login_activity as $day => $cnt) : ?>
            <li><?php echo esc_html($day . ': ' . $cnt . ' ' . esc_html__('logins', 'gw2-guild-login')); ?></li>
        <?php endforeach; ?>
    </ul>

    <h2><?php esc_html_e('Failed Login Attempts (Last 7 Days)', 'gw2-guild-login'); ?></h2>
    <p><?php echo esc_html($failed); ?></p>

    <h2><?php esc_html_e('User Engagement Metrics', 'gw2-guild-login'); ?></h2>
    <ul>
        <li><?php echo esc_html(sprintf(__('%d total users', 'gw2-guild-login'), $total_users)); ?></li>
        <li><?php echo esc_html(sprintf(__('%d users with GW2 linked', 'gw2-guild-login'), $guild_users)); ?></li>
        <li><?php echo esc_html(sprintf(__('%d active in last 30 days', 'gw2-guild-login'), $active_users)); ?></li>
    </ul>

    <h2><?php esc_html_e('Security Events', 'gw2-guild-login'); ?></h2>
    <ul>
        <li><?php echo esc_html(sprintf(__('%d users with 2FA enabled', 'gw2-guild-login'), $twofa_users)); ?></li>
        <!-- TODO: Add more security event counts if available -->
    </ul>
</div>
