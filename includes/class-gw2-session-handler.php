<?php
/**
 * Handles session management for the plugin
 */
class GW2_Session_Handler {
    /**
     * Initialize session handling
     */
    public static function init() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start( [
                'cookie_httponly' => true,
                'cookie_secure'   => is_ssl(),
                'cookie_samesite' => 'Lax',
            ] );
        }
    }

    /**
     * Set a session variable
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set( $key, $value ) {
        self::init();
        $_SESSION['gw2_guild_login'][ $key ] = $value;
    }

    /**
     * Get a session variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get( $key, $default = null ) {
        self::init();
        return $_SESSION['gw2_guild_login'][ $key ] ?? $default;
    }

    /**
     * Remove a session variable
     * 
     * @param string $key
     */
    public static function remove( $key ) {
        self::init();
        unset( $_SESSION['gw2_guild_login'][ $key ] );
    }

    /**
     * Clear all session data
     */
    public static function clear() {
        self::init();
        unset( $_SESSION['gw2_guild_login'] );
    }

    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        self::init();
        session_regenerate_id( true );
    }
}

// Initialize session early
add_action( 'plugins_loaded', [ 'GW2_Session_Handler', 'init' ], 1 );
