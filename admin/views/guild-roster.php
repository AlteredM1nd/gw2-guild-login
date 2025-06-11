<?php
/**
 * Guild Roster admin view for GW2 Guild Login plugin.
 *
 * Displays the list of guild members and allows filtering by rank.
 *
 * @package GW2_Guild_Login
 */

declare(strict_types=1);
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// Load settings and fetch guild data.
$settings_raw    = get_option( 'gw2gl_settings', array() );
$settings        = is_array( $settings_raw ) ? $settings_raw : array();
$guild_ids_raw   = isset( $settings['guild_ids'] ) && is_string( $settings['guild_ids'] ) ? $settings['guild_ids'] : '';
$guild_ids       = is_string( $guild_ids_raw ) ? array_filter( array_map( 'trim', explode( ',', $guild_ids_raw ) ) ) : array();
$guild_id        = isset( $guild_ids[0] ) && is_string( $guild_ids[0] ) ? $guild_ids[0] : '';
$ranks_handler   = GW2_Guild_Ranks::instance();
$data            = $ranks_handler->fetch_guild_data( (string) $guild_id );
$members         = array();
$available_ranks = array();
if ( ! is_wp_error( $data ) ) {
	$members         = isset( $data['members'] ) && is_array( $data['members'] ) ? $data['members'] : array();
	$available_ranks = isset( $data['ranks'] ) && is_array( $data['ranks'] ) ? $data['ranks'] : array();
}

// Handle rank filter.
$filter_rank_raw = filter_input( INPUT_GET, 'filter_rank', FILTER_SANITIZE_SPECIAL_CHARS );
$filter_rank     = is_string( $filter_rank_raw ) && '' !== $filter_rank_raw ? sanitize_key( $filter_rank_raw ) : '';
if ( '' !== $filter_rank ) {
	$members = array_filter(
		$members,
		function ( $m ) use ( $filter_rank ) {
			return is_array( $m ) && isset( $m['rank'] ) && (string) $m['rank'] === $filter_rank;
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
				<?php $name = is_string( $rank_name ) ? $rank_name : ( is_array( $rank_name ) && isset( $rank_name['name'] ) && is_string( $rank_name['name'] ) ? $rank_name['name'] : '' ); ?>
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
			<?php if ( count( $members ) > 0 ) : ?>
				<?php foreach ( $members as $member ) : ?>
					<?php
					$name        = is_array( $member ) && isset( $member['name'] ) && is_string( $member['name'] ) ? $member['name'] : '';
					$rank        = is_array( $member ) && isset( $member['rank'] ) && is_string( $member['rank'] ) ? $member['rank'] : '';
					$joined_val  = is_array( $member ) && isset( $member['joined'] ) ? $member['joined'] : '';
					$joined_ts   = ( is_string( $joined_val ) || is_numeric( $joined_val ) ) ? strtotime( (string) $joined_val ) : 0;
					$date_format = (string) get_option( 'date_format' );
					$time_format = (string) get_option( 'time_format' );
					$joined      = $joined_ts > 0 ? date_i18n( $date_format . ' ' . $time_format, $joined_ts ) : '';
					$users       = is_string( $name ) && '' !== $name ? get_users(
						array(
							'meta_key'   => 'gw2_account_name',
							'meta_value' => $name,
							'number'     => 1,
						)
					) : array();
					$last_login  = '';
					if ( is_array( $users ) && ! empty( $users ) && isset( $users[0] ) && is_object( $users[0] ) && isset( $users[0]->ID ) ) {
						$uid_val    = $users[0]->ID;
						$uid        = is_numeric( $uid_val ) ? (int) $uid_val : 0;
						$raw        = get_user_meta( $uid, 'gw2_last_login', true );
						$ts         = is_numeric( $raw ) ? (int) $raw : ( is_string( $raw ) && ctype_digit( $raw ) ? (int) $raw : 0 );
						$last_login = $ts > 0 ? date_i18n( $date_format . ' ' . $time_format, $ts ) : esc_html__( 'Never', 'gw2-guild-login' );
					}
					?>
					<tr>
						<td><?php echo esc_html( $name ); ?></td>
						<td><?php echo esc_html( $rank ); ?></td>
						<td><?php echo esc_html( $joined ); ?></td>
						<td><?php echo esc_html( $last_login ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No guild members found.', 'gw2-guild-login' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
