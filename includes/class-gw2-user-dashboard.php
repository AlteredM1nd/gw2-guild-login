<?php
/**
 * GW2_User_Dashboard
 *
 * Handles the enhanced user dashboard for the GW2 Guild Login plugin.
 * Manages user data display, session management, AJAX actions, and profile integration.
 *
 * @package GW2_Guild_Login
 * @since 2.6.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_dashboard_setup',
	function (): void {
		wp_add_dashboard_widget(
			'gw2gl_stats',
			__( 'GW2 Guild Login Security', 'gw2-guild-login' ),
			function (): void {
				global $wpdb;
				// @phpstan-ignore-next-line for $wpdb as WordPress core global
				/** @phpstan-ignore-next-line */
				$encrypted_count      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'gw2_api_key' AND meta_value != ''" );
				$encrypted_count_int  = is_numeric( $encrypted_count ) ? (int) $encrypted_count : 0;
				$encrypted_count_safe = $encrypted_count_int;

				$last_cache_flush      = get_option( 'gw2gl_last_cache_flush' );
				$last_cache_flush_safe = is_numeric( $last_cache_flush ) ? (int) $last_cache_flush : 0;

				/** @phpstan-ignore-next-line */
				$option_names = $wpdb->get_col(
				/** @phpstan-ignore-next-line */
					$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'gw2gl_failed_attempts_%' )
				);
				$failed_attempts = 0;
				if ( is_array( $option_names ) ) {
					foreach ( $option_names as $opt ) {
						if ( ! is_string( $opt ) ) {
							continue; // Harden for PHPStan: only use string option names.
						}
						$data = get_option( $opt );
						if ( is_array( $data ) && isset( $data['time'] ) && $data['time'] > ( time() - 86400 ) ) {
							$failed_attempts += isset( $data['count'] ) && is_numeric( $data['count'] ) ? (int) $data['count'] : 0;
						} elseif ( is_numeric( $data ) && $data > 0 ) {
							$failed_attempts += (int) $data;
						}
					}
				}
				$failed_attempts_safe = (int) $failed_attempts;

				$secure_auth_key      = ( defined( 'SECURE_AUTH_KEY' ) && is_string( SECURE_AUTH_KEY ) ) ? SECURE_AUTH_KEY : '';
				$encryption_status    = ( strlen( $secure_auth_key ) >= 64 )
				? '<span style="color:green">✔ Active</span>'
				: '<span style="color:red">✖ Insecure</span>';
				$last_cache_flush_str = $last_cache_flush_safe > 0 ? esc_html( gmdate( 'Y-m-d H:i', $last_cache_flush_safe ) ) : esc_html__( 'Never', 'gw2-guild-login' );

				$encrypted_count_str   = (string) $encrypted_count_int;
				$encryption_status_str = $encryption_status;
				$last_cache_flush_str  = $last_cache_flush_safe > 0 ? gmdate( 'Y-m-d H:i', $last_cache_flush_safe ) : esc_html__( 'Never', 'gw2-guild-login' );
				$failed_attempts_str   = (string) (int) $failed_attempts_safe;

				echo '<p><strong>Encrypted API Keys:</strong> ' . esc_html( $encrypted_count_str ) . '</p>';
				echo '<p><strong>Encryption Status:</strong> ' . esc_html( $encryption_status_str ) . '</p>';
				echo '<p><strong>Last Cache Flush:</strong> ' . esc_html( $last_cache_flush_str ) . '</p>';
				echo '<p><strong>Failed Logins (24h):</strong> ' . esc_html( $failed_attempts_str ) . '</p>';
				// PHPStan: All variables are explicitly string and escaped as needed.
			}
		);
	}
);

/**
 * GW2 User Dashboard Handler.
 *
 * Handles the user dashboard functionality for the GW2 Guild Login plugin.
 */
