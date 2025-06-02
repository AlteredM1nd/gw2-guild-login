<?php
/**
 * GW2 API Cache Utilities
 * Provides helpers to clear or inspect API cache for debugging and admin tools.
 */
if ( ! function_exists( 'gw2gl_clear_api_cache' ) ) {
    /**
     * Clear the cached response for a given endpoint+api_key
     * @param string $endpoint
     * @param string $api_key
     * @param int $user_id
     */
    function gw2gl_clear_api_cache( $endpoint, $api_key, $user_id = null ) {
        $url = GW2_API::API_BASE_URL . ltrim( $endpoint, '/' );
        $user_id = isset($user_id) ? $user_id : get_current_user_id();
        $cache_key = 'gw2gl_' . $user_id . '_' . md5( $api_key . $endpoint );
        delete_transient( $cache_key );
    }
}
