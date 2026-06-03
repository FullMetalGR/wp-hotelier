<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeFacilitiesTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php as
		// ( $thing instanceof WP_Error ); it cannot be Brain-Monkey aliased
		// (Patchwork DefinedTooEarly) and already gives the behaviour we need.
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
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

	public function test_renders_property_facilities_list(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn(
			array( 'facilities' => array( 'Pool', 'Parking', 'Wifi' ) )
		);

		$html = $this->shortcodes( $api )->render_facilities( array() );
		$this->assertStringContainsString( 'wh-facilities', $html );
		$this->assertStringContainsString( 'Pool', $html );
		$this->assertStringContainsString( 'Wifi', $html );
	}

	public function test_handles_grouped_facilities(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->andReturn(
			array(
				'facilities' => array(
					array( 'name' => 'Spa' ),
					array( 'name' => 'Gym' ),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_facilities( array() );
		$this->assertStringContainsString( 'Spa', $html );
		$this->assertStringContainsString( 'Gym', $html );
	}
}
