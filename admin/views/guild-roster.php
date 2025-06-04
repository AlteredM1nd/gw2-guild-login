<?php
declare(strict_types=1);
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// Load settings and fetch guild data
$settings        = get_option( 'gw2gl_settings', array() );
$guild_ids       = array_filter( array_map( 'trim', explode( ',', $settings['guild_ids'] ?? '' ) ) );
$guild_id        = $guild_ids[0] ?? '';
$ranks_handler   = GW2_Guild_Ranks::instance();
$data            = $ranks_handler->fetch_guild_data( (string) $guild_id );
$members         = array();
$available_ranks = array();
if ( ! is_wp_error( $data ) && is_array( $data ) ) {
	$members         = is_array( $data['members'] ?? array() ) ? $data['members'] : array();
	$available_ranks = is_array( $data['ranks'] ?? array() ) ? $data['ranks'] : array();
}

// Handle rank filter
$filter_rank = isset( $_GET['filter_rank'] ) ? sanitize_text_field( $_GET['filter_rank'] ) : '';
if ( $filter_rank !== '' ) {
	$members = array_filter(
		$members,
		function ( $m ) use ( $filter_rank ) {
			return isset( $m['rank'] ) && (string) $m['rank'] === $filter_rank;
		}
	);
}
?>
<div class="wrap gw2-admin-guild-roster">
	<h1><?php esc_html_e( 'Guild Roster', 'gw2-guild-login' ); ?></h1>

	<form method="get" class="alignright">
		<label for="filter_rank"><?php esc_html_e( 'Filter by Rank:', 'gw2-guild-login' ); ?></label>
		<select id="filter_rank" name="filter_rank">
			<option value=""><?php esc_html_e( 'All', 'gw2-guild-login' ); ?></option>
			<?php foreach ( $available_ranks as $rank_name ) : ?>
				<?php $name = is_string( $rank_name ) ? $rank_name : ( is_array( $rank_name['name'] ?? '' ) ? '' : $rank_name['name'] ?? '' ); ?>
				<option value="<?php echo esc_attr( (string) $name ); ?>" <?php selected( $filter_rank, $name ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<button class="button"><?php esc_html_e( 'Filter', 'gw2-guild-login' ); ?></button>
	</form>

	<table class="widefat fixed striped gw2-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Player Name', 'gw2-guild-login' ); ?></th>
				<th><?php esc_html_e( 'Rank', 'gw2-guild-login' ); ?></th>
				<th><?php esc_html_e( 'Joined', 'gw2-guild-login' ); ?></th>
				<th><?php esc_html_e( 'Last Login', 'gw2-guild-login' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( count( $members ) === 0 ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No guild members found.', 'gw2-guild-login' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $members as $member ) : ?>
					<?php
					$name      = is_string( $member['name'] ?? '' ) ? $member['name'] : '';
					$rank      = is_string( $member['rank'] ?? '' ) ? $member['rank'] : '';
					$joined_ts = isset( $member['joined'] ) && ( is_string( $member['joined'] ) || is_numeric( $member['joined'] ) )
						? strtotime( (string) $member['joined'] ) : 0;
					$joined    = $joined_ts > 0 ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $joined_ts ) : '';

					// Find WP user by GW2 account name
					$users      = get_users(
						array(
							'meta_key'   => 'gw2_account_name',
							'meta_value' => $name,
							'number'     => 1,
						)
					);
					$last_login = '';
					if ( ! empty( $users ) && isset( $users[0] ) ) {
						$uid        = $users[0]->ID;
						$raw        = get_user_meta( $uid, 'gw2_last_login', true );
						$ts         = is_numeric( $raw ) ? (int) $raw : ( is_string( $raw ) && ctype_digit( $raw ) ? (int) $raw : 0 );
						$last_login = $ts > 0
							? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts )
							: esc_html__( 'Never', 'gw2-guild-login' );
					}
					?>
					<tr>
						<td><?php echo esc_html( $name ); ?></td>
						<td><?php echo esc_html( $rank ); ?></td>
						<td><?php echo esc_html( $joined ); ?></td>
						<td><?php echo esc_html( $last_login ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
