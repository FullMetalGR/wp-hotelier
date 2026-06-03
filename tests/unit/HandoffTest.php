<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class HandoffTest extends WH_UnitTestCase {

	/** @var array<string,mixed> */
	private $availability;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		$json               = file_get_contents( WH_FIXTURES_DIR . '/availability-demo.json' );
		$envelope           = json_decode( $json, true );
		$this->availability = $envelope['data'];
	}

	public function test_from_rate_returns_engine_url_verbatim_when_present(): void {
		$rate = $this->availability['rates'][0]; // 522770, has url.engine.
		$url  = \WH_Handoff::from_rate( $rate );
		$this->assertSame(
			'https://demohotel.reserve-online.net/?checkin=2026-08-01&checkout=2026-08-08&party=[{"adults":4}]&lang=en_GB&rate=522770',
			$url
		);
	}

	public function test_from_rate_returns_empty_string_when_engine_missing(): void {
		$rate = $this->availability['rates'][1]; // 522771, no url.engine.
		$this->assertSame( '', \WH_Handoff::from_rate( $rate ) );
	}

	public function test_build_constructs_url_from_base_engine_and_params(): void {
		$base = $this->availability['url']['engine'];
		$url  = \WH_Handoff::build(
			$base,
			array(
				'rate'  => 522771,
				'room'  => 'BOHEM',
				'lang'  => 'en_GB',
			)
		);
		$this->assertStringStartsWith( 'https://demohotel.reserve-online.net/?', $url );
		$this->assertStringContainsString( 'rate=522771', $url );
		$this->assertStringContainsString( 'room=BOHEM', $url );
		$this->assertStringContainsString( 'lang=en_GB', $url );
		// Pre-existing query params on the base engine URL are preserved.
		$this->assertStringContainsString( 'checkin=2026-08-01', $url );
		$this->assertStringContainsString( 'checkout=2026-08-08', $url );
	}

	public function test_build_overrides_duplicate_params_with_new_values(): void {
		$url = \WH_Handoff::build(
			'https://host.example/?lang=en_GB&rate=1',
			array( 'rate' => 999 )
		);
		// The new rate replaces the old one; only one rate= appears.
		$this->assertSame( 1, substr_count( $url, 'rate=' ) );
		$this->assertStringContainsString( 'rate=999', $url );
		$this->assertStringNotContainsString( 'rate=1', $url );
	}

	public function test_build_returns_empty_string_for_empty_base(): void {
		$this->assertSame( '', \WH_Handoff::build( '', array( 'rate' => 1 ) ) );
	}

	public function test_build_appends_voucher_when_supplied(): void {
		$url = \WH_Handoff::build(
			'https://host.example/',
			array( 'rate' => 5, 'voucher' => 'SECRET10' )
		);
		$this->assertStringContainsString( 'voucher=SECRET10', $url );
	}
}
