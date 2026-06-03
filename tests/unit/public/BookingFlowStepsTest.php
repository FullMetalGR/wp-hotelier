<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-booking-flow.php';

use Brain\Monkey\Functions;

final class BookingFlowStepsTest extends WH_Public_TestCase {

	public function test_defaults_to_search_step(): void {
		$flow = new WH_Booking_Flow( $this->mock_settings() );
		$this->assertSame( 'search', $flow->resolve_step( array() ) );
	}

	public function test_unknown_step_falls_back_to_search(): void {
		$flow = new WH_Booking_Flow( $this->mock_settings() );
		$this->assertSame( 'search', $flow->resolve_step( array( 'wh_step' => 'bogus' ) ) );
	}

	public function test_valid_step_passes_through(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		$flow = new WH_Booking_Flow( $this->mock_settings() );
		// Each step must carry the prerequisites the guard rails require:
		// results+ need dates, and review+ need a selected rate and valid nonce.
		$base = array(
			'checkin'  => '2026-08-01',
			'checkout' => '2026-08-08',
			'rate'     => '522770',
			'wh_nonce' => 'good',
		);
		foreach ( array( 'search', 'results', 'review', 'complete', 'confirmation' ) as $step ) {
			$this->assertSame(
				$step,
				$flow->resolve_step( array_merge( $base, array( 'wh_step' => $step ) ) )
			);
		}
	}

	public function test_transition_to_results_requires_dates(): void {
		$flow = new WH_Booking_Flow( $this->mock_settings() );
		// Missing dates -> bounce back to search.
		$this->assertSame( 'search', $flow->resolve_step( array( 'wh_step' => 'results' ) ) );
		// With dates -> allowed.
		$this->assertSame(
			'results',
			$flow->resolve_step(
				array( 'wh_step' => 'results', 'checkin' => '2026-08-01', 'checkout' => '2026-08-08' )
			)
		);
	}

	public function test_review_and_beyond_require_nonce(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( false );
		$flow  = new WH_Booking_Flow( $this->mock_settings() );
		$query = array(
			'wh_step'  => 'review',
			'checkin'  => '2026-08-01',
			'checkout' => '2026-08-08',
			'rate'     => '522770',
			'wh_nonce' => 'bad',
		);
		// Bad nonce -> bounce to results (last safe step).
		$this->assertSame( 'results', $flow->resolve_step( $query ) );
	}

	public function test_review_allowed_with_valid_nonce_and_rate(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		$flow  = new WH_Booking_Flow( $this->mock_settings() );
		$query = array(
			'wh_step'  => 'review',
			'checkin'  => '2026-08-01',
			'checkout' => '2026-08-08',
			'rate'     => '522770',
			'wh_nonce' => 'good',
		);
		$this->assertSame( 'review', $flow->resolve_step( $query ) );
	}
}
