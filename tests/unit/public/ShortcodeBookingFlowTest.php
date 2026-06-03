<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-booking-flow.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeBookingFlowTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE123' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/book/' );
		Functions\when( 'wp_unslash' )->returnArg();
	}

	public function test_renders_flow_search_step_from_get(): void {
		$_GET = array();
		$sc   = new WH_Shortcodes( $this->mock_settings( array( 'results_page' => 0 ) ) );
		$html = $sc->render_booking_flow( array() );
		$this->assertStringContainsString( 'wh-booking-flow', $html );
		$this->assertStringContainsString( 'wh-search-form', $html );
		$_GET = array();
	}
}
