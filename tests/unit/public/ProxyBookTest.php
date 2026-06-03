<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';

use Brain\Monkey\Functions;

final class ProxyBookTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php as
		// ( $thing instanceof WP_Error ); the error() factory returns a real
		// WP_Error, recognised without Brain Monkey aliasing.
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	private function req( array $params ) {
		$req = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_json_params' )->andReturn( $params )->byDefault();
		$req->shouldReceive( 'get_param' )->andReturnUsing(
			static function ( $k ) use ( $params ) {
				return array_key_exists( $k, $params ) ? $params[ $k ] : '';
			}
		);
		return $req;
	}

	private function proxy( $settings, $bookings ) {
		$apis = new class( $bookings ) {
			private $b;
			public function __construct( $b ) {
				$this->b = $b; }
			public function bookings_api() {
				return $this->b; }
		};
		return new WH_Proxy( $settings, $apis );
	}

	public function test_book_disabled_in_hosted_mode(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'hosted' ) );
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldNotReceive( 'create' );

		$proxy = $this->proxy( $settings, $bookings );
		$resp  = $proxy->route_book( $this->req( array( 'rate' => '522770', 'payment_method' => 'CHKIN' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_book_disabled', $resp->get_error_code() );
	}

	public function test_book_rejects_card_fields(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'native_noncard' ) );
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldNotReceive( 'create' );

		$proxy = $this->proxy( $settings, $bookings );
		$resp  = $proxy->route_book(
			$this->req(
				array(
					'rate'           => '522770',
					'payment_method' => 'CHKIN',
					'card_number'    => '4111111111111111',
				)
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_card_rejected', $resp->get_error_code() );
	}

	public function test_book_rejects_invalid_payment_method(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'native_noncard' ) );
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldNotReceive( 'create' );

		$proxy = $this->proxy( $settings, $bookings );
		$resp  = $proxy->route_book( $this->req( array( 'rate' => '522770', 'payment_method' => 'CARD' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $resp );
		$this->assertSame( 'wh_invalid_payment', $resp->get_error_code() );
	}

	public function test_book_success_calls_create_with_chkin(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'native_noncard', 'default_property' => 'DEMO' ) );

		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'create' )
			->once()
			->with(
				'DEMO',
				\Mockery::on( static function ( $p ) {
					return 'CHKIN' === $p['payment_method']
						&& '522770' === $p['rate']
						&& ! isset( $p['card_number'] );
				} )
			)
			->andReturn( array( 'res_id' => 'R123', 'summaryUrl' => 'https://x/summary' ) );

		$proxy = $this->proxy( $settings, $bookings );
		$resp  = $proxy->route_book(
			$this->req(
				array(
					'rate'           => '522770',
					'payment_method' => 'CHKIN',
					'checkin'        => '2026-08-01',
					'checkout'       => '2026-08-08',
					'firstname'      => 'Jane',
					'lastname'       => 'Doe',
					'email'          => 'jane@example.com',
				)
			)
		);

		$this->assertSame( 'R123', $resp['data']['res_id'] );
	}
}
