<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_Settings_PageTest extends WH_Admin_TestCase {

	private function make_page() {
		$settings = Mockery::mock( 'WH_Settings' );
		$property = Mockery::mock( 'WH_Property_API' );
		return new \WH_Settings_Page( $settings, $property, 'single' );
	}

	public function test_register_settings_declares_option_and_all_fields() {
		$page = $this->make_page();

		$registered_group   = null;
		$registered_option  = null;
		$sanitize_cb        = null;
		Functions\expect( 'register_setting' )
			->once()
			->andReturnUsing( function ( $group, $option, $args ) use ( &$registered_group, &$registered_option, &$sanitize_cb ) {
				$registered_group  = $group;
				$registered_option = $option;
				$sanitize_cb       = is_array( $args ) ? ( $args['sanitize_callback'] ?? null ) : null;
			} );

		$fields = array();
		Functions\when( 'add_settings_section' )->justReturn( true );
		Functions\expect( 'add_settings_field' )
			->atLeast()->times( 17 )
			->andReturnUsing( function ( $id ) use ( &$fields ) { $fields[] = $id; } );
		Functions\when( '__' )->returnArg( 1 );

		$page->register_settings();

		$this->assertSame( 'wh_settings_group', $registered_group );
		$this->assertSame( 'wh_settings', $registered_option );
		$this->assertIsCallable( $sanitize_cb );

		// Every documented settings key must have a field.
		$expected = array(
			'api_user', 'api_pass', 'api_base', 'mode', 'default_property', 'currency',
			'locale', 'completion_mode', 'engine_open', 'cache_ttl_content', 'cache_ttl_availability',
			'results_page', 'market_country', 'device', 'map_provider', 'map_api_key', 'debug',
		);
		foreach ( $expected as $key ) {
			$this->assertContains( 'wh_field_' . $key, $fields, "missing field for $key" );
		}
	}

	public function test_sanitize_casts_types_and_allowlists_enums() {
		$settings = Mockery::mock( 'WH_Settings' );
		// current stored password used for keep-current behaviour
		$settings->shouldReceive( 'get' )->with( 'api_pass', '' )->andReturn( 'OLDPASS' );
		$property = Mockery::mock( 'WH_Property_API' );
		$page = new \WH_Settings_Page( $settings, $property, 'single' );

		$this->stub_wp_escaping();
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );

		$raw = array(
			'api_user'               => '  hotelier  ',
			'api_pass'               => '',                     // blank => keep current
			'api_base'               => 'https://rest.reserve-online.net',
			'mode'                   => 'bogus',                // invalid enum => default 'single'
			'default_property'       => 'DEMO',
			'currency'               => 'eur',                  // upcased
			'locale'                 => 'en_GB',
			'completion_mode'        => 'native_noncard',
			'engine_open'            => 'iframe',
			'cache_ttl_content'      => '1800',
			'cache_ttl_availability' => '-5',                   // clamped to >= 0
			'results_page'           => '42',
			'market_country'         => 'GB',
			'device'                 => 'desktop',
			'map_provider'           => 'leaflet',
			'map_api_key'            => 'KEY123',
			'debug'                  => '1',
		);

		$out = $page->sanitize( $raw );

		$this->assertSame( 'hotelier', $out['api_user'] );
		$this->assertSame( 'OLDPASS', $out['api_pass'], 'blank password keeps current' );
		$this->assertSame( 'single', $out['mode'], 'invalid enum falls back to default' );
		$this->assertSame( 'EUR', $out['currency'] );
		$this->assertSame( 'native_noncard', $out['completion_mode'] );
		$this->assertSame( 'iframe', $out['engine_open'] );
		$this->assertSame( 1800, $out['cache_ttl_content'] );
		$this->assertSame( 0, $out['cache_ttl_availability'], 'negative ttl clamped to 0' );
		$this->assertSame( 42, $out['results_page'] );
		$this->assertTrue( $out['debug'] );
	}

	public function test_sanitize_keeps_new_password_when_provided() {
		$settings = Mockery::mock( 'WH_Settings' );
		$settings->shouldReceive( 'get' )->with( 'api_pass', '' )->andReturn( 'OLDPASS' );
		$property = Mockery::mock( 'WH_Property_API' );
		$page = new \WH_Settings_Page( $settings, $property, 'single' );

		$this->stub_wp_escaping();
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );

		$out = $page->sanitize( array( 'api_pass' => 'NEWPASS' ) + $this->minimal_valid() );
		$this->assertSame( 'NEWPASS', $out['api_pass'] );
	}

	public function test_render_masks_password_and_shows_keep_hint() {
		$settings = Mockery::mock( 'WH_Settings' );
		$settings->shouldReceive( 'get' )->andReturnUsing( function ( $k, $d = '' ) {
			$map = array( 'api_user' => 'hotelier', 'api_pass' => 'SECRET', 'default_property' => 'DEMO', 'mode' => 'single' );
			return $map[ $k ] ?? $d;
		} );
		$property = Mockery::mock( 'WH_Property_API' );
		$page = new \WH_Settings_Page( $settings, $property, 'single' );

		$this->stub_wp_escaping();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'settings_fields' )->justReturn( null );
		Functions\when( 'do_settings_sections' )->justReturn( null );
		Functions\when( 'submit_button' )->alias( function () { echo '<button>Save</button>'; } );
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );

		ob_start();
		$page->render();
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'SECRET', $html, 'password value must never be echoed' );
		$this->assertStringContainsString( 'leave blank to keep', strtolower( $html ) );
		$this->assertStringContainsString( 'Test Connection', $html );
	}

	private function minimal_valid() {
		return array(
			'api_user' => 'u', 'api_base' => 'https://rest.reserve-online.net', 'mode' => 'single',
			'default_property' => 'DEMO', 'currency' => 'EUR', 'locale' => 'en_US',
			'completion_mode' => 'hosted', 'engine_open' => 'redirect', 'cache_ttl_content' => '1800',
			'cache_ttl_availability' => '60', 'results_page' => '0', 'market_country' => '', 'device' => '',
			'map_provider' => 'leaflet', 'map_api_key' => '', 'debug' => '0',
		);
	}
}
