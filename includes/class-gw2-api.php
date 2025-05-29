<?php
/**
 * Handles all GW2 API interactions
 */
class GW2_API {
	/**
	 * Base URL for the GW2 API
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://api.guildwars2.com/v2/';

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	protected $cache_expiry = 3600; // 1 hour

	/**
	 * Constructor
	 */
	public function __construct() {
		// Get cache expiry from options
		$options = get_option( 'gw2gl_settings', array() );
		if ( isset( $options['api_cache_expiry'] ) ) {
			$this->cache_expiry = (int) $options['api_cache_expiry'];
		}
	}

	/**
	 * Validate an API key and return account info
	 *
	 * @param string $api_key
	 * @return array|WP_Error
	 */
	public function validate_api_key( $api_key ) {
		// Sanitize the API key
		$api_key = $this->sanitize_api_key( $api_key );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key format.', 'gw2-guild-login' ) );
		}

		// Check token info first
		$token_info = $this->make_api_request( 'tokeninfo', $api_key );
		if ( is_wp_error( $token_info ) ) {
			return $token_info;
		}

		// Check required permissions
		$required_permissions = array( 'account', 'guilds' );
		$missing_permissions  = array_diff( $required_permissions, $token_info['permissions'] );

		if ( ! empty( $missing_permissions ) ) {
			return new WP_Error(
				'missing_permissions',
				sprintf(
					__( 'API key is missing required permissions: %s', 'gw2-guild-login' ),
					implode( ', ', $missing_permissions )
				)
			);
		}

		// Get account info
		$account_info = $this->make_api_request( 'account', $api_key );
		if ( is_wp_error( $account_info ) ) {
			return $account_info;
		}

		// Add token info to account info
		$account_info['permissions'] = $token_info['permissions'];
		return $account_info;
	}

	/**
	 * Check if the account is a member of the target guild
	 *
	 * @param string $api_key
	 * @param string $account_id
	 * @return bool|WP_Error
	 */
	public function is_guild_member( $api_key, $account_id ) {
		$options         = get_option( 'gw2gl_settings', array() );
		$target_guild_id = isset( $options['target_guild_id'] ) ? $options['target_guild_id'] : '';

		if ( empty( $target_guild_id ) ) {
			return new WP_Error(
				'no_guild_configured',
				__( 'No target guild has been configured.', 'gw2-guild-login' )
			);
		}

		// Get account guilds
		$guilds = $this->make_api_request( 'account/guilds', $api_key );

		if ( is_wp_error( $guilds ) ) {
			return $guilds;
		}

		// Check if the account is in the target guild
		return in_array( $target_guild_id, $guilds );
	}

	/**
	 * Make a request to the GW2 API
	 *
	 * @param string $endpoint
	 * @param string $api_key
	 * @return array|WP_Error
	 */
	protected function make_api_request( $endpoint, $api_key = '' ) {
		// Build the request URL
		$url = self::API_BASE_URL . ltrim( $endpoint, '/' );

		// Add API key if provided
		$args = array(
			'timeout'   => 30,
			'sslverify' => true,
			'headers'   => array(),
		);

		if ( ! empty( $api_key ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
		}

		// Check cache first
		$transient_key   = 'gw2gl_' . md5( $url . $api_key );
		$cached_response = get_transient( $transient_key );

		if ( $cached_response !== false ) {
			return $cached_response;
		}

		// Make the request
		$response = wp_remote_get( $url, $args );

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					__( 'Failed to connect to the GW2 API: %s', 'gw2-guild-login' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		// Check for API errors
		if ( $response_code !== 200 ) {
			$error_message = __( 'Unknown API error', 'gw2-guild-login' );

			if ( ! empty( $data['text'] ) ) {
				$error_message = $data['text'];
			} elseif ( is_string( $data ) ) {
				$error_message = $data;
			}

			return new WP_Error(
				'api_error',
				sprintf(
					__( 'GW2 API error (%1$d): %2$s', 'gw2-guild-login' ),
					$response_code,
					$error_message
				)
			);
		}

		// Cache the response
		set_transient( $transient_key, $data, $this->cache_expiry );

		return $data;
	}

	/**
	 * Sanitize an API key
	 *
	 * @param string $api_key
	 * @return string|false
	 */
	protected function sanitize_api_key( $api_key ) {
		$api_key = trim( $api_key );

		// Check if the API key matches the expected format
		if ( ! preg_match( '/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{20}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/i', $api_key ) ) {
			return false;
		}

		return $api_key;
	}

	/**
	 * Get guild details
	 *
	 * @param string $guild_id
	 * @return array|WP_Error
	 */
	public function get_guild_details( $guild_id ) {
		if ( empty( $guild_id ) ) {
			return new WP_Error( 'missing_guild_id', __( 'Guild ID is required.', 'gw2-guild-login' ) );
		}

		return $this->make_api_request( 'guild/' . urlencode( $guild_id ) );
	}

	/**
	 * Get character names for an account
	 *
	 * @param string $api_key
	 * @return array|WP_Error
	 */
	public function get_character_names( $api_key ) {
		return $this->make_api_request( 'characters', $api_key );
	}
}
