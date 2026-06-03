<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class AssetsEnqueueTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_URL' ) ) {
			define( 'WH_URL', 'https://example.com/wp-content/plugins/webhotelier/' );
		}
		if ( ! defined( 'WH_VERSION' ) ) {
			define( 'WH_VERSION', '1.0.0' );
		}
	}

	public function test_registers_and_localizes_script(): void {
		$registered = array();
		$localized  = array();

		Functions\when( 'wp_register_style' )->alias(
			static function ( $h ) use ( &$registered ) {
				$registered[] = 'style:' . $h; }
		);
		Functions\when( 'wp_register_script' )->alias(
			static function ( $h ) use ( &$registered ) {
				$registered[] = 'script:' . $h; }
		);
		Functions\when( 'wp_localize_script' )->alias(
			static function ( $handle, $obj, $data ) use ( &$localized ) {
				$localized[ $obj ] = $data; }
		);
		Functions\when( 'rest_url' )->alias(
			static function ( $p = '' ) {
				return 'https://example.com/wp-json/' . ltrim( (string) $p, '/' ); }
		);
		Functions\when( 'wp_create_nonce' )->justReturn( 'NONCE123' );

		$sc = new WH_Shortcodes( $this->mock_settings() );
		$sc->register_assets();

		$this->assertContains( 'style:wh-public', $registered );
		$this->assertContains( 'script:wh-public', $registered );
		$this->assertArrayHasKey( 'WH_Public', $localized );
		$this->assertSame( 'NONCE123', $localized['WH_Public']['nonce'] );
		$this->assertStringContainsString( 'webhotelier/v1', $localized['WH_Public']['root'] );
	}
}
