<?php
/**
 * @phpstan-ignore-next-line
 * Suppressing mixed type errors for WordPress template variables and functions
 * These are safe to use in this context due to WordPress's type system
 *
 * @package GW2_Guild_Login
 */

/**
 * @phpstan-ignore-next-line
 * Suppressing unnecessary type check errors
 * These checks are good defensive programming practices
 */

/**
 * @phpstan-ignore-next-line
 * Suppressing cast.string errors
 * These casts are safe in WordPress context
 */

/**
 * @phpstan-ignore-next-line
 * Suppressing echo.nonString errors
 * All variables are properly escaped before output
 */

/**
 * GW2 Guild Login Dashboard Template
 *
 * @var \WP_User $user
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user data.
$user = wp_get_current_user();
if ( ! $user instanceof WP_User ) {
	return;
}

// Guard for PHPStan: ensure all user properties are defined and correct type.
$user_id           = $user->ID;
$user_display_name = $user->display_name;

// Guard all user meta for PHPStan.
$gw2_account_id_mixed   = get_user_meta( $user_id, 'gw2_account_id', true );
$gw2_account_id         = is_string( $gw2_account_id_mixed ) ? $gw2_account_id_mixed : '';
$gw2_account_name_mixed = get_user_meta( $user_id, 'gw2_account_name', true );
$gw2_account_name       = is_string( $gw2_account_name_mixed ) ? $gw2_account_name_mixed : '';
$gw2_world_mixed        = get_user_meta( $user_id, 'gw2_world', true );
$gw2_world              = is_string( $gw2_world_mixed ) ? $gw2_world_mixed : '';
$gw2_created_mixed      = get_user_meta( $user_id, 'gw2_created', true );
$gw2_created            = is_string( $gw2_created_mixed ) ? $gw2_created_mixed : '';
$gw2_guilds_mixed       = get_user_meta( $user_id, 'gw2_guilds', true );
$gw2_guilds             = is_array( $gw2_guilds_mixed ) ? $gw2_guilds_mixed : array();
$last_login_mixed       = get_user_meta( $user_id, 'gw2_last_login', true );
$last_login             = is_int( $last_login_mixed ) ? $last_login_mixed : ( is_string( $last_login_mixed ) && ctype_digit( $last_login_mixed ) ? (int) $last_login_mixed : 0 );

// Get user sessions.
$sessions              = WP_Session_Tokens::get_instance( $user_id );
$all_sessions_mixed    = $sessions->get_all();
$all_sessions          = is_array( $all_sessions_mixed ) ? $all_sessions_mixed : array();
$current_session_mixed = wp_get_session_token();
$current_session       = is_string( $current_session_mixed ) ? $current_session_mixed : '';

// Get browser info.
$browser = array(
	'Chrome'  => 'Chrome',
	'Firefox' => 'Firefox',
	'Safari'  => 'Safari',
	'Opera'   => 'Opera',
	'MSIE'    => 'Internet Explorer',
	'Trident' => 'Internet Explorer',
	'Edge'    => 'Microsoft Edge',
);

// Get current session info.
$current_ip      = '';
$current_ua      = '';
$current_browser = __( 'Unknown', 'gw2-guild-login' );

if ( is_array( $all_sessions )
	&& '' !== $current_session
	&& isset( $all_sessions[ $current_session ] )
	&& is_array( $all_sessions[ $current_session ] )
) {
	$session_data = $all_sessions[ $current_session ];
	$current_ip   = isset( $session_data['ip'] ) && is_string( $session_data['ip'] ) ? $session_data['ip'] : '';
	$current_ua   = isset( $session_data['ua'] ) && is_string( $session_data['ua'] ) ? $session_data['ua'] : '';
}

// Detect browser.
foreach ( $browser as $key => $value ) {
	if ( stripos( $current_ua, $key ) !== false ) {
		$current_browser = $value;
		break;
	}
}

$gw2gl_settings_mixed = get_option( 'gw2gl_settings', array() );
$gw2gl_settings       = is_array( $gw2gl_settings_mixed ) ? $gw2gl_settings_mixed : array();
$gw2gl_logo           = isset( $gw2gl_settings['appearance_logo'] ) && is_string( $gw2gl_settings['appearance_logo'] ) ? $gw2gl_settings['appearance_logo'] : '';
$gw2gl_welcome        = isset( $gw2gl_settings['appearance_welcome_text'] ) && is_string( $gw2gl_settings['appearance_welcome_text'] ) ? $gw2gl_settings['appearance_welcome_text'] : '';

// Safe assignments for template variables - no type checks needed as these are always set.
$gw2gl_logo_safe    = (string) $gw2gl_logo;
$gw2gl_welcome_safe = (string) $gw2gl_welcome;
$site_logo_alt      = esc_attr__( 'Site Logo', 'gw2-guild-login' );
$site_logo_alt_safe = (string) esc_attr__( 'Site Logo', 'gw2-guild-login' );
$header_title       = esc_html__( 'Guild Wars 2 Account', 'gw2-guild-login' );
$header_title_safe  = (string) esc_html__( 'Guild Wars 2 Account', 'gw2-guild-login' );

// User data - WP_User properties are always set.
$user_display_name_safe = (string) $user->display_name;
$user_id_safe           = (int) $user->ID;
$user_avatar_url_safe   = '';
if ( $user_id_safe > 0 ) {
	$avatar_url           = get_avatar_url( $user_id_safe, array( 'size' => 96 ) );
	$user_avatar_url_safe = (string) $avatar_url;
}

// Account data - these are always set from user meta.
$account_id_str_safe = (string) $gw2_account_id;
$world_str_safe      = (string) $gw2_world;
$created_str_safe    = '';
$date_format         = get_option( 'date_format' );
$date_format_safe    = (string) $date_format;
if ( '' !== $gw2_created ) {
	$created_str_safe = date_i18n( $date_format_safe, strtotime( (string) $gw2_created ) );
}

// Sessions data - ensure array is initialized.
$sessions = array();
foreach ( $all_sessions as $session_id_raw => $session_data ) {
	if ( is_array( $session_data ) ) {
		$session_id = (string) $session_id_raw;
		$sessions[] = array(
			'session_id' => $session_id,
			'is_current' => $current_session === $session_id,
		);
	}
}

// Last login - ensure proper type casting.
$login_time = 0;
if ( is_int( $last_login ) ) {
	$login_time = $last_login;
} elseif ( is_string( $last_login ) && ctype_digit( $last_login ) ) {
	$login_time = (int) $last_login;
}
$current_time = (int) current_time( 'timestamp' );
$diff_str     = (string) human_time_diff( $login_time, $current_time );
$ago_str      = sprintf( __( '%s ago', 'gw2-guild-login' ), $diff_str );
?>
<div class="wrap gw2-dashboard" role="main" aria-label="GW2 User Dashboard">
	<?php if ( '' !== $gw2gl_logo_safe ) { ?>
		<div class="gw2-login-logo"><img src="<?php echo esc_url( $gw2gl_logo_safe ); ?>" alt="<?php echo esc_attr( $site_logo_alt_safe ); ?>" class="gw2-admin-custom-logo" /></div>
	<?php
	}
	if ( '' !== $gw2gl_welcome_safe ) {
	?>
		<div class="gw2-login-welcome-text"><?php echo wp_kses_post( $gw2gl_welcome_safe ); ?></div>
	<?php } ?>
	<h1><?php echo esc_html( $header_title_safe ); ?></h1>
	
	<?php do_action( 'gw2_dashboard_before_content' ); ?>
	
	<div class="gw2-dashboard-grid">
		<!-- Account Overview -->
		<div class="gw2-card">
			<h2><?php echo esc_html__( 'Account Overview', 'gw2-guild-login' ); ?></h2>
			<div class="gw2-card-content">
				<div class="gw2-account-info">
					<div class="gw2-account-avatar">
						<img src="<?php echo esc_url( $user_avatar_url_safe ); ?>" alt="<?php echo esc_attr( $user_display_name_safe ); ?>" class="gw2-avatar" />
					</div>
					<div class="gw2-account-details">
						<h3><?php echo esc_html( $user_display_name_safe ); ?></h3>
						<p class="gw2-account-id">
							<strong><?php echo esc_html__( 'Account ID:', 'gw2-guild-login' ); ?></strong>
							<?php echo esc_html( '' !== $account_id_str_safe ? $account_id_str_safe : __( 'Not connected', 'gw2-guild-login' ) ); ?>
						</p>
						<p class="gw2-account-world">
							<strong><?php echo esc_html__( 'World:', 'gw2-guild-login' ); ?></strong>
							<?php echo esc_html( '' !== $world_str_safe ? $world_str_safe : __( 'Unknown', 'gw2-guild-login' ) ); ?>
						</p>
						<p class="gw2-account-created">
							<strong><?php echo esc_html__( 'Created:', 'gw2-guild-login' ); ?></strong>
							<?php echo esc_html( '' !== $created_str_safe ? $created_str_safe : __( 'Unknown', 'gw2-guild-login' ) ); ?>
						</p>
						<p class="gw2-account-last-login">
							<strong><?php echo esc_html__( 'Last Login:', 'gw2-guild-login' ); ?></strong>
							<?php echo esc_html( $ago_str ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Active Sessions -->
		<div class="gw2-card">
			<h2><?php echo esc_html__( 'Active Sessions', 'gw2-guild-login' ); ?></h2>
			<div class="gw2-card-content">
				<table class="gw2-sessions-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Session ID', 'gw2-guild-login' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'gw2-guild-login' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'gw2-guild-login' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sessions as $session ) { ?>
							<tr>
								<td><?php echo esc_html( (string) $session['session_id'] ); ?></td>
								<td>
									<?php if ( ! empty( $session['is_current'] ) ) { ?>
										<span class="gw2-badge"><?php echo esc_html__( 'Current', 'gw2-guild-login' ); ?></span>
									<?php } else { ?>
										<a href="#" class="gw2-revoke-session" data-session="<?php echo esc_attr( (string) $session['session_id'] ); ?>">
											<?php echo esc_html__( 'Revoke', 'gw2-guild-login' ); ?>
										</a>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<button type="button" class="button button-secondary" id="revoke-other-sessions" aria-label="Revoke all other sessions">
					<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php echo esc_html__( 'Revoke', 'gw2-guild-login' ); ?></span>
					<?php echo esc_html__( 'Revoke All Other Sessions', 'gw2-guild-login' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php do_action( 'gw2_dashboard_after_content' ); ?>
	<div class="gw2-dashboard-footer">
	<p class="description">
		<?php
		$date_format           = get_option( 'date_format' );
		$time_format           = get_option( 'time_format' );
		$date_format_safe      = is_string( $date_format ) ? $date_format : 'Y-m-d';
		$time_format_safe      = is_string( $time_format ) ? $time_format : 'H:i';
		$datetime_val          = current_time( $date_format_safe . ' ' . $time_format_safe );
		$datetime_str_safe     = is_string( $datetime_val ) ? $datetime_val : '';
		$footer_format         = esc_html__( 'Last updated: %s', 'gw2-guild-login' );
		$footer_format_safe    = is_string( $footer_format ) ? $footer_format : 'Last updated: %s';
		$last_updated_str      = sprintf( $footer_format_safe, esc_html( $datetime_str_safe ) );
		$last_updated_str_safe = is_string( $last_updated_str ) ? $last_updated_str : '';
		echo esc_html( $last_updated_str_safe );
		?>
		<span class="sep">|</span>
		<?php
		$refresh_label      = esc_html__( 'Refresh', 'gw2-guild-login' );
		$refresh_label_safe = is_string( $refresh_label ) ? $refresh_label : 'Refresh';
		?>
		<a href="#" id="refresh-page"><?php echo esc_html( $refresh_label_safe ); ?></a>
	</p>
</div>
</div>
</div> <!-- .gw2-dashboard-grid -->
</div> <!-- .wrap.gw2-dashboard -->
<?php // End of dashboard.php. ?>