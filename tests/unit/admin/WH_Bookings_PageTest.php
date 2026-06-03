<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_Bookings_PageTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is needed here (Patchwork
		// cannot redefine the already-defined is_wp_error()).
	}

	private function make_page( $bookings ) {
		return new \WH_Bookings_Page( $bookings );
	}

	public function test_build_filters_sanitizes_and_allowlists_status() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$page = $this->make_page( $bookings );

		$query = array(
			'checkin_from' => '2026-08-01',
			'checkin_to'   => 'not-a-date',     // dropped
			'status'       => 'confirmed',
			'lastName'     => '  Papas  ',
			'email'        => 'a@b.com',
			'source'       => 'WEBSITE',
			'junk'         => 'ignored',
		);

		$filters = $page->build_filters( $query );

		$this->assertSame( '2026-08-01', $filters['checkin_from'] );
		$this->assertArrayNotHasKey( 'checkin_to', $filters, 'invalid date dropped' );
		$this->assertSame( 'confirmed', $filters['status'] );
		$this->assertSame( 'Papas', $filters['lastName'] );
		$this->assertSame( 'a@b.com', $filters['email'] );
		$this->assertSame( 'WEBSITE', $filters['source'] );
		$this->assertArrayNotHasKey( 'junk', $filters );
	}

	public function test_build_filters_rejects_unknown_status() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$page = $this->make_page( $bookings );
		$filters = $page->build_filters( array( 'status' => 'bogus' ) );
		$this->assertArrayNotHasKey( 'status', $filters );
	}

	public function test_render_search_lists_reservations() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'search' )
			->once()
			->andReturn( array(
				'data' => array(
					array( 'res_id' => 'R1', 'lastName' => 'Papas', 'status' => 'confirmed', 'checkin' => '2026-08-01', 'checkout' => '2026-08-08', 'total' => '1200.00', 'currency' => 'EUR' ),
					array( 'res_id' => 'R2', 'lastName' => 'Jones', 'status' => 'cancelled', 'checkin' => '2026-09-01', 'checkout' => '2026-09-05', 'total' => '800.00', 'currency' => 'EUR' ),
				),
			) );

		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'add_query_arg' )->alias( function ( $args, $url = '' ) { return is_array( $args ) ? $url . '?' . http_build_query( $args ) : $url; } );

		ob_start();
		$page->render( array() ); // no res_id => search view
		$html = ob_get_clean();

		$this->assertStringContainsString( 'R1', $html );
		$this->assertStringContainsString( 'Papas', $html );
		$this->assertStringContainsString( 'R2', $html );
	}

	public function test_render_detail_shows_guest_and_pricing() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )
			->once()
			->with( 'R1' )
			->andReturn( array(
				'res_id'    => 'R1',
				'firstName' => 'Nikos',
				'lastName'  => 'Papas',
				'email'     => 'nikos@example.com',
				'status'    => 'confirmed',
				'room'      => 'ZEN',
				'rate'      => 'Non-refundable',
				'board'     => 'RO',
				'checkin'   => '2026-08-01',
				'checkout'  => '2026-08-08',
				'total'     => '1200.00',
				'currency'  => 'EUR',
				'cancellation_policy' => 'No refund',
				'payment_policy'      => 'Prepay',
			) );

		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'add_query_arg' )->alias( function ( $args, $url = '' ) { return $url; } );

		ob_start();
		$page->render( array( 'res_id' => 'R1' ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Nikos', $html );
		$this->assertStringContainsString( 'Papas', $html );
		$this->assertStringContainsString( '1200.00', $html );
		$this->assertStringContainsString( 'No refund', $html );
		// Action buttons present.
		$this->assertStringContainsString( 'cancel', strtolower( $html ) );
		$this->assertStringContainsString( 'mark', strtolower( $html ) );
	}

	public function test_render_detail_handles_error() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'retrieve' )->once()->andReturn( new \WP_Error( 'NOT_FOUND', 'Reservation not found.' ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'add_query_arg' )->alias( function ( $a, $u = '' ) { return $u; } );

		ob_start();
		$page->render( array( 'res_id' => 'NOPE' ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Reservation not found.', $html );
	}
}
