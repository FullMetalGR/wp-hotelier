<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';

use Brain\Monkey\Functions;

final class RenderTest extends WH_Public_TestCase {

	private string $plugin_dir;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_dir = dirname( __DIR__, 3 ) . '/';
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', $this->plugin_dir );
		}
	}

	public function test_resolves_plugin_template_when_theme_has_none(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );

		$path = WH_Render::locate( 'notice' );
		$this->assertSame( $this->plugin_dir . 'public/templates/notice.php', $path );
	}

	public function test_child_theme_override_wins(): void {
		$child  = sys_get_temp_dir() . '/wh-child-' . uniqid();
		$parent = sys_get_temp_dir() . '/wh-parent-' . uniqid();
		mkdir( $child . '/webhotelier', 0777, true );
		mkdir( $parent . '/webhotelier', 0777, true );
		file_put_contents( $child . '/webhotelier/notice.php', '<?php /* child */' );
		file_put_contents( $parent . '/webhotelier/notice.php', '<?php /* parent */' );

		Functions\when( 'get_stylesheet_directory' )->justReturn( $child );
		Functions\when( 'get_template_directory' )->justReturn( $parent );

		$this->assertSame( $child . '/webhotelier/notice.php', WH_Render::locate( 'notice' ) );

		unlink( $child . '/webhotelier/notice.php' );
		unlink( $parent . '/webhotelier/notice.php' );
		rmdir( $child . '/webhotelier' );
		rmdir( $parent . '/webhotelier' );
		rmdir( $child );
		rmdir( $parent );
	}

	public function test_parent_theme_override_used_when_no_child(): void {
		$child  = sys_get_temp_dir() . '/wh-child-' . uniqid();
		$parent = sys_get_temp_dir() . '/wh-parent-' . uniqid();
		mkdir( $child, 0777, true );
		mkdir( $parent . '/webhotelier', 0777, true );
		file_put_contents( $parent . '/webhotelier/notice.php', '<?php /* parent */' );

		Functions\when( 'get_stylesheet_directory' )->justReturn( $child );
		Functions\when( 'get_template_directory' )->justReturn( $parent );

		$this->assertSame( $parent . '/webhotelier/notice.php', WH_Render::locate( 'notice' ) );

		unlink( $parent . '/webhotelier/notice.php' );
		rmdir( $parent . '/webhotelier' );
		rmdir( $parent );
		rmdir( $child );
	}

	public function test_template_returns_rendered_string_with_vars(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );

		$html = WH_Render::template( 'notice', array( 'message' => 'Hello <b>world</b>', 'type' => 'info' ) );
		$this->assertStringContainsString( 'wh-notice wh-notice--info', $html );
		// esc_html is passthrough in tests; assert message present.
		$this->assertStringContainsString( 'Hello <b>world</b>', $html );
	}

	public function test_unknown_template_returns_empty_string(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );

		$this->assertSame( '', WH_Render::template( 'does-not-exist-xyz', array() ) );
	}

	public function test_error_renders_notice_from_wp_error(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );

		$err = \Mockery::mock( 'WP_Error' );
		$err->shouldReceive( 'get_error_message' )->andReturn( 'Boom' );

		$html = WH_Render::error( $err );
		$this->assertStringContainsString( 'wh-notice--error', $html );
		$this->assertStringContainsString( 'Boom', $html );
	}
}
