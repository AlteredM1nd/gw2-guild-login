<?php
/**
 * Reports admin view for GW2 Guild Login plugin.
 *
 * Shows various reports and allows filtering data from guild members.
 *
 * @package GW2_Guild_Login
 */

declare(strict_types=1);

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// Handle AJAX requests for data export and detailed views.
$request_action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

// Sanitize page query parameter.
$current_page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

if ( 'gw2_export_data' === $request_action ) {
	$report_type_raw = isset( $_GET['report_type'] ) && is_string( $_GET['report_type'] ) ? sanitize_text_field( wp_unslash( $_GET['report_type'] ) ) : '';
	$start_date_raw  = isset( $_GET['start_date'] ) && is_string( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date_raw    = isset( $_GET['end_date'] ) && is_string( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

	$report_type = is_string( $report_type_raw ) ? $report_type_raw : '';
	$start_date  = is_string( $start_date_raw ) ? $start_date_raw : '';
	$end_date    = is_string( $end_date_raw ) ? $end_date_raw : '';

	if ( '' !== $report_type ) {
		gw2_handle_data_export( $report_type, $start_date, $end_date );
	}
	return;
}

// Get filter parameters.
$selected_period_raw = isset( $_GET['period'] ) && is_string( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : '7_days';
$start_date_raw      = isset( $_GET['start_date'] ) && is_string( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
$end_date_raw        = isset( $_GET['end_date'] ) && is_string( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
$search_user_raw     = isset( $_GET['search_user'] ) && is_string( $_GET['search_user'] ) ? sanitize_text_field( wp_unslash( $_GET['search_user'] ) ) : '';

$selected_period = is_string( $selected_period_raw ) ? $selected_period_raw : '7_days';
$start_date      = is_string( $start_date_raw ) ? $start_date_raw : '';
$end_date        = is_string( $end_date_raw ) ? $end_date_raw : '';
$search_user     = is_string( $search_user_raw ) ? $search_user_raw : '';
$current_tab     = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';

// Ensure $wpdb is a wpdb object before property access.
/** @var \wpdb $wpdb */
global $wpdb;
if ( ! ( $wpdb instanceof \wpdb ) ) {
	return;
}

// Use $wpdb properties directly.
$users_table    = $wpdb->users;
$usermeta_table = $wpdb->usermeta;
$options_table  = $wpdb->options;

// Calculate date range based on period or custom dates.
if ( $start_date && $end_date ) {
	$period_start_result = strtotime( $start_date );
	$period_end_result   = strtotime( $end_date . ' 23:59:59' );
	$period_start        = false !== $period_start_result ? $period_start_result : strtotime( '-7 days' );
	$period_end          = false !== $period_end_result ? $period_end_result : time();
} else {
	switch ( $selected_period ) {
		case '24_hours':
			$period_start = strtotime( '-1 day' );
			break;
		case '7_days':
			$period_start = strtotime( '-7 days' );
			break;
		case '30_days':
			$period_start = strtotime( '-30 days' );
			break;
		case '90_days':
			$period_start = strtotime( '-90 days' );
			break;
		case '1_year':
			$period_start = strtotime( '-1 year' );
			break;
		default:
			$period_start = strtotime( '-7 days' );
	}
	$period_end = time();
}

/**
 * Retrieve login activity data.
 *
 * @param int    $start_timestamp Unix timestamp for the start of the interval.
 * @param int    $end_timestamp   Unix timestamp for the end of the interval.
 * @param string $search_user     Optional GW2 account name filter.
 * @return array<int, array<string, mixed>> List of login activity records.
 */
function gw2_get_login_activity( int $start_timestamp, int $end_timestamp, string $search_user = '' ): array {
	global $wpdb;
	/** @var \wpdb $wpdb */
	if ( ! $wpdb instanceof \wpdb ) {
		return array();
	}

	$user_filter = '';
	if ( '' !== $search_user ) {
		$user_ids_result = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s",
				'%' . $wpdb->esc_like( $search_user ) . '%',
				'%' . $wpdb->esc_like( $search_user ) . '%',
				'%' . $wpdb->esc_like( $search_user ) . '%'
			)
		);
		$user_ids        = is_array( $user_ids_result ) ? array_map(
			function ( $value ) {
			return is_numeric( $value ) ? (int) $value : 0;
			},
			$user_ids_result
		) : array();
		if ( ! empty( $user_ids ) ) {
			$user_filter = 'AND user_id IN (' . implode( ',', $user_ids ) . ')';
		} else {
			return array(); // No matching users found.
		}
	}

	// Prepare base query for login activity.
	$base_query = "SELECT user_id, meta_value as last_login FROM {$wpdb->usermeta} WHERE meta_key = %s AND CAST(meta_value AS SIGNED) BETWEEN %d AND %d";

	if ( '' !== $user_filter ) {
		$base_query .= ' ' . esc_sql( $user_filter );
	}
	$base_query .= ' ORDER BY CAST(meta_value AS SIGNED) DESC';

	$results       = $wpdb->get_results(
		$wpdb->prepare(
			$base_query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'gw2_last_login',
			$start_timestamp,
			$end_timestamp
		)
	);
	$activity_data = array();

	if ( is_array( $results ) ) {
		foreach ( $results as $row ) {
			if ( is_object( $row ) && property_exists( $row, 'user_id' ) && property_exists( $row, 'last_login' ) ) {
				$user_id = is_numeric( $row->user_id ) ? (int) $row->user_id : 0;
				$user    = get_userdata( $user_id );
				if ( $user instanceof \WP_User ) {
					$gw2_account_meta = get_user_meta( $user_id, 'gw2_account_name', true );
					$gw2_account      = is_string( $gw2_account_meta ) ? $gw2_account_meta : '';
					$last_login_val   = is_numeric( $row->last_login ) ? (int) $row->last_login : 0;

					$activity_data[] = array(
						'user_id'      => $user_id,
						'username'     => $user->user_login,
						'display_name' => $user->display_name,
						'email'        => $user->user_email,
						'last_login'   => gmdate( 'Y-m-d H:i:s', $last_login_val ),
						'gw2_account'  => $gw2_account,
					);
				}
			}
		}
	}

	return $activity_data;
}

/**
 * Retrieve failed login attempts data.
 *
 * Returns detailed failed login attempt records including IP, count, and timestamp.
 *
 * @param int $start_timestamp Unix timestamp for the start of the interval.
 * @param int $end_timestamp   Unix timestamp for the end of the interval.
 * @return array<int, array<string, mixed>> List of associative arrays with 'ip_address', 'attempts', and 'last_attempt'.
 */
function gw2_get_failed_attempts( int $start_timestamp, int $end_timestamp ): array {
	global $wpdb;
	$options_table = $wpdb->options;
	/** @var \wpdb $wpdb */
	if ( ! $wpdb instanceof \wpdb ) {
		return array();
	}

	/** @phpstan-ignore-next-line */
	$option_names_result = $wpdb->get_col(
		$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'gw2gl_failed_attempts_%' )
	);
	$option_names        = is_array( $option_names_result ) ? $option_names_result : array();

	$failed_attempts = array();
	foreach ( $option_names as $opt ) {
		if ( ! is_string( $opt ) ) {
			continue;
		}
		$data = get_option( $opt );
		if ( is_array( $data ) && isset( $data['count'], $data['time'] ) ) {
			$attempt_time = is_numeric( $data['time'] ) ? (int) $data['time'] : 0;
			if ( $attempt_time >= $start_timestamp && $attempt_time <= $end_timestamp ) {
				$ip                = str_replace( 'gw2gl_failed_attempts_', '', $opt );
				$attempts_val      = is_numeric( $data['count'] ) ? (int) $data['count'] : 0;
				$failed_attempts[] = array(
					'ip_address'   => $ip,
					'attempts'     => $attempts_val,
					'last_attempt' => gmdate( 'Y-m-d H:i:s', $attempt_time ),
				);
			}
		}
	}

	return $failed_attempts;
}

/**
 * Retrieve user engagement data.
 *
 * @param string $search_user GW2 account name to retrieve engagement for.
 * @return array<int, array<string, mixed>> Associative array of user engagement metrics.
 */
function gw2_get_user_engagement( string $search_user = '' ): array {
	global $wpdb;
	$users_table    = $wpdb->users;
	$usermeta_table = $wpdb->usermeta;
	$prefix         = $wpdb->prefix;
	/** @var \wpdb $wpdb */
	if ( ! $wpdb instanceof \wpdb ) {
		return array();
	}

	$user_filter = '';
	if ( $search_user ) {
		$prepared_filter = $wpdb->prepare(
			/** @phpstan-ignore-next-line */
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			' AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)',
			'%' . $wpdb->esc_like( $search_user ) . '%',
			'%' . $wpdb->esc_like( $search_user ) . '%',
			'%' . $wpdb->esc_like( $search_user ) . '%'
		);
		$user_filter = is_string( $prepared_filter ) ? $prepared_filter : '';
	}

	// Prepare query for user engagement data.
	$base_query = 'SELECT u.ID, u.user_login, u.display_name, u.user_email, u.user_registered, '
		. 'gw2_account.meta_value as gw2_account_name, '
		. 'last_login.meta_value as last_login_timestamp, '
		. 'twofa.user_id as has_2fa '
		. "FROM {$wpdb->users} u "
		. "LEFT JOIN {$wpdb->usermeta} gw2_account ON u.ID = gw2_account.user_id AND gw2_account.meta_key = 'gw2_account_name' "
		. "LEFT JOIN {$wpdb->usermeta} last_login ON u.ID = last_login.user_id AND last_login.meta_key = 'gw2_last_login' "
		. "LEFT JOIN {$wpdb->prefix}gw2_2fa_secrets twofa ON u.ID = twofa.user_id "
		. 'WHERE 1=1' . $user_filter . ' '
		. 'ORDER BY u.user_registered DESC';

	$results = $wpdb->get_results( $base_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$engagement_data = array();
	if ( $results ) {
		foreach ( $results as $row ) {
			if ( ! is_object( $row ) ) {
				continue;
			}
			/** @phpstan-ignore-next-line */
			$last_login_timestamp = property_exists( $row, 'last_login_timestamp' ) ? $row->last_login_timestamp : null;
			$last_login_val       = $last_login_timestamp && is_numeric( $last_login_timestamp ) ? (int) $last_login_timestamp : null;
			$last_login           = $last_login_val ? gmdate( 'Y-m-d H:i:s', $last_login_val ) : 'Never';
			$days_since_login     = $last_login_val ? round( ( time() - $last_login_val ) / DAY_IN_SECONDS ) : 'N/A';

			$user_id      = property_exists( $row, 'ID' ) && is_numeric( $row->ID ) ? (int) $row->ID : 0;
			$username     = property_exists( $row, 'user_login' ) && is_string( $row->user_login ) ? $row->user_login : '';
			$display_name = property_exists( $row, 'display_name' ) && is_string( $row->display_name ) ? $row->display_name : '';
			$email        = property_exists( $row, 'user_email' ) && is_string( $row->user_email ) ? $row->user_email : '';
			$registered   = property_exists( $row, 'user_registered' ) && is_string( $row->user_registered ) ? $row->user_registered : '';
			$gw2_account  = property_exists( $row, 'gw2_account_name' ) && is_string( $row->gw2_account_name ) ? $row->gw2_account_name : '';
			$has_2fa      = property_exists( $row, 'has_2fa' ) && ! empty( $row->has_2fa );

			$engagement_data[] = array(
				'user_id'          => $user_id,
				'username'         => $username,
				'display_name'     => $display_name,
				'email'            => $email,
				'registered'       => $registered,
				'gw2_account'      => $gw2_account,
				'last_login'       => $last_login,
				'days_since_login' => $days_since_login,
				'has_2fa'          => $has_2fa,
			);
		}
	}

	return $engagement_data;
}

/**
 * Determine user activity status based on last login timestamp.
 *
 * @param int|null $last_login_timestamp Unix timestamp of the last login.
 * @return string Activity status string.
 */
function gw2_get_user_activity_status( ?int $last_login_timestamp ): string {
	if ( ! $last_login_timestamp ) {
		return 'Never logged in';
	}

	$days_ago = ( time() - $last_login_timestamp ) / DAY_IN_SECONDS;

	if ( $days_ago <= 7 ) {
		return 'Active (7 days)';
	}
	if ( $days_ago <= 30 ) {
		return 'Recent (30 days)';
	}
	if ( $days_ago <= 90 ) {
		return 'Inactive (90 days)';
	}
	return 'Dormant (90+ days)';
}

/**
 * Handle exporting report data as CSV or JSON file.
 *
 * @param string $report_type Type of report to export.
 * @param string $start_date  Start date (Y-m-d).
 * @param string $end_date    End date (Y-m-d).
 */
function gw2_handle_data_export( string $report_type, string $start_date, string $end_date ): void {
	$filename = "gw2-guild-login-{$report_type}-" . gmdate( 'Y-m-d' ) . '.csv';

	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$output = fopen( 'php://output', 'w' );

	if ( false === $output ) {
		return;
	}

	switch ( $report_type ) {
		case 'login_activity':
			$start_timestamp_raw = $start_date ? strtotime( (string) $start_date ) : strtotime( '-7 days' );
			$end_timestamp_raw   = $end_date ? strtotime( $end_date . ' 23:59:59' ) : time();
			$start_timestamp     = false !== $start_timestamp_raw ? $start_timestamp_raw : strtotime( '-7 days' );
			$end_timestamp       = false !== $end_timestamp_raw ? $end_timestamp_raw : time();

			$data = gw2_get_login_activity( $start_timestamp, $end_timestamp );

			fputcsv( $output, array( 'User ID', 'Username', 'Display Name', 'Email', 'Last Login', 'GW2 Account' ) );
			foreach ( $data as $row ) {
				if ( is_array( $row ) ) {
					fputcsv(
						$output,
						array(
							(string) ( $row['user_id'] ?? '' ),
							(string) ( $row['username'] ?? '' ),
							(string) ( $row['display_name'] ?? '' ),
							(string) ( $row['email'] ?? '' ),
							(string) ( $row['last_login'] ?? '' ),
							(string) ( $row['gw2_account'] ?? '' ),
						)
					);
				}
			}
			break;

		case 'user_engagement':
			$data = gw2_get_user_engagement();

			fputcsv( $output, array( 'User ID', 'Username', 'Display Name', 'Email', 'Registered', 'GW2 Account', 'Last Login', 'Days Since Login', '2FA Enabled', 'Status' ) );
			foreach ( $data as $row ) {
				if ( is_array( $row ) ) {
					fputcsv(
						$output,
						array(
							(string) ( $row['user_id'] ?? '' ),
							(string) ( $row['username'] ?? '' ),
							(string) ( $row['display_name'] ?? '' ),
							(string) ( $row['email'] ?? '' ),
							(string) ( $row['registered'] ?? '' ),
							(string) ( $row['gw2_account'] ?? '' ),
							(string) ( $row['last_login'] ?? '' ),
							(string) ( $row['days_since_login'] ?? '' ),
							(string) ( $row['has_2fa'] ?? '' ),
							(string) ( $row['status'] ?? '' ),
						)
					);
				}
			}
			break;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	fclose( $output );
}

// Get data for current filters.
$login_activity  = gw2_get_login_activity( $period_start, $period_end, $search_user );
$failed_attempts = gw2_get_failed_attempts( $period_start, $period_end );
$user_engagement = gw2_get_user_engagement( $search_user );

// Calculate summary statistics.
$total_logins = count( $login_activity );
$unique_users = count( array_unique( array_column( $login_activity, 'user_id' ) ) );
$total_failed = array_sum( array_column( $failed_attempts, 'attempts' ) );

// Calculate user engagement statistics.
$gw2_linked       = count(
	array_filter(
		$user_engagement,
		function ( $user ) {
			return ( $user['gw2_account'] ?? '' ) !== 'Not linked';
		}
	)
);
$twofa_enabled    = count(
	array_filter(
		$user_engagement,
		function ( $user ) {
			return ( $user['has_2fa'] ?? '' ) === 'Yes';
		}
	)
);
$active_users_30d = count(
	array_filter(
		$user_engagement,
		function ( $user ) {
			return is_numeric( $user['days_since_login'] ?? '' ) && ( $user['days_since_login'] ?? 0 ) <= 30;
		}
	)
);

?>

<div class="wrap gw2-admin-reports">
	<h1><?php esc_html_e( 'Advanced Reports & Analytics', 'gw2-guild-login' ); ?></h1>
	
	<!-- Filter Controls -->
	<div class="gw2-report-filters card" style="padding: 15px; margin-bottom: 20px;">
		<form method="get" action="">
			<input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>">
			
			<div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
				<div>
					<label for="period"><strong><?php esc_html_e( 'Time Period:', 'gw2-guild-login' ); ?></strong></label><br>
					<select name="period" id="period" onchange="toggleCustomDates()">
						<option value="24_hours" <?php selected( $selected_period, '24_hours' ); ?>><?php esc_html_e( 'Last 24 Hours', 'gw2-guild-login' ); ?></option>
						<option value="7_days" <?php selected( $selected_period, '7_days' ); ?>><?php esc_html_e( 'Last 7 Days', 'gw2-guild-login' ); ?></option>
						<option value="30_days" <?php selected( $selected_period, '30_days' ); ?>><?php esc_html_e( 'Last 30 Days', 'gw2-guild-login' ); ?></option>
						<option value="90_days" <?php selected( $selected_period, '90_days' ); ?>><?php esc_html_e( 'Last 90 Days', 'gw2-guild-login' ); ?></option>
						<option value="1_year" <?php selected( $selected_period, '1_year' ); ?>><?php esc_html_e( 'Last Year', 'gw2-guild-login' ); ?></option>
						<option value="custom" <?php selected( ( $start_date ? 'custom' : '' ), 'custom' ); ?>><?php esc_html_e( 'Custom Range', 'gw2-guild-login' ); ?></option>
					</select>
				</div>
				
				<div id="custom-dates" style="display: <?php echo $start_date ? 'flex' : 'none'; ?>; gap: 10px;">
					<div>
						<label for="start_date"><strong><?php esc_html_e( 'Start Date:', 'gw2-guild-login' ); ?></strong></label><br>
						<input type="date" name="start_date" id="start_date" value="<?php echo esc_attr( $start_date ); ?>">
					</div>
					<div>
						<label for="end_date"><strong><?php esc_html_e( 'End Date:', 'gw2-guild-login' ); ?></strong></label><br>
						<input type="date" name="end_date" id="end_date" value="<?php echo esc_attr( $end_date ); ?>">
					</div>
				</div>
				
				<div>
					<label for="search_user"><strong><?php esc_html_e( 'Search User:', 'gw2-guild-login' ); ?></strong></label><br>
					<input type="text" name="search_user" id="search_user" value="<?php echo esc_attr( $search_user ); ?>" 
							placeholder="<?php esc_attr_e( 'Username, email, or display name', 'gw2-guild-login' ); ?>" style="width: 200px;">
				</div>
				
				<div>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Apply Filters', 'gw2-guild-login' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . (string) $current_page ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'gw2-guild-login' ); ?></a>
				</div>
			</div>
		</form>
	</div>

	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'overview' ) ); ?>" 
			class="nav-tab <?php echo 'overview' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Overview', 'gw2-guild-login' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'login_activity' ) ); ?>" 
			class="nav-tab <?php echo 'login_activity' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Login Activity', 'gw2-guild-login' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'user_engagement' ) ); ?>" 
			class="nav-tab <?php echo 'user_engagement' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'User Engagement', 'gw2-guild-login' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'security' ) ); ?>" 
			class="nav-tab <?php echo 'security' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Security', 'gw2-guild-login' ); ?>
		</a>
	</nav>

	<div class="tab-content" style="margin-top: 20px;">
		
		<?php if ( 'overview' === $current_tab ) : ?>
			<!-- Overview Dashboard -->
			<div class="gw2-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<div class="gw2-stat-card card" style="padding: 20px; text-align: center;">
					<h3 style="margin: 0; color: #2196F3; font-size: 2.5em;"><?php echo esc_html( (string) $total_logins ); ?></h3>
					<p style="margin: 5px 0 0 0; font-size: 1.1em;"><?php esc_html_e( 'Total Logins', 'gw2-guild-login' ); ?></p>
					<small style="color: #666;"><?php echo esc_html( sprintf( __( 'in last %s', 'gw2-guild-login' ), 'custom' === $selected_period ? 'selected period' : str_replace( '_', ' ', $selected_period ) ) ); ?></small>
				</div>
				
				<div class="gw2-stat-card card" style="padding: 20px; text-align: center;">
					<h3 style="margin: 0; color: #4CAF50; font-size: 2.5em;"><?php echo esc_html( (string) $unique_users ); ?></h3>
					<p style="margin: 5px 0 0 0; font-size: 1.1em;"><?php esc_html_e( 'Unique Users', 'gw2-guild-login' ); ?></p>
					<small style="color: #666;"><?php esc_html_e( 'who logged in', 'gw2-guild-login' ); ?></small>
				</div>
				
				<div class="gw2-stat-card card" style="padding: 20px; text-align: center;">
					<h3 style="margin: 0; color: #FF9800; font-size: 2.5em;"><?php echo esc_html( (string) $active_users_30d ); ?></h3>
					<p style="margin: 5px 0 0 0; font-size: 1.1em;"><?php esc_html_e( 'Active Users', 'gw2-guild-login' ); ?></p>
					<small style="color: #666;"><?php esc_html_e( 'last 30 days', 'gw2-guild-login' ); ?></small>
				</div>
				
				<div class="gw2-stat-card card" style="padding: 20px; text-align: center;">
					<h3 style="margin: 0; color: #f44336; font-size: 2.5em;"><?php echo esc_html( (string) $total_failed ); ?></h3>
					<p style="margin: 5px 0 0 0; font-size: 1.1em;"><?php esc_html_e( 'Failed Attempts', 'gw2-guild-login' ); ?></p>
					<small style="color: #666;"><?php esc_html_e( 'security events', 'gw2-guild-login' ); ?></small>
				</div>
			</div>
			
			<!-- Quick Actions -->
			<div class="card" style="padding: 20px;">
				<h3><?php esc_html_e( 'Quick Actions', 'gw2-guild-login' ); ?></h3>
				<p>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'login_activity' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'View Login Details', 'gw2-guild-login' ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'user_engagement' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Analyze User Engagement', 'gw2-guild-login' ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'security' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Security Overview', 'gw2-guild-login' ); ?>
					</a>
				</p>
			</div>
			
		<?php elseif ( 'login_activity' === $current_tab ) : ?>
			<!-- Login Activity Details -->
			<div class="card" style="padding: 20px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<h3><?php esc_html_e( 'Login Activity Details', 'gw2-guild-login' ); ?></h3>
					<a href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'action'      => 'gw2_export_data',
								'report_type' => 'login_activity',
								'start_date'  => $start_date,
								'end_date'    => $end_date,
							)
						)
					);
					?>
					" class="button button-secondary">
						<?php esc_html_e( 'Export CSV', 'gw2-guild-login' ); ?>
					</a>
				</div>
				
				<?php if ( empty( $login_activity ) ) : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No login activity found for the selected period.', 'gw2-guild-login' ); ?></p>
				<?php else : ?>
					<div style="overflow-x: auto;">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'User', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Display Name', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Email', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'GW2 Account', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Last Login', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'gw2-guild-login' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $login_activity as $login ) : ?>
									<?php
									if ( ! is_array( $login ) ) {
continue;}
?>
									<tr>
										<td><strong><?php echo esc_html( (string) ( $login['username'] ?? '' ) ); ?></strong></td>
										<td><?php echo esc_html( (string) ( $login['display_name'] ?? '' ) ); ?></td>
										<td><?php echo esc_html( (string) ( $login['email'] ?? '' ) ); ?></td>
										<td>
											<?php $gw2_account = (string) ( $login['gw2_account'] ?? '' ); ?>
											<?php if ( $gw2_account ) : ?>
												<span style="color: #4CAF50;">✓ <?php echo esc_html( $gw2_account ); ?></span>
											<?php else : ?>
												<span style="color: #f44336;">✗ Not linked</span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( (string) ( $login['last_login'] ?? '' ) ); ?></td>
										<td>
											<?php $user_id = is_numeric( $login['user_id'] ?? 0 ) ? $login['user_id'] : 0; ?>
											<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (string) $user_id ) ); ?>" 
												class="button button-small"><?php esc_html_e( 'View User', 'gw2-guild-login' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					
					<p style="margin-top: 15px; color: #666;">
						<?php echo esc_html( sprintf( __( 'Showing %d login records', 'gw2-guild-login' ), count( $login_activity ) ) ); ?>
						<?php if ( $search_user ) : ?>
							<?php echo esc_html( sprintf( __( ' for users matching "%s"', 'gw2-guild-login' ), $search_user ) ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			
		<?php elseif ( 'user_engagement' === $current_tab ) : ?>
			<!-- User Engagement Analysis -->
			<div class="card" style="padding: 20px;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<h3><?php esc_html_e( 'User Engagement Analysis', 'gw2-guild-login' ); ?></h3>
					<a href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'action'      => 'gw2_export_data',
								'report_type' => 'user_engagement',
							)
						)
					);
					?>
					" class="button button-secondary">
						<?php esc_html_e( 'Export CSV', 'gw2-guild-login' ); ?>
					</a>
				</div>
				
				<!-- Engagement Statistics -->
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
					<?php $status_counts = array_count_values( array_column( $user_engagement, 'status' ) ); ?>
					
					<div style="text-align: center;">
						<strong style="display: block; font-size: 1.5em; color: #4CAF50;"><?php echo esc_html( (string) ( $status_counts['Active (7 days)'] ?? 0 ) ); ?></strong>
						<span><?php esc_html_e( 'Active (7 days)', 'gw2-guild-login' ); ?></span>
					</div>
					
					<div style="text-align: center;">
						<strong style="display: block; font-size: 1.5em; color: #FF9800;"><?php echo esc_html( (string) ( $status_counts['Recent (30 days)'] ?? 0 ) ); ?></strong>
						<span><?php esc_html_e( 'Recent (30 days)', 'gw2-guild-login' ); ?></span>
					</div>
					
					<div style="text-align: center;">
						<strong style="display: block; font-size: 1.5em; color: #f44336;"><?php echo esc_html( (string) ( $status_counts['Inactive (90 days)'] ?? 0 ) ); ?></strong>
						<span><?php esc_html_e( 'Inactive (90 days)', 'gw2-guild-login' ); ?></span>
					</div>
					
					<div style="text-align: center;">
						<strong style="display: block; font-size: 1.5em; color: #2196F3;"><?php echo esc_html( (string) ( $status_counts['Dormant (90+ days)'] ?? 0 ) ); ?></strong>
						<span><?php esc_html_e( 'Dormant (90+ days)', 'gw2-guild-login' ); ?></span>
					</div>
					
					<div style="text-align: center;">
						<strong style="display: block; font-size: 1.5em; color: #9C27B0;"><?php echo esc_html( (string) $twofa_enabled ); ?></strong>
						<span><?php esc_html_e( '2FA Enabled', 'gw2-guild-login' ); ?></span>
					</div>
				</div>
				
				<!-- User Details Table -->
				<?php if ( empty( $user_engagement ) ) : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No users found matching the current filters.', 'gw2-guild-login' ); ?></p>
				<?php else : ?>
					<div style="overflow-x: auto;">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'User', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Display Name', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'GW2 Account', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Registered', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Last Login', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( '2FA', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Status', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'gw2-guild-login' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $user_engagement as $user ) : ?>
									<?php
									if ( ! is_array( $user ) ) {
continue;}
?>
									<tr>
										<td><strong><?php echo esc_html( (string) ( $user['username'] ?? '' ) ); ?></strong></td>
										<td><?php echo esc_html( (string) ( $user['display_name'] ?? '' ) ); ?></td>
										<td>
											<?php if ( ( $user['gw2_account'] ?? '' ) !== 'Not linked' ) : ?>
												<span style="color: #4CAF50;"><?php echo esc_html( (string) ( $user['gw2_account'] ?? '' ) ); ?></span>
											<?php else : ?>
												<span style="color: #999;">Not linked</span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( gmdate( 'Y-m-d', is_string( $user['registered'] ?? '' ) ? strtotime( $user['registered'] ) : 0 ) ); ?></td>
										<td>
											<?php if ( ( $user['last_login'] ?? '' ) !== 'Never' ) : ?>
												<?php echo esc_html( (string) ( $user['last_login'] ?? '' ) ); ?>
												<br><small style="color: #666;"><?php echo esc_html( (string) ( $user['days_since_login'] ?? '' ) ); ?> days ago</small>
											<?php else : ?>
												<span style="color: #f44336;">Never</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( ( $user['has_2fa'] ?? '' ) === 'Yes' ) : ?>
												<span style="color: #4CAF50;">✓ Yes</span>
											<?php else : ?>
												<span style="color: #999;">✗ No</span>
											<?php endif; ?>
										</td>
										<td>
											<span class="gw2-status-badge" data-status="<?php echo esc_attr( strtolower( str_replace( ' ', '-', (string) ( $user['status'] ?? '' ) ) ) ); ?>">
												<?php echo esc_html( (string) ( $user['status'] ?? '' ) ); ?>
											</span>
										</td>
										<td>
											<?php $user_id = is_numeric( $user['user_id'] ?? 0 ) ? $user['user_id'] : 0; ?>
											<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (string) $user_id ) ); ?>" 
												class="button button-small"><?php esc_html_e( 'Edit User', 'gw2-guild-login' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					
					<p style="margin-top: 15px; color: #666;">
						<?php echo esc_html( sprintf( __( 'Showing %d users', 'gw2-guild-login' ), count( $user_engagement ) ) ); ?>
						<?php if ( $search_user ) : ?>
							<?php echo esc_html( sprintf( __( ' matching "%s"', 'gw2-guild-login' ), $search_user ) ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			
		<?php elseif ( 'security' === $current_tab ) : ?>
			<!-- Security Analysis -->
			<div class="card" style="padding: 20px; margin-bottom: 20px;">
				<h3><?php esc_html_e( 'Security Overview', 'gw2-guild-login' ); ?></h3>
				
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
					<div style="padding: 15px; background: #f0f8ff; border-left: 4px solid #2196F3; border-radius: 4px;">
						<h4 style="margin: 0 0 10px 0; color: #2196F3;">2FA Adoption</h4>
						<p style="margin: 0; font-size: 1.2em;">
							<strong><?php echo esc_html( (string) $twofa_enabled ); ?></strong> of <strong><?php echo esc_html( (string) count( $user_engagement ) ); ?></strong> users
							<br><small>(<?php echo count( $user_engagement ) > 0 ? round( ( $twofa_enabled / count( $user_engagement ) ) * 100, 1 ) : 0; ?>%)</small>
						</p>
					</div>
					
					<div style="padding: 15px; background: #fff3e0; border-left: 4px solid #FF9800; border-radius: 4px;">
						<h4 style="margin: 0 0 10px 0; color: #FF9800;">Failed Login Attempts</h4>
						<p style="margin: 0; font-size: 1.2em;">
							<strong><?php echo esc_html( (string) $total_failed ); ?></strong> attempts
							<br><small>in selected period</small>
						</p>
					</div>
					
					<div style="padding: 15px; background: #f3e5f5; border-left: 4px solid #9C27B0; border-radius: 4px;">
						<h4 style="margin: 0 0 10px 0; color: #9C27B0;">Account Linking</h4>
						<p style="margin: 0; font-size: 1.2em;">
							<strong><?php echo esc_html( (string) $gw2_linked ); ?></strong> of <strong><?php echo esc_html( (string) count( $user_engagement ) ); ?></strong> linked
							<br><small>(<?php echo count( $user_engagement ) > 0 ? round( ( $gw2_linked / count( $user_engagement ) ) * 100, 1 ) : 0; ?>%)</small>
						</p>
					</div>
				</div>
			</div>
			
			<!-- Failed Login Attempts Details -->
			<div class="card" style="padding: 20px;">
				<h3><?php esc_html_e( 'Failed Login Attempts by IP', 'gw2-guild-login' ); ?></h3>
				
				<?php if ( empty( $failed_attempts ) ) : ?>
					<p style="color: #4CAF50; font-style: italic;">
						<?php esc_html_e( 'No failed login attempts recorded in the selected period. Great security!', 'gw2-guild-login' ); ?>
					</p>
				<?php else : ?>
					<div style="overflow-x: auto;">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Failed Attempts', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Last Attempt', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Risk Level', 'gw2-guild-login' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'gw2-guild-login' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $failed_attempts as $attempt ) : ?>
									<?php
									if ( ! is_array( $attempt ) ) {
continue;}
?>
									<?php
									$attempts_count = is_numeric( $attempt['attempts'] ?? 0 ) ? (int) $attempt['attempts'] : 0;
									$risk_level     = 'Low';
									$risk_color     = '#4CAF50';
									if ( $attempts_count >= 10 ) {
										$risk_level = 'High';
										$risk_color = '#f44336';
									} elseif ( $attempts_count >= 5 ) {
										$risk_level = 'Medium';
										$risk_color = '#FF9800';
									}
									?>
									<tr>
										<td><code><?php echo esc_html( (string) ( $attempt['ip_address'] ?? '' ) ); ?></code></td>
										<td><strong><?php echo esc_html( (string) $attempts_count ); ?></strong></td>
										<td><?php echo esc_html( (string) ( $attempt['last_attempt'] ?? '' ) ); ?></td>
										<td>
											<span style="color: <?php echo esc_attr( $risk_color ); ?>; font-weight: bold;">
												<?php echo esc_html( $risk_level ); ?>
											</span>
										</td>
										<td>
											<?php $ip_address = (string) ( $attempt['ip_address'] ?? '' ); ?>
											<a href="https://whatismyipaddress.com/ip/<?php echo esc_attr( $ip_address ); ?>" 
												target="_blank" class="button button-small">
												<?php esc_html_e( 'Lookup IP', 'gw2-guild-login' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					
					<p style="margin-top: 15px; color: #666;">
						<?php echo esc_html( sprintf( __( 'Total: %1$d failed attempts from %2$d unique IPs', 'gw2-guild-login' ), $total_failed, count( $failed_attempts ) ) ); ?>
					</p>
				<?php endif; ?>
			</div>
			
		<?php endif; ?>
		
	</div>
</div>

<script>
function toggleCustomDates() {
	const periodSelect = document.getElementById('period');
	const customDates = document.getElementById('custom-dates');
	
	if (periodSelect.value === 'custom') {
		customDates.style.display = 'flex';
	} else {
		customDates.style.display = 'none';
	}
}

// Auto-submit form when period changes (except custom)
document.getElementById('period').addEventListener('change', function() {
	if (this.value !== 'custom') {
		this.form.submit();
	}
});
</script>

<style>
.gw2-status-badge {
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: bold;
	text-transform: uppercase;
}

.gw2-status-badge[data-status="active-(7-days)"] {
	background-color: #e8f5e8;
	color: #4CAF50;
}

.gw2-status-badge[data-status="recent-(30-days)"] {
	background-color: #fff3e0;
	color: #FF9800;
}

.gw2-status-badge[data-status="inactive-(90-days)"] {
	background-color: #ffebee;
	color: #f44336;
}

.gw2-status-badge[data-status="dormant-(90+-days)"] {
	background-color: #f3e5f5;
	color: #9C27B0;
}

.gw2-status-badge[data-status="never-logged-in"] {
	background-color: #e1f5fe;
	color: #2196F3;
}

.card {
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.nav-tab-wrapper {
	border-bottom: 1px solid #ccc;
	margin: 0;
	padding-top: 0;
}

.tab-content {
	min-height: 400px;
}

@media (max-width: 768px) {
	.gw2-dashboard-stats {
		grid-template-columns: 1fr !important;
	}
	
	.gw2-report-filters > form > div {
		flex-direction: column !important;
		align-items: stretch !important;
	}
	
	.gw2-report-filters input[type="text"],
	.gw2-report-filters select {
		width: 100% !important;
	}
}
</style>
