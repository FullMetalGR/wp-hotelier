<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';

use Brain\Monkey\Functions;

final class ProxyContentRoutesTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	/** Build a request whose get_param returns the provided map (default ''). */
	private function req( array $params ) {
		$req = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_param' )->andReturnUsing(
			static function ( $key ) use ( $params ) {
				return array_key_exists( $key, $params ) ? $params[ $key ] : '';
			}
		);
		return $req;
	}

	private function proxy_with( $methodName, $mock ) {
		$settings = $this->mock_settings();
		$apis     = new class( $methodName, $mock ) {
			private $name;
			private $mock;
			public function __construct( $name, $mock ) {
				$this->name = $name;
				$this->mock = $mock; }
			public function property_api() {
				return 'property' === $this->name ? $this->mock : null; }
			public function availability_api() {
				return 'availability' === $this->name ? $this->mock : null; }
			public function offers_api() {
				return 'offers' === $this->name ? $this->mock : null; }
		};
		return new WH_Proxy( $settings, $apis );
	}

	public function test_property_route_uses_default_property(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn( array( 'name' => 'Demo Hotel' ) );
		$proxy = $this->proxy_with( 'property', $api );

		$resp = $proxy->route_property( $this->req( array() ) );
		$this->assertSame( array( 'name' => 'Demo Hotel' ), $resp['data'] );
	}

	public function test_rooms_route(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rooms' )->once()->with( 'DEMO' )->andReturn( array( 'rooms' => array() ) );
		$proxy = $this->proxy_with( 'property', $api );

		$resp = $proxy->route_rooms( $this->req( array() ) );
		$this->assertSame( array( 'rooms' => array() ), $resp['data'] );
	}

	public function test_room_route_requires_room_param(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'room' )->once()->with( 'DEMO', 'ZEN' )->andReturn( array( 'room' => 'ZEN' ) );
		$proxy = $this->proxy_with( 'property', $api );

		$resp = $proxy->route_room( $this->req( array( 'room' => 'ZEN' ) ) );
		$this->assertSame( array( 'room' => 'ZEN' ), $resp['data'] );
	}

	public function test_rates_route_optional_room(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rates' )->once()->with( 'DEMO', 'ZEN' )->andReturn( array( 'rates' => array() ) );
		$proxy = $this->proxy_with( 'property', $api );

		$resp = $proxy->route_rates( $this->req( array( 'room' => 'ZEN' ) ) );
		$this->assertSame( array( 'rates' => array() ), $resp['data'] );
	}

	public function test_offers_route(): void {
		$api = \Mockery::mock( 'WH_Offers_API' );
		$api->shouldReceive( 'single' )->once()->with( 'DEMO', array() )->andReturn( array( 'offers' => array() ) );
		$proxy = $this->proxy_with( 'offers', $api );

		$resp = $proxy->route_offers( $this->req( array() ) );
		$this->assertSame( array( 'offers' => array() ), $resp['data'] );
	}

	public function test_calendar_route_forwards_fromd_and_tod_dates(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'calendar' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $params ) {
				return isset( $params['fromd'] ) && '2026-08-01' === $params['fromd']
					&& isset( $params['tod'] ) && '2026-08-31' === $params['tod']
					&& ! isset( $params['month'] ) && ! isset( $params['months'] );
			} )
		)->andReturn( array( 'calendar' => array() ) );
		$proxy = $this->proxy_with( 'availability', $api );

		$resp = $proxy->route_calendar( $this->req( array( 'fromd' => '2026-08-01', 'tod' => '2026-08-31' ) ) );
		$this->assertSame( array( 'calendar' => array() ), $resp['data'] );
	}

	public function test_calendar_route_converts_months_to_fromd_tod(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$captured = null;
		$api->shouldReceive( 'calendar' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $params ) use ( &$captured ) {
				$captured = $params;
				return isset( $params['fromd'], $params['tod'] )
					&& ! isset( $params['month'] ) && ! isset( $params['months'] );
			} )
		)->andReturn( array( 'calendar' => array() ) );
		$proxy = $this->proxy_with( 'availability', $api );

		// months=2 with no explicit dates: fromd=today, tod=today+2 months.
		$proxy->route_calendar( $this->req( array( 'months' => 2 ) ) );

		$this->assertSame( gmdate( 'Y-m-d' ), $captured['fromd'] );
		$this->assertSame( gmdate( 'Y-m-d', strtotime( '+2 months', strtotime( $captured['fromd'] ) ) ), $captured['tod'] );
	}

	public function test_calendar_route_caps_tod_at_three_months(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$captured = null;
		$api->shouldReceive( 'calendar' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $params ) use ( &$captured ) {
				$captured = $params;
				return true;
			} )
		)->andReturn( array( 'calendar' => array() ) );
		$proxy = $this->proxy_with( 'availability', $api );

		// tod is a year out; it must be clamped to fromd + 3 months.
		$proxy->route_calendar( $this->req( array( 'fromd' => '2026-01-01', 'tod' => '2026-12-31' ) ) );

		$this->assertSame( '2026-01-01', $captured['fromd'] );
		$this->assertSame( '2026-04-01', $captured['tod'] );
	}

	public function test_bar_route(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'bar' )->once()->with( 'DEMO', \Mockery::type( 'array' ) )->andReturn( array( 'bar' => array() ) );
		$proxy = $this->proxy_with( 'availability', $api );

		$resp = $proxy->route_bar( $this->req( array() ) );
		$this->assertSame( array( 'bar' => array() ), $resp['data'] );
	}

	public function test_extras_route_requires_rate(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'extras' )->once()->with( 'DEMO', '522770', \Mockery::type( 'array' ) )->andReturn( array( 'extras' => array() ) );
		$proxy = $this->proxy_with( 'availability', $api );

		$resp = $proxy->route_extras( $this->req( array( 'rate' => '522770' ) ) );
		$this->assertSame( array( 'extras' => array() ), $resp['data'] );
	}

	public function test_extras_route_missing_rate_returns_error_response(): void {
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php as
		// ( $thing instanceof WP_Error ); the error() factory returns a real
		// WP_Error, so the guard recognises it without Brain Monkey aliasing
		// (which Patchwork forbids — DefinedTooEarly).
		$api   = \Mockery::mock( 'WH_Availability_API' );
		$proxy = $this->proxy_with( 'availability', $api );

		$resp = $proxy->route_extras( $this->req( array() ) );
		$this->assertInstanceOf( \WP_Error::class, $resp );
	}
}
