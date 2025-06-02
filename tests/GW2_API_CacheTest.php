<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/class-gw2-api.php';
require_once __DIR__ . '/../includes/class-gw2-api-cache-utils.php';

class GW2_API_CacheTest extends TestCase {
    public function test_cache_and_clear_api_response(): void {
        $api = $this->getMockBuilder('GW2_API')
            ->disableOriginalConstructor()
            ->onlyMethods(['make_api_request'])
            ->getMock();
        // Simulate a cached response
        $endpoint = 'test/endpoint';
        $api_key = 'dummy';
        $user_id = 1;
        $cache_key = 'gw2gl_' . $user_id . '_' . md5($api_key . $endpoint);
        $value = ['foo' => 'bar'];
        set_transient($cache_key, $value, 60);
        $this->assertEquals($value, get_transient($cache_key));
        // Clear cache
        gw2gl_clear_api_cache($endpoint, $api_key, $user_id);
        $this->assertFalse(get_transient($cache_key));
    }
}
