<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;
use WH\Tests\Unit\Fakes\FakeHttp;

final class ClientTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Common WP function stubs used by the client and its collaborators.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $data ) {
				return json_encode( $data );
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'absint' )->alias(
			static function ( $v ) {
				return abs( (int) $v );
			}
		);
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_user' => 'user',
				'api_pass' => 'pass',
				'api_base' => 'https://rest.reserve-online.net',
				'locale'   => 'en_GB',
			)
		);
	}

	private function make_client( FakeHttp $http, $cache = null ) {
		$settings = new \WH_Settings();
		if ( null === $cache ) {
			$cache = \Mockery::mock( \WH_Cache::class );
			$cache->shouldReceive( 'get' )->andReturn( false )->byDefault();
			$cache->shouldReceive( 'set' )->andReturnNull()->byDefault();
			$cache->shouldReceive( 'key' )->andReturn( 'wh:test:hash:en_GB' )->byDefault();
		}
		return new \WH_Client( $settings, $http, $cache );
	}

	public function test_get_builds_basic_auth_and_accept_headers(): void {
		$http = ( new FakeHttp() )->queue(
			200,
			json_encode(
				array(
					'request_id' => 'r1',
					'error_code' => 'OK',
					'http_code'  => 200,
					'data'       => array( 'ok' => true ),
				)
			)
		);
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/property/DEMO' );

		$this->assertSame( array( 'ok' => true ), $result );
		$headers = $http->last_request['headers'];
		$this->assertSame( 'Basic ' . base64_encode( 'user:pass' ), $headers['Authorization'] );
		$this->assertSame( 'application/json', $headers['Accept'] );
		$this->assertSame( 'en_GB', $headers['Accept-Language'] );
		$this->assertSame( 'GET', $http->last_request['method'] );
		$this->assertSame( 'https://rest.reserve-online.net/property/DEMO', $http->last_request['url'] );
		$this->assertNull( $http->last_request['body'] );
	}

	public function test_get_appends_args_as_query_string(): void {
		$http   = ( new FakeHttp() )->queue( 200, json_encode( array( 'error_code' => 'OK', 'data' => array() ) ) );
		$client = $this->make_client( $http );
		$client->request( 'GET', '/availability/DEMO', array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08' ) );

		$url = $http->last_request['url'];
		$this->assertStringContainsString( '/availability/DEMO?', $url );
		$this->assertStringContainsString( 'checkin=2026-08-01', $url );
		$this->assertStringContainsString( 'checkout=2026-08-08', $url );
	}

	public function test_post_sends_urlencoded_body(): void {
		$http   = ( new FakeHttp() )->queue( 200, json_encode( array( 'error_code' => 'OK', 'data' => array( 'res_id' => 'R123' ) ) ) );
		$client = $this->make_client( $http );
		$result = $client->request( 'POST', '/book/DEMO', array( 'rate' => '522770', 'adults' => 4 ) );

		$this->assertSame( array( 'res_id' => 'R123' ), $result );
		$this->assertSame( 'POST', $http->last_request['method'] );
		$this->assertSame( 'https://rest.reserve-online.net/book/DEMO', $http->last_request['url'] );
		$this->assertSame( 'application/x-www-form-urlencoded', $http->last_request['headers']['Content-Type'] );
		$this->assertSame( 'rate=522770&adults=4', $http->last_request['body'] );
	}

	public function test_success_returns_data_for_ok(): void {
		$http   = ( new FakeHttp() )->queue( 200, json_encode( array( 'error_code' => 'OK', 'data' => array( 'name' => 'Demo Hotel' ) ) ) );
		$client = $this->make_client( $http );
		$this->assertSame( array( 'name' => 'Demo Hotel' ), $client->request( 'GET', '/property/DEMO' ) );
	}

	public function test_no_availability_is_a_data_bearing_success(): void {
		$http   = ( new FakeHttp() )->queue(
			200,
			json_encode(
				array(
					'error_code' => 'NO_AVAILABILITY',
					'http_code'  => 200,
					'data'       => array( 'code' => 'DEMO', 'rates' => array() ),
				)
			)
		);
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/availability/DEMO' );
		$this->assertIsArray( $result );
		$this->assertSame( 'DEMO', $result['code'] );
		$this->assertSame( array(), $result['rates'] );
	}

	public function test_no_hotels_found_is_a_data_bearing_success(): void {
		$http   = ( new FakeHttp() )->queue(
			200,
			json_encode(
				array(
					'error_code' => 'NO_HOTELS_FOUND',
					'http_code'  => 200,
					'data'       => array( 'results' => array() ),
				)
			)
		);
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/availability' );
		$this->assertSame( array( 'results' => array() ), $result );
	}

	public function test_api_error_code_returns_wp_error(): void {
		$http   = ( new FakeHttp() )->queue(
			403,
			json_encode(
				array(
					'error_code' => 'INVALID_AUTH',
					'error_msg'  => 'This method is only accessible with a multi-property API account',
					'http_code'  => 403,
				)
			)
		);
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/property' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'INVALID_AUTH', $result->get_error_code() );
		$data = $result->get_error_data();
		$this->assertSame( 403, $data['http_code'] );
	}

	public function test_non_2xx_without_error_code_returns_wp_error(): void {
		$http   = ( new FakeHttp() )->queue( 500, json_encode( array( 'error_code' => 'OK' ) ) );
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/property/DEMO' );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_invalid_json_returns_transport_wp_error(): void {
		$http   = ( new FakeHttp() )->queue( 200, 'not-json-at-all' );
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/property/DEMO' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wh_transport', $result->get_error_code() );
	}

	public function test_zero_http_code_returns_transport_wp_error(): void {
		$http   = ( new FakeHttp() )->queue( 0, '' );
		$client = $this->make_client( $http );
		$result = $client->request( 'GET', '/property/DEMO' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wh_transport', $result->get_error_code() );
	}

	public function test_get_consults_cache_when_ttl_set_and_returns_cached(): void {
		$cache = \Mockery::mock( \WH_Cache::class );
		$cache->shouldReceive( 'key' )->once()->andReturn( 'wh:prop:hash:en_GB' );
		$cache->shouldReceive( 'get' )->once()->with( 'wh:prop:hash:en_GB' )->andReturn( array( 'cached' => true ) );
		$cache->shouldReceive( 'set' )->never();

		$http   = new FakeHttp(); // No response queued; must NOT be called.
		$client = $this->make_client( $http, $cache );
		$result = $client->get( '/property/DEMO', array(), array( 'cache_ttl' => 1800 ) );

		$this->assertSame( array( 'cached' => true ), $result );
		$this->assertNull( $http->last_request );
	}

	public function test_get_stores_in_cache_on_miss(): void {
		$cache = \Mockery::mock( \WH_Cache::class );
		$cache->shouldReceive( 'key' )->andReturn( 'wh:prop:hash:en_GB' );
		$cache->shouldReceive( 'get' )->once()->andReturn( false );
		$cache->shouldReceive( 'set' )->once()->with( 'wh:prop:hash:en_GB', array( 'name' => 'Demo Hotel' ), 1800 );

		$http   = ( new FakeHttp() )->queue( 200, json_encode( array( 'error_code' => 'OK', 'data' => array( 'name' => 'Demo Hotel' ) ) ) );
		$client = $this->make_client( $http, $cache );
		$result = $client->get( '/property/DEMO', array(), array( 'cache_ttl' => 1800 ) );

		$this->assertSame( array( 'name' => 'Demo Hotel' ), $result );
	}

	public function test_post_wrapper_never_caches(): void {
		$cache = \Mockery::mock( \WH_Cache::class );
		$cache->shouldReceive( 'get' )->never();
		$cache->shouldReceive( 'set' )->never();
		$cache->shouldReceive( 'key' )->andReturn( 'x' )->byDefault();

		$http   = ( new FakeHttp() )->queue( 200, json_encode( array( 'error_code' => 'OK', 'data' => array( 'res_id' => 'R1' ) ) ) );
		$client = $this->make_client( $http, $cache );
		$result = $client->post( '/book/DEMO', array( 'rate' => '1' ) );

		$this->assertSame( array( 'res_id' => 'R1' ), $result );
	}
}
