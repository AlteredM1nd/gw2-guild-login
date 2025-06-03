<?php
declare(strict_types=1);
/** @var string $guild_id */
?>
<div class="wrap gw2-admin-dashboard" role="main" aria-label="GW2 Guild Admin Dashboard">
    <h1>GW2 Guild Dashboard</h1>
    
    <div class="gw2-admin-cards">
        <div class="gw2-admin-card" role="region" aria-label="Guild Status">
            <h2>Guild Status</h2>
            <div class="gw2-admin-card-content">
                <?php
                $guild_id_raw = get_option('gw2_guild_id', '');
                $guild_id = is_string($guild_id_raw) ? $guild_id_raw : '';
                 if ($guild_id !== '') {
                     echo '<p><strong>Guild ID:</strong> ' . esc_html($guild_id) . '</p>';
                 } else {
                    $settings_url_raw = admin_url('admin.php?page=gw2-guild-login');
                    $settings_url = is_string($settings_url_raw) ? $settings_url_raw : '';
                     echo '<p>No guild configured. <a href="' . esc_url($settings_url) . '">Configure now</a></p>';
                 }
                ?>
            </div>
        </div>
        
        <div class="gw2-admin-card" role="region" aria-label="Quick Actions">
            <h2>Quick Actions</h2>
            <div class="gw2-admin-card-content">
                <ul class="gw2-admin-actions">
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=gw2-guild-login')); ?>" aria-label="Go to Guild Settings">Guild Settings</a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=gw2-rank-access')); ?>" aria-label="Manage Rank Access">Manage Rank Access</a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=gw2-user-management')); ?>" aria-label="User Management">User Management</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="gw2-admin-card gw2-admin-card-wide" role="region" aria-label="Recent Activity">
        <h2>Recent Activity</h2>
        <div class="gw2-admin-card-content">
            <p>Recent login activity and system events will appear here.</p>
            <!-- Add activity log or recent events here -->
        </div>
    </div>
</div>
