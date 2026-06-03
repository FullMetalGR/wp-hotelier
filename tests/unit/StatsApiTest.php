<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class StatsApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_summary_gets_performance_summary_code(): void {
		$params = array( 'date_from' => '2026-01-01', 'date_to' => '2026-12-31' );
		$client = $this->client_expecting_get( '/statistics/performance_summary/DEMO', $params, array( 'revenue' => 1 ) );
		$api    = new \WH_Stats_API( $client );
		$this->assertSame( array( 'revenue' => 1 ), $api->summary( 'DEMO', $params ) );
	}

	public function test_per_day_gets_performance_per_day_code(): void {
		$params = array( 'date_from' => '2026-01-01', 'date_to' => '2026-01-31' );
		$client = $this->client_expecting_get( '/statistics/performance_per_day/DEMO', $params, array( 'days' => array() ) );
		$api    = new \WH_Stats_API( $client );
		$this->assertSame( array( 'days' => array() ), $api->per_day( 'DEMO', $params ) );
	}

	public function test_per_country_gets_performance_per_country_code(): void {
		$params = array( 'date_from' => '2026-01-01', 'date_to' => '2026-12-31' );
		$client = $this->client_expecting_get( '/statistics/performance_per_country/DEMO', $params, array( 'countries' => array() ) );
		$api    = new \WH_Stats_API( $client );
		$this->assertSame( array( 'countries' => array() ), $api->per_country( 'DEMO', $params ) );
	}
}
