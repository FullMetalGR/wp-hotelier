<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class PropertyApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_info_calls_property_code(): void {
		$client = $this->client_expecting_get( '/property/DEMO', array(), array( 'name' => 'Demo Hotel' ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'name' => 'Demo Hotel' ), $api->info( 'DEMO' ) );
	}

	public function test_info_passes_include_param(): void {
		$client = $this->client_expecting_get( '/property/DEMO', array( 'include' => 'photos' ), array( 'ok' => 1 ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'ok' => 1 ), $api->info( 'DEMO', 'photos' ) );
	}

	public function test_search_calls_property_root_with_params(): void {
		$client = $this->client_expecting_get( '/property', array( 'destination' => 'Mykonos' ), array( 'results' => array() ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'results' => array() ), $api->search( array( 'destination' => 'Mykonos' ) ) );
	}

	public function test_rooms_calls_room_code(): void {
		$client = $this->client_expecting_get( '/room/DEMO', array(), array( 'rooms' => array() ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'rooms' => array() ), $api->rooms( 'DEMO' ) );
	}

	public function test_room_calls_room_code_room(): void {
		$client = $this->client_expecting_get( '/room/DEMO/ZEN', array(), array( 'name' => 'Villa Zen' ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'name' => 'Villa Zen' ), $api->room( 'DEMO', 'ZEN' ) );
	}

	public function test_rates_without_room_calls_rate_code(): void {
		$client = $this->client_expecting_get( '/rate/DEMO', array(), array() );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array(), $api->rates( 'DEMO' ) );
	}

	public function test_rates_with_room_calls_rate_code_room(): void {
		$client = $this->client_expecting_get( '/rate/DEMO/ZEN', array(), array() );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array(), $api->rates( 'DEMO', 'ZEN' ) );
	}

	public function test_rate_calls_rate_code_room_rateid(): void {
		$client = $this->client_expecting_get( '/rate/DEMO/ZEN/522770', array(), array( 'id' => 522770 ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'id' => 522770 ), $api->rate( 'DEMO', 'ZEN', 522770 ) );
	}

	public function test_extras_calls_extra_code(): void {
		$client = $this->client_expecting_get( '/extra/DEMO', array(), array( 'extras' => array() ) );
		$api    = new \WH_Property_API( $client );
		$this->assertSame( array( 'extras' => array() ), $api->extras( 'DEMO' ) );
	}
}
