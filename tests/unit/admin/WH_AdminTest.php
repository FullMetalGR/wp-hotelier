<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_AdminTest extends WH_Admin_TestCase {

	private function make_plugin() {
		// Container exposing the accessors WH_Admin needs; all return Mockery doubles.
		$plugin = Mockery::mock( 'WH_Plugin' );
		// WH_Settings double tolerates get() — page controllers may read settings on construct.
		$settings = Mockery::mock( 'WH_Settings' );
		$settings->shouldReceive( 'get' )->andReturn( 'single' );
		$plugin->shouldReceive( 'settings' )->andReturn( $settings );
		$plugin->shouldReceive( 'client' )->andReturn( Mockery::mock( 'WH_Client' ) );
		$plugin->shouldReceive( 'cache' )->andReturn( Mockery::mock( 'WH_Cache' ) );
		$plugin->shouldReceive( 'property_api' )->andReturn( Mockery::mock( 'WH_Property_API' ) );
		$plugin->shouldReceive( 'availability_api' )->andReturn( Mockery::mock( 'WH_Availability_API' ) );
		$plugin->shouldReceive( 'offers_api' )->andReturn( Mockery::mock( 'WH_Offers_API' ) );
		$plugin->shouldReceive( 'bookings_api' )->andReturn( Mockery::mock( 'WH_Bookings_API' ) );
		$plugin->shouldReceive( 'vouchers_api' )->andReturn( Mockery::mock( 'WH_Vouchers_API' ) );
		$plugin->shouldReceive( 'stats_api' )->andReturn( Mockery::mock( 'WH_Stats_API' ) );
		$plugin->shouldReceive( 'endpoints' )->andReturn( Mockery::mock( 'WH_Endpoints' ) );
		return $plugin;
	}

	public function test_hooks_registered_on_construct() {
		$plugin = $this->make_plugin();
		$admin  = new \WH_Admin( $plugin );

		$this->assertNotFalse(
			has_action( 'admin_menu', array( $admin, 'register_menu' ) ),
			'admin_menu must invoke register_menu'
		);
		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) ),
			'admin_enqueue_scripts must invoke enqueue_assets'
		);
	}

	public function test_register_menu_adds_top_level_and_six_submenus() {
		$plugin = $this->make_plugin();
		$admin  = new \WH_Admin( $plugin );

		$top = array();
		Functions\expect( 'add_menu_page' )
			->once()
			->andReturnUsing( function ( $page, $title, $cap, $slug ) use ( &$top ) {
				$top = compact( 'page', 'title', 'cap', 'slug' );
				return 'toplevel_page_' . $slug;
			} );

		$subs = array();
		Functions\expect( 'add_submenu_page' )
			->times( 6 )
			->andReturnUsing( function ( $parent, $page, $title, $cap, $slug ) use ( &$subs ) {
				$subs[] = compact( 'parent', 'slug', 'cap' );
				return 'webhotelier_page_' . $slug;
			} );

		Functions\when( '__' )->returnArg( 1 );

		$admin->register_menu();

		$this->assertSame( 'webhotelier', $top['slug'], 'top-level slug must be webhotelier' );
		$this->assertSame( 'manage_options', $top['cap'], 'top-level requires manage_options' );

		$slugs = array_column( $subs, 'slug' );
		$this->assertSame(
			array(
				'webhotelier',          // Settings reuses parent slug
				'webhotelier-bookings',
				'webhotelier-stats',
				'webhotelier-vouchers',
				'webhotelier-sync',
				'webhotelier-explorer',
			),
			$slugs
		);
		foreach ( $subs as $s ) {
			$this->assertSame( 'webhotelier', $s['parent'] );
			$this->assertSame( 'manage_options', $s['cap'], 'every submenu requires manage_options' );
		}
	}

	public function test_enqueue_assets_only_on_plugin_screens() {
		$plugin = $this->make_plugin();
		$admin  = new \WH_Admin( $plugin );

		// Off-screen: no enqueues.
		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();
		$admin->enqueue_assets( 'edit.php' );

		$this->assertTrue( true ); // reached without unexpected enqueue
	}

	public function test_enqueue_assets_loads_css_js_on_settings_screen() {
		$plugin = $this->make_plugin();
		$admin  = new \WH_Admin( $plugin );

		if ( ! defined( 'WH_URL' ) ) { define( 'WH_URL', 'http://example.test/wp-content/plugins/webhotelier/' ); }
		if ( ! defined( 'WH_VERSION' ) ) { define( 'WH_VERSION', '1.0.0' ); }

		$styles = array();
		$scripts = array();
		Functions\when( 'wp_enqueue_style' )->alias( function ( $h, $src = '', $d = array(), $v = false ) use ( &$styles ) {
			$styles[] = $h;
		} );
		Functions\when( 'wp_enqueue_script' )->alias( function ( $h, $src = '', $d = array(), $v = false, $f = false ) use ( &$scripts ) {
			$scripts[] = $h;
		} );
		Functions\when( 'wp_localize_script' )->justReturn( true );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'wp_create_nonce' )->justReturn( 'n0nce' );

		$admin->enqueue_assets( 'toplevel_page_webhotelier' );

		$this->assertContains( 'wh-admin', $styles );
		$this->assertContains( 'wh-admin', $scripts );
	}
}
