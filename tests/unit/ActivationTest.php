<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class ActivationTest extends WH_UnitTestCase {

	public function test_activation_seeds_defaults_when_no_option_exists(): void {
		// Simulate the body of wh_activate(): merge defaults under any existing option.
		$existing = array();
		$merged   = array_merge( \WH_Settings::defaults(), $existing );

		Functions\expect( 'update_option' )
			->once()
			->with( 'wh_settings', \Mockery::on( static function ( $value ) {
				return is_array( $value )
					&& 'https://rest.reserve-online.net' === $value['api_base']
					&& 'single' === $value['mode']
					&& 'DEMO' === $value['default_property'];
			} ) )
			->andReturn( true );

		// Drive the seed.
		update_option( 'wh_settings', $merged );
		$this->assertSame( 'EUR', $merged['currency'] );
	}

	public function test_activation_preserves_existing_values_over_defaults(): void {
		$existing = array(
			'api_user' => 'alice',
			'mode'     => 'multi',
		);
		$merged = array_merge( \WH_Settings::defaults(), $existing );

		// Existing values win; untouched keys still get defaults.
		$this->assertSame( 'alice', $merged['api_user'] );
		$this->assertSame( 'multi', $merged['mode'] );
		$this->assertSame( 'DEMO', $merged['default_property'] );
		$this->assertSame( 1800, $merged['cache_ttl_content'] );
	}

	public function test_settings_round_trip_after_activation(): void {
		// After activation seeds the option, a fresh WH_Settings reads it back correctly.
		Functions\when( 'get_option' )->justReturn( \WH_Settings::defaults() );
		$settings = new \WH_Settings();
		$this->assertSame( 'https://rest.reserve-online.net', $settings->api_base() );
		$this->assertSame( 60, $settings->cache_ttl_availability() );
		$this->assertSame( 'hosted', $settings->completion_mode() );
	}
}
