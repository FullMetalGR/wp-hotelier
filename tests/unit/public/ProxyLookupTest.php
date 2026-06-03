<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';

use Brain\Monkey\Functions;

final class ProxyLookupTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php as
		// ( $thing instanceof WP_Error ); the error() factory and the upstream
		// WP_Error are recognised without Brain Monkey aliasing.
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	private function req( array $params ) {
		$req = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_param' )->andReturnUsing(
			static function ( $k ) use ( $params ) {
				return array_key_exists( $k, $params ) ? $params[ $k ] : '';
			}
		);
		return $req;
	}

	private function proxy( $bookings ) {
		$apis = new class( $bookings ) {
			private $b;
			public function __construct( $b ) {
				$this->b = $b; }
			public function bookings_api() {
				return $this->b; }
		};
		return new WH_Proxy( $this->mock_settings(), $apis );
	}

	public function test_missing_resid_returns_error(): void {
		$proxy = $this->proxy( \Mockery::mock( 'WH_Bookings_API' ) );
		$resp  = $proxy->route_lookup( $this->req( array( 'email' => 'a@b.com' ) ) );
		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_missing_param', $resp->get_error_code() );
	}

	public function test_missing_identity_returns_error(): void {
		$proxy = $this->proxy( \Mockery::mock( 'WH_Bookings_API' ) );
		$resp  = $proxy->route_lookup( $this->req( array( 'res_id' => 'R1' ) ) );
		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_missing_param', $resp->get_error_code() );
	}

	public function test_email_mismatch_returns_not_found_not_data(): void {
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )->once()->with( 'R1' )->andReturn(
			array(
				'res_id'   => 'R1',
				'email'    => 'owner@example.com',
				'lastname' => 'Doe',
			)
		);

		$proxy = $this->proxy( $bookings );
		$resp  = $proxy->route_lookup( $this->req( array( 'res_id' => 'R1', 'email' => 'attacker@evil.com' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_not_found', $resp->get_error_code() );
	}

	public function test_email_match_returns_filtered_data(): void {
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )->once()->with( 'R1' )->andReturn(
			array(
				'res_id'   => 'R1',
				'email'    => 'owner@example.com',
				'lastname' => 'Doe',
				'rate'     => 'Deluxe',
			)
		);

		$proxy = $this->proxy( $bookings );
		$resp  = $proxy->route_lookup( $this->req( array( 'res_id' => 'R1', 'email' => 'OWNER@example.com' ) ) );

		$this->assertSame( 'R1', $resp['data']['res_id'] );
		$this->assertSame( 'Deluxe', $resp['data']['rate'] );
	}

	public function test_lastname_match_case_insensitive(): void {
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )->once()->with( 'R1' )->andReturn(
			array(
				'res_id'   => 'R1',
				'email'    => 'owner@example.com',
				'lastname' => 'Doe',
			)
		);

		$proxy = $this->proxy( $bookings );
		$resp  = $proxy->route_lookup( $this->req( array( 'res_id' => 'R1', 'lastName' => 'doe' ) ) );

		$this->assertSame( 'R1', $resp['data']['res_id'] );
	}

	public function test_upstream_error_passed_through(): void {
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )->once()->andReturn( new \WP_Error( 'NOT_FOUND', 'Not found' ) );

		$proxy = $this->proxy( $bookings );
		$resp  = $proxy->route_lookup( $this->req( array( 'res_id' => 'R1', 'email' => 'a@b.com' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_not_found', $resp->get_error_code() );
	}
}
