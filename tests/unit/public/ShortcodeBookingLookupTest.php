<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeBookingLookupTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE123' );
		Functions\when( 'rest_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.com/wp-json/' . ltrim( (string) $path, '/' ); }
		);
	}

	private function shortcodes() {
		return new WH_Shortcodes( $this->mock_settings() );
	}

	public function test_renders_lookup_form_with_nonce_and_endpoint(): void {
		$html = $this->shortcodes()->render_booking_lookup( array() );
		$this->assertStringContainsString( 'wh-booking-lookup', $html );
		$this->assertStringContainsString( 'name="res_id"', $html );
		$this->assertStringContainsString( 'name="email"', $html );
		$this->assertStringContainsString( 'NONCE123', $html );
		$this->assertStringContainsString( 'webhotelier/v1/lookup', $html );
	}
}
