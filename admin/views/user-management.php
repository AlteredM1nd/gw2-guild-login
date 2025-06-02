<?php
declare(strict_types=1);
/** @var \WP_User[] $users */
?>

<div class="wrap gw2-admin-user-management">
    <h1>User Management</h1>
    
    <div class="gw2-admin-cards">
        <div class="gw2-admin-card gw2-admin-card-wide">
            <h2>Guild Members</h2>
            <div class="gw2-admin-card-content">
                <p>Manage guild member access and permissions.</p>
                
                <?php
                // Fetch users with GW2 account, ensure iterable array of WP_User
                $users_raw = get_users(array(
                     'meta_key'     => 'gw2_account_name',
                     'meta_compare' => 'EXISTS',
                ));
                /** @var \WP_User[] $users */
                $users = is_array($users_raw) ? $users_raw : [];
                
                if (count($users) > 0) :
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
                <?php else : ?>
                    <p>No guild members found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
