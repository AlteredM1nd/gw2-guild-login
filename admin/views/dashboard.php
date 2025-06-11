<?php
/**
 * Admin Dashboard view for GW2 Guild Login plugin.
 *
 * This view displays latest login activity and system information.
 *
 * @package GW2_Guild_Login
 */

declare(strict_types=1);
// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @var string $guild_id */
// Fetch recent login activity.
$recent_logins = get_transient( 'gw2_recent_logins' );
if ( false === $recent_logins ) {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$recent_logins = get_users(
		array(
			'meta_key' => 'gw2_last_login',
			'orderby'  => 'meta_value_num',
			'order'    => 'DESC',
			'number'   => 5,
		)
	);
	set_transient( 'gw2_recent_logins', $recent_logins, HOUR_IN_SECONDS );
}
// System info.
$current_php_version    = PHP_VERSION;
$wp_ver                 = get_bloginfo( 'version' );
$current_plugin_version = defined( 'GW2_GUILD_LOGIN_VERSION' ) ? GW2_GUILD_LOGIN_VERSION : '';
/** @var wpdb $wpdb */
global $wpdb;
$db_ver = $wpdb->db_version();
?>
<div class="wrap gw2-admin-dashboard" role="main" aria-label="GW2 Guild Admin Dashboard">
	<h1>GW2 Guild Dashboard</h1>

	<div class="gw2-admin-cards">
		<div class="gw2-admin-card" role="region" aria-label="Guild Status">
			<h2>Guild Status</h2>
			<div class="gw2-admin-card-content">
				<?php
				$guild_id_raw = get_option( 'gw2_guild_id', '' );
				$guild_id     = is_string( $guild_id_raw ) ? $guild_id_raw : '';
				if ( '' !== $guild_id ) {
					echo '<p><strong>Guild ID:</strong> ' . esc_html( $guild_id ) . '</p>';
				} else {
					$settings_url_raw = admin_url( 'admin.php?page=gw2-guild-login' );
					$settings_url     = is_string( $settings_url_raw ) ? $settings_url_raw : '';
					echo '<p>No guild configured. <a href="' . esc_url( $settings_url ) . '">Configure now</a></p>';
				}
				?>
			</div>
		</div>

		<div class="gw2-admin-card" role="region" aria-label="Quick Actions">
			<h2>Quick Actions</h2>
			<div class="gw2-admin-card-content">
				<ul class="gw2-admin-actions">
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=gw2-guild-login' ) ); ?>" aria-label="Go to Guild Settings">Guild Settings</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=gw2-rank-access' ) ); ?>" aria-label="Manage Rank Access">Manage Rank Access</a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=gw2-user-management' ) ); ?>" aria-label="User Management">User Management</a></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="gw2-admin-card gw2-admin-card-wide" role="region" aria-label="Recent Activity">
		<h2>Recent Activity</h2>
		<div class="gw2-admin-card-content">
			<p>Recent login activity and system events will appear here.</p>
			<ul>
				<?php if ( empty( $recent_logins ) || ! is_array( $recent_logins ) ) : ?>
					<li><?php esc_html_e( 'No recent logins.', 'gw2-guild-login' ); ?></li>
				<?php else : ?>
					<?php foreach ( $recent_logins as $user ) : ?>
						<?php if ( is_object( $user ) && isset( $user->ID, $user->display_name ) ) : ?>
							<?php
							/** @var WP_User $user */
							$user_id      = $user->ID;
							$meta_val     = get_user_meta( $user_id, 'gw2_last_login', true );
							$ts           = is_numeric( $meta_val ) ? (int) $meta_val : 0;
							$date_format  = (string) get_option( 'date_format' );
							$time_format  = (string) get_option( 'time_format' );
							$time         = $ts > 0 ? date_i18n( $date_format . ' ' . $time_format, $ts ) : __( 'Never', 'gw2-guild-login' );
							$display_name = (string) $user->display_name;
							?>
							<li><?php echo esc_html( sprintf( '%s: %s', $display_name, $time ) ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<div class="gw2-admin-card" role="region" aria-label="System Status">
		<h2>System Status</h2>
		<div class="gw2-admin-card-content">
			<p>
			<?php
				$php_version    = (string) $current_php_version;
				$wp_ver_display = (string) $wp_ver;
				$plugin_version = (string) $current_plugin_version;
				echo esc_html( sprintf( __( 'PHP %1$s | WP %2$s | Plugin %3$s', 'gw2-guild-login' ), $php_version, $wp_ver_display, $plugin_version ) );
			?>
			</p>
		</div>
	</div>

	<div class="gw2-admin-card" role="region" aria-label="Server Environment">
		<h2>Server Environment</h2>
		<div class="gw2-admin-card-content">
			<p>
			<?php
				$db_version = (string) $db_ver;
				echo esc_html( sprintf( __( 'DB Version: %s', 'gw2-guild-login' ), $db_version ) );
			?>
			</p>
		</div>
	</div>
</div>
