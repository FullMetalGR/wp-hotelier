<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeFlexCalendarTest extends WH_Public_TestCase {

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

	public function test_renders_flexible_stay_options(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'flexible_calendar' )->once()->with( 'DEMO', \Mockery::type( 'array' ) )->andReturn(
			array(
				'currency' => 'EUR',
				'stays'    => array(
					array( 'checkin' => '2026-08-01', 'nights' => 7, 'price' => 3500 ),
					array( 'checkin' => '2026-08-02', 'nights' => 7, 'price' => 3600 ),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_flex_calendar(
			array( 'checkin' => '2026-08-01', 'adults' => '4' )
		);
		$this->assertStringContainsString( 'wh-flex-calendar', $html );
		$this->assertStringContainsString( '2026-08-01', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// loaded WH_I18n produces (real class or per-test eval stub).
		$this->assertStringContainsString( WH_I18n::money( 3500, 'EUR' ), $html );
	}

	public function test_empty_stays_notice(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'flexible_calendar' )->andReturn( array( 'stays' => array() ) );
		$html = $this->shortcodes( $api )->render_flex_calendar( array() );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
