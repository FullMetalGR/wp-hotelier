<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Base test case for all public-layer unit tests.
 *
 * Boots Brain Monkey and provides commonly-needed WP function stubs plus
 * helpers to build settings/client mocks. Concrete tests opt into more
 * specific behaviour as needed.
 */
abstract class WH_Public_TestCase extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Escaping helpers default to passthrough; tests asserting escaping
		// override these explicitly.
		Monkey\Functions\when( 'esc_html' )->returnArg();
		Monkey\Functions\when( 'esc_attr' )->returnArg();
		Monkey\Functions\when( 'esc_url' )->returnArg();
		Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		Monkey\Functions\when( 'esc_textarea' )->returnArg();
		Monkey\Functions\when( 'wp_kses_post' )->returnArg();
		Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		Monkey\Functions\when( 'sanitize_email' )->returnArg();
		Monkey\Functions\when( 'sanitize_key' )->alias(
			static function ( $k ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) );
			}
		);
		Monkey\Functions\when( '__' )->returnArg();
		Monkey\Functions\when( '_n' )->alias(
			static function ( $single, $plural, $number ) {
				return 1 === (int) $number ? $single : $plural;
			}
		);
		Monkey\Functions\when( 'esc_html__' )->returnArg();
		Monkey\Functions\when( 'esc_attr__' )->returnArg();
		Monkey\Functions\when( 'esc_html_e' )->alias(
			static function ( $s ) {
				echo $s;
			}
		);
		Monkey\Functions\when( 'absint' )->alias(
			static function ( $n ) {
				return abs( (int) $n );
			}
		);
		Monkey\Functions\when( 'wp_unslash' )->returnArg();
		// Asset enqueueing is a no-op side effect by default; tests that assert
		// on enqueue behaviour override these explicitly.
		Monkey\Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Monkey\Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Monkey\Functions\when( 'shortcode_atts' )->alias(
			static function ( $defaults, $atts ) {
				$atts = (array) $atts;
				$out  = array();
				foreach ( $defaults as $key => $default ) {
					$out[ $key ] = array_key_exists( $key, $atts ) ? $atts[ $key ] : $default;
				}
				return $out;
			}
		);
		Monkey\Functions\when( 'trailingslashit' )->alias(
			static function ( $s ) {
				return rtrim( (string) $s, '/\\' ) . '/';
			}
		);
		Monkey\Functions\when( 'add_query_arg' )->alias(
			static function ( $args, $url = '' ) {
				$q = is_array( $args ) ? http_build_query( $args ) : (string) $args;
				return ( '' === $url ? '' : $url ) . ( false === strpos( (string) $url, '?' ) ? '?' : '&' ) . $q;
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/** Default property code used across fixtures. */
	protected function default_property(): string {
		return 'DEMO';
	}

	/**
	 * Build a Mockery mock of WH_Settings with sensible defaults.
	 *
	 * @param array<string,mixed> $overrides Method => return value overrides.
	 */
	protected function mock_settings( array $overrides = array() ) {
		$defaults = array(
			'mode'             => 'single',
			'default_property' => 'DEMO',
			'currency'         => 'EUR',
			'completion_mode'  => 'hosted',
			'engine_open'      => 'redirect',
			'locale'           => 'en_GB',
			'map_provider'     => 'leaflet',
			'map_api_key'      => '',
			'results_page'     => 0,
			'debug'            => false,
		);
		$values = array_merge( $defaults, $overrides );

		$settings = \Mockery::mock( 'WH_Settings' );
		foreach ( $values as $method => $value ) {
			$settings->shouldReceive( $method )->andReturn( $value )->byDefault();
		}
		return $settings;
	}
}
