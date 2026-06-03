<?php
/**
 * Shared base TestCase for all WebHotelier admin unit tests.
 *
 * Boots Brain\Monkey (so WP functions can be stubbed/expected) and Mockery
 * (so WH_*_API collaborators can be mocked). Provides small helpers used across
 * the admin test suite: capability/nonce stubs and common escaping passthroughs.
 *
 * @package webhotelier
 */

namespace WH\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

abstract class WH_Admin_TestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Stub the WP escaping/i18n/sanitize functions used by admin views as
	 * identity (or near-identity) passthroughs so assertions can target the
	 * meaningful substring of rendered HTML without WP loaded.
	 */
	protected function stub_wp_escaping(): void {
		Functions\stubs( array(
			'esc_html'      => static function ( $t ) { return $t; },
			'esc_attr'      => static function ( $t ) { return $t; },
			'esc_url'       => static function ( $t ) { return $t; },
			'esc_url_raw'   => static function ( $t ) { return $t; },
			'esc_textarea'  => static function ( $t ) { return $t; },
			'esc_html__'    => static function ( $t ) { return $t; },
			'esc_html_e'    => static function ( $t ) { echo $t; },
			'esc_attr__'    => static function ( $t ) { return $t; },
			'esc_attr_e'    => static function ( $t ) { echo $t; },
			'__'            => static function ( $t ) { return $t; },
			'_e'            => static function ( $t ) { echo $t; },
			'_x'            => static function ( $t ) { return $t; },
			'wp_kses_post'  => static function ( $t ) { return $t; },
			'sanitize_text_field' => static function ( $t ) { return is_string( $t ) ? trim( $t ) : $t; },
			'sanitize_key'  => static function ( $t ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $t ) ); },
			'absint'        => static function ( $t ) { return abs( (int) $t ); },
			'wp_unslash'    => static function ( $t ) { return $t; },
			'selected'      => static function ( $a, $b = true, $echo = true ) {
				$r = ( (string) $a === (string) $b ) ? " selected='selected'" : '';
				if ( $echo ) { echo $r; }
				return $r;
			},
			'checked'       => static function ( $a, $b = true, $echo = true ) {
				$r = ( (string) $a === (string) $b ) ? " checked='checked'" : '';
				if ( $echo ) { echo $r; }
				return $r;
			},
			'esc_html_x'    => static function ( $t ) { return $t; },
			'number_format_i18n' => static function ( $n, $d = 0 ) { return number_format( (float) $n, (int) $d ); },
		) );
	}

	/**
	 * Make current_user_can() return the given verdict for any capability.
	 */
	protected function stub_capability( bool $allowed ): void {
		Functions\when( 'current_user_can' )->justReturn( $allowed );
	}

	/**
	 * Make nonce verification return the given verdict.
	 */
	protected function stub_nonce_valid( bool $valid ): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( $valid ? 1 : false );
		Functions\when( 'check_admin_referer' )->justReturn( $valid ? 1 : false );
		Functions\when( 'check_ajax_referer' )->justReturn( $valid ? 1 : false );
		Functions\when( 'wp_create_nonce' )->justReturn( 'test-nonce' );
	}

	/**
	 * Build a Mockery mock of a WH_*_API class name (string), tolerating that
	 * the real class may not be autoloadable in the admin-only test context.
	 */
	protected function api_mock( string $class ) {
		return Mockery::mock( $class );
	}
}
