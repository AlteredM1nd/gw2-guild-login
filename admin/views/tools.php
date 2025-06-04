<?php
declare(strict_types=1);
if (!current_user_can('manage_options')) {
    return;
}

// Handle Tools actions
if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['gw2_tools_nonce']) && wp_verify_nonce($_POST['gw2_tools_nonce'], 'gw2_tools')) {
    $action = $_POST['gw2_tools_action'] ?? '';
    switch ($action) {
        case 'export_data':
            $export = [
                'settings' => get_option('gw2gl_settings', []),
                'rank_map' => get_option('gw2_rank_role_map', []),
            ];
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="gw2-guild-login-export.json"');
            echo json_encode($export);
            exit;
        case 'import_data':
            if (!empty($_FILES['gw2_import_file']['tmp_name'])) {
                $json = file_get_contents($_FILES['gw2_import_file']['tmp_name']);
                $data = json_decode($json, true);
                if (is_array($data)) {
                    update_option('gw2gl_settings', $data['settings'] ?? []);
                    update_option('gw2_rank_role_map', $data['rank_map'] ?? []);
                    add_settings_error('gw2_tools', 'import_success', __('Import completed.', 'gw2-guild-login'), 'updated');
                } else {
                    add_settings_error('gw2_tools', 'import_fail', __('Invalid import file.', 'gw2-guild-login'), 'error');
                }
            }
            break;
        case 'sync_guild':
            $settings = get_option('gw2gl_settings', []);
            $ids = array_filter(array_map('trim', explode(',', $settings['guild_ids'] ?? '')));
            foreach ($ids as $gid) {
                delete_transient('gw2_guild_members_' . $gid);
            }
            add_settings_error('gw2_tools', 'sync_done', __('Guild members cache cleared.', 'gw2-guild-login'), 'updated');
            break;
        case 'clear_cache':
            global $wpdb;
            $keys = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_gw2_guild_members_%'");
            foreach ($keys as $opt) {
                $transient = preg_replace('/^_transient_/', '', $opt);
                delete_transient($transient);
            }
            add_settings_error('gw2_tools', 'clear_cache', __('All plugin transients cleared.', 'gw2-guild-login'), 'updated');
            break;
        case 'reset_settings':
            delete_option('gw2gl_settings');
            delete_option('gw2_rank_role_map');
            add_settings_error('gw2_tools', 'reset_done', __('Settings and mappings reset to defaults.', 'gw2-guild-login'), 'updated');
            break;
    }
}
settings_errors('gw2_tools');
?>
<div class="wrap gw2-admin-tools">
    <h1><?php esc_html_e('Tools', 'gw2-guild-login'); ?></h1>

    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('gw2_tools', 'gw2_tools_nonce'); ?>
        <h2><?php esc_html_e('Import/Export', 'gw2-guild-login'); ?></h2>
        <p>
            <input type="file" name="gw2_import_file" accept="application/json">
            <button type="submit" name="gw2_tools_action" value="import_data" class="button"><?php esc_html_e('Import Data', 'gw2-guild-login'); ?></button>
            <button type="submit" name="gw2_tools_action" value="export_data" class="button"><?php esc_html_e('Export Data', 'gw2-guild-login'); ?></button>
        </p>

        <h2><?php esc_html_e('System Tools', 'gw2-guild-login'); ?></h2>
        <p>
            <button type="submit" name="gw2_tools_action" value="sync_guild" class="button"><?php esc_html_e('Sync Guild Members', 'gw2-guild-login'); ?></button>
            <button type="submit" name="gw2_tools_action" value="clear_cache" class="button"><?php esc_html_e('Clear Cache', 'gw2-guild-login'); ?></button>
            <button type="submit" name="gw2_tools_action" value="reset_settings" class="button"><?php esc_html_e('Reset Settings', 'gw2-guild-login'); ?></button>
        </p>

        <h3><?php esc_html_e('Debug Information', 'gw2-guild-login'); ?></h3>
        <table class="widefat fixed striped">
            <tr><th><?php esc_html_e('PHP Version', 'gw2-guild-login'); ?></th><td><?php echo esc_html(PHP_VERSION); ?></td></tr>
            <tr><th><?php esc_html_e('WordPress Version', 'gw2-guild-login'); ?></th><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
            <tr><th><?php esc_html_e('Plugin Version', 'gw2-guild-login'); ?></th><td><?php echo esc_html(GW2_GUILD_LOGIN_VERSION); ?></td></tr>
            <tr><th><?php esc_html_e('Active Theme', 'gw2-guild-login'); ?></th><td><?php echo esc_html(get_template()); ?></td></tr>            <tr><th><?php esc_html_e('Active Plugins', 'gw2-guild-login'); ?></th><td>
                <?php 
                $active_plugins = get_option('active_plugins');
                if (is_array($active_plugins)) {
                    foreach ($active_plugins as $p) { 
                        echo esc_html($p) . '<br>'; 
                    } 
                } else {
                    esc_html_e('No active plugins found', 'gw2-guild-login');
                }
                ?>
            </td></tr>
        </table>
    </form>
</div>
