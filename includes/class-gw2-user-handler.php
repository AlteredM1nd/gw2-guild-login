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
		try {
			// Validate API key and get account info
			$account_info = $this->api->validate_api_key( $api_key );

			if ( is_wp_error( $account_info ) ) {
				return $account_info;
			}

			// Log the API validation
			$this->log( sprintf( 'API validation successful for account: %s', $account_info['name'] ) );

			// Check guild membership if required
			$options = get_option( 'gw2gl_settings', array() );
			if ( ! empty( $options['target_guild_id'] ) ) {
				$is_member = $this->api->is_guild_member( $api_key, $account_info['id'] );

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
			$user = $this->find_or_create_user( $account_info, $api_key );

			if ( is_wp_error( $user ) ) {
				$this->log( 'Failed to find or create user', $user );
				return $user;
			}

			// Log the user in
			$login_result = $this->login_user( $user, $remember );

			if ( is_wp_error( $login_result ) ) {
				$this->log( 'Login failed', $login_result );
				return $login_result;
			}

			// Update user meta
			$update_result = $this->update_user_meta( $user->ID, $account_info, $api_key );

			if ( is_wp_error( $update_result ) ) {
				$this->log( 'Failed to update user meta', $update_result );
				// Continue anyway as this is not a critical error
			}

			// Log successful login
			$this->log( sprintf( 'User logged in successfully: %s (ID: %d)', $user->user_login, $user->ID ) );

			return array(
				'user_id'      => $user->ID,
				'account_name' => $account_info['name'],
				'is_new_user'  => ! empty( $user->just_created ),
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
	protected function log( $message, $data = null ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[GW2 Guild Login] ' . $message );
			if ( $data ) {
				error_log( print_r( $data, true ) );
			}
		}
	}

	/**
	 * Find or create a WordPress user for the GW2 account
	 *
	 * @param array  $account_info
	 * @param string $api_key
	 * @return WP_User|WP_Error
	 */
	protected function find_or_create_user( $account_info, $api_key ) {
		try {
			// Try to find an existing user by GW2 account ID
			$user = $this->get_user_by_gw2_account_id( $account_info['id'] );

			// If user not found and auto-registration is enabled, create a new user
			if ( is_wp_error( $user ) ) {
				if ( $user->get_error_code() !== 'user_not_found' ) {
					return $user; // Return other errors
				}

				$options = get_option( 'gw2gl_settings', array() );

				if ( empty( $options['enable_auto_register'] ) ) {
					return new WP_Error(
						'registration_disabled',
						__( 'Auto-registration is disabled. Please contact an administrator.', 'gw2-guild-login' )
					);
				}

				$user = $this->create_user( $account_info, $api_key );

				// Ensure create_user returned a valid user or error
				if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
					return is_wp_error( $user )
						? $user
						: new WP_Error( 'invalid_user_object', __( 'Failed to create user account.', 'gw2-guild-login' ) );
				}

				// Send welcome email if enabled
				if ( ! empty( $options['send_welcome_email'] ) ) {
					// Generate a temporary password for the welcome email
					$temp_password = wp_generate_password( 24, true, true );
					$this->send_welcome_email( $user, $temp_password );
				}
			}

			// Ensure we have a valid user object
			if ( ! ( $user instanceof WP_User ) ) {
				return new WP_Error( 'invalid_user_object', __( 'Invalid user object returned.', 'gw2-guild-login' ) );
			}

			return $user;

		} catch ( Exception $e ) {
			return new WP_Error( 'user_processing_error', __( 'An error occurred while processing the user account.', 'gw2-guild-login' ) );
		}
	}

	/**
	 * Get user by GW2 account ID
	 *
	 * @param string $account_id
	 * @return WP_User|WP_Error
	 */
	protected function get_user_by_gw2_account_id( $account_id ) {
		try {
			if ( empty( $account_id ) ) {
				return new WP_Error(
					'invalid_account_id',
					__( 'Invalid account ID provided.', 'gw2-guild-login' )
				);
			}

			// Use direct DB query for better performance
			global $wpdb;
			$user_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'gw2_account_id' AND meta_value = %s LIMIT 1",
					sanitize_text_field( $account_id )
				)
			);

			if ( ! $user_id ) {
				return new WP_Error(
					'user_not_found',
					__( 'No user found with this GW2 account.', 'gw2-guild-login' )
				);
			}

			$user = get_user_by( 'id', $user_id );

			if ( ! $user || ! ( $user instanceof WP_User ) ) {
				return new WP_Error(
					'user_not_found',
					__( 'User account not found.', 'gw2-guild-login' )
				);
			}

			return $user;

		} catch ( Exception $e ) {
			return new WP_Error(
				'database_error',
				sprintf( __( 'Database error: %s', 'gw2-guild-login' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Create a new WordPress user for a GW2 account
	 *
	 * @param array  $account_info
	 * @param string $api_key
	 * @return WP_User|WP_Error
	 */
	protected function create_user( $account_info, $api_key ) {
		if ( ! is_array( $account_info ) || empty( $account_info['name'] ) || empty( $account_info['id'] ) ) {
			return new WP_Error( 'invalid_account_info', __( 'Invalid account information provided.', 'gw2-guild-login' ) );
		}

		try {
			// Generate a unique username
			$username = $this->generate_username( $account_info['name'] );
			
			// Generate a unique email if not provided
			$email = $this->generate_email( $username );
			
			// Generate a strong password
			$password = wp_generate_password( 24, true, true );

			// Get role from settings
			$options = get_option( 'gw2gl_settings', array() );
			$role    = isset( $options['member_role'] ) ? $options['member_role'] : 'subscriber';

			// Sanitize user data
			$user_data = array(
				'user_login'   => sanitize_user( $username, true ),
				'user_email'   => sanitize_email( $email ),
				'user_pass'    => $password,
				'role'         => $role,
				'display_name' => sanitize_text_field( $account_info['name'] ),
				'first_name'   => sanitize_text_field( $account_info['name'] ),
			);

			// Allow filtering of user data
			$user_data = apply_filters( 'gw2gl_before_user_creation', $user_data, $account_info );

			// Create the user
			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Get the user object
			$user = get_user_by( 'id', $user_id );

			if ( ! $user || ! ( $user instanceof WP_User ) ) {
				return new WP_Error(
					'user_creation_failed',
					__( 'Failed to retrieve the created user.', 'gw2-guild-login' )
				);
			}

			// Add a flag to indicate this is a newly created user
			$user->just_created = true;

			// Store GW2 account info
			try {
				$this->update_user_meta( $user_id, $account_info, $api_key );
			} catch ( Exception $e ) {
				error_log( 'GW2 Guild Login: Failed to update user meta - ' . $e->getMessage() );
				// Continue anyway since the user was created
			}

			// Send welcome email if the user was just created
			try {
				$this->send_welcome_email( $user, $password );
			} catch ( Exception $e ) {
				error_log( 'GW2 Guild Login: Failed to send welcome email to ' . $user->user_email . ' - ' . $e->getMessage() );
				// Continue anyway since this is not a critical error
			}

			return $user;

		} catch ( Exception $e ) {
			error_log( 'GW2 Guild Login: Uncaught exception in create_user - ' . $e->getMessage() );
			return new WP_Error(
				'user_creation_exception',
				__( 'An error occurred while creating your account. Please try again later.', 'gw2-guild-login' ),
				$e->getMessage()
			);
		}
	}

	/**
	 * Update user meta with GW2 account info
	 *
	 * @param int    $user_id
	 * @param array  $account_info
	 * @param string $api_key
	 * @return true|WP_Error True on success, WP_Error on failure
	 */
	protected function update_user_meta( $user_id, $account_info, $api_key ) {
		try {
			// Validate input
			if ( ! is_array( $account_info ) || empty( $account_info['id'] ) || empty( $account_info['name'] ) ) {
				return new WP_Error(
					'invalid_account_info',
					__( 'Invalid account information provided.', 'gw2-guild-login' )
				);
			}

			// Store GW2 account info
			update_user_meta( $user_id, 'gw2_account_id', $account_info['id'] );
			update_user_meta( $user_id, 'gw2_account_name', $account_info['name'] );

			// Store encrypted API key
			if ( ! empty( $api_key ) ) {
				$encrypted_key = $this->encrypt_api_key( $api_key );
				if ( is_wp_error( $encrypted_key ) ) {
					return $encrypted_key;
				}
				update_user_meta( $user_id, 'gw2_api_key', $encrypted_key );
			}

			// Store account creation date
			if ( isset( $account_info['created'] ) ) {
				update_user_meta( $user_id, 'gw2_account_created', $account_info['created'] );
			}

			// Store world/home server if available
			if ( isset( $account_info['world'] ) ) {
				update_user_meta( $user_id, 'gw2_world', $account_info['world'] );
			}

			// Store last login time
			update_user_meta( $user_id, 'gw2_last_login', current_time( 'mysql' ) );

			return true;
		} catch ( Exception $e ) {
			return new WP_Error(
				'update_meta_failed',
				sprintf( __( 'Failed to update user meta: %s', 'gw2-guild-login' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Log in a user
	 *
	 * @param WP_User $user
	 * @param bool $remember Whether to remember the user
	 * @return true|WP_Error True on success, WP_Error on failure
	 */
	protected function login_user( $user, $remember = false ) {
		try {
			// Clear any existing auth cookies
			wp_clear_auth_cookie();

			// Set the current user
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID, $remember );
			do_action( 'wp_login', $user->user_login, $user );

			return true;
		} catch ( Exception $e ) {
			return new WP_Error(
				'login_failed',
				sprintf( __( 'Failed to log in user: %s', 'gw2-guild-login' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Generate a unique username based on GW2 account name
	 *
	 * @param string $account_name
	 * @return string
	 */
	protected function generate_username( $account_name ) {
		$username          = sanitize_user( $account_name, true );
		$original_username = $username;
		$i                 = 1;

		// Ensure username is unique
		while ( username_exists( $username ) ) {
			$username = $original_username . $i;
			++$i;
		}

		return $username;
	}

	/**
	 * Generate a unique email address
	 *
	 * @param string $username
	 * @return string
	 */
	protected function generate_email( $username ) {
		$email = $username . '@' . parse_url( home_url(), PHP_URL_HOST );
		$email = str_replace( 'www.', '', $email );

		// Ensure email is unique
		$original_email = $email;
		$i              = 1;

		while ( email_exists( $email ) ) {
			$email = str_replace( '@', $i . '@', $original_email );
			++$i;
		}

		return $email;
	}

	/**
	 * Encrypt an API key for storage
	 *
	 * @param string $api_key
	 * @return string
	 */
	protected function encrypt_api_key( $api_key ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			// Fallback to basic obfuscation if OpenSSL is not available
			return base64_encode( $api_key );
		}

		$method = 'aes-256-cbc';
		$key    = $this->get_encryption_key();
		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $method ) );

		$encrypted = openssl_encrypt( $api_key, $method, $key, 0, $iv );

		// Return base64-encoded iv + encrypted data
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt an API key
	 *
	 * @param string $encrypted_key
	 * @return string|false
	 */
	public function decrypt_api_key( $encrypted_key ) {
		if ( empty( $encrypted_key ) ) {
			return false;
		}

		if ( ! extension_loaded( 'openssl' ) ) {
			// Fallback for keys encrypted without OpenSSL
			return base64_decode( $encrypted_key );
		}

		$method = 'aes-256-cbc';
		$key    = $this->get_encryption_key();

		$data      = base64_decode( $encrypted_key );
		$iv_length = openssl_cipher_iv_length( $method );

		if ( strlen( $data ) < $iv_length ) {
			return false;
		}

		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );

		return openssl_decrypt( $encrypted, $method, $key, 0, $iv );
	}

	/**
	 * Get the encryption key for API keys
	 *
	 * @return string
	 */
	protected function get_encryption_key() {
		$key = get_option( 'gw2gl_encryption_key' );

		if ( empty( $key ) ) {
			$key = wp_generate_password( 64, true, true );
			update_option( 'gw2gl_encryption_key', $key, false );
		}

		return $key;
	}

	/**
	 * Send welcome email to new users
	 *
	 * @param WP_User $user
	 * @param string  $password
	 */
	protected function send_welcome_email( $user, $password ) {
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$message  = sprintf( __( 'Username: %s', 'gw2-guild-login' ), $user->user_login ) . "\r\n";
		$message .= sprintf( __( 'Password: %s', 'gw2-guild-login' ), $password ) . "\r\n\r\n";
		$message .= __( 'You can log in to your account using the link below:', 'gw2-guild-login' ) . "\r\n";
		$message .= wp_login_url() . "\r\n";

		wp_mail(
			$user->user_email,
			sprintf( __( '[%s] Your account', 'gw2-guild-login' ), $blogname ),
			$message
		);
	}

	/**
	 * Check if the current user is a guild member
	 *
	 * @return bool|WP_Error
	 */
	public function current_user_is_guild_member() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();
		$api_key = $this->get_user_api_key( $user_id );

		if ( empty( $api_key ) ) {
			return false;
		}

		$account_info = $this->api->validate_api_key( $api_key );

		if ( is_wp_error( $account_info ) ) {
			return $account_info;
		}

		return $this->api->is_guild_member( $api_key, $account_info['id'] );
	}

	/**
	 * Get a user's API key
	 *
	 * @param int $user_id
	 * @return string|false
	 */
	public function get_user_api_key( $user_id ) {
		$encrypted_key = get_user_meta( $user_id, 'gw2_api_key', true );

		if ( empty( $encrypted_key ) ) {
			return false;
		}

		return $this->decrypt_api_key( $encrypted_key );
	}
}
