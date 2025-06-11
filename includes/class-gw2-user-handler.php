<?php
/**
 * GW2 User Handler.
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

/**
 * Handles user-related functionality.
 */
class GW2_User_Handler {
	/**
	 * GW2 API instance
	 *
	 * @var GW2_API
	 */
	protected GW2_API $api;

	/**
	 * Encryption key for API keys
	 *
	 * @var string
	 */
	protected string $encryption_key;

	/**
	 * Constructor
	 *
	 * @param GW2_API $api The GW2 API instance.
	 */
	public function __construct( GW2_API $api ) {
		$this->api = $api;
		$this->setup_encryption_key();
	}

	/**
	 * Set up encryption key for API keys.
	 *
	 * @return void
	 */
	protected function setup_encryption_key(): void {
		// Use WP salts if available, otherwise generate a new one.
		$key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		if ( empty( $key ) && function_exists( 'wp_generate_password' ) ) {
			$key = wp_generate_password( 64, true, true );
		}
		$this->encryption_key = is_string( $key ) ? $key : '';
	}

	/**
	 * Encrypt sensitive data
	 *
	 * @param string $data The data to encrypt.
	 * @return string|false The encrypted data or false on failure.
	 */
	protected function encrypt( string $data ): string|false {
		if ( empty( $this->encryption_key ) ) {
			return false;
		}

		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		if ( ! is_int( $iv_length ) ) {
			return false;
		}
		$iv        = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $data, 'aes-256-cbc', $this->encryption_key, 0, $iv );
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt data
	 *
	 * @param string $data The data to decrypt.
	 * @return string|false The decrypted data or false on failure.
	 */
	protected function decrypt( string $data ): string|false {
		if ( empty( $this->encryption_key ) ) {
			return false;
		}

		$decoded = base64_decode( $data );
		if ( false === $decoded ) {
			return false;
		}

		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		if ( ! is_int( $iv_length ) ) {
			return false;
		}

		$iv        = substr( $decoded, 0, $iv_length );
		$encrypted = substr( $decoded, $iv_length );
		return openssl_decrypt( $encrypted, 'aes-256-cbc', $this->encryption_key, 0, $iv );
	}

	/**
	 * Decrypt API key for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string|false Decrypted API key or false on failure.
	 */
	public function decrypt_api_key( int $user_id ): string|false {
		$encrypted_key = get_user_meta( $user_id, 'gw2_api_key', true );
		if ( ! is_string( $encrypted_key ) || empty( $encrypted_key ) ) {
			return false;
		}

		return $this->decrypt( $encrypted_key );
	}

