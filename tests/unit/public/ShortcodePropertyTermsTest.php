<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodePropertyTermsTest extends WH_Public_TestCase {

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

	public function test_renders_general_terms_html(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', 'general_terms' )->andReturn(
			array( 'general_terms' => '<p>Check-in after 3pm.</p>' )
		);

		$html = $this->shortcodes( $api )->render_property_terms( array() );
		$this->assertStringContainsString( 'wh-property-terms', $html );
		$this->assertStringContainsString( 'Check-in after 3pm.', $html );
	}

	public function test_empty_terms_shows_notice(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->andReturn( array( 'general_terms' => '' ) );

		$html = $this->shortcodes( $api )->render_property_terms( array() );
		$this->assertStringContainsString( 'wh-property-terms', $html );
	}
}
