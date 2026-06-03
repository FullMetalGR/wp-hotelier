<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';

use Brain\Monkey\Functions;

final class ProxyAvailabilityTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php (instanceof
		// WP_Error); WP_Error_Stub extends that double so the guard recognises
		// it without aliasing (which Patchwork forbids — DefinedTooEarly).
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	public function test_single_mode_calls_single_with_default_property(): void {
		$settings = $this->mock_settings( array( 'mode' => 'single', 'default_property' => 'DEMO' ) );

		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )
			->once()
			->with( 'DEMO', \Mockery::on( static function ( $params ) {
				return isset( $params['checkin'] ) && '2026-08-01' === $params['checkin']
					&& isset( $params['adults'] ) && 4 === $params['adults'];
			} ) )
			->andReturn( array( 'code' => 'DEMO', 'rates' => array() ) );

		$apis = new class( $availability ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};

		$proxy = new WH_Proxy( $settings, $apis );

		$req = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_param' )->with( 'property' )->andReturn( '' );
		$req->shouldReceive( 'get_param' )->with( 'checkin' )->andReturn( '2026-08-01' );
		$req->shouldReceive( 'get_param' )->with( 'checkout' )->andReturn( '2026-08-08' );
		$req->shouldReceive( 'get_param' )->with( 'adults' )->andReturn( 4 );
		$req->shouldReceive( 'get_param' )->with( 'children' )->andReturn( 0 );
		$req->shouldReceive( 'get_param' )->with( 'rooms' )->andReturn( 0 );
		$req->shouldReceive( 'get_param' )->with( 'voucher' )->andReturn( '' );

		$resp = $proxy->route_availability( $req );

		$this->assertSame( array( 'code' => 'DEMO', 'rates' => array() ), $resp['data'] );
	}

	public function test_wp_error_returned_with_status_data(): void {
		$settings = $this->mock_settings();

		$err = new \WP_Error_Stub();

		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->andReturn( $err );

		$apis = new class( $availability ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};

		$proxy = new WH_Proxy( $settings, $apis );

		$req = \Mockery::mock( 'WP_REST_Request' );
		foreach ( array( 'property', 'checkin', 'checkout', 'voucher' ) as $p ) {
			$req->shouldReceive( 'get_param' )->with( $p )->andReturn( '' );
		}
		foreach ( array( 'adults', 'children', 'rooms' ) as $p ) {
			$req->shouldReceive( 'get_param' )->with( $p )->andReturn( 0 );
		}

		$resp = $proxy->route_availability( $req );
		$this->assertInstanceOf( \WP_Error_Stub::class, $resp );
		// respond() keeps in-range (400-599) statuses; the stub reports 422.
		$this->assertSame( 422, $err->added_status );
	}
}

/**
 * Minimal WP_Error stand-in for proxy tests.
 *
 * Extends the wp-stubs WP_Error double so the pre-defined is_wp_error()
 * (instanceof WP_Error) recognises it without Brain Monkey aliasing.
 */
class WP_Error_Stub extends \WP_Error {
	public $added_status = 0;
	public function get_error_data( $code = '' ) {
		// Mirror the real envelope produced by WH_Errors::to_wp_error() /
		// WH_Client: error data is an array keyed by 'http_code' (plus 'raw').
		return array( 'http_code' => 422, 'raw' => array( 'error_code' => 'wh_unknown' ) ); }
	public function add_data( $data, $code = '' ) {
		$this->added_status = isset( $data['status'] ) ? $data['status'] : 0; }
	public function get_error_message() {
		return 'err'; }
}
