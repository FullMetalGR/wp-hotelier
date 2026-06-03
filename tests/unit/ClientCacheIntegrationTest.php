<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;
use WH\Tests\Unit\Fakes\FakeHttp;

/**
 * Integration test for the cache layer wired into WH_Client::get().
 *
 * Uses a real WH_Cache backed by an in-memory transient store (get_transient /
 * set_transient stubs) and a request-counting FakeHttp to prove that an
 * identical GET is served from cache on the second call (transport runs once),
 * while POST is never cached.
 */
final class ClientCacheIntegrationTest extends WH_UnitTestCase {

	/** @var array<string,mixed> In-memory transient store. */
	private $store = array();

	protected function setUp(): void {
		parent::setUp();
		$this->store = array();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $data ) {
				return json_encode( $data );
			}
		);
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_user'               => 'user',
				'api_pass'               => 'pass',
				'api_base'               => 'https://rest.reserve-online.net',
				'locale'                 => 'en_GB',
				'cache_ttl_content'      => 1800,
				'cache_ttl_availability' => 60,
			)
		);

		// Real transient-backed store.
		Functions\when( 'get_transient' )->alias(
			function ( $key ) {
				return array_key_exists( $key, $this->store ) ? $this->store[ $key ] : false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $ttl = 0 ) {
				$this->store[ $key ] = $value;
				return true;
			}
		);
	}

	private function make_client( FakeHttp $http ) {
		$settings = new \WH_Settings();
		$cache    = new \WH_Cache();
		return new \WH_Client( $settings, $http, $cache );
	}

	public function test_identical_get_is_cached_after_first_transport_call(): void {
		$envelope = json_encode(
			array(
				'error_code' => 'OK',
				'http_code'  => 200,
				'data'       => array( 'name' => 'Demo Hotel' ),
			)
		);
		// Only ONE response is queued; a second transport call would yield code 0.
		$http   = ( new FakeHttp() )->queue( 200, $envelope );
		$client = $this->make_client( $http );

		// No cache_ttl supplied: the client must derive it from WH_Cache::ttl_for().
		$first  = $client->get( '/property/DEMO', array( 'include' => 'photos' ) );
		$second = $client->get( '/property/DEMO', array( 'include' => 'photos' ) );

		$this->assertSame( array( 'name' => 'Demo Hotel' ), $first );
		$this->assertSame( array( 'name' => 'Demo Hotel' ), $second );
		// Transport ran exactly once; the second call was served from cache.
		$this->assertCount( 1, $http->requests );
	}

	public function test_different_params_miss_cache_and_hit_transport_again(): void {
		$ok = static function ( $name ) {
			return json_encode(
				array(
					'error_code' => 'OK',
					'http_code'  => 200,
					'data'       => array( 'name' => $name ),
				)
			);
		};
		$http = ( new FakeHttp() )->queue( 200, $ok( 'A' ) )->queue( 200, $ok( 'B' ) );
		$client = $this->make_client( $http );

		$client->get( '/property/DEMO', array( 'include' => 'photos' ) );
		$client->get( '/property/DEMO', array( 'include' => 'rooms' ) );

		// Distinct param sets => distinct cache keys => two transport calls.
		$this->assertCount( 2, $http->requests );
	}

	public function test_post_is_never_cached(): void {
		$envelope = json_encode(
			array(
				'error_code' => 'OK',
				'http_code'  => 200,
				'data'       => array( 'res_id' => 'R1' ),
			)
		);
		$http   = ( new FakeHttp() )->queue( 200, $envelope )->queue( 200, $envelope );
		$client = $this->make_client( $http );

		$client->post( '/book/DEMO', array( 'rate' => '1' ) );
		$client->post( '/book/DEMO', array( 'rate' => '1' ) );

		// Two POSTs => two transport calls; nothing was written to the store.
		$this->assertCount( 2, $http->requests );
		$this->assertSame( array(), $this->store );
	}
}
