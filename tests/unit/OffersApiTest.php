<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class OffersApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_multi_calls_offers_root(): void {
		$params = array( 'destination' => 'Mykonos' );
		$client = $this->client_expecting_get( '/offers', $params, array( 'offers' => array() ) );
		$api    = new \WH_Offers_API( $client );
		$this->assertSame( array( 'offers' => array() ), $api->multi( $params ) );
	}

	public function test_single_calls_offers_code(): void {
		$params = array();
		$client = $this->client_expecting_get( '/offers/DEMO', $params, array( 'offers' => array() ) );
		$api    = new \WH_Offers_API( $client );
		$this->assertSame( array( 'offers' => array() ), $api->single( 'DEMO', $params ) );
	}

	public function test_info_calls_offers_code_offerid(): void {
		$client = $this->client_expecting_get( '/offers/DEMO/77', array(), array( 'id' => 77 ) );
		$api    = new \WH_Offers_API( $client );
		$this->assertSame( array( 'id' => 77 ), $api->info( 'DEMO', 77 ) );
	}
}
