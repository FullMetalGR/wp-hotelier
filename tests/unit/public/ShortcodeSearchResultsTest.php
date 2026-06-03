<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeSearchResultsTest extends WH_Public_TestCase {

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

	private function shortcodes( $availability, $mode = 'multi' ) {
		$apis = new class( $availability ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};
		return new WH_Shortcodes( $this->mock_settings( array( 'mode' => $mode ) ), $apis );
	}

	public function test_multi_mode_lists_properties(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'multi' )->once()->andReturn(
			array(
				'results' => array(
					array(
						'code'     => 'DEMO',
						'name'     => 'Demo Hotel',
						'currency' => 'EUR',
						'price'    => 3500,
						'url'      => array( 'engine' => 'https://mg.reserve-online.net/' ),
						'photo'    => 'https://cdn.webhotelier.net/photos/mg/1.jpg',
					),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_search_results(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '2' )
		);
		$this->assertStringContainsString( 'wh-search-results', $html );
		$this->assertStringContainsString( 'Demo Hotel', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// active formatter produces so the test is stable under classmap autoloading.
		$this->assertStringContainsString( WH_I18n::money( 3500, 'EUR' ), $html );
	}

	public function test_single_mode_falls_back_to_single(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'single' )->once()->with( 'DEMO', \Mockery::type( 'array' ) )->andReturn(
			array( 'code' => 'DEMO', 'name' => 'Demo Hotel', 'currency' => 'EUR', 'rates' => array() )
		);

		$html = $this->shortcodes( $api, 'single' )->render_search_results(
			array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => '2' )
		);
		$this->assertStringContainsString( 'wh-search-results', $html );
	}
}
