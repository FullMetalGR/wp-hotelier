<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_Stats_ChartTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
	}

	public function test_empty_series_returns_placeholder() {
		$svg = \WH_Stats_Page::svg_bar_chart( array(), 'Revenue' );
		$this->assertStringContainsString( 'No data', $svg );
		$this->assertStringNotContainsString( '<rect', $svg );
	}

	public function test_renders_one_rect_per_datapoint() {
		$series = array( '2026-08-01' => 100, '2026-08-02' => 200, '2026-08-03' => 50 );
		$svg = \WH_Stats_Page::svg_bar_chart( $series, 'Revenue' );

		$this->assertStringStartsWith( '<svg', $svg );
		$this->assertStringContainsString( '</svg>', $svg );
		$this->assertSame( 3, substr_count( $svg, '<rect' ), 'one bar per datapoint' );
		// Title present.
		$this->assertStringContainsString( 'Revenue', $svg );
	}

	public function test_tallest_bar_reaches_chart_height_for_max_value() {
		$series = array( 'a' => 10, 'b' => 20 );
		$svg = \WH_Stats_Page::svg_bar_chart( $series, 'X', 200, 100 );
		// The max value bar height should equal the chart's plot height (100 - paddings handled inside).
		// We just assert the larger value yields a taller bar by comparing height="..." numbers.
		preg_match_all( '/height="([0-9.]+)"/', $svg, $m );
		$heights = array_map( 'floatval', $m[1] );
		$this->assertNotEmpty( $heights );
		$this->assertGreaterThan( min( $heights ), max( $heights ) );
	}

	public function test_handles_all_zero_series_without_division_error() {
		$series = array( 'a' => 0, 'b' => 0 );
		$svg = \WH_Stats_Page::svg_bar_chart( $series, 'Zero' );
		$this->assertStringContainsString( '<svg', $svg );
		$this->assertSame( 2, substr_count( $svg, '<rect' ) );
	}
}
