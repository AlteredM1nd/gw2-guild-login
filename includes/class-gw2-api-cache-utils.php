<?php
/**
 * GW2 API Cache Utilities
 * Provides helpers to clear or inspect API cache for debugging and admin tools.
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GW2GL;

/**
 * Class GW2_API_Cache_Utils
 * Provides utilities for managing the GW2 API cache.
 */
class GW2_API_Cache_Utils {
	/**
	 * Cache directory path
	 *
	 * @var string
	 */
	private string $cache_dir;

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private int $cache_expiration;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cache_dir        = WP_CONTENT_DIR . '/gw2-api-cache';
		$this->cache_expiration = 3600; // 1 hour
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Cached data or null if not found/expired.
	 */
	public function get_cache( string $key ) {
		$cache_file = $this->get_cache_file_path( $key );

		if ( ! file_exists( $cache_file ) ) {
			return null;
		}

		$data = file_get_contents( $cache_file );
		if ( false === $data ) {
			return null;
		}

		$cache = json_decode( $data, true );
		if ( ! is_array( $cache ) ) {
			return null;
		}

		if ( isset( $cache['expires'] ) && $cache['expires'] < time() ) {
			$this->delete_cache( $key );
			return null;
		}

		return $cache['data'] ?? null;
	}

	/**
	 * Set cache data
	 *
	 * @param string   $key        Cache key.
	 * @param mixed    $data       Data to cache.
	 * @param int|null $expiration Optional custom expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function set_cache( string $key, $data, ?int $expiration = null ): bool {
		$cache_file = $this->get_cache_file_path( $key );
		$expiration = $expiration ?? $this->cache_expiration;

		$cache = array(
			'data'    => $data,
			'expires' => time() + $expiration,
		);

		if ( ! is_dir( $this->cache_dir ) ) {
			if ( ! wp_mkdir_p( $this->cache_dir ) ) {
				return false;
			}
		}

		return file_put_contents( $cache_file, json_encode( $cache ) ) !== false;
	}

	/**
	 * Delete cache
	 *
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete_cache( string $key ): bool {
		$cache_file = $this->get_cache_file_path( $key );

		if ( file_exists( $cache_file ) ) {
			wp_delete_file( $cache_file );
			// @phpstan-ignore-next-line booleanNot.alwaysFalse
			return ! file_exists( $cache_file );
		}

		// File doesn't exist, consider deletion successful.
		return true;
	}

	/**
	 * Clear all cache
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache(): bool {
		if ( ! is_dir( $this->cache_dir ) ) {
			return true;
		}

		$files = glob( $this->cache_dir . '/*' );
		if ( false === $files ) {
			return false;
		}

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}

		return true;
	}

	/**
	 * Get cache file path
	 *
	 * @param string $key Cache key.
	 * @return string Cache file path.
	 */
	private function get_cache_file_path( string $key ): string {
		return $this->cache_dir . '/' . md5( $key ) . '.json';
	}

	/**
	 * Clear the cached response for a given endpoint+api_key
	 *
	 * @param string   $endpoint The API endpoint to clear cache for.
	 * @param string   $api_key  The API key used for the request.
	 * @param int|null $user_id  Optional user ID, defaults to current user.
	 * @return void
	 */
	public static function clear_api_cache( string $endpoint, string $api_key, ?int $user_id = null ): void {
		// PHPStan guarantees $endpoint and $api_key are strings, $user_id is int|null.
		$endpoint_safe = $endpoint;
		$api_key_safe  = $api_key;
		$user_id_safe  = null !== $user_id ? $user_id : get_current_user_id();
		$url           = \GW2_API::API_BASE_URL . ltrim( $endpoint_safe, '/' );
		$cache_key     = 'gw2gl_' . $user_id_safe . '_' . md5( $api_key_safe . $endpoint_safe );
		delete_transient( $cache_key );
	}
}

// For backward compatibility.
if ( ! function_exists( 'gw2gl_clear_api_cache' ) ) {
	/**
	 * Clear the cached response for a given endpoint+api_key
	 *
	 * @param string   $endpoint The API endpoint.
	 * @param string   $api_key  The API key.
	 * @param int|null $user_id  The user ID.
	 * @return void
	 */
	function gw2gl_clear_api_cache( string $endpoint, string $api_key, ?int $user_id = null ): void { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
		GW2_API_Cache_Utils::clear_api_cache( $endpoint, $api_key, $user_id );
	}
}