	/**
	 * Process user login with GW2 API key
	 *
	 * @param string $api_key The API key to process.
	 * @param bool   $remember Whether to remember the user.
	 * @return array{user_id: int, account_name: string, is_new_user: bool}|\WP_Error The login result or error.
	 */
	public function process_login( string $api_key, bool $remember = false ): array|\WP_Error {
		// Brute-force protection.
		$ip            = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$opt_name      = 'gw2gl_failed_attempts_' . md5( $ip );
		$attempt_mixed = get_option(
			$opt_name,
			array(
				'count'         => 0,
				'time'          => 0,
				'blocked_until' => 0,
			)
		);
		$attempt       = is_array( $attempt_mixed ) ? $attempt_mixed : array(
			'count'         => 0,
			'time'          => 0,
			'blocked_until' => 0,
		);
		$now           = time();

		// If blocked.
		$blocked_until = isset( $attempt['blocked_until'] ) && is_int( $attempt['blocked_until'] ) ? $attempt['blocked_until'] : 0;
		if ( $blocked_until > 0 && $now < $blocked_until ) {
			$this->log( 'Brute-force block: ' . $ip, $attempt );
			return new WP_Error( 'login_blocked', __( 'Too many failed login attempts. Try again later.', 'gw2-guild-login' ) );
		}

		// Reset if window expired.
		$attempt_time = isset( $attempt['time'] ) && is_int( $attempt['time'] ) ? $attempt['time'] : 0;
		if ( $now - $attempt_time > 900 ) { // 15 min.
			$attempt = array(
				'count'         => 0,
				'time'          => $now,
				'blocked_until' => 0,
			);
		}

		try {
			// Validate API key and get account info.
			$account_info = $this->api->validate_api_key( $api_key );

			if ( is_wp_error( $account_info ) ) {
				// Failed: increment.
				$attempt_count = isset( $attempt['count'] ) && is_int( $attempt['count'] ) ? $attempt['count'] : 0;
				++$attempt_count;
				$attempt['count'] = $attempt_count;
				$attempt['time']  = $now;
				if ( $attempt_count >= 5 ) {
					$attempt['blocked_until'] = $now + 600; // Block 10 min.
					$this->log( 'Brute-force lockout: ' . $ip, $attempt );
				}
				update_option( $opt_name, $attempt );
				return new WP_Error( 'login_failed', __( 'Login failed. Please try again later.', 'gw2-guild-login' ) );
			}

			// Success: reset counter.
			if ( isset( $attempt['count'] ) && $attempt['count'] > 0 ) {
				delete_option( $opt_name );
			}

			// Log the API validation.
			$account_name = isset( $account_info['name'] ) && is_string( $account_info['name'] ) ? $account_info['name'] : '';
			$this->log( sprintf( 'API validation successful for account: %s', $account_name ) );

			// Check guild membership if required.
			$options         = get_option( 'gw2gl_settings', array() );
			$options         = is_array( $options ) ? $options : array();
			$target_guild_id = isset( $options['target_guild_id'] ) && is_string( $options['target_guild_id'] ) ? $options['target_guild_id'] : '';

			if ( ! empty( $target_guild_id ) && isset( $account_info['id'] ) ) {
				$account_id = is_string( $account_info['id'] ) ? $account_info['id'] : '';
				$is_member  = $this->api->is_guild_member( $api_key, $account_id );

				if ( is_wp_error( $is_member ) ) {
					return $is_member;
				}

				if ( ! $is_member ) {
					$error = new WP_Error(
						'not_guild_member',
						__( 'Your account is not a member of the required guild.', 'gw2-guild-login' )
					);
					$this->log( 'Guild membership check failed', $error );
					return $error;
				}
			}

			// Find or create user.
			$account_id = isset( $account_info['id'] ) && is_string( $account_info['id'] ) ? $account_info['id'] : '';
			$user       = $this->find_or_create_user( $account_info, $api_key );

			if ( is_wp_error( $user ) ) {
				$this->log( 'Failed to find or create user', $user );
				return $user;
			}

			// Log the user in.
			$login_result = $this->login_user( $user, $remember );

			if ( is_wp_error( $login_result ) ) {
				$this->log( 'Login failed', $login_result );
				return $login_result;
			}

			// Update user meta.
			$user_id       = $user->ID;
			$update_result = $this->update_user_meta( $user_id, $account_info, $api_key );

			if ( is_wp_error( $update_result ) ) {
				$this->log( 'Failed to update user meta', $update_result );
				// Continue anyway as this is not a critical error.
			}

			// Log successful login.
			$user_login   = $user->user_login;
			$just_created = isset( $user->just_created ) ? (bool) $user->just_created : false;
			$account_name = isset( $account_info['name'] ) && is_string( $account_info['name'] ) ? $account_info['name'] : '';
			$this->log( sprintf( 'User logged in successfully: %s (ID: %d)', $user_login, $user_id ) );

			return array(
				'user_id'      => $user_id,
				'account_name' => $account_name,
				'is_new_user'  => $just_created,
			);
		} catch ( Exception $e ) {
			$this->log( 'Login exception: ' . $e->getMessage() );
			return new WP_Error( 'login_exception', __( 'An error occurred during login.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Get user by GW2 account ID
	 *
	 * @param string $account_id The GW2 account ID.
	 * @return WP_User|WP_Error The user object or error.
	 */
	public function get_user_by_account_id( string $account_id ): \WP_User|\WP_Error {
		// Use direct DB query for better performance.
		global $wpdb;
		/** @phpstan-ignore-next-line */
		/** @phpstan-ignore-next-line */
		/** @phpstan-ignore-next-line */
		$user_id_mixed = $wpdb->get_var(
		/** @phpstan-ignore-next-line */
			$wpdb->prepare(
			/** @phpstan-ignore-next-line */
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'gw2_account_id' AND meta_value = %s LIMIT 1",
				sanitize_text_field( $account_id )
			)
		);
		/** @phpstan-ignore-next-line */
		/** @phpstan-ignore-next-line */
		/** @phpstan-ignore-next-line */
		$user_id = is_int( $user_id_mixed ) ? $user_id_mixed : ( is_string( $user_id_mixed ) && ctype_digit( $user_id_mixed ) ? (int) $user_id_mixed : 0 );
		if ( 0 === $user_id ) {
			return new \WP_Error( 'user_not_found', __( 'No user found for the given GW2 account ID.', 'gw2-guild-login' ) );
		}
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'user_not_found', __( 'No user found for the given GW2 account ID.', 'gw2-guild-login' ) );
		}
		return $user;
	}

	/**
	 * Migrate legacy API keys (no-op placeholder)
	 *
	 * @return void
	 */
	public static function maybe_migrate_api_keys(): void {
		// Legacy migration logic could go here.
	}

	/**
	 * Check if encryption key is weak or missing
	 *
	 * @return bool
	 */
	public static function is_encryption_key_weak(): bool {
		// Always return false for now; implement real check if needed.
		return false;
	}

	/**
	 * Logs a message with optional context.
	 *
	 * @param string                                $message The message to log.
	 * @param \WP_Error|array<mixed>|Exception|null $context Additional context for the log message.
	 * @return void
	 */
	protected function log( string $message, $context = null ): void {
		// Implementation assumed to exist elsewhere.
		$context_str = '';
		if ( is_string( $context ) ) {
			$context_str = $context;
		} elseif ( is_array( $context ) ) {
			$context_str = wp_json_encode( $context );
		} elseif ( $context instanceof \WP_Error ) {
			$context_str = $context->get_error_message();
		} elseif ( $context instanceof \Exception ) {
			$context_str = $context->getMessage();
		}
		// The context string is used for logging purposes.
	}

	/**
	 * Finds or creates a user based on account info.
	 *
	 * @param array<string, mixed> $account_info The GW2 account information.
	 * @param string               $api_key      The API key for the account.
	 * @return \WP_User|\WP_Error
	 */
	protected function find_or_create_user( array $account_info, string $api_key ): \WP_User|\WP_Error {
		// Implementation assumed to exist elsewhere.
		return new \WP_Error( 'not_implemented', 'Method not implemented' );
	}

	/**
	 * Updates user meta data.
	 *
	 * @param int                  $user_id      The user ID.
	 * @param array<string, mixed> $account_info The GW2 account information.
	 * @param string               $api_key      The API key for the account.
	 * @return \WP_Error|null
	 */
	protected function update_user_meta( int $user_id, array $account_info, string $api_key ): ?\WP_Error {
		// Implementation assumed to exist elsewhere.
		return null;
	}

	/**
	 * Log a user in
	 *
	 * @param \WP_User $user     The user to log in.
	 * @param bool     $remember Whether to remember the user.
	 * @return true|\WP_Error True on success, WP_Error on failure
	 */
	protected function login_user( \WP_User $user, bool $remember = false ): true|\WP_Error {
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $remember );
		do_action( 'wp_login', $user->user_login, $user );
		return true;
	}
}
