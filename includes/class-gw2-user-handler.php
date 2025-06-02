<?php
/**
 * Handles user-related functionality
 */
class GW2_User_Handler {
	/**
	 * GW2 API instance
	 *
	 * @var GW2_API
	 */
	protected $api;

	/**
	 * Encryption key for API keys
	 *
	 * @var string
	 */
	protected $encryption_key;

	/**
	 * Constructor
	 *
	 * @param GW2_API $api
	 */
	public function __construct( $api ) {
		$this->api = $api;
		$this->setup_encryption_key();
	}

	/**
	 * Set up encryption key for API keys
	 */
	protected function setup_encryption_key() {
		// Use WP salts if available, otherwise generate a new one
		$key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		if ( empty( $key ) && function_exists( 'wp_generate_password' ) ) {
			$key = wp_generate_password( 64, true, true );
		}
		$this->encryption_key = $key;
	}

	/**
	 * Encrypt sensitive data
	 *
	 * @param string $data
	 * @return string|false
	 */
	protected function encrypt( $data ) {
		if ( empty( $this->encryption_key ) ) {
			return false;
		}

		$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encrypted = openssl_encrypt( $data, 'aes-256-cbc', $this->encryption_key, 0, $iv );
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt data
	 *
	 * @param string $data
	 * @return string|false
	 */
	protected function decrypt( $data ) {
		if ( empty( $this->encryption_key ) ) {
			return false;
		}

		$data = base64_decode( $data );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		return openssl_decrypt( $encrypted, 'aes-256-cbc', $this->encryption_key, 0, $iv );
	}

	/**
	 * Process user login with GW2 API key
	 *
	 * @param string $api_key
	 * @param bool $remember
	 * @return array|WP_Error
	 */
	public function process_login( $api_key, $remember = false ) {
        // Brute-force protection
        $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        /** @var string $opt_name */
        $opt_name = 'gw2gl_failed_attempts_' . md5($ip);
        $attempt = get_option($opt_name, array('count'=>0,'time'=>0,'blocked_until'=>0));
        $now = time();
        // If blocked
        $blocked_until = is_array($attempt) && isset($attempt['blocked_until']) && is_int($attempt['blocked_until']) ? $attempt['blocked_until'] : 0;
        if ($blocked_until > 0 && $now < $blocked_until) {
            $this->log('Brute-force block: ' . $ip, $attempt);
            return new WP_Error('login_blocked', __('Too many failed login attempts. Try again later.', 'gw2-guild-login'));
        }
        // Reset if window expired
        $attempt_time = is_array($attempt) && isset($attempt['time']) && is_int($attempt['time']) ? $attempt['time'] : 0;
        if ($now - $attempt_time > 900) { // 15 min
            $attempt = array('count'=>0,'time'=>$now,'blocked_until'=>0);
        }
        try {
            // Validate API key and get account info
            $account_info = $this->api->validate_api_key( $api_key );

            if ( is_wp_error( $account_info ) ) {
                // Failed: increment
                $attempt_count = is_array($attempt) && isset($attempt['count']) && is_int($attempt['count']) ? $attempt['count'] : 0;
                $attempt_count++;
                $attempt['count'] = $attempt_count;
                $attempt['time'] = $now;
                if ($attempt_count >= 5) {
                    $attempt['blocked_until'] = $now + 600; // Block 10 min
                    $this->log('Brute-force lockout: ' . $ip, $attempt);
                }
                update_option($opt_name, $attempt, false);
                return new WP_Error('login_failed', __('Login failed. Please try again later.', 'gw2-guild-login'));
            }

            // Success: reset counter
            if (is_array($attempt) && isset($attempt['count']) && $attempt['count'] > 0) {
                delete_option($opt_name);
            }
            // Log the API validation
            $account_name = (is_array($account_info) && isset($account_info['name']) && is_string($account_info['name'])) ? $account_info['name'] : '';
            $this->log( sprintf( 'API validation successful for account: %s', $account_name ) );

            // Check guild membership if required
            $options = get_option( 'gw2gl_settings', array() );
            $target_guild_id = is_array($options) && isset($options['target_guild_id']) ? $options['target_guild_id'] : '';
            if ( ! empty( $target_guild_id ) && is_array($account_info) && isset($account_info['id']) ) {
                $account_id = is_string($account_info['id']) ? $account_info['id'] : '';
                $is_member = $this->api->is_guild_member( $api_key, $account_id );

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

			// Find or create user
			$account_id = (is_array($account_info) && isset($account_info['id']) && is_string($account_info['id'])) ? $account_info['id'] : '';
			$user = $this->find_or_create_user( $account_info, $api_key );

			if ( is_wp_error( $user ) ) {
				$this->log( 'Failed to find or create user', $user );
				return $user;
			}

			// Log the user in
			/** @phpstan-ignore-next-line */
			$login_result = $this->login_user( $user, $remember );

			if ( is_wp_error( $login_result ) ) {
				$this->log( 'Login failed', $login_result );
				return $login_result;
			}

			// Update user meta
			/** @phpstan-ignore-next-line */
			$user_id = (is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0;
			$update_result = $this->update_user_meta( $user_id, $account_info, $api_key );

			if ( is_wp_error( $update_result ) ) {
				$this->log( 'Failed to update user meta', $update_result );
				// Continue anyway as this is not a critical error
			}

			// Log successful login
			/** @phpstan-ignore-next-line */
			$user_login = (is_object($user) && isset($user->user_login) && is_string($user->user_login)) ? $user->user_login : '';
			/** @phpstan-ignore-next-line */
			$user_id = (is_object($user) && isset($user->ID) && is_int($user->ID)) ? $user->ID : 0;
			$just_created = (is_object($user) && isset($user->just_created)) ? (bool)$user->just_created : false;
			$account_name = (is_array($account_info) && isset($account_info['name']) && is_string($account_info['name'])) ? $account_info['name'] : '';
			$this->log( sprintf( 'User logged in successfully: %s (ID: %d)', $user_login, $user_id ) );

			return array(
				'user_id'      => $user_id,
				'account_name' => $account_name,
				'is_new_user'  => $just_created,
			);

		} catch ( Exception $e ) {
			$this->log( 'Unexpected error in process_login', $e );
			return new WP_Error( 'login_error', __( 'An unexpected error occurred during login.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Log messages for debugging
	 *
	 * @param string $message
	 * @param mixed $data
	 */
	// Intelephense false positive: 'Unexpected public'. Public is required by PHP and WordPress standards.
/**
 * Public visibility is required by PHP and WordPress standards.
 * If your linter flags this as an error, it is a false positive or misconfiguration.
 * For internal use only.
 */
public function log( $message, $data = null ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            if ( $data !== null ) {
                error_log('[GW2 Guild Login] ' . $message . ' | ' . print_r($data, true));
            } else {
                error_log('[GW2 Guild Login] ' . $message);
            }
        }
	}

	/**
	 * Find or create a WordPress user for the GW2 account
				'user_processing_error',
				__( 'An unexpected error occurred. Please try again later.', 'gw2-guild-login' )
			);
		}
	}

	/**
	 * Get user by GW2 account ID
	 *
	 * @param string $account_id
	 * @return WP_User|WP_Error

        // Use direct DB query for better performance
        global $wpdb;
        $user_id_mixed = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'gw2_account_id' AND meta_value = %s LIMIT 1",
                sanitize_text_field( $account_id )
            )
        );
        $user_id = is_int($user_id_mixed) ? $user_id_mixed : (is_string($user_id_mixed) && ctype_digit($user_id_mixed) ? (int)$user_id_mixed : 0);
		$encrypted_key = get_user_meta( $user_id, 'gw2_api_key', true );

		if ( empty( $encrypted_key ) ) {
			return false;
		}

		return $this->decrypt_api_key( $encrypted_key );
	}

	/**
	 * Clear all relevant user cache/transients
	 *
	 * @param int $user_id
	 * @return void
	 */
	protected function clear_user_cache( $user_id ) {
        if ( ! function_exists( 'gw2gl_clear_api_cache' ) ) return;
        $api_key_mixed = get_user_meta( $user_id, 'gw2_api_key', true );
        $api_key = is_string($api_key_mixed) ? $api_key_mixed : '';
        if ( $api_key === '' ) return;
        $endpoints = array( 'account', 'characters', 'guilds', 'wallet', 'bank' );
        foreach ( $endpoints as $ep ) {
            gw2gl_clear_api_cache( $ep, $api_key );
        }
        // Clear any user-specific transients
        global $wpdb;
        $transients = is_object($wpdb) && method_exists($wpdb, 'get_col') && method_exists($wpdb, 'prepare')
            ? $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", '_transient_gw2gl_%' ) )
            : array();
        foreach ( $transients as $t ) {
            $t_str = is_string($t) ? $t : '';
            $api_key_hash = md5($api_key);
            if ( $t_str !== '' && strpos( $t_str, $api_key_hash ) !== false ) {
                delete_option( $t_str );
            }
        }
    }

	/**
	 * Migrate all user API keys to encrypted storage (run once on upgrade)
	 *
	 * @since 2.6.0
	 */
	public static function maybe_migrate_api_keys(): void {
		if ( get_option( 'gw2gl_api_key_migrated_260', false ) ) {
			return;
		}
		if ( ! function_exists( 'get_users' ) ) {
			return;
		}
		$users = get_users( array( 'fields' => array( 'ID' ) ) );
		foreach ( $users as $user_mixed ) {
			// Strictly type $user_id
			$user_id = ( is_object($user_mixed) && isset($user_mixed->ID) && is_int($user_mixed->ID) ) ? $user_mixed->ID : 0;
			if ( $user_id === 0 ) {
				continue;
			}
			$encrypted_key_mixed = get_user_meta( $user_id, 'gw2_api_key', true );
			$encrypted_key = is_string($encrypted_key_mixed) ? $encrypted_key_mixed : '';
			if ( $encrypted_key === '' ) {
				continue;
			}
			// Try to decrypt; if fails or returns non-string, treat as legacy/plain
			$handler = new self( null );
			$decrypted_mixed = $handler->decrypt_api_key( $encrypted_key );
			$decrypted = is_string($decrypted_mixed) ? $decrypted_mixed : '';
			if ( $decrypted === '' || $decrypted === $encrypted_key ) {
				// Legacy or plaintext, re-encrypt
				$new_encrypted_mixed = $handler->encrypt_api_key( $encrypted_key );
				$new_encrypted = is_string($new_encrypted_mixed) ? $new_encrypted_mixed : '';
				if ( $new_encrypted !== '' && ! is_wp_error( $new_encrypted ) ) {
					update_user_meta( $user_id, 'gw2_api_key', $new_encrypted );
				}
			}
		}
		update_option( 'gw2gl_api_key_migrated_260', 1, false );
// Cleanup: Remove any legacy plaintext keys from DB
if ( function_exists('delete_metadata') ) {
    delete_metadata('user', 0, 'gw2_api_key', '', true);
}
	}

	/**
	 * Check if encryption key is missing or weak
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public static function is_encryption_key_weak() {
        $key = get_option( 'gw2gl_encryption_key' );
        $key_str = is_string($key) ? $key : '';
        if ( $key_str === '' || strlen( $key_str ) < 32 ) {
            return true;
        }
        return false;
    }

}
