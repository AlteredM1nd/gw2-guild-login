<?php
/**
 * GW2 Guild Ranks Handler.
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

declare(strict_types=1);

/**
 * GW2_Guild_Ranks
 *
 * Handles Guild Rank-based access control for the GW2 Guild Login plugin.
 * Provides methods for restricting content, managing rank settings, and integrating with the GW2 API.
 */
class GW2_Guild_Ranks {
	/** @var self|null Singleton instance */
	private static ?self $instance = null;

	/** @var string Guild ranks table name */
	private string $table_ranks;

	/** @var string Guild members cache key prefix */
	private string $cache_prefix = 'gw2_guild_members_';

	/** @var int Cache expiration in seconds (1 hour) */
	private int $cache_expiration;

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	/**
	 * Constructor
	 */
	private function __construct() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$this->table_ranks = (string) $wpdb->prefix . 'gw2_guild_ranks';

		// Set cache expiration (1 hour).
		$this->cache_expiration = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;

		// Register activation hook.
		register_activation_hook( GW2_GUILD_LOGIN_FILE, array( $this, 'activate' ) );

		// Register shortcode.
		add_shortcode( 'gw2_restricted', array( $this, 'restricted_content_shortcode' ) );
	}

	/**
	 * Plugin activation
	 */
	/**
	 * Plugin activation
	 *
	 * @return void
	 */
	public function activate(): void {
		/** @var wpdb $wpdb */
		global $wpdb;

		$sql = "CREATE TABLE {$this->table_ranks} (
         id mediumint(9) NOT NULL AUTO_INCREMENT,
         rank_id varchar(50) NOT NULL,
         rank_name varchar(100) NOT NULL,
         guild_id varchar(50) NOT NULL,
         permissions text,
         last_updated datetime DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY  (id),
         UNIQUE KEY rank_guild (rank_id, guild_id)
    		) " . (string) $wpdb->get_charset_collate() . ';';

		// Ensure the path to upgrade.php is correct.
		$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';
		if ( file_exists( $upgrade_file ) ) {
			require_once $upgrade_file;
			dbDelta( $sql );
		} else {
			error_log( 'GW2 Guild Login: upgrade.php not found at ' . $upgrade_file );
		}
	}

	/**
	 * Add admin menu
	 */
	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'GW2 Guild Settings', 'gw2-guild-login' ),
			__( 'GW2 Guild', 'gw2-guild-login' ),
			'manage_options',
			'gw2-guild-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page
	 */
	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission for rank-role mappings.
		$rank_roles_raw = filter_input( INPUT_POST, 'gw2_rank_roles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( is_array( $rank_roles_raw ) ) {
			$mappings = array_map(
				function ( $item ) {
						return is_string( $item ) ? sanitize_text_field( $item ) : '';
				},
				$rank_roles_raw
			);
			update_option( 'gw2_rank_role_map', $mappings );
			add_settings_error( 'gw2_messages', 'gw2_message', __( 'Rank mappings saved.', 'gw2-guild-login' ), 'updated' );
		}

		// Fetch saved general settings.
		$general   = get_option( 'gw2gl_settings', array() );
		$guild_ids = is_array( $general ) && isset( $general['guild_ids'] ) ? array_filter( array_map( 'trim', explode( ',', $general['guild_ids'] ) ) ) : array();
		$guild_id  = isset( $guild_ids[0] ) ? $guild_ids[0] : '';

		// Fetch guild ranks from API.
		$data  = $this->fetch_guild_data( (string) $guild_id );
		$ranks = is_array( $data ) && isset( $data['ranks'] ) && is_array( $data['ranks'] ) ? $data['ranks'] : array();

		// Get available WP roles.
		if ( function_exists( 'get_editable_roles' ) ) {
			$roles = get_editable_roles();
		} else {
			global $wp_roles;
			$roles = isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ? $wp_roles->roles : array();
		}

		// Load existing mappings.
		$saved_map = get_option( 'gw2_rank_role_map', array() );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Guild Rank to Role Mapping', 'gw2-guild-login' ); ?></h1>
			<?php settings_errors( 'gw2_messages' ); ?>
			<form method="post">
				<?php wp_nonce_field( 'gw2_rank_settings' ); ?>
				<table class="form-table">
					<?php foreach ( $ranks as $rank ) : ?>
						<?php
						if ( is_array( $rank ) && isset( $rank['name'] ) ) :
							$name = (string) $rank['name'];
							?>
						<tr>
							<th scope="row"><?php echo esc_html( $name ); ?></th>
							<td>
								<select name="gw2_rank_roles[<?php echo esc_attr( $name ); ?>]">
									<?php foreach ( $roles as $slug => $info ) : ?>
										<option value="<?php echo esc_attr( is_string( $slug ) ? $slug : '' ); ?>" <?php selected( isset( $saved_map[ $name ] ) ? $saved_map[ $name ] : '', $slug ); ?>>
											<?php echo esc_html( isset( $info['name'] ) && is_string( $info['name'] ) ? $info['name'] : '' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</table>
				<?php submit_button( __( 'Save Rank Mappings', 'gw2-guild-login' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Fetch guild data from GW2 API.
	 *
	 * @param string $guild_id Guild UUID.
	 * @return array<string, mixed>|\WP_Error Associative array of ranks and members or WP_Error on failure.
	 */
	public function fetch_guild_data( string $guild_id ): array|\WP_Error {
		$api_key_mixed = get_option( 'gw2_api_key' );
		$guild_id_safe = is_string( $guild_id ) ? $guild_id : '';
		$api_key_safe  = is_string( $api_key_mixed ) ? $api_key_mixed : '';
		if ( '' === $api_key_safe || '' === $guild_id_safe ) {
			return new WP_Error( 'missing_api_key', 'GW2 API key is not configured' );
		}
		$ranks_url        = "https://api.guildwars2.com/v2/guild/$guild_id_safe/ranks?access_token=$api_key_safe";
		$members_url      = "https://api.guildwars2.com/v2/guild/$guild_id_safe/members?access_token=$api_key_safe";
		$ranks_response   = wp_remote_get( $ranks_url );
		$members_response = wp_remote_get( $members_url );
		if ( is_wp_error( $ranks_response ) || is_wp_error( $members_response ) ) {
			return new WP_Error( 'api_error', 'Failed to fetch guild data from GW2 API' );
		}
		$ranks_json   = wp_remote_retrieve_body( $ranks_response );
		$members_json = wp_remote_retrieve_body( $members_response );
		$ranks        = is_string( $ranks_json ) ? json_decode( $ranks_json, true ) : array();
		$members      = is_string( $members_json ) ? json_decode( $members_json, true ) : array();
		if ( ! is_array( $ranks ) ) {
			$ranks = array();
		}
		if ( ! is_array( $members ) ) {
			$members = array();
		}
		return array(
			'ranks'     => $ranks,
			'members'   => $members,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Check if a user has the required guild rank.
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $required_rank Required guild rank name.
	 * @return bool True if user has rank, false otherwise.
	 */
	public function check_rank_access( int $user_id, string $required_rank ): bool {
		$guild_id_mixed     = get_user_meta( $user_id, 'gw2_guild_id', true );
		$account_name_mixed = get_user_meta( $user_id, 'gw2_account_name', true );
		$required_rank_safe = is_string( $required_rank ) ? $required_rank : '';
		$guild_id_safe      = is_string( $guild_id_mixed ) ? $guild_id_mixed : '';
		$account_name_safe  = is_string( $account_name_mixed ) ? $account_name_mixed : '';
		if ( '' === $guild_id_safe || '' === $account_name_safe || '' === $required_rank_safe ) {
			return false;
		}
		$cache_key = $this->cache_prefix . $guild_id_safe;
		$data      = get_transient( $cache_key );
		// If no cache or cache is invalid, fetch fresh data.
		if ( false === $data || ! is_array( $data ) || ! isset( $data['members'] ) || ! is_array( $data['members'] ) ) {
			$data = $this->fetch_guild_data( $guild_id_safe );
			if ( is_wp_error( $data ) || ! is_array( $data ) || ! isset( $data['members'] ) || ! is_array( $data['members'] ) ) {
				return false;
			}
			set_transient( $cache_key, $data, $this->cache_expiration );
		}
		// Find the user in members list.
		foreach ( $data['members'] as $member ) {
			if ( is_array( $member ) && isset( $member['name'], $member['rank'] ) && is_string( $member['name'] ) && is_string( $member['rank'] ) ) {
				if ( $account_name_safe === $member['name'] ) {
					return $required_rank_safe === $member['rank'];
				}
			}
		}
		return false;
	}

	/**
	 * Restricted content shortcode
	 *
	 * @param array<string, string> $atts Attributes array with 'rank' and 'message'.
	 * @param string|null           $content The content enclosed by the shortcode.
	 * @return string
	 */
	public function restricted_content_shortcode( array $atts, ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'rank'    => '',
				'message' => esc_html__( 'You do not have permission to view this content.', 'gw2-guild-login' ),
			),
			$atts
		);

		if ( empty( $atts['rank'] ) ) {
			return '<div class="gw2-error">' . esc_html__( 'Error: No rank specified in shortcode.', 'gw2-guild-login' ) . '</div>';
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return '<div class="gw2-login-required">' . esc_html__( 'Please log in to view this content.', 'gw2-guild-login' ) . '</div>';
		}

		if ( $this->check_rank_access( $user_id, $atts['rank'] ) ) {
			// Ensure $content is a string before using do_shortcode.
			$content = is_string( $content ) ? $content : '';
			return do_shortcode( $content );
		} else {
			return '<div class="gw2-access-denied">' . esc_html( $atts['message'] ) . '</div>';
		}
	}
}

// Initialize the plugin.
/**
 * Initialize GW2_Guild_Ranks plugin singleton.
 *
 * @return GW2_Guild_Ranks Plugin instance.
 */
function gw2_guild_ranks_init(): GW2_Guild_Ranks { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	return GW2_Guild_Ranks::instance();
}
add_action( 'plugins_loaded', 'gw2_guild_ranks_init' );
