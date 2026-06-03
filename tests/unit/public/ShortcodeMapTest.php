<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeMapTest extends WH_Public_TestCase {

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
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $d ) {
				return json_encode( $d ); }
		);
	}

	private function shortcodes( $property, $provider = 'leaflet' ) {
		$apis = new class( $property ) {
			private $p;
			public function __construct( $p ) {
				$this->p = $p; }
			public function property_api() {
				return $this->p; }
		};
		return new WH_Shortcodes( $this->mock_settings( array( 'map_provider' => $provider ) ), $apis );
	}

	public function test_renders_leaflet_map_with_single_marker(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn(
			array(
				'name'     => 'Demo Hotel',
				'location' => array( 'lat' => 37.4, 'lon' => 25.3 ),
			)
		);

		$html = $this->shortcodes( $api )->render_map( array() );
		$this->assertStringContainsString( 'wh-map', $html );
		$this->assertStringContainsString( 'data-provider="leaflet"', $html );
		$this->assertStringContainsString( '37.4', $html );
		$this->assertStringContainsString( '25.3', $html );
	}

	public function test_height_attribute_applied(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->andReturn(
			array( 'location' => array( 'lat' => 1, 'lon' => 2 ) )
		);
		$html = $this->shortcodes( $api )->render_map( array( 'height' => '500' ) );
		$this->assertStringContainsString( 'height:500px', $html );
	}
}
