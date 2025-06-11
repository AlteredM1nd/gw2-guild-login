<?php
/**
 * GW2 Session Handler.
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

/**
 * Handles session management for the plugin
 */
class GW2_Session_Handler {
	/**
	 * Initialize session handling
	 */
	public static function init(): void {
		if ( ! session_id() && ! headers_sent() ) {
			session_start(
				array(
					'cookie_httponly' => true,
					'cookie_secure'   => is_ssl(),
					'cookie_samesite' => 'Lax',
				)
			);
		}
	}

	/**
	 * Set a session variable
	 *
	 * @param string $key   The session key.
	 * @param mixed  $value The value to store.
	 */
	public static function set( string $key, mixed $value ): void {
		self::init();
		if ( '' === $key ) {
			return;
		}
		if ( ! isset( $_SESSION['gw2_guild_login'] ) || ! is_array( $_SESSION['gw2_guild_login'] ) ) {
			$_SESSION['gw2_guild_login'] = array();
		}
		$_SESSION['gw2_guild_login'][ $key ] = $value;
	}

	/**
	 * Get a session variable
	 *
	 * @param string $key           The session key.
	 * @param mixed  $default_value The default value if key doesn't exist.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default_value = null ): mixed {
		self::init();
		if ( '' === $key ) {
			return $default_value;
		}
		if ( ! isset( $_SESSION['gw2_guild_login'] ) || ! is_array( $_SESSION['gw2_guild_login'] ) ) {
			return $default_value;
		}
		return array_key_exists( $key, $_SESSION['gw2_guild_login'] ) ? $_SESSION['gw2_guild_login'][ $key ] : $default_value; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Remove a session variable
	 *
	 * @param string $key The session key to remove.
	 */
	public static function remove( string $key ): void {
		self::init();
		if ( '' === $key ) {
			return;
		}
		if ( isset( $_SESSION['gw2_guild_login'] ) && is_array( $_SESSION['gw2_guild_login'] ) && array_key_exists( $key, $_SESSION['gw2_guild_login'] ) ) {
			unset( $_SESSION['gw2_guild_login'][ $key ] );
		}
	}

	/**
	 * Clear all session data
	 */
	public static function clear(): void {
		self::init();
		if ( isset( $_SESSION['gw2_guild_login'] ) ) {
			unset( $_SESSION['gw2_guild_login'] );
		}
	}

	/**
	 * Regenerate session ID
	 */
	public static function regenerate(): void {
		self::init();
		session_regenerate_id( true );
	}
}

// Initialize session early.
add_action( 'plugins_loaded', array( 'GW2_Session_Handler', 'init' ), 1 );
