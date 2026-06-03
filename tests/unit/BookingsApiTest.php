<?php

namespace WH\Tests\Unit;

use WH\Tests\Unit\Support\ClientSpy;

final class BookingsApiTest extends WH_UnitTestCase {

	use ClientSpy;

	public function test_create_posts_to_book_code(): void {
		$params = array( 'rate' => 522770, 'payment_method' => 'CHKIN' );
		$client = $this->client_expecting_post( '/book/DEMO', $params, array( 'res_id' => 'R1' ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'res_id' => 'R1' ), $api->create( 'DEMO', $params ) );
	}

	public function test_purge_gets_purge_resid(): void {
		$client = $this->client_expecting_get( '/purge/R1', array(), array( 'purged' => true ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'purged' => true ), $api->purge( 'R1' ) );
	}

	public function test_cancel_posts_to_reservation_cancel_resid(): void {
		$client = $this->client_expecting_post( '/reservation/cancel/R1', array(), array( 'cancelled' => true ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'cancelled' => true ), $api->cancel( 'R1' ) );
	}

	public function test_confirmation_email_posts_params(): void {
		$params = array( 'res_id' => 'R1', 'email' => 'guest@example.com' );
		$client = $this->client_expecting_post( '/reservation/confirmation_email', $params, array( 'sent' => true ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'sent' => true ), $api->confirmationEmail( $params ) );
	}

	public function test_search_gets_reservation_with_params(): void {
		$params = array( 'status' => 'confirmed' );
		$client = $this->client_expecting_get( '/reservation', $params, array( 'results' => array() ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'results' => array() ), $api->search( $params ) );
	}

	public function test_retrieve_gets_reservation_resid(): void {
		$client = $this->client_expecting_get( '/reservation/R1', array(), array( 'res_id' => 'R1' ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'res_id' => 'R1' ), $api->retrieve( 'R1' ) );
	}

	public function test_pending_gets_reservation_new(): void {
		$client = $this->client_expecting_get( '/reservation/new', array(), array( 'pending' => array() ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'pending' => array() ), $api->pending() );
	}

	public function test_mark_synced_gets_reservation_sync_resid(): void {
		$client = $this->client_expecting_get( '/reservation/sync/R1', array(), array( 'synced' => true ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'synced' => true ), $api->markSynced( 'R1' ) );
	}

	public function test_push_ping_posts_params(): void {
		$params = array( 'channel' => 'booking.com' );
		$client = $this->client_expecting_post( '/push/ping', $params, array( 'pong' => true ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'pong' => true ), $api->pushPing( $params ) );
	}

	public function test_sources_gets_sources(): void {
		$client = $this->client_expecting_get( '/sources', array(), array( 'sources' => array() ) );
		$api    = new \WH_Bookings_API( $client );
		$this->assertSame( array( 'sources' => array() ), $api->sources() );
	}
}
