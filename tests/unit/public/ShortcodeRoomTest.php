<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeRoomTest extends WH_Public_TestCase {

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

	public function test_requires_room_attribute(): void {
		$api  = \Mockery::mock( 'WH_Property_API' );
		$api->shouldNotReceive( 'room' );
		$html = $this->shortcodes( $api )->render_room( array() );
		$this->assertStringContainsString( 'wh-notice', $html );
	}

	public function test_renders_room_detail(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'room' )->once()->with( 'DEMO', 'ZEN' )->andReturn(
			array(
				'code'        => 'ZEN',
				'name'        => 'Zen Villa',
				'description' => '<p>Serene</p>',
				'photos'      => array(
					'https://cdn.webhotelier.net/photos/zen/1.jpg',
					'https://cdn.webhotelier.net/photos/zen/2.jpg',
				),
				'facilities'  => array( 'Pool', 'Wifi' ),
				'max_persons' => 6,
				'url'         => array( 'engine' => 'https://mg.reserve-online.net/?room=ZEN' ),
			)
		);

		$html = $this->shortcodes( $api )->render_room( array( 'room' => 'ZEN' ) );
		$this->assertStringContainsString( 'wh-room', $html );
		$this->assertStringContainsString( 'Zen Villa', $html );
		$this->assertStringContainsString( 'Serene', $html );
		$this->assertStringContainsString( 'Pool', $html );
		$this->assertStringContainsString( 'Book', $html );
	}
}
