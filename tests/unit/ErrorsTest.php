<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorsTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	/**
	 * Every error code listed in the shared contract.
	 *
	 * @return array<int,array<int,string>>
	 */
	public static function code_provider(): array {
		return array(
			array( 'OK' ),
			array( 'NO_AVAILABILITY' ),
			array( 'NO_HOTELS_FOUND' ),
			array( 'INVALID_METHOD' ),
			array( 'INVALID_PARAM' ),
			array( 'ZERO_RESULTS_GEO' ),
			array( 'NOT_ALLOWED' ),
			array( 'NOT_FOUND' ),
			array( 'NO_AUTH' ),
			array( 'INVALID_AUTH' ),
			array( 'FORBIDDEN' ),
			array( 'ALLOT_DEPLETED' ),
			array( 'INVALID_PRICE' ),
			array( 'GEO_OVER_QUOTA' ),
			array( 'NO_PRIVILEGES' ),
			array( 'PROPERTY_NOT_FOUND' ),
		);
	}

	#[DataProvider( 'code_provider' )]
	public function test_every_code_has_a_mapped_message( string $code ): void {
		$this->assertTrue( \WH_Errors::has( $code ), "Code {$code} should be mapped." );
		$msg = \WH_Errors::message( $code );
		$this->assertIsString( $msg );
		$this->assertNotSame( '', $msg );
	}

	public function test_unknown_code_falls_back_to_raw_then_generic(): void {
		$this->assertSame( 'raw server text', \WH_Errors::message( 'SOMETHING_NEW', 'raw server text' ) );
		$generic = \WH_Errors::message( 'SOMETHING_NEW', '' );
		$this->assertNotSame( '', $generic );
	}

	public function test_to_wp_error_uses_mapped_message_and_carries_http_code(): void {
		$err = \WH_Errors::to_wp_error(
			array(
				'error_code' => 'INVALID_AUTH',
				'error_msg'  => 'server raw',
				'http_code'  => 403,
			)
		);
		$this->assertInstanceOf( \WP_Error::class, $err );
		$this->assertSame( 'INVALID_AUTH', $err->get_error_code() );
		$this->assertNotSame( '', $err->get_error_message() );
		$data = $err->get_error_data();
		$this->assertSame( 403, $data['http_code'] );
		$this->assertSame( 'INVALID_AUTH', $data['raw']['error_code'] );
	}

	public function test_to_wp_error_defaults_code_when_missing(): void {
		$err = \WH_Errors::to_wp_error( array() );
		$this->assertSame( 'wh_unknown', $err->get_error_code() );
	}

	public function test_mapped_message_takes_priority_over_raw_for_known_code(): void {
		// For a known code we prefer the friendly mapped message, not the raw server text.
		$mapped = \WH_Errors::message( 'NO_AVAILABILITY' );
		$this->assertSame( $mapped, \WH_Errors::message( 'NO_AVAILABILITY', 'raw text from server' ) );
	}
}
