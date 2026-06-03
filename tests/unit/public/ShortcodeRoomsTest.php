<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeRoomsTest extends WH_Public_TestCase {

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

	public function test_renders_room_grid(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rooms' )->once()->with( 'DEMO' )->andReturn(
			array(
				'rooms' => array(
					array(
						'code'        => 'ZEN',
						'name'        => 'Zen Villa',
						'description' => '<p>Calm</p>',
						'photos'      => array( 'https://cdn.webhotelier.net/photos/zen/1.jpg' ),
						'max_persons' => 6,
					),
					array(
						'code' => 'BOHEM',
						'name' => 'Bohem Villa',
					),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_rooms( array( 'columns' => '3' ) );
		$this->assertStringContainsString( 'wh-rooms', $html );
		$this->assertStringContainsString( 'wh-rooms--cols-3', $html );
		$this->assertStringContainsString( 'Zen Villa', $html );
		$this->assertStringContainsString( 'Bohem Villa', $html );
		$this->assertStringContainsString( 'w=600:h=400', $html );
	}

	public function test_empty_rooms_shows_empty_notice(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'rooms' )->andReturn( array( 'rooms' => array() ) );

		$html = $this->shortcodes( $api )->render_rooms( array() );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
