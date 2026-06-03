<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeExtrasTest extends WH_Public_TestCase {

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

	public function test_lists_extras_with_prices(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'extras' )->once()->with( 'DEMO' )->andReturn(
			array(
				'extras' => array(
					array(
						'name'        => 'Airport transfer',
						'description' => '<p>Door to door</p>',
						'price'       => 80,
						'currency'    => 'EUR',
					),
					array(
						'name'     => 'Daily breakfast',
						'price'    => 15,
						'currency' => 'EUR',
					),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_extras( array() );
		$this->assertStringContainsString( 'wh-extras', $html );
		$this->assertStringContainsString( 'Airport transfer', $html );
		$this->assertStringContainsString( 'Door to door', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// active formatter produces (eval stub in isolation, real class in the
		// full suite) so the test is stable under classmap autoloading.
		$this->assertStringContainsString( WH_I18n::money( 80, 'EUR' ), $html );
	}

	public function test_empty_extras_notice(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'extras' )->andReturn( array( 'extras' => array() ) );
		$html = $this->shortcodes( $api )->render_extras( array() );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
