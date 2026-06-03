<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeBookingEngineTest extends WH_Public_TestCase {

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
		if ( ! class_exists( 'WH_Handoff' ) ) {
			eval(
				'class WH_Handoff {
					public static function from_rate( $rate ) { return ""; }
					public static function build( $engineUrl, $params ) { return rtrim( $engineUrl, "/" ) . "/?" . http_build_query( $params ); }
				}'
			);
		}
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c; } public static function accept_language( $s = null ) { return "en_GB"; } }' );
		}
	}

	private function shortcodes( $property ) {
		$apis = new class( $property ) {
			private $p;
			public function __construct( $p ) {
				$this->p = $p; }
			public function property_api() {
				return $this->p; }
		};
		return new WH_Shortcodes( $this->mock_settings(), $apis );
	}

	public function test_renders_iframe_with_engine_src(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->andReturn(
			array( 'url' => array( 'engine' => 'https://mg.reserve-online.net/' ) )
		);

		$html = $this->shortcodes( $api )->render_booking_engine(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '2', 'height' => '900' )
		);
		$this->assertStringContainsString( 'wh-booking-engine', $html );
		$this->assertStringContainsString( '<iframe', $html );
		$this->assertStringContainsString( 'mg.reserve-online.net', $html );
		$this->assertStringContainsString( 'height:900px', $html );
	}
}
