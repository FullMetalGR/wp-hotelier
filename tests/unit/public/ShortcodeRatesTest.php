<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeRatesTest extends WH_Public_TestCase {

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
		// WH_I18n::money stub.
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c . " " . number_format( (float) $a, 2 ); } public static function accept_language() { return "en_GB"; } }' );
		}
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

	public function test_lists_rates_with_names_and_descriptions(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rates' )->once()->with( 'DEMO', 'ZEN' )->andReturn(
			array(
				'rates' => array(
					array(
						'id'        => '522770',
						'rate'      => 'Non-refundable',
						'rate_desc' => '<p>Best price</p>',
						'board'     => 'Room only',
					),
					array(
						'id'   => '522771',
						'rate' => 'Flexible',
					),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_rates( array( 'room' => 'ZEN' ) );
		$this->assertStringContainsString( 'wh-rates', $html );
		$this->assertStringContainsString( 'Non-refundable', $html );
		$this->assertStringContainsString( 'Best price', $html );
		$this->assertStringContainsString( 'Flexible', $html );
	}

	public function test_empty_rates_notice(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rates' )->andReturn( array( 'rates' => array() ) );
		$html = $this->shortcodes( $api )->render_rates( array( 'room' => 'ZEN' ) );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
