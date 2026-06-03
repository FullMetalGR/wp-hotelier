<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class CacheTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $data ) {
				return json_encode( $data );
			}
		);
	}

	public function test_key_is_prefixed_and_includes_locale(): void {
		$cache = new \WH_Cache();
		$key   = $cache->key( '/property/DEMO', array( 'include' => 'photos' ), 'en_GB' );
		$this->assertStringStartsWith( 'wh:', $key );
		$this->assertStringContainsString( '/property/DEMO', $key );
		$this->assertStringEndsWith( ':en_GB', $key );
	}

	public function test_key_is_order_independent_for_params(): void {
		$cache = new \WH_Cache();
		$a     = $cache->key( '/availability/DEMO', array( 'b' => 2, 'a' => 1 ), 'en_GB' );
		$b     = $cache->key( '/availability/DEMO', array( 'a' => 1, 'b' => 2 ), 'en_GB' );
		$this->assertSame( $a, $b );
	}

	public function test_key_differs_by_locale(): void {
		$cache = new \WH_Cache();
		$en    = $cache->key( '/property/DEMO', array(), 'en_GB' );
		$el    = $cache->key( '/property/DEMO', array(), 'el' );
		$this->assertNotSame( $en, $el );
	}

	public function test_key_differs_by_params(): void {
		$cache = new \WH_Cache();
		$one   = $cache->key( '/availability/DEMO', array( 'checkin' => '2026-08-01' ), 'en_GB' );
		$two   = $cache->key( '/availability/DEMO', array( 'checkin' => '2026-09-01' ), 'en_GB' );
		$this->assertNotSame( $one, $two );
	}

	public function test_get_delegates_to_get_transient(): void {
		Functions\expect( 'get_transient' )->once()->with( 'wh:k' )->andReturn( array( 'x' => 1 ) );
		$cache = new \WH_Cache();
		$this->assertSame( array( 'x' => 1 ), $cache->get( 'wh:k' ) );
	}

	public function test_set_delegates_to_set_transient(): void {
		Functions\expect( 'set_transient' )->once()->with( 'wh:k', array( 'x' => 1 ), 60 )->andReturn( true );
		$cache = new \WH_Cache();
		$cache->set( 'wh:k', array( 'x' => 1 ), 60 );
		$this->assertTrue( true );
	}

	public function test_ttl_for_picks_availability_policy_for_volatile_endpoints(): void {
		$settings = \Mockery::mock( \WH_Settings::class );
		$settings->shouldReceive( 'cache_ttl_availability' )->andReturn( 60 );
		$settings->shouldReceive( 'cache_ttl_content' )->andReturn( 1800 );
		$cache = new \WH_Cache();

		$this->assertSame( 60, $cache->ttl_for( '/availability/DEMO', $settings ) );
		$this->assertSame( 60, $cache->ttl_for( '/availability/DEMO/calendar', $settings ) );
		$this->assertSame( 60, $cache->ttl_for( '/availability/DEMO/flexible-calendar', $settings ) );
		$this->assertSame( 60, $cache->ttl_for( '/bar/DEMO', $settings ) );
	}

	public function test_ttl_for_picks_content_policy_for_stable_endpoints(): void {
		$settings = \Mockery::mock( \WH_Settings::class );
		$settings->shouldReceive( 'cache_ttl_availability' )->andReturn( 60 );
		$settings->shouldReceive( 'cache_ttl_content' )->andReturn( 1800 );
		$cache = new \WH_Cache();

		$this->assertSame( 1800, $cache->ttl_for( '/property/DEMO', $settings ) );
		$this->assertSame( 1800, $cache->ttl_for( '/room/DEMO', $settings ) );
		$this->assertSame( 1800, $cache->ttl_for( '/rate/DEMO', $settings ) );
		$this->assertSame( 1800, $cache->ttl_for( '/extra/DEMO', $settings ) );
		$this->assertSame( 1800, $cache->ttl_for( '/offers/DEMO', $settings ) );
	}
}
