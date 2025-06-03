<?php
declare(strict_types=1);
/** @var \WP_User[] $users */
$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'all-users';
?>

<h2 class="nav-tab-wrapper">
    <a href="?page=gw2-user-management&tab=all-users" class="nav-tab <?php echo $tab==='all-users'?'nav-tab-active':''; ?>"><?php esc_html_e('All Users', 'gw2-guild-login'); ?></a>
    <a href="?page=gw2-user-management&tab=add-new" class="nav-tab <?php echo $tab==='add-new'?'nav-tab-active':''; ?>"><?php esc_html_e('Add New', 'gw2-guild-login'); ?></a>
</h2>

<?php if ($tab==='all-users'): ?>
<div class="wrap gw2-admin-user-management">
    <h1><?php esc_html_e('Guild Members', 'gw2-guild-login'); ?></h1>
    <form method="get" class="alignright">
        <input type="hidden" name="page" value="gw2-user-management" />
        <input type="hidden" name="tab" value="all-users" />
        <label for="filter_role"><?php esc_html_e('Filter by Role:', 'gw2-guild-login'); ?></label>
        <select id="filter_role" name="filter_role">
            <option value=""><?php esc_html_e('All', 'gw2-guild-login'); ?></option>
            <?php foreach (get_editable_roles() as $slug=>$info): ?>
                <option value="<?php echo esc_attr($slug); ?>" <?php selected($_GET['filter_role'] ?? '', $slug); ?>><?php echo esc_html($info['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button"><?php esc_html_e('Filter', 'gw2-guild-login'); ?></button>
    </form>
    <form method="post">
        <?php // Bulk actions placeholder ?>
        <div class="bulk-actions">
            <select name="bulk_action">
                <option value=""><?php esc_html_e('Bulk Actions', 'gw2-guild-login'); ?></option>
                <option value="export"><?php esc_html_e('Export Selected', 'gw2-guild-login'); ?></option>
                <option value="remove"><?php esc_html_e('Remove Access', 'gw2-guild-login'); ?></option>
            </select>
            <button class="button action"><?php esc_html_e('Apply', 'gw2-guild-login'); ?></button>
        </div>
        <?php
        // Fetch and optionally filter users
        $args = ['meta_key'=>'gw2_account_name','meta_compare'=>'EXISTS'];
        if (!empty($_GET['filter_role'])) {
            $args['role'] = sanitize_key($_GET['filter_role']);
        }
        $users_raw = get_users($args);
        $users = is_array($users_raw) ? $users_raw : [];
        if ($users): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>GW2 Account</th>
                    <th>Guild Rank</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) :
                    // Ensure meta values are correct types
                    $account_name_raw = get_user_meta($user->ID, 'gw2_account_name', true);
                    $account_name = is_string($account_name_raw) ? $account_name_raw : '';
                    $last_login_raw = get_user_meta($user->ID, 'gw2_last_login', true);
                    $last_login = is_int($last_login_raw) || ctype_digit((string)$last_login_raw) ? (int)$last_login_raw : 0;
                ?>
                <tr>
                    <td><?php echo esc_html((string)$user->display_name); ?></td>
                    <td><?php echo esc_html($account_name); ?></td>
                    <?php
                        $rank_raw = get_user_meta($user->ID, 'gw2_guild_rank', true);
                        $rank = is_string($rank_raw) && $rank_raw !== '' ? $rank_raw : 'N/A';
                        $date_format_raw = get_option('date_format', '\Y-m-d');
                        $time_format_raw = get_option('time_format', '\H:i:s');
                        $date_format = is_string($date_format_raw) ? $date_format_raw : 'Y-m-d';
                        $time_format = is_string($time_format_raw) ? $time_format_raw : 'H:i:s';
                        $login_display = $last_login > 0
                            ? date_i18n($date_format . ' ' . $time_format, $last_login)
                            : 'Never';
                    ?>
                    <td><?php echo esc_html($rank); ?></td>
                    <td><?php echo esc_html($login_display); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p><?php esc_html_e('No guild members found.', 'gw2-guild-login'); ?></p><?php endif; ?>
    </form>
</div>
<?php elseif ($tab==='add-new'): ?>
<div class="wrap gw2-admin-user-management">
    <h1><?php esc_html_e('Add New User', 'gw2-guild-login'); ?></h1>
    <?php
do_action('admin_notices');
if ('POST'===
    $_SERVER['REQUEST_METHOD'] && wp_verify_nonce($_POST['new_user_nonce'] ?? '', 'gw2_new_user')) {
    $u = sanitize_user($_POST['username'] ?? '');
    $e = sanitize_email($_POST['email'] ?? '');
    $r = sanitize_key($_POST['role'] ?? 'subscriber');
    $id = wp_insert_user(['user_login'=>$u,'user_email'=>$e,'role'=>$r]);
    if (!is_wp_error($id)) {
        update_user_meta($id,'gw2_guild_rank',sanitize_text_field($_POST['guild_rank']));
        echo '<div class="updated"><p>'.esc_html__('User created.', 'gw2-guild-login').'</p></div>';
    } else {
        echo '<div class="error"><p>'.esc_html($id->get_error_message()).'</p></div>';
    }
}
?>
<form method="post">
    <?php wp_nonce_field('gw2_new_user','new_user_nonce'); ?>
    <table class="form-table">
        <tr><th><label for="username"><?php esc_html_e('Username', 'gw2-guild-login'); ?></label></th><td><input type="text" name="username" id="username" required /></td></tr>
        <tr><th><label for="email"><?php esc_html_e('Email', 'gw2-guild-login'); ?></label></th><td><input type="email" name="email" id="email" required /></td></tr>
        <tr><th><label for="role"><?php esc_html_e('Role', 'gw2-guild-login'); ?></label></th><td><?php $roles=get_editable_roles(); ?><select name="role"><?php foreach ($roles as $slug=>$info): ?><option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($info['name']); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th><label for="guild_rank"><?php esc_html_e('Guild Rank', 'gw2-guild-login'); ?></label></th><td><?php $ranks=GW2_Guild_Ranks::instance()->fetch_guild_data(current(get_option('gw2gl_settings')['guild_ids']?:[]))['ranks'] ?? []; ?><select name="guild_rank"><?php foreach ($ranks as $rank): ?><option><?php echo esc_html(is_array($rank)?($rank['name']?:''):esc_html($rank)); ?></option><?php endforeach; ?></select></td></tr>
    </table>
    <?php submit_button(__('Create User','gw2-guild-login')); ?>
</form>
</div>
<?php endif; ?>
