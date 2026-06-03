<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class PluginContainerTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_user' => 'user',
				'api_pass' => 'pass',
			)
		);
		// WH_Plugin::instance() loads include files relative to WH_PATH if defined;
		// in tests the classmap already loaded them, so the requires are guarded.
	}

	public function test_instance_is_singleton(): void {
		$a = \WH_Plugin::instance();
		$b = \WH_Plugin::instance();
		$this->assertSame( $a, $b );
	}

	public function test_container_exposes_settings_client_cache(): void {
		$plugin = \WH_Plugin::instance();
		$this->assertInstanceOf( \WH_Settings::class, $plugin->settings() );
		$this->assertInstanceOf( \WH_Client::class, $plugin->client() );
		$this->assertInstanceOf( \WH_Cache::class, $plugin->cache() );
	}

	public function test_container_exposes_all_six_resource_apis(): void {
		$plugin = \WH_Plugin::instance();
		$this->assertInstanceOf( \WH_Property_API::class, $plugin->property_api() );
		$this->assertInstanceOf( \WH_Availability_API::class, $plugin->availability_api() );
		$this->assertInstanceOf( \WH_Offers_API::class, $plugin->offers_api() );
		$this->assertInstanceOf( \WH_Bookings_API::class, $plugin->bookings_api() );
		$this->assertInstanceOf( \WH_Vouchers_API::class, $plugin->vouchers_api() );
		$this->assertInstanceOf( \WH_Stats_API::class, $plugin->stats_api() );
	}

	public function test_resource_apis_are_memoized(): void {
		$plugin = \WH_Plugin::instance();
		$this->assertSame( $plugin->property_api(), $plugin->property_api() );
		$this->assertSame( $plugin->client(), $plugin->client() );
	}
}
