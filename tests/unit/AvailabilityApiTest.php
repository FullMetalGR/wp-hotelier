<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class AvailabilityApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_single_calls_availability_code(): void {
		$params = array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'adults' => 4 );
		$client = $this->client_expecting_get( '/availability/DEMO', $params, array( 'rates' => array() ) );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array( 'rates' => array() ), $api->single( 'DEMO', $params ) );
	}

	public function test_multi_calls_availability_root(): void {
		$params = array( 'destination' => 'Mykonos' );
		$client = $this->client_expecting_get( '/availability', $params, array( 'results' => array() ) );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array( 'results' => array() ), $api->multi( $params ) );
	}

	public function test_breakdown_calls_breakdown_path(): void {
		$params = array( 'rate' => 522770 );
		$client = $this->client_expecting_get( '/availability/DEMO/breakdown', $params, array() );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array(), $api->breakdown( 'DEMO', $params ) );
	}

	public function test_calendar_calls_calendar_path(): void {
		// Live calendar requires fromd/tod (not month); the resource just passes
		// params through to the client GET.
		$params = array( 'fromd' => '2026-08-01', 'tod' => '2026-08-31' );
		$client = $this->client_expecting_get( '/availability/DEMO/calendar', $params, array() );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array(), $api->calendar( 'DEMO', $params ) );
	}

	public function test_flexible_calendar_calls_flexible_calendar_path(): void {
		$params = array( 'month' => '2026-08', 'nights' => 7 );
		$client = $this->client_expecting_get( '/availability/DEMO/flexible-calendar', $params, array() );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array(), $api->flexible_calendar( 'DEMO', $params ) );
	}

	public function test_bar_calls_bar_code(): void {
		$params = array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08' );
		$client = $this->client_expecting_get( '/bar/DEMO', $params, array( 'price' => 14000 ) );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array( 'price' => 14000 ), $api->bar( 'DEMO', $params ) );
	}

	public function test_extras_calls_extras_with_rateid(): void {
		$params = array( 'checkin' => '2026-08-01', 'checkout' => '2026-08-08' );
		$client = $this->client_expecting_get( '/availability/DEMO/extras/522770', $params, array() );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( array(), $api->extras( 'DEMO', 522770, $params ) );
	}

	public function test_ical_calls_ical_path(): void {
		$client = $this->client_expecting_get( '/ical/DEMO-ZEN.ics', array(), 'BEGIN:VCALENDAR' );
		$api    = new \WH_Availability_API( $client );
		$this->assertSame( 'BEGIN:VCALENDAR', $api->ical( 'DEMO', 'ZEN' ) );
	}
}
