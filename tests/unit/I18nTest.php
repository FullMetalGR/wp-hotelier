<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class I18nTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	public function test_accept_language_prefers_explicit_setting(): void {
		$settings = \Mockery::mock( \WH_Settings::class );
		$settings->shouldReceive( 'locale' )->andReturn( 'el' );
		$this->assertSame( 'el', \WH_I18n::accept_language( $settings ) );
	}

	public function test_accept_language_falls_back_to_get_locale(): void {
		$settings = \Mockery::mock( \WH_Settings::class );
		$settings->shouldReceive( 'locale' )->andReturn( '' );
		Functions\when( 'get_locale' )->justReturn( 'fr_FR' );
		$this->assertSame( 'fr_FR', \WH_I18n::accept_language( $settings ) );
	}

	public function test_accept_language_defaults_to_en_gb_when_all_empty(): void {
		$settings = \Mockery::mock( \WH_Settings::class );
		$settings->shouldReceive( 'locale' )->andReturn( '' );
		Functions\when( 'get_locale' )->justReturn( '' );
		$this->assertSame( 'en_GB', \WH_I18n::accept_language( $settings ) );
	}

	public function test_money_formats_eur_amount_with_symbol(): void {
		$out = \WH_I18n::money( 1234.5, 'EUR' );
		$this->assertStringContainsString( '1,234.50', $out );
		$this->assertStringContainsString( '€', $out );
	}

	public function test_money_uses_iso_code_for_unknown_currency(): void {
		$out = \WH_I18n::money( 99, 'XYZ' );
		$this->assertStringContainsString( '99.00', $out );
		$this->assertStringContainsString( 'XYZ', $out );
	}

	public function test_money_handles_zero_and_integers(): void {
		$this->assertStringContainsString( '0.00', \WH_I18n::money( 0, 'USD' ) );
		$this->assertStringContainsString( '$', \WH_I18n::money( 0, 'USD' ) );
	}

	public function test_money_casts_numeric_strings(): void {
		$out = \WH_I18n::money( '250', 'GBP' );
		$this->assertStringContainsString( '250.00', $out );
		$this->assertStringContainsString( '£', $out );
	}
}