class GW2_User_Dashboard {
	/**
	 * Instance of this class.
	 *
	 * @since 2.6.2
	 * @var GW2_User_Dashboard|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since 2.6.2
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.6.2
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_dashboard_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_gw2_dashboard_action', array( $this, 'handle_ajax_request' ) );
	}

	/**
	 * Initialize the dashboard.
	 *
	 * @since 2.6.2
	 * @return void
	 */
	public function init(): void {
		// Add user profile fields.
		add_action( 'show_user_profile', array( $this, 'add_profile_section' ) );
		add_action( 'edit_user_profile', array( $this, 'add_profile_section' ) );

		// Save profile fields.
		add_action( 'personal_options_update', array( $this, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
	}

	/**
	 * Add dashboard menu item.
	 *
	 * @since 2.6.2
	 * @return void
	 */
	public function add_dashboard_menu(): void {
		add_users_page(
			esc_html__( 'GW2 Account', 'gw2-guild-login' ),
			esc_html__( 'GW2 Account', 'gw2-guild-login' ),
			'read',
			'gw2-account',
			array( $this, 'render_dashboard_page' )
		);
	}

	/**
	 * Enqueue dashboard scripts and styles.
	 *
	 * @since 2.6.2
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_scripts( string $hook ): void {
		if ( 'users_page_gw2-account' !== $hook && 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'gw2-dashboard-css',
			plugins_url( '../assets/css/gw2-dashboard.css', __FILE__ ),
			array(),
			'2.4.0'
		);

		wp_enqueue_script(
			'gw2-dashboard-js',
			plugins_url( '../assets/js/gw2-dashboard.js', __FILE__ ),
			array( 'jquery' ),
			'2.4.0',
			true
		);

		wp_localize_script(
			'gw2-dashboard-js',
			'gw2Dashboard',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gw2-dashboard-nonce' ),
				'i18n'    => array(
					'confirmLogoutAll' => __( 'Are you sure you want to log out of all other devices?', 'gw2-guild-login' ),
					'confirmRevokeKey' => __( 'Are you sure you want to revoke your API key? This will log you out.', 'gw2-guild-login' ),
					'error'            => __( 'An error occurred. Please try again.', 'gw2-guild-login' ),
				),
			)
		);
	}

	/**
	 * Render the dashboard page.
	 *
	 * @since 2.6.2
	 * @return void
	 */
	public function render_dashboard_page(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to view this page.', 'gw2-guild-login' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'gw2gl' ) );
		}

		// $user_id is always int from get_current_user_id() in WP 5.3+.
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id ); // WP guarantees WP_User|false.
		if ( ! $user instanceof \WP_User ) {
			wp_die( esc_html__( 'User not found.', 'gw2-guild-login' ) );
		}

		$gw2_account_id_mixed = get_user_meta( $user_id, 'gw2_account_id', true );
		$gw2_account_id       = is_string( $gw2_account_id_mixed ) ? $gw2_account_id_mixed : '';
		// Use GW2_User_Handler to decrypt API key.
		// Always pass GW2_API instance to GW2_User_Handler for PHPStan compliance.
		$gw2_api_dashboard = class_exists( 'GW2_API' ) ? new GW2_API() : null;
		$user_handler      = ( $gw2_api_dashboard && class_exists( 'GW2_User_Handler' ) ) ? new GW2_User_Handler( $gw2_api_dashboard ) : null;
		$gw2_api_key       = $user_handler ? $user_handler->decrypt_api_key( $user_id ) : '';
		$last_login        = get_user_meta( $user_id, 'gw2_last_login', true );

		$sessions = WP_Session_Tokens::get_instance( $user_id );
		/** @phpstan-ignore-next-line */
		$all_sessions    = method_exists( $sessions, 'get_all' ) ? $sessions->get_all() : array();
		$current_session = wp_get_session_token();
		$current_ip      = '';
		$current_ua      = '';

		if ( isset( $all_sessions[ $current_session ] ) ) {
			$session_entry = $all_sessions[ $current_session ];
			/** @phpstan-ignore-next-line */
			if ( is_array( $session_entry ) ) {
				/** @phpstan-ignore-next-line */
				$current_ip = isset( $session_entry['ip'] ) && is_string( $session_entry['ip'] ) ? $session_entry['ip'] : '';
				/** @phpstan-ignore-next-line */
				$current_ua = isset( $session_entry['ua'] ) && is_string( $session_entry['ua'] ) ? $session_entry['ua'] : '';
			} else {
				$current_ip = '';
				$current_ua = '';
			}
		}

		include plugin_dir_path( __DIR__ ) . 'templates/dashboard/dashboard.php';
	}

