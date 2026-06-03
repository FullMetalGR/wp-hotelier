<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

/**
 * Verifies that the calendar/map widgets register and enqueue their own
 * scripts (and pull in Leaflet for the map), so the interactive behaviour can
 * actually run on the front end.
 */
final class CalendarMapEnqueueTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		if ( ! defined( 'WH_URL' ) ) {
			define( 'WH_URL', 'https://example.com/wp-content/plugins/webhotelier/' );
		}
		if ( ! defined( 'WH_VERSION' ) ) {
			define( 'WH_VERSION', '1.0.0' );
		}
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $d ) {
				return json_encode( $d ); }
		);
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c . " " . number_format( (float) $a, 2 ); } public static function accept_language() { return "en_GB"; } }' );
		}
	}

	public function test_register_assets_registers_calendar_map_and_leaflet(): void {
		$scripts = array();
		$styles  = array();

		Functions\when( 'wp_register_style' )->alias(
			static function ( $h, $src = '', $deps = array() ) use ( &$styles ) {
				$styles[ $h ] = array( 'src' => $src, 'deps' => $deps ); }
		);
		Functions\when( 'wp_register_script' )->alias(
			static function ( $h, $src = '', $deps = array() ) use ( &$scripts ) {
				$scripts[ $h ] = array( 'src' => $src, 'deps' => $deps ); }
		);
		Functions\when( 'wp_localize_script' )->justReturn( true );
		Functions\when( 'rest_url' )->justReturn( 'https://example.com/wp-json/webhotelier/v1' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE' );

		$sc = new WH_Shortcodes( $this->mock_settings() );
		$sc->register_assets();

		$this->assertArrayHasKey( 'wh-calendar', $scripts );
		$this->assertStringContainsString( 'calendar.js', $scripts['wh-calendar']['src'] );

		$this->assertArrayHasKey( 'wh-map', $scripts );
		$this->assertStringContainsString( 'map.js', $scripts['wh-map']['src'] );
		$this->assertContains( 'wh-leaflet', $scripts['wh-map']['deps'] );

		// Leaflet must be a real, resolvable source (bundled file or CDN), not a
		// path to a file that does not exist in the repo.
		$this->assertArrayHasKey( 'wh-leaflet', $scripts );
		$this->assertArrayHasKey( 'wh-leaflet', $styles );
		$this->assertNotSame( '', (string) $scripts['wh-leaflet']['src'] );
		$this->assertNotSame( '', (string) $styles['wh-leaflet']['src'] );
		$this->assertLeafletSrcResolvable( (string) $scripts['wh-leaflet']['src'] );
		$this->assertLeafletSrcResolvable( (string) $styles['wh-leaflet']['src'] );
	}

	public function test_render_calendar_enqueues_calendar_script(): void {
		$enqueued = array();
		Functions\when( 'wp_enqueue_script' )->alias(
			static function ( $h ) use ( &$enqueued ) {
				$enqueued[] = $h; }
		);
		Functions\when( 'wp_enqueue_style' )->alias(
			static function ( $h ) use ( &$enqueued ) {
				$enqueued[] = $h; }
		);

		$api = \Mockery::mock( 'WH_Availability_API' );
		$api->shouldReceive( 'calendar' )->andReturn(
			array(
				'currency' => 'EUR',
				'days'     => array( '2026-08-01' => array( 'price' => 500, 'available' => true ) ),
			)
		);
		$apis = new class( $api ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};

		$sc = new WH_Shortcodes( $this->mock_settings(), $apis );
		$sc->render_calendar( array() );

		$this->assertContains( 'wh-calendar', $enqueued );
	}

	public function test_render_map_enqueues_map_and_leaflet_assets(): void {
		$enqueued = array();
		Functions\when( 'wp_enqueue_script' )->alias(
			static function ( $h ) use ( &$enqueued ) {
				$enqueued[] = 'script:' . $h; }
		);
		Functions\when( 'wp_enqueue_style' )->alias(
			static function ( $h ) use ( &$enqueued ) {
				$enqueued[] = 'style:' . $h; }
		);

		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->andReturn(
			array( 'location' => array( 'lat' => 37.4, 'lon' => 25.3 ) )
		);
		$apis = new class( $api ) {
			private $p;
			public function __construct( $p ) {
				$this->p = $p; }
			public function property_api() {
				return $this->p; }
		};

		$sc = new WH_Shortcodes( $this->mock_settings( array( 'map_provider' => 'leaflet' ) ), $apis );
		$sc->render_map( array() );

		$this->assertContains( 'script:wh-map', $enqueued );
		$this->assertContains( 'style:wh-leaflet', $enqueued );
	}

	/**
	 * A Leaflet source is acceptable when it is either an absolute URL (CDN) or
	 * a path that resolves to a file that actually exists under the plugin.
	 */
	private function assertLeafletSrcResolvable( string $src ): void {
		if ( preg_match( '#^https?://#', $src ) ) {
			$this->assertTrue( true );
			return;
		}
		$rel  = str_replace( WH_URL, '', $src );
		$path = WH_PATH . $rel;
		$this->assertFileExists( $path, 'Leaflet asset must exist in the repo when not loaded from a CDN: ' . $src );
	}
}
