<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-booking-flow.php';

final class BookingFlowCompleteTest extends WH_Public_TestCase {

	// is_wp_error() is already defined in tests/unit/wp-stubs.php as
	// `$thing instanceof WP_Error`, so it is NOT aliased here (Patchwork
	// DefinedTooEarly).

	private function flow( $settings, $availability = null, $bookings = null ) {
		$apis = new class( $availability, $bookings ) {
			private $a;
			private $b;
			public function __construct( $a, $b ) {
				$this->a = $a;
				$this->b = $b; }
			public function availability_api() {
				return $this->a; }
			public function bookings_api() {
				return $this->b; }
		};
		return new WH_Booking_Flow( $settings, $apis );
	}

	public function test_hosted_mode_returns_handoff_directive(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'hosted', 'engine_open' => 'redirect', 'default_property' => 'DEMO' ) );

		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->once()->andReturn(
			array(
				'rates' => array(
					array(
						'id'      => '522770',
						'url'     => array( 'engine' => 'https://mg.reserve-online.net/?rate=522770' ),
						'pricing' => array( 'price' => 3500 ),
					),
				),
			)
		);

		$flow   = $this->flow( $settings, $availability );
		$result = $flow->complete(
			array(
				'checkin'  => '2026-08-01',
				'checkout' => '2026-08-08',
				'rate'     => '522770',
				'adults'   => 4,
			)
		);

		$this->assertSame( 'handoff', $result['action'] );
		$this->assertSame( 'redirect', $result['open'] );
		$this->assertSame( 'https://mg.reserve-online.net/?rate=522770', $result['url'] );
	}

	public function test_native_mode_posts_book_and_returns_confirmation(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'native_noncard', 'default_property' => 'DEMO' ) );

		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->andReturn(
			array(
				'rates' => array(
					array( 'id' => '522770', 'pricing' => array( 'price' => 3500 ), 'url' => array( 'engine' => 'x' ) ),
				),
			)
		);

		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'create' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $p ) {
				return 'CHKIN' === $p['payment_method'] && '522770' === $p['rate'] && 3500 == $p['price'];
			} )
		)->andReturn( array( 'res_id' => 'R999', 'summaryUrl' => 'https://x/summary' ) );

		$flow   = $this->flow( $settings, $availability, $bookings );
		$result = $flow->complete(
			array(
				'checkin'        => '2026-08-01',
				'checkout'       => '2026-08-08',
				'rate'           => '522770',
				'adults'         => 4,
				'payment_method' => 'CHKIN',
				'firstname'      => 'Jane',
				'lastname'       => 'Doe',
				'email'          => 'jane@example.com',
			)
		);

		$this->assertSame( 'confirmation', $result['action'] );
		$this->assertSame( 'R999', $result['res_id'] );
		$this->assertSame( 'https://x/summary', $result['summaryUrl'] );
	}

	public function test_native_mode_price_changed_returns_friendly_error(): void {
		$settings = $this->mock_settings( array( 'completion_mode' => 'native_noncard', 'default_property' => 'DEMO' ) );

		$availability = \Mockery::mock( 'WH_Availability_API' );
		// Live price differs from the echoed price -> guard trips before any /book call.
		$availability->shouldReceive( 'single' )->andReturn(
			array(
				'rates' => array(
					array( 'id' => '522770', 'pricing' => array( 'price' => 4000 ), 'url' => array( 'engine' => 'x' ) ),
				),
			)
		);

		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldNotReceive( 'create' );

		$flow   = $this->flow( $settings, $availability, $bookings );
		$result = $flow->complete(
			array(
				'checkin'        => '2026-08-01',
				'checkout'       => '2026-08-08',
				'rate'           => '522770',
				'adults'         => 4,
				'payment_method' => 'CHKIN',
				'price'          => 3500,
			)
		);

		$this->assertSame( 'error', $result['action'] );
		$this->assertSame( 'INVALID_PRICE', $result['code'] );
	}

	public function test_native_mode_rejects_card_payment_method(): void {
		$settings     = $this->mock_settings( array( 'completion_mode' => 'native_noncard' ) );
		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->andReturn(
			array( 'rates' => array( array( 'id' => '522770', 'pricing' => array( 'price' => 3500 ), 'url' => array( 'engine' => 'x' ) ) ) )
		);
		$bookings = \Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldNotReceive( 'create' );

		$flow   = $this->flow( $settings, $availability, $bookings );
		$result = $flow->complete(
			array(
				'checkin'        => '2026-08-01',
				'checkout'       => '2026-08-08',
				'rate'           => '522770',
				'payment_method' => 'CARD',
			)
		);
		$this->assertSame( 'error', $result['action'] );
		$this->assertSame( 'wh_invalid_payment', $result['code'] );
	}

	public function test_no_longer_available_rate_returns_friendly_error(): void {
		$settings     = $this->mock_settings( array( 'completion_mode' => 'hosted', 'default_property' => 'DEMO' ) );
		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->andReturn( array( 'rates' => array() ) );

		$flow   = $this->flow( $settings, $availability );
		$result = $flow->complete(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'rate' => '522770' )
		);
		$this->assertSame( 'error', $result['action'] );
		$this->assertSame( 'NO_AVAILABILITY', $result['code'] );
	}
}