	/**
	 * Add GW2 account section to user profile.
	 *
	 * @since 2.6.2
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public function add_profile_section( \WP_User $user ): void {
		$user_id              = $user->ID;
		$gw2_account_id_mixed = get_user_meta( $user_id, 'gw2_account_id', true );
		$gw2_account_id       = is_string( $gw2_account_id_mixed ) ? $gw2_account_id_mixed : '';
		$gw2_api_profile      = class_exists( 'GW2_API' ) ? new GW2_API() : null;
		$user_handler         = ( $gw2_api_profile && class_exists( 'GW2_User_Handler' ) ) ? new GW2_User_Handler( $gw2_api_profile ) : null;
		$gw2_api_key_mixed    = $user_handler ? $user_handler->decrypt_api_key( $user_id ) : '';
		/** @phpstan-ignore-next-line */
		$gw2_api_key      = is_string( $gw2_api_key_mixed ) ? $gw2_api_key_mixed : '';
		$last_login_mixed = get_user_meta( $user_id, 'gw2_last_login', true );
		/** @phpstan-ignore-next-line */
		$last_login = is_string( $last_login_mixed ) ? $last_login_mixed : '';

		$last_login_safe = '';
		/** @phpstan-ignore-next-line */
		if ( '' !== $last_login && ( is_numeric( $last_login ) || ( is_string( $last_login ) && false !== strtotime( $last_login ) ) ) ) {
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			/** @phpstan-ignore-next-line */
			$timestamp = is_numeric( $last_login ) ? (int) $last_login : ( is_string( $last_login ) ? strtotime( $last_login ) : 0 );
			/** @phpstan-ignore-next-line */
			$date_format_str = is_string( $date_format ) ? $date_format : 'Y-m-d';
			/** @phpstan-ignore-next-line */
			$time_format_str = is_string( $time_format ) ? $time_format : 'H:i';
			$last_login_safe = date_i18n( $date_format_str . ' ' . $time_format_str, $timestamp );
		} else {
			$last_login_safe = esc_html__( 'Never', 'gw2-guild-login' );
		}
		?>
		<h2><?php esc_html_e( 'Guild Wars 2 Account', 'gw2-guild-login' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="gw2_account_id"><?php esc_html_e( 'GW2 Account ID', 'gw2-guild-login' ); ?></label></th>
				<td>
					<?php /** @phpstan-ignore-next-line */ ?>
<input type="text" name="gw2_account_id" id="gw2_account_id" value="<?php echo esc_attr( is_string( $gw2_account_id ) ? $gw2_account_id : '' ); ?>" class="regular-text" disabled />
					<p class="description"><?php esc_html_e( 'Your Guild Wars 2 account ID.', 'gw2-guild-login' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="gw2_last_login"><?php esc_html_e( 'Last Login', 'gw2-guild-login' ); ?></label></th>
				<td>
					<?php /** @phpstan-ignore-next-line */ ?>
<input type="text" name="gw2_last_login" id="gw2_last_login" value="<?php echo esc_attr( is_string( $last_login ) ? $last_login : '' ); ?>" class="regular-text" disabled />
				</td>
			</tr>
			<?php
			/** @phpstan-ignore-next-line */
			if ( current_user_can( 'manage_options' ) && is_string( $gw2_api_key ) && '' !== $gw2_api_key ) :
				?>
			<tr>
				<th><label for="gw2_api_key"><?php esc_html_e( 'API Key', 'gw2-guild-login' ); ?></label></th>
				<th><label><?php esc_html_e( 'API Key', 'gw2-guild-login' ); ?></label></th>
				<td>
					<div class="gw2-api-key-wrapper">
									<?php /** @phpstan-ignore-next-line */ ?>
<input type="password" value="<?php echo esc_attr( is_string( $gw2_api_key ) ? $gw2_api_key : '' ); ?>" class="regular-text" id="gw2_api_key" readonly autocomplete="off" />
						<button type="button" class="button button-secondary" id="toggle-api-key"><?php esc_html_e( 'Show', 'gw2-guild-login' ); ?></button>
						<button type="button" class="button button-secondary" id="copy-api-key"><?php esc_html_e( 'Copy', 'gw2-guild-login' ); ?></button>
					</div>
					<p class="description"><?php esc_html_e( 'Keep your API key secure and do not share it with anyone.', 'gw2-guild-login' ); ?></p>
				</td>
			</tr>
						<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Save profile fields.
	 *
	 * @since 2.6.2
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_profile_fields( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Add any additional fields to save here.
	}

	/**
	 * Handle AJAX requests.
	 *
	 * @since 2.6.2
	 * @return void
	 */
	public function handle_ajax_request(): void {
		check_ajax_referer( 'gw2-dashboard-nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[GW2 Guild Login] Unauthorized AJAX attempt by non-logged-in user.' );
			}
			wp_send_json_error( __( 'You must be logged in to perform this action.', 'gw2-guild-login' ) );
		}

		$action_raw = filter_input( INPUT_POST, 'action_type', FILTER_SANITIZE_SPECIAL_CHARS );
		$action     = is_string( $action_raw ) ? sanitize_text_field( $action_raw ) : '';
		$user_id    = (int) get_current_user_id();

		switch ( $action ) {
			case 'revoke_sessions':
				$this->revoke_other_sessions( $user_id );
				break;
			case 'refresh_data':
				$this->refresh_user_data( $user_id );
				break;
			default:
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[GW2 Guild Login] Invalid AJAX action: ' . $action . ' by user ' . $user_id );
				}
				wp_send_json_error( __( 'An unexpected error occurred. Please try again later.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Revoke all sessions except the current one.
	 *
	 * @phpstan-ignore-next-line WP_Session_Tokens is a WordPress core class.
	 * @since 2.6.2
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function revoke_other_sessions( int $user_id ): void {
		/** @phpstan-ignore-next-line */
		$sessions        = WP_Session_Tokens::get_instance( $user_id );
		$current_session = wp_get_session_token();
		/** @phpstan-ignore-next-line */
		if ( is_object( $sessions ) && method_exists( $sessions, 'destroy_others' ) ) {
			/** @phpstan-ignore-next-line */
			$sessions->destroy_others( $current_session );
		}
		wp_send_json_success( __( 'All other sessions have been revoked.', 'gw2-guild-login' ) );
	}

	/**
	 * Refresh user data from GW2 API.
	 *
	 * @since 2.6.2
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function refresh_user_data( int $user_id ): void {
		// Always pass GW2_API instance to GW2_User_Handler for PHPStan compliance.
		$gw2_api_refresh = class_exists( 'GW2_API' ) ? new GW2_API() : null;
		$user_handler    = ( $gw2_api_refresh && class_exists( 'GW2_User_Handler' ) ) ? new GW2_User_Handler( $gw2_api_refresh ) : null;
		/** @phpstan-ignore-next-line */
		$api_key_mixed = $user_handler ? $user_handler->decrypt_api_key( $user_id ) : '';
		$api_key       = is_string( $api_key_mixed ) ? $api_key_mixed : '';

		if ( '' === $api_key ) {
			wp_send_json_error( __( 'No API key found.', 'gw2-guild-login' ) );
		}

		$account_data = null;
		if ( class_exists( 'GW2_API' ) ) {
			$gw2_api = new GW2_API();
			/** @phpstan-ignore-next-line */
			$account_data = $gw2_api->get_account_data( $api_key );
		}

		if ( is_wp_error( $account_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$err_msg = $account_data->get_error_message();
				error_log( '[GW2 Guild Login] GW2 API error for user ' . $user_id . ': ' . $err_msg );
			}
			wp_send_json_error( __( 'Failed to fetch account data from GW2 API. Please try again later.', 'gw2-guild-login' ) );
		}

		if ( is_array( $account_data ) ) {
			if ( isset( $account_data['name'] ) && is_string( $account_data['name'] ) ) {
				update_user_meta( $user_id, 'gw2_account_name', $account_data['name'] );
			}
			if ( isset( $account_data['world'] ) && is_string( $account_data['world'] ) ) {
				update_user_meta( $user_id, 'gw2_world', $account_data['world'] );
			}
			if ( isset( $account_data['created'] ) && is_string( $account_data['created'] ) ) {
				update_user_meta( $user_id, 'gw2_created', $account_data['created'] );
			}
			if ( isset( $account_data['guilds'] ) && is_array( $account_data['guilds'] ) && ! empty( $account_data['guilds'] ) ) {
				update_user_meta( $user_id, 'gw2_guilds', $account_data['guilds'] );
			}
			wp_send_json_success( __( 'Account data refreshed successfully.', 'gw2-guild-login' ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[GW2 Guild Login] Failed to refresh account data for user ' . $user_id );
		}
		wp_send_json_error( __( 'An unexpected error occurred while refreshing your account data. Please try again later.', 'gw2-guild-login' ) );
	}
}

/**
 * Initialize the user dashboard.
 *
 * @since 2.6.2
 * @return void
 */
function gw2_user_dashboard_init(): void { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	GW2_User_Dashboard::get_instance();
}
add_action( 'plugins_loaded', 'gw2_user_dashboard_init' );
