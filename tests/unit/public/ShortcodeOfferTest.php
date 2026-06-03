<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeOfferTest extends WH_Public_TestCase {

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

	private function shortcodes( $offers ) {
		$apis = new class( $offers ) {
			private $o;
			public function __construct( $o ) {
				$this->o = $o; }
			public function offers_api() {
				return $this->o; }
		};
		return new WH_Shortcodes( $this->mock_settings(), $apis );
	}

	public function test_requires_offer_id(): void {
		$api = \Mockery::mock( 'WH_Offers_API' );
		$api->shouldNotReceive( 'info' );
		$html = $this->shortcodes( $api )->render_offer( array() );
		$this->assertStringContainsString( 'wh-notice', $html );
	}

	public function test_renders_offer_detail(): void {
		$api = \Mockery::mock( 'WH_Offers_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', 'SUMMER' )->andReturn(
			array(
				'name'        => 'Summer Escape',
				'description' => '<p>Save 20%</p>',
				'terms'       => '<p>Book by July</p>',
				'url'         => array( 'engine' => 'https://mg.reserve-online.net/?offer=SUMMER' ),
			)
		);

		$html = $this->shortcodes( $api )->render_offer( array( 'offer' => 'SUMMER' ) );
		$this->assertStringContainsString( 'wh-offer', $html );
		$this->assertStringContainsString( 'Summer Escape', $html );
		$this->assertStringContainsString( 'Book by July', $html );
	}
}
