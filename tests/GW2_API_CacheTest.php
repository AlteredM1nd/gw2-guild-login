<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/class-gw2-api.php';
require_once __DIR__ . '/../includes/class-gw2-api-cache-utils.php';

class GW2_API_CacheTest extends TestCase {
    public function test_cache_and_clear_api_response() {
        $api = $this->getMockBuilder('GW2_API')
            ->disableOriginalConstructor()
            ->onlyMethods(['make_api_request'])
            ->getMock();
        // Simulate a cached response
        $endpoint = 'test/endpoint';
        $api_key = 'dummy';
        $url = GW2_API::API_BASE_URL . ltrim($endpoint, '/');
        $transient_key = 'gw2gl_' . md5($url . $api_key);
        $value = ['foo' => 'bar'];
        set_transient($transient_key, $value, 60);
        $this->assertEquals($value, get_transient($transient_key));
        // Clear cache
        gw2gl_clear_api_cache($endpoint, $api_key);
        $this->assertFalse(get_transient($transient_key));
    }
}
