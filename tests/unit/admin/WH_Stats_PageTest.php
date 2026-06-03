<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_Stats_PageTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is used here (Patchwork
		// cannot redefine the already-defined is_wp_error()).
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
	}

	private function make_page( $stats ) {
		$settings = Mockery::mock( 'WH_Settings' );
		$settings->shouldReceive( 'get' )->with( 'default_property', Mockery::any() )->andReturn( 'DEMO' );
		$settings->shouldReceive( 'get' )->andReturn( '' );
		return new \WH_Stats_Page( $stats, $settings );
	}

	public function test_resolve_range_defaults_to_last_30_days() {
		$stats = Mockery::mock( 'WH_Stats_API' );
		$page  = $this->make_page( $stats );

		$range = $page->resolve_range( array() );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $range['from'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $range['to'] );
		$this->assertLessThanOrEqual( $range['to'], $range['from'] );
	}

	public function test_resolve_range_accepts_valid_inputs_and_rejects_garbage() {
		$stats = Mockery::mock( 'WH_Stats_API' );
		$page  = $this->make_page( $stats );

		$range = $page->resolve_range( array( 'from' => '2026-01-01', 'to' => '2026-02-01' ) );
		$this->assertSame( '2026-01-01', $range['from'] );
		$this->assertSame( '2026-02-01', $range['to'] );

		$bad = $page->resolve_range( array( 'from' => 'xx', 'to' => '2026-02-01' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $bad['from'], 'garbage from replaced by default' );
	}

	public function test_render_calls_three_endpoints_and_outputs_charts() {
		$has_dates = Mockery::on( function ( $p ) {
			return isset( $p['date_from'], $p['date_to'] );
		} );
		$stats = Mockery::mock( 'WH_Stats_API' );
		$stats->shouldReceive( 'summary' )->once()->with( 'DEMO', $has_dates )->andReturn( array( 'revenue' => 12000, 'bookings' => 24, 'roomnights' => 120, 'adr' => 100 ) );
		$stats->shouldReceive( 'per_day' )->once()->with( 'DEMO', $has_dates )->andReturn( array(
			'data' => array(
				array( 'date' => '2026-08-01', 'revenue' => 100 ),
				array( 'date' => '2026-08-02', 'revenue' => 250 ),
			),
		) );
		$stats->shouldReceive( 'per_country' )->once()->with( 'DEMO', $has_dates )->andReturn( array(
			'data' => array(
				array( 'country' => 'GB', 'revenue' => 800 ),
				array( 'country' => 'DE', 'revenue' => 400 ),
			),
		) );

		$page = $this->make_page( $stats );
		$this->stub_capability( true );

		ob_start();
		$page->render( array( 'from' => '2026-08-01', 'to' => '2026-08-31' ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( '12000', $html, 'summary revenue shown' );
		$this->assertStringContainsString( '<svg', $html, 'per-day chart' );
		$this->assertStringContainsString( 'GB', $html, 'per-country label' );
		$this->assertStringContainsString( '2026-08-01', $html, 'range echoed' );
	}

	public function test_render_shows_error_on_summary_failure() {
		$stats = Mockery::mock( 'WH_Stats_API' );
		$stats->shouldReceive( 'summary' )->once()->andReturn( new \WP_Error( 'NOT_ALLOWED', 'No stats access.' ) );
		$stats->shouldReceive( 'per_day' )->andReturn( array( 'data' => array() ) );
		$stats->shouldReceive( 'per_country' )->andReturn( array( 'data' => array() ) );

		$page = $this->make_page( $stats );
		$this->stub_capability( true );

		ob_start();
		$page->render( array() );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No stats access.', $html );
	}
}
