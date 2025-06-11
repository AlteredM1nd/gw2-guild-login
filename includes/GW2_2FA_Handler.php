<?php
/**
 * Two-Factor Authentication Handler
 *
 * @package GW2_Guild_Login
 *
 * @phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 */

declare(strict_types=1);
namespace GW2GuildLogin;

use PragmaRX\Google2FA\Google2FA;

/**
 * Handles Two-Factor Authentication functionality.
 *
 * @package GW2_Guild_Login
 */
class GW2_2FA_Handler {
	/** @var self|null Singleton instance */
	private static $instance = null;

	/** @var string Encryption key */
	private $encryption_key = '';

	/** @var string Secrets table name */
	private $table_secrets;

	/** @var string Trusted devices table name */
	private $table_devices;

	/**
	 * Get the singleton instance
	 *
	 * @param bool $skip_wpdb For unit testing only; skips $wpdb setup.
	 * @return self
	 */
	public static function instance( bool $skip_wpdb = false ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $skip_wpdb );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param bool $skip_wpdb For unit testing only; skips $wpdb setup.
	 */
	private function __construct( bool $skip_wpdb = false ) {
		if ( ! $skip_wpdb ) {
			global $wpdb;
			/** @var \wpdb $wpdb */
			if ( $wpdb instanceof \wpdb ) {
				$this->table_secrets = $wpdb->prefix . 'gw2_2fa_secrets';
				$this->table_devices = $wpdb->prefix . 'gw2_2fa_trusted_devices';
			} else {
				$this->table_secrets = '';
				$this->table_devices = '';
			}
		}
		// Set up encryption key.
		$this->encryption_key = $this->get_encryption_key();
	}

	/**
	 * Check if 2FA is enabled for a user
	 *
	 * @param int $user_id The user ID to check.
	 * @return bool
	 */
	public function is_2fa_enabled( int $user_id ): bool {
		global $wpdb;
		$enabled = is_object( $wpdb ) && method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' )
			? $wpdb->get_var(
				$wpdb->prepare(
					'SELECT is_enabled FROM ' . $this->table_secrets . ' WHERE user_id = %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$user_id
				)
			)
			: null;
		return (bool) $enabled;
	}

	/**
	 * Generate a new TOTP secret
	 *
	 * @return string
	 */
	public function generate_secret(): string {
		$chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret = '';
		for ( $i = 0; $i < 16; $i++ ) {
			$secret .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}
		return $secret;
	}

	/**
	 * Generate backup codes for 2FA
	 *
	 * @param int $count Number of codes to generate (default: 10).
	 * @param int $length Length of each code (default: 8).
	 * @return array<string> Array of generated backup codes.
	 */
	public function generate_backup_codes( int $count = 10, int $length = 8 ): array {
		$codes        = array();
		$chars        = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$chars_length = strlen( $chars );

		for ( $i = 0; $i < $count; $i++ ) {
			$code = '';
			for ( $j = 0; $j < $length; $j++ ) {
				$code .= $chars[ wp_rand( 0, $chars_length - 1 ) ];
			}
			// Format the code with hyphens for better readability (e.g., XXXX-XXXX).
			// Ensure substr parameters are always int.
			$formatted_code = substr( $code, 0, 4 ) . '-' . substr( $code, 4, $length - 4 );
			$codes[]        = $formatted_code;
		}

		return $codes;
	}

	/**
	 * Verify a TOTP code
	 *
	 * @param string $secret The TOTP secret.
	 * @param string $code The code to verify.
	 * @param int    $window Time window in 30-second steps (default: 1).
	 * @return bool|\WP_Error True if valid, false if invalid, WP_Error on failure.
	 */
	public function verify_totp( string $secret, string $code, int $window = 1 ): bool|\WP_Error {
		$google2fa = $this->get_google2fa_instance();
		if ( is_wp_error( $google2fa ) ) {
			return $google2fa;
		}
		if ( ! is_string( $secret ) || ! is_string( $code ) || ! is_int( $window ) ) {
			return false;
		}
		// No need for try-catch, verifyKey does not throw.
		return (bool) $google2fa->verifyKey( $secret, $code, $window );
	}

	/**
	 * Get Google2FA instance
	 *
	 * @return Google2FA|\WP_Error Google2FA instance or WP_Error on failure
	 */
	private function get_google2fa_instance(): Google2FA|\WP_Error {
		if ( ! class_exists( 'PragmaRX\\Google2FA\\Google2FA' ) ) {
			return new \WP_Error(
				'2fa_error',
				__( 'Two-factor authentication library not found. Please install the required dependencies.', 'gw2-guild-login' )
			);
		}
		return new Google2FA();
	}

