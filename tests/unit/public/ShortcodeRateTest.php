<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeRateTest extends WH_Public_TestCase {

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

	public function test_requires_room_and_rate(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldNotReceive( 'rate' );
		$html = $this->shortcodes( $api )->render_rate( array( 'room' => 'ZEN' ) );
		$this->assertStringContainsString( 'wh-notice', $html );
	}

	public function test_renders_rate_detail_with_policies(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rate' )->once()->with( 'DEMO', 'ZEN', '522770' )->andReturn(
			array(
				'rate'                => 'Non-refundable',
				'rate_desc'           => '<p>Best price</p>',
				'cancellation_policy' => '<p>No cancellation</p>',
				'payment_policy'      => '<p>Pay now</p>',
				'board'               => 'Room only',
			)
		);

		$html = $this->shortcodes( $api )->render_rate( array( 'room' => 'ZEN', 'rate' => '522770' ) );
		$this->assertStringContainsString( 'wh-rate', $html );
		$this->assertStringContainsString( 'Non-refundable', $html );
		$this->assertStringContainsString( 'No cancellation', $html );
		$this->assertStringContainsString( 'Pay now', $html );
	}
}
