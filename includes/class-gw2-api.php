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
	 * Rate limiting settings
	 *
	 * @var array
	 */
	protected $rate_limit = [
		'requests' => 300, // Requests per window
		'window'   => 60,  // Seconds
	];

	/**
	 * Track rate limiting
	 *
	 * @var array
	 */
	protected $rate_limits = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		// Get cache expiry from options
		$options = get_option( 'gw2gl_settings', array() );
		if ( isset( $options['api_cache_expiry'] ) ) {
			$this->cache_expiry = (int) $options['api_cache_expiry'];
		}

		// Initialize rate limiting
		$this->rate_limits = get_transient( 'gw2gl_rate_limits' ) ?: [];
	}

	/**
	 * Validate an API key and return account info
	 *
	 * @param string $api_key
	 * @return array|WP_Error
	 */
	public function validate_api_key( $api_key ) {
		// Check rate limit first
		$rate_limited = $this->check_rate_limit( 'validate_key' );
		if ( is_wp_error( $rate_limited ) ) {
			return $rate_limited;
		}

		// Sanitize the API key
		$api_key = $this->sanitize_api_key( $api_key );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key format.', 'gw2-guild-login' ) );
		}

		try {
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

		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', __( 'An error occurred while validating the API key.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Check if the account is a member of the target guild
	 *
	 * @param string $api_key
	 * @param string $account_id
	 * @return bool|WP_Error
	 */
	public function is_guild_member( $api_key, $account_id ) {
		// Check rate limit first
		$rate_limited = $this->check_rate_limit( 'guild_check' );
		if ( is_wp_error( $rate_limited ) ) {
			return $rate_limited;
		}

		$options         = get_option( 'gw2gl_settings', array() );
		$target_guild_id = isset( $options['target_guild_id'] ) ? $options['target_guild_id'] : '';

		if ( empty( $target_guild_id ) ) {
			return new WP_Error(
				'no_guild_configured',
				__( 'No target guild has been configured.', 'gw2-guild-login' )
			);
		}

		try {
			// Get account guilds
			$guilds = $this->make_api_request( 'account/guilds', $api_key );

			if ( is_wp_error( $guilds ) ) {
				return $guilds;
			}

			// Check if the account is in the target guild
			return in_array( $target_guild_id, (array) $guilds );

		} catch ( Exception $e ) {
			return new WP_Error( 'guild_check_failed', __( 'Failed to verify guild membership.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Check and enforce rate limiting
	 *
	 * @param string $endpoint
	 * @return bool|WP_Error True if allowed, WP_Error if rate limited
	 */
	protected function check_rate_limit( $endpoint ) {
		$now = time();
		$key = md5( $endpoint . ( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ) );

		// Initialize if not set
		if ( ! isset( $this->rate_limits[ $key ] ) ) {
			$this->rate_limits[ $key ] = [
				'count'   => 0,
				'reset'   => $now + $this->rate_limit['window'],
			];
		}

		// Reset counter if window has passed
		if ( $now > $this->rate_limits[ $key ]['reset'] ) {
			$this->rate_limits[ $key ] = [
				'count'   => 0,
				'reset'   => $now + $this->rate_limit['window'],
			];
		}

		// Check if rate limited
		if ( $this->rate_limits[ $key ]['count'] >= $this->rate_limit['requests'] ) {
			return new WP_Error(
				'rate_limited',
				sprintf(
					__( 'Too many requests. Please try again in %d seconds.', 'gw2-guild-login' ),
					$this->rate_limits[ $key ]['reset'] - $now
				)
			);
		}

		// Increment counter
		$this->rate_limits[ $key ]['count']++;
		set_transient( 'gw2gl_rate_limits', $this->rate_limits, $this->rate_limit['window'] );

		return true;
	}

	/**
	 * Make a request to the GW2 API
	 *
	 * @param string $endpoint
	 * @param string $api_key
	 * @return array|WP_Error
	 */
	protected function make_api_request( $endpoint, $api_key = '' ) {
		try {
			// Build the request URL
			$url = self::API_BASE_URL . ltrim( $endpoint, '/' );

			// Add API key if provided
			$args = array(
				'timeout'   => 30,
				'sslverify' => true,
				'headers'   => array(
					'User-Agent' => 'GW2-Guild-Login/' . GW2_GUILD_LOGIN_VERSION . '; ' . home_url(),
				),
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
				throw new Exception( $response->get_error_message() );
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

				throw new Exception( $error_message, $response_code );
			}

			// Cache the response
			set_transient( $transient_key, $data, $this->cache_expiry );

			return $data;

		} catch ( Exception $e ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					__( 'GW2 API error: %s', 'gw2-guild-login' ),
					$e->getMessage()
				),
				$e->getCode()
			);
		}
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
			return new WP_Error( 'invalid_guild_id', __( 'Invalid guild ID', 'gw2-guild-login' ) );
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
