<?php
/**
 * User Management admin view for GW2 Guild Login plugin.
 *
 * Displays and manages guild users and allows adding new users.
 *
 * @package GW2_Guild_Login
 */

declare(strict_types=1);

/** @var \WP_User[] $users */
$current_tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'all-users';
?>

<h2 class="nav-tab-wrapper">
	<a href="?page=gw2-user-management&tab=all-users" class="nav-tab <?php echo 'all-users' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'All Users', 'gw2-guild-login' ); ?></a>
	<a href="?page=gw2-user-management&tab=add-new" class="nav-tab <?php echo 'add-new' === $current_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Add New', 'gw2-guild-login' ); ?></a>
</h2>

<?php if ( 'all-users' === $current_tab ) : ?>
<div class="wrap gw2-admin-user-management">
	<h1><?php esc_html_e( 'Guild Members', 'gw2-guild-login' ); ?></h1>
	<form method="get" class="alignright">
		<input type="hidden" name="page" value="gw2-user-management" />
		<input type="hidden" name="tab" value="all-users" />
		<label for="filter_role"><?php esc_html_e( 'Filter by Role:', 'gw2-guild-login' ); ?></label>
		<select id="filter_role" name="filter_role">
			<option value=""><?php esc_html_e( 'All', 'gw2-guild-login' ); ?></option>
			<?php foreach ( get_editable_roles() as $slug => $info ) : ?>
				<option value="<?php echo esc_attr( is_string( $slug ) ? $slug : '' ); ?>" <?php selected( isset( $_GET['filter_role'] ) && is_string( $_GET['filter_role'] ) ? sanitize_key( wp_unslash( $_GET['filter_role'] ) ) : '', $slug ); ?>><?php echo esc_html( isset( $info['name'] ) && is_string( $info['name'] ) ? $info['name'] : '' ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button"><?php esc_html_e( 'Filter', 'gw2-guild-login' ); ?></button>
	</form>
	<form method="post">
		<?php // Bulk actions placeholder. ?>
		<div class="bulk-actions">
			<select name="bulk_action">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'gw2-guild-login' ); ?></option>
				<option value="export"><?php esc_html_e( 'Export Selected', 'gw2-guild-login' ); ?></option>
				<option value="remove"><?php esc_html_e( 'Remove Access', 'gw2-guild-login' ); ?></option>
			</select>
			<button class="button action"><?php esc_html_e( 'Apply', 'gw2-guild-login' ); ?></button>
		</div>
		<?php
		// Fetch and optionally filter users.
		$args = array(
			'meta_key'     => 'gw2_account_name',
			'meta_compare' => 'EXISTS',
		);
		if ( ! empty( $_GET['filter_role'] ) ) {
			$args['role'] = isset( $_GET['filter_role'] ) && is_string( $_GET['filter_role'] ) ? sanitize_key( wp_unslash( $_GET['filter_role'] ) ) : '';
		}
		$users_raw = get_users( $args );
		$users     = is_array( $users_raw ) ? $users_raw : array();
		if ( $users ) :
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
				<?php
				foreach ( $users as $user ) :
					if ( ! $user instanceof WP_User ) {
						continue;
					}
					// Ensure meta values are correct types.
					$account_name_raw = get_user_meta( $user->ID, 'gw2_account_name', true );
					$account_name     = is_string( $account_name_raw ) ? $account_name_raw : '';
					$last_login_raw   = get_user_meta( $user->ID, 'gw2_last_login', true );
					$last_login       = is_int( $last_login_raw ) || ctype_digit( (string) $last_login_raw ) ? (int) $last_login_raw : 0;
					$display_name     = isset( $user->display_name ) && is_string( $user->display_name ) ? $user->display_name : '';
					?>
				<tr>
					<td><?php echo esc_html( $display_name ); ?></td>
					<td><?php echo esc_html( $account_name ); ?></td>
					<?php
						$rank_raw        = get_user_meta( $user->ID, 'gw2_guild_rank', true );
						$rank            = is_string( $rank_raw ) && '' !== $rank_raw ? $rank_raw : 'N/A';
						$date_format_raw = get_option( 'date_format', '\Y-m-d' );
						$time_format_raw = get_option( 'time_format', '\H:i:s' );
						$date_format     = is_string( $date_format_raw ) ? $date_format_raw : 'Y-m-d';
						$time_format     = is_string( $time_format_raw ) ? $time_format_raw : 'H:i:s';
						$login_display   = 0 < $last_login
							? date_i18n( $date_format . ' ' . $time_format, $last_login )
							: 'Never';
					?>
					<td><?php echo esc_html( $rank ); ?></td>
					<td><?php echo esc_html( $login_display ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		else :
			?>
			<p><?php esc_html_e( 'No guild members found.', 'gw2-guild-login' ); ?></p>
		<?php endif; ?>
	</form>
</div>
<?php elseif ( 'add-new' === $current_tab ) : ?>
<div class="wrap gw2-admin-user-management">
	<h1><?php esc_html_e( 'Add New User', 'gw2-guild-login' ); ?></h1>
	<?php
	do_action( 'admin_notices' );
	$request_method = ( isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
	if ( 'POST' === $request_method && wp_verify_nonce( isset( $_POST['new_user_nonce'] ) && is_string( $_POST['new_user_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['new_user_nonce'] ) ) : '', 'gw2_new_user' ) ) {
		$u       = sanitize_user( isset( $_POST['username'] ) && is_string( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : '' );
		$e       = sanitize_email( isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
		$r       = sanitize_key( isset( $_POST['role'] ) && is_string( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : 'subscriber' );
		$user_id = wp_insert_user(
			array(
				'user_login' => $u,
				'user_email' => $e,
				'role'       => $r,
			)
		);
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'gw2_guild_rank', sanitize_text_field( isset( $_POST['guild_rank'] ) && is_string( $_POST['guild_rank'] ) ? wp_unslash( $_POST['guild_rank'] ) : '' ) );
			echo '<div class="updated"><p>' . esc_html__( 'User created.', 'gw2-guild-login' ) . '</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html( $user_id->get_error_message() ) . '</p></div>';
		}
	}
	?>
<form method="post">
	<?php wp_nonce_field( 'gw2_new_user', 'new_user_nonce' ); ?>
	<table class="form-table">
		<tr><th><label for="username"><?php esc_html_e( 'Username', 'gw2-guild-login' ); ?></label></th><td><input type="text" name="username" id="username" required /></td></tr>
		<tr><th><label for="email"><?php esc_html_e( 'Email', 'gw2-guild-login' ); ?></label></th><td><input type="email" name="email" id="email" required /></td></tr>
		<tr><th><label for="role"><?php esc_html_e( 'Role', 'gw2-guild-login' ); ?></label></th><td><?php $roles = get_editable_roles(); ?><select name="role">
		<?php
		foreach ( $roles as $slug => $info ) :
			?>
			<option value="<?php echo esc_attr( is_string( $slug ) ? $slug : '' ); ?>"><?php echo esc_html( isset( $info['name'] ) && is_string( $info['name'] ) ? $info['name'] : '' ); ?></option><?php endforeach; ?></select></td></tr>
		<tr><th><label for="guild_rank"><?php esc_html_e( 'Guild Rank', 'gw2-guild-login' ); ?></label></th><td>
		<?php
		$guild_ids = get_option( 'gw2gl_settings' );
		$ranks     = array();
		if ( is_array( $guild_ids ) && isset( $guild_ids['guild_ids'] ) && is_array( $guild_ids['guild_ids'] ) && count( $guild_ids['guild_ids'] ) > 0 && is_string( current( $guild_ids['guild_ids'] ) ) ) {
			$guild_data = GW2_Guild_Ranks::instance()->fetch_guild_data( current( $guild_ids['guild_ids'] ) );
			$ranks      = ( is_array( $guild_data ) && isset( $guild_data['ranks'] ) && is_array( $guild_data['ranks'] ) ) ? $guild_data['ranks'] : array();
		}
		?>
		<select name="guild_rank">
		<?php
		foreach ( $ranks as $rank ) :
			$rank_name = '';
			if ( is_array( $rank ) && isset( $rank['name'] ) ) {
				$rank_name = is_string( $rank['name'] ) ? $rank['name'] : '';
			} elseif ( is_string( $rank ) ) {
				$rank_name = $rank;
			}
			?>
			<option><?php echo esc_html( $rank_name ); ?></option>
		<?php endforeach; ?>
		</select></td></tr>
	</table>
	<?php submit_button( __( 'Create User', 'gw2-guild-login' ) ); ?>
</form>
</div>
<?php endif; ?>
