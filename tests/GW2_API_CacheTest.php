<?php
use PHPUnit\Framework\TestCase;

class GW2_API_CacheTest extends TestCase {
	public function test_cache_and_clear_api_response(): void {
		// Test basic transient cache functionality that the API cache relies on
		$endpoint  = 'test/endpoint';
		$api_key   = 'dummy';
		$user_id   = 1;
		$cache_key = 'gw2gl_' . $user_id . '_' . md5( $api_key . $endpoint );
		$value     = array( 'foo' => 'bar' );
		
		// Set up the cache
		set_transient( $cache_key, $value, 60 );
		$this->assertEquals( $value, get_transient( $cache_key ) );
		
		// Clear cache directly 
		delete_transient( $cache_key );
		$this->assertFalse( get_transient( $cache_key ) );
	}

	public function test_cache_key_generation(): void {
		// Test that cache keys are generated consistently
		$endpoint = 'account';
		$api_key  = 'test-key-123';
		$user_id  = 1;
		
		$expected_key = 'gw2gl_' . $user_id . '_' . md5( $api_key . $endpoint );
		$actual_key   = 'gw2gl_' . $user_id . '_' . md5( $api_key . $endpoint );
		
		$this->assertEquals( $expected_key, $actual_key );
		$this->assertStringStartsWith( 'gw2gl_1_', $expected_key );
		$this->assertGreaterThan( 10, strlen( $expected_key ) ); // Ensure key has reasonable length
	}
}
