<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeVoucherFormTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/results/' );
	}

	private function shortcodes() {
		return new WH_Shortcodes( $this->mock_settings( array( 'results_page' => 42 ) ) );
	}

	public function test_renders_voucher_input(): void {
		$html = $this->shortcodes()->render_voucher_form( array() );
		$this->assertStringContainsString( 'wh-voucher-form', $html );
		$this->assertStringContainsString( 'name="voucher"', $html );
		$this->assertStringContainsString( 'action="https://example.com/results/"', $html );
	}

	public function test_custom_target_overrides(): void {
		$html = $this->shortcodes()->render_voucher_form( array( 'target' => 'https://example.com/villas/' ) );
		$this->assertStringContainsString( 'action="https://example.com/villas/"', $html );
	}
}
