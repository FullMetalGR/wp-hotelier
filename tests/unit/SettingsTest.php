<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class SettingsTest extends WH_UnitTestCase {

	private function stub_sanitizers(): void {
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $v ) {
				return is_string( $v ) ? trim( $v ) : $v;
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			static function ( $v ) {
				return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) );
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'absint' )->alias(
			static function ( $v ) {
				return abs( (int) $v );
			}
		);
	}

	public function test_defaults_match_contract(): void {
		$d = \WH_Settings::defaults();
		$this->assertSame( 'https://rest.reserve-online.net', $d['api_base'] );
		$this->assertSame( 'single', $d['mode'] );
		$this->assertSame( '', $d['default_property'] );
		$this->assertSame( 'EUR', $d['currency'] );
		$this->assertSame( 'hosted', $d['completion_mode'] );
		$this->assertSame( 'redirect', $d['engine_open'] );
		$this->assertSame( 1800, $d['cache_ttl_content'] );
		$this->assertSame( 60, $d['cache_ttl_availability'] );
		$this->assertSame( 'leaflet', $d['map_provider'] );
		$this->assertFalse( $d['debug'] );
	}

	public function test_getters_fall_back_to_defaults_when_option_empty(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		$s = new \WH_Settings();
		$this->assertSame( 'https://rest.reserve-online.net', $s->api_base() );
		$this->assertSame( 'single', $s->mode() );
		$this->assertSame( '', $s->default_property() );
		$this->assertSame( 1800, $s->cache_ttl_content() );
		$this->assertSame( 60, $s->cache_ttl_availability() );
		$this->assertFalse( $s->debug() );
	}

	public function test_stored_values_override_defaults(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_user'  => 'alice',
				'api_pass'  => 'secret',
				'mode'      => 'multi',
				'debug'     => true,
				'cache_ttl_availability' => 30,
			)
		);
		$s = new \WH_Settings();
		$this->assertSame( 'alice', $s->api_user() );
		$this->assertSame( 'secret', $s->api_pass() );
		$this->assertSame( 'multi', $s->mode() );
		$this->assertTrue( $s->debug() );
		$this->assertSame( 30, $s->cache_ttl_availability() );
	}

	public function test_mode_enum_is_validated(): void {
		Functions\when( 'get_option' )->justReturn( array( 'mode' => 'bogus' ) );
		$s = new \WH_Settings();
		$this->assertSame( 'single', $s->mode() );
	}

	public function test_completion_mode_and_engine_open_enums_are_validated(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'completion_mode' => 'wat',
				'engine_open'     => 'wat',
			)
		);
		$s = new \WH_Settings();
		$this->assertSame( 'hosted', $s->completion_mode() );
		$this->assertSame( 'redirect', $s->engine_open() );
	}

	public function test_sanitize_keeps_current_password_when_field_blank(): void {
		$this->stub_sanitizers();
		Functions\when( 'get_option' )->justReturn( array( 'api_pass' => 'existing-pass' ) );
		$s     = new \WH_Settings();
		$clean = $s->sanitize(
			array(
				'api_user' => 'bob',
				'api_pass' => '',
			)
		);
		$this->assertSame( 'existing-pass', $clean['api_pass'] );
		$this->assertSame( 'bob', $clean['api_user'] );
	}

	public function test_sanitize_updates_password_when_field_provided(): void {
		$this->stub_sanitizers();
		Functions\when( 'get_option' )->justReturn( array( 'api_pass' => 'existing-pass' ) );
		$s     = new \WH_Settings();
		$clean = $s->sanitize( array( 'api_pass' => 'new-pass' ) );
		$this->assertSame( 'new-pass', $clean['api_pass'] );
	}

	public function test_sanitize_coerces_types_and_validates_enums(): void {
		$this->stub_sanitizers();
		Functions\when( 'get_option' )->justReturn( array() );
		$s     = new \WH_Settings();
		$clean = $s->sanitize(
			array(
				'api_base'               => 'https://example.test',
				'mode'                   => 'invalid-mode',
				'cache_ttl_content'      => '900',
				'cache_ttl_availability' => '45',
				'debug'                  => '1',
				'map_provider'           => 'google',
			)
		);
		$this->assertSame( 'https://example.test', $clean['api_base'] );
		$this->assertSame( 'single', $clean['mode'] );
		$this->assertSame( 900, $clean['cache_ttl_content'] );
		$this->assertSame( 45, $clean['cache_ttl_availability'] );
		$this->assertTrue( $clean['debug'] );
		$this->assertSame( 'google', $clean['map_provider'] );
	}

	public function test_get_returns_full_array_merged_with_defaults(): void {
		Functions\when( 'get_option' )->justReturn( array( 'api_user' => 'carol' ) );
		$s   = new \WH_Settings();
		$all = $s->all();
		$this->assertSame( 'carol', $all['api_user'] );
		$this->assertSame( 'https://rest.reserve-online.net', $all['api_base'] );
	}

	public function test_get_returns_supplied_default_for_unknown_key(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		$s = new \WH_Settings();
		$this->assertSame( 'fallback', $s->get( 'no_such_key', 'fallback' ) );
		$this->assertNull( $s->get( 'no_such_key' ) );
		// A known key is unaffected by the default arg.
		$this->assertSame( 'single', $s->get( 'mode', 'fallback' ) );
	}
}
