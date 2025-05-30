<div class="wrap gw2-admin-user-management">
    <h1>User Management</h1>
    
    <div class="gw2-admin-cards">
        <div class="gw2-admin-card gw2-admin-card-wide">
            <h2>Guild Members</h2>
            <div class="gw2-admin-card-content">
                <p>Manage guild member access and permissions.</p>
                
                <?php
                // This would be populated with actual user data
                $users = get_users(array(
                    'meta_key'     => 'gw2_account_name',
                    'meta_compare' => 'EXISTS',
                ));
                
                if (!empty($users)) :
                ?>
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
                            $account_name = get_user_meta($user->ID, 'gw2_account_name', true);
                            $last_login = get_user_meta($user->ID, 'gw2_last_login', true);
                        ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($account_name); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'gw2_guild_rank', true) ?: 'N/A'); ?></td>
                            <td><?php echo $last_login ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_login) : 'Never'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p>No guild members found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
