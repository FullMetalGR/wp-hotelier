<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class VouchersApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_bundles_gets_voucher_root(): void {
		$client = $this->client_expecting_get( '/voucher', array(), array( 'bundles' => array() ) );
		$api    = new \WH_Vouchers_API( $client );
		$this->assertSame( array( 'bundles' => array() ), $api->bundles() );
	}

	public function test_codes_gets_voucher_bundleid(): void {
		$client = $this->client_expecting_get( '/voucher/42', array(), array( 'codes' => array() ) );
		$api    = new \WH_Vouchers_API( $client );
		$this->assertSame( array( 'codes' => array() ), $api->codes( 42 ) );
	}

	public function test_manage_code_posts_to_voucher_bundleid_code(): void {
		$params = array( 'action' => 'disable' );
		$client = $this->client_expecting_post( '/voucher/42/SECRET10', $params, array( 'updated' => true ) );
		$api    = new \WH_Vouchers_API( $client );
		$this->assertSame( array( 'updated' => true ), $api->manageCode( 42, 'SECRET10', $params ) );
	}
}
