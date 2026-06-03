<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodePropertyTest extends WH_Public_TestCase {

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

	public function test_renders_name_description_and_location(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn(
			array(
				'name'        => 'Demo Hotel',
				'description' => '<p>Luxury villas.</p>',
				'location'    => array( 'lat' => 37.4, 'lon' => 25.3 ),
				'url'         => array( 'engine' => 'https://mg.reserve-online.net/' ),
			)
		);

		$html = $this->shortcodes( $api )->render_property( array() );

		$this->assertStringContainsString( 'wh-property', $html );
		$this->assertStringContainsString( 'Demo Hotel', $html );
		$this->assertStringContainsString( 'Luxury villas.', $html );
	}

	public function test_error_renders_notice(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$err = new \WP_Error( 'NO_AUTH', 'Auth failed' );
		$api->shouldReceive( 'info' )->andReturn( $err );

		$html = $this->shortcodes( $api )->render_property( array() );
		$this->assertStringContainsString( 'wh-notice--error', $html );
		$this->assertStringContainsString( 'Auth failed', $html );
	}
}
