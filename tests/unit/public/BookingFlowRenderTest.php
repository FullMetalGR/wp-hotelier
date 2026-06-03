<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';
require_once __DIR__ . '/../../../public/class-wh-booking-flow.php';

use Brain\Monkey\Functions;

final class BookingFlowRenderTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		// is_wp_error() is already defined in tests/unit/wp-stubs.php (returns
		// false for non-WP_Error values), so it is NOT aliased here (Patchwork
		// DefinedTooEarly).
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE123' );
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/book/' );
		Functions\when( 'esc_attr_e' )->alias(
			static function ( $s ) {
				echo $s; }
		);
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c . " " . number_format( (float) $a, 2 ); } public static function accept_language() { return "en_GB"; } }' );
		}
	}

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
		$shortcodes = new WH_Shortcodes( $settings, $apis );
		return new WH_Booking_Flow( $settings, $apis, $shortcodes );
	}

	public function test_search_step_renders_search_form(): void {
		$flow = $this->flow( $this->mock_settings( array( 'results_page' => 0 ) ) );
		$html = $flow->render( array(), array() );
		$this->assertStringContainsString( 'wh-booking-flow', $html );
		$this->assertStringContainsString( 'wh-search-form', $html );
	}

	public function test_results_step_renders_rate_cards(): void {
		$availability = \Mockery::mock( 'WH_Availability_API' );
		$availability->shouldReceive( 'single' )->andReturn(
			array(
				'currency' => 'EUR',
				'rates'    => array(
					array(
						'id'      => '522770',
						'room'    => 'Zen Villa',
						'rate'    => 'Non-refundable',
						'url'     => array( 'engine' => 'https://mg.reserve-online.net/?rate=522770' ),
						'pricing' => array( 'price' => 3500, 'currency' => 'EUR' ),
					),
				),
			)
		);
		$flow = $this->flow( $this->mock_settings(), $availability );
		$html = $flow->render(
			array( 'wh_step' => 'results', 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '4' ),
			array()
		);
		$this->assertStringContainsString( 'Zen Villa', $html );
	}

	public function test_confirmation_step_renders_resid(): void {
		$flow = $this->flow( $this->mock_settings( array( 'completion_mode' => 'native_noncard' ) ) );
		$html = $flow->render_confirmation(
			array( 'res_id' => 'R999', 'summaryUrl' => 'https://x/summary', 'data' => array() )
		);
		$this->assertStringContainsString( 'wh-flow-confirmation', $html );
		$this->assertStringContainsString( 'R999', $html );
		$this->assertStringContainsString( 'https://x/summary', $html );
	}
}