	/**
	 * Get the QR code URL for setting up an authenticator app
	 *
	 * @param string $secret The TOTP secret.
	 * @param string $username The username.
	 * @param string $issuer The issuer name (default: 'GW2 Guild Login').
	 * @return string QR code URL or empty string on failure.
	 */
	public function get_qr_code_url( string $secret, string $username, string $issuer = 'GW2 Guild Login' ): string {
		// Build the otpauth URI for Google Authenticator.
		$otpauth = sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s',
			rawurlencode( $issuer ),
			rawurlencode( $username ),
			rawurlencode( $secret ),
			rawurlencode( $issuer )
		);
		// Use Google Charts API to generate a QR code.
		$qr_url = sprintf(
			'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=%s',
			rawurlencode( $otpauth )
		);
		return $qr_url;
	}

	/**
	 * Enable 2FA for a user
	 *
	 * @param int           $user_id      The user ID.
	 * @param string        $secret       The 2FA secret.
	 * @param array<string> $backup_codes Array of backup codes.
	 * @return bool|\WP_Error
	 */
	public function enable_2fa( int $user_id, string $secret, array $backup_codes ): bool|\WP_Error {
		global $wpdb;

		// Encrypt the secret before storing.
		$encrypted_secret = $this->encrypt_secret( $secret );
		if ( is_wp_error( $encrypted_secret ) ) {
			return $encrypted_secret;
		}
		$encrypted_codes = $this->encrypt_backup_codes( $backup_codes );
		if ( is_wp_error( $encrypted_codes ) ) {
			return $encrypted_codes;
		}

		// Check if 2FA is already enabled.
		/** @var \wpdb $wpdb */
		if ( ! $wpdb instanceof \wpdb ) {
			return new \WP_Error( 'database_error', 'Database connection not available' );
		}

		// @phpstan-ignore-next-line
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_secrets} WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		$data = array(
			'user_id'      => $user_id,
			'secret'       => $encrypted_secret,
			'backup_codes' => $encrypted_codes,
			'is_enabled'   => 1,
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		if ( $existing && is_object( $existing ) && property_exists( $existing, 'id' ) ) {
			$existing_id = is_numeric( $existing->id ) ? (int) $existing->id : 0;
			$wpdb->update( $this->table_secrets, $data, array( 'id' => $existing_id ) );
		} else {
			$wpdb->insert( $this->table_secrets, $data );
		}

		// Store backup codes in user meta (encrypted).
		$set_result = $this->set_backup_codes_for_user( $user_id, $backup_codes );
		if ( is_wp_error( $set_result ) ) {
			return $set_result;
		}

		return true;
	}

	/**
	 * Disable 2FA for a user
	 *
	 * @param int $user_id The user ID.
	 * @return bool|\WP_Error
	 */
	public function disable_2fa( int $user_id ): bool|\WP_Error {
		global $wpdb;
		/** @var \wpdb $wpdb */
		if ( ! $wpdb instanceof \wpdb ) {
			return new \WP_Error( 'database_error', 'Database connection not available' );
		}

		$result = $wpdb->delete(
			$this->table_secrets,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', 'Failed to disable 2FA' );
		}

		// Clean up related data.
		delete_user_meta( $user_id, 'gw2_2fa_backup_codes' );
		$wpdb->delete(
			$this->table_devices,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Encrypt a secret before storing it in the database
	 *
	 * @param string $secret The secret to encrypt.
	 * @return string|\WP_Error
	 */
	public function encrypt_secret( string $secret ): string|\WP_Error {
		if ( ! extension_loaded( 'openssl' ) ) {
			return new \WP_Error( 'openssl_missing', __( 'OpenSSL PHP extension is not enabled.', 'gw2-guild-login' ) );
		}
		$iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
		if ( ! is_int( $iv_length ) ) {
			return new \WP_Error( 'openssl_iv_length', __( 'Failed to get IV length.', 'gw2-guild-login' ) );
		}
		$iv = openssl_random_pseudo_bytes( $iv_length );
		if ( ! is_string( $iv ) ) {
			return new \WP_Error( 'openssl_iv', __( 'Failed to generate IV.', 'gw2-guild-login' ) );
		}
		$encrypted = openssl_encrypt( $secret, 'AES-256-CBC', $this->encryption_key, 0, $iv );
		if ( ! is_string( $encrypted ) ) {
			return new \WP_Error( 'openssl_encrypt', __( 'Failed to encrypt secret.', 'gw2-guild-login' ) );
		}
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt a secret from the database
	 *
	 * @param string $encrypted_secret The encrypted secret to decrypt.
	 * @return string|false|\WP_Error The decrypted secret, false on failure, or WP_Error if OpenSSL is missing
	 */
	public function decrypt_secret( string $encrypted_secret ): string|false|\WP_Error {
		if ( ! extension_loaded( 'openssl' ) ) {
			return new \WP_Error( 'openssl_missing', __( 'OpenSSL PHP extension is not enabled.', 'gw2-guild-login' ) );
		}
		$decoded = base64_decode( $encrypted_secret, true );
		if ( ! is_string( $decoded ) ) {
			return false;
		}
		$iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
		if ( ! is_int( $iv_length ) ) {
			return false;
		}
		$iv        = substr( $decoded, 0, $iv_length );
		$encrypted = substr( $decoded, $iv_length );
		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $this->encryption_key, 0, $iv );
		if ( ! is_string( $decrypted ) ) {
			return false;
		}
		return $decrypted;
	}

	/**
	 * Encrypt backup codes.
	 *
	 * @param array<string> $backup_codes Backup codes to encrypt.
	 * @return string|\WP_Error Encrypted string or WP_Error on failure.
	 */
	private function encrypt_backup_codes( array $backup_codes ): string|\WP_Error {
		if ( ! extension_loaded( 'openssl' ) ) {
			return new \WP_Error( 'openssl_missing', __( 'OpenSSL PHP extension is not enabled.', 'gw2-guild-login' ) );
		}
		$iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
		if ( ! is_int( $iv_length ) ) {
			return new \WP_Error( 'openssl_iv_length', __( 'Failed to get IV length.', 'gw2-guild-login' ) );
		}
		$iv = openssl_random_pseudo_bytes( $iv_length );
		if ( ! is_string( $iv ) ) {
			return new \WP_Error( 'openssl_iv', __( 'Failed to generate IV.', 'gw2-guild-login' ) );
		}
		$codes_json = wp_json_encode( $backup_codes );
		if ( false === $codes_json ) {
			return new \WP_Error( 'json_encode', __( 'Failed to encode backup codes.', 'gw2-guild-login' ) );
		}
		$encrypted = openssl_encrypt( $codes_json, 'AES-256-CBC', $this->encryption_key, 0, $iv );
		if ( ! is_string( $encrypted ) ) {
			return new \WP_Error( 'openssl_encrypt', __( 'Failed to encrypt backup codes.', 'gw2-guild-login' ) );
		}
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Store encrypted backup codes in user meta
	 *
	 * @param int           $user_id The user ID.
	 * @param array<string> $codes   Array of backup codes.
	 * @return bool|\WP_Error
	 */
	public function set_backup_codes_for_user( int $user_id, array $codes ): bool|\WP_Error {
		if ( $user_id <= 0 || empty( $codes ) ) {
			return new \WP_Error( '2fa_backup_codes_error', __( 'Invalid user ID or codes.', 'gw2-guild-login' ) );
		}

		$encrypted = $this->encrypt_backup_codes( $codes );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}

		update_user_meta( $user_id, 'gw2_2fa_backup_codes', $encrypted );
		return true;
	}

	/**
	 * Retrieve and decrypt backup codes from user meta.
	 *
	 * @param int $user_id User ID.
	 * @return array<string> Array of backup codes.
	 */
	public function get_backup_codes_for_user( int $user_id ): array {
		$encrypted_meta = get_user_meta( $user_id, 'gw2_2fa_backup_codes', true );
		$encrypted_str  = is_string( $encrypted_meta ) ? $encrypted_meta : '';
		if ( '' === $encrypted_str ) {
			return array();
		}

		$decrypted = $this->decrypt_secret( $encrypted_str );
		if ( is_wp_error( $decrypted ) || ! is_string( $decrypted ) || '' === $decrypted ) {
			return array();
		}

		$codes = json_decode( $decrypted, true );
		if ( ! is_array( $codes ) ) {
			return array();
		}
		// Ensure array<string> with explicit closures for PHPStan.
		return array_values(
			array_filter(
				array_map(
					function ( $v ): string {
					return (string) $v;
						},
					$codes
				),
				function ( $v ): bool {
				return is_string( $v ) && strlen( $v ) > 0;
				}
			)
		);
	}

	/**
	 * Get the encryption key (32 bytes for AES-256-CBC).
	 *
	 * @return string Encryption key.
	 */
	private function get_encryption_key(): string {
		// Allow override via constant, else use stored option or generate a new key.
		$raw_value = defined( 'GW2GL_ENCRYPTION_KEY' )
			? GW2GL_ENCRYPTION_KEY
			: get_option( 'gw2gl_encryption_key', '' );

		$raw = is_string( $raw_value ) ? $raw_value : '';

		if ( empty( $raw ) ) {
			// Generate a secure random key and persist it.
			$raw = wp_generate_password( 32, false, false );
			update_option( 'gw2gl_encryption_key', $raw );
		}

		// Derive a 32-byte key via SHA-256.
		return substr( hash( 'sha256', $raw ), 0, 32 );
	}
}
