<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeBarTest extends WH_Public_TestCase {

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

	public function test_renders_bar_price(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'bar' )->once()->with( 'DEMO', \Mockery::type( 'array' ) )->andReturn(
			array(
				'currency' => 'EUR',
				'price'    => 450,
				'url'      => array( 'engine' => 'https://mg.reserve-online.net/' ),
			)
		);

		$html = $this->shortcodes( $api )->render_bar( array() );
		$this->assertStringContainsString( 'wh-bar', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// loaded WH_I18n produces (real class or per-test eval stub).
		$this->assertStringContainsString( WH_I18n::money( 450, 'EUR' ), $html );
		$this->assertStringContainsString( 'mg.reserve-online.net', $html );
	}

	public function test_no_price_renders_empty_notice(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'bar' )->andReturn( array( 'currency' => 'EUR' ) );
		$html = $this->shortcodes( $api )->render_bar( array() );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
