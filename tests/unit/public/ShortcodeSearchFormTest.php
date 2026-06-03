<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeSearchFormTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/results/' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE123' );
		Functions\when( 'esc_attr_e' )->alias(
			static function ( $s ) {
				echo $s; }
		);
	}

	private function shortcodes() {
		return new WH_Shortcodes( $this->mock_settings( array( 'results_page' => 42 ) ) );
	}

	public function test_search_form_has_date_and_occupancy_fields(): void {
		$html = $this->shortcodes()->render_search_form( array() );
		$this->assertStringContainsString( 'wh-search-form', $html );
		$this->assertStringContainsString( 'name="checkin"', $html );
		$this->assertStringContainsString( 'name="checkout"', $html );
		$this->assertStringContainsString( 'name="adults"', $html );
		$this->assertStringContainsString( 'action="https://example.com/results/"', $html );
	}

	public function test_quick_search_uses_compact_layout(): void {
		$html = $this->shortcodes()->render_quick_search( array() );
		$this->assertStringContainsString( 'wh-search-form--compact', $html );
		$this->assertStringContainsString( 'name="checkin"', $html );
	}

	public function test_adults_default_attribute_applied(): void {
		$html = $this->shortcodes()->render_search_form( array( 'adults_default' => '3' ) );
		$this->assertStringContainsString( 'value="3"', $html );
	}
}
