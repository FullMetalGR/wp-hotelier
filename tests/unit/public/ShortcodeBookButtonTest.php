<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeBookButtonTest extends WH_Public_TestCase {

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
		// WH_Handoff stub: build() returns a URL string from engine + params.
		if ( ! class_exists( 'WH_Handoff' ) ) {
			eval(
				'class WH_Handoff {
					public static function from_rate( $rate ) { return isset( $rate["url"]["engine"] ) ? $rate["url"]["engine"] : ""; }
					public static function build( $engineUrl, $params ) {
						$q = http_build_query( $params );
						return rtrim( $engineUrl, "/" ) . "/?" . $q;
					}
				}'
			);
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

	public function test_builds_handoff_from_property_engine(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn(
			array( 'url' => array( 'engine' => 'https://mg.reserve-online.net/' ) )
		);

		$html = $this->shortcodes( $api )->render_book_button(
			array(
				'checkin'  => '2026-08-01',
				'checkout' => '2026-08-08',
				'adults'   => '4',
				'rate'     => '522770',
				'label'    => 'Reserve now',
			)
		);

		$this->assertStringContainsString( 'wh-book-button', $html );
		$this->assertStringContainsString( 'Reserve now', $html );
		$this->assertStringContainsString( 'mg.reserve-online.net', $html );
		$this->assertStringContainsString( 'rate=522770', $html );
		$this->assertStringContainsString( 'checkin=2026-08-01', $html );
	}

	public function test_newtab_open_mode_adds_target_blank(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->andReturn(
			array( 'url' => array( 'engine' => 'https://mg.reserve-online.net/' ) )
		);
		$html = $this->shortcodes( $api )->render_book_button( array( 'open' => 'newtab' ) );
		$this->assertStringContainsString( 'target="_blank"', $html );
	}
}
