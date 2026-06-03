<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeAvailabilityTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php and cannot be
		// Brain-Monkey aliased (Patchwork DefinedTooEarly); it already returns
		// ( $thing instanceof WP_Error ), which is the behaviour we need.
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c . " " . number_format( (float) $a, 2 ); } public static function accept_language() { return "en_GB"; } }' );
		}
	}

	private function shortcodes( $availability ) {
		$apis = new class( $availability ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};
		return new WH_Shortcodes( $this->mock_settings(), $apis );
	}

	private function sample_data() {
		return array(
			'code'     => 'DEMO',
			'name'     => 'Demo Hotel',
			'currency' => 'EUR',
			'url'      => array( 'engine' => 'https://mg.reserve-online.net/' ),
			'location' => array( 'lat' => 37.4, 'lon' => 25.3 ),
			'rates'    => array(
				array(
					'id'                  => '522770',
					'type'                => 'ZEN',
					'room'                => 'Zen Villa',
					'rate'                => 'Non-refundable',
					'rate_desc'           => '<p>Best price</p>',
					'board'               => 'Room only',
					'remaining'           => 2,
					'min_stay'            => 3,
					'cancellation_policy' => '<p>No cancellation</p>',
					'url'                 => array(
						'engine' => 'https://mg.reserve-online.net/?checkin=2026-08-01&checkout=2026-08-08&party=%5B%7B%22adults%22%3A4%7D%5D&lang=en_GB&rate=522770',
						'photo'  => 'https://cdn.webhotelier.net/photos/zen/1.jpg',
					),
					'pricing'             => array( 'price' => 3500, 'currency' => 'EUR' ),
					'status'              => 'available',
				),
			),
		);
	}

	public function test_renders_rate_card_with_engine_handoff(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'single' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $p ) {
				return '2026-08-01' === $p['checkin'] && 4 === $p['adults'];
			} )
		)->andReturn( $this->sample_data() );

		$html = $this->shortcodes( $api )->render_availability(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '4' )
		);

		$this->assertStringContainsString( 'wh-availability', $html );
		$this->assertStringContainsString( 'Zen Villa', $html );
		$this->assertStringContainsString( 'Non-refundable', $html );
		$this->assertStringContainsString( 'rate=522770', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// active formatter produces (eval stub in isolation, real class in the
		// full suite) so the test is stable under classmap autoloading.
		$this->assertStringContainsString( WH_I18n::money( 3500, 'EUR' ), $html );
		// Verified handoff: the Book link IS rate.url.engine.
		$this->assertStringContainsString( 'mg.reserve-online.net/?checkin=2026-08-01', $html );
	}

	public function test_no_availability_shows_friendly_message(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'single' )->andReturn(
			array( 'code' => 'DEMO', 'currency' => 'EUR', 'rates' => array() )
		);

		$html = $this->shortcodes( $api )->render_availability(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '2' )
		);
		$this->assertStringContainsString( 'wh-availability__empty', $html );
	}

	public function test_room_attr_filters_rates(): void {
		$data            = $this->sample_data();
		$data['rates'][] = array(
			'id'      => '999',
			'type'    => 'BOHEM',
			'room'    => 'Bohem Villa',
			'rate'    => 'Flexible',
			'url'     => array( 'engine' => 'https://mg.reserve-online.net/?rate=999' ),
			'pricing' => array( 'price' => 100, 'currency' => 'EUR' ),
		);
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'single' )->andReturn( $data );

		$html = $this->shortcodes( $api )->render_availability(
			array( 'room' => 'ZEN', 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '4' )
		);
		$this->assertStringContainsString( 'Zen Villa', $html );
		$this->assertStringNotContainsString( 'Bohem Villa', $html );
	}
}
