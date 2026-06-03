<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeGalleryTest extends WH_Public_TestCase {

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

	public function test_property_gallery_from_info_photos(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'info' )->once()->with( 'DEMO', null )->andReturn(
			array(
				'photos' => array(
					'https://cdn.webhotelier.net/photos/p/1.jpg',
					'https://cdn.webhotelier.net/photos/p/2.jpg',
				),
			)
		);

		$html = $this->shortcodes( $api )->render_gallery( array() );
		$this->assertStringContainsString( 'wh-gallery', $html );
		$this->assertStringContainsString( 'w=1024:h=683', $html );
		$this->assertSame( 2, substr_count( $html, '<img' ) );
	}

	public function test_room_gallery_when_room_attr_present(): void {
		$api = \Mockery::mock( 'WH_Property_API' );
		$api->shouldReceive( 'room' )->once()->with( 'DEMO', 'ZEN' )->andReturn(
			array( 'photos' => array( 'https://cdn.webhotelier.net/photos/zen/1.jpg' ) )
		);

		$html = $this->shortcodes( $api )->render_gallery( array( 'room' => 'ZEN' ) );
		$this->assertSame( 1, substr_count( $html, '<img' ) );
	}
}
