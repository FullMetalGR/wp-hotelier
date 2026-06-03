<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';

final class CalendarMapJsTest extends WH_Public_TestCase {

	public function test_calendar_js_exists_and_fetches_calendar(): void {
		$path = dirname( __DIR__, 3 ) . '/public/assets/js/calendar.js';
		$this->assertFileExists( $path );
		$js = (string) file_get_contents( $path );
		$this->assertStringContainsString( 'data-wh-calendar', $js );
		$this->assertStringContainsString( '/calendar', $js );
	}

	public function test_calendar_js_requests_fromd_tod_not_months(): void {
		$path = dirname( __DIR__, 3 ) . '/public/assets/js/calendar.js';
		$js   = (string) file_get_contents( $path );
		$this->assertStringContainsString( 'fromd', $js );
		$this->assertStringContainsString( 'tod', $js );
		$this->assertStringContainsString( 'data-fromd', $js );
		$this->assertStringContainsString( 'data-tod', $js );
		// The broken month(s) param must be gone.
		$this->assertStringNotContainsString( "'months'", $js );
		$this->assertStringNotContainsString( 'data-months', $js );
	}

	public function test_calendar_js_escapes_api_values_before_innerhtml(): void {
		$path = dirname( __DIR__, 3 ) . '/public/assets/js/calendar.js';
		$js   = (string) file_get_contents( $path );
		// A textContent-based esc() helper must exist and be applied to values.
		$this->assertStringContainsString( 'function esc(', $js );
		$this->assertStringContainsString( 'textContent', $js );
		$this->assertStringContainsString( 'esc( row.date )', $js );
		$this->assertStringContainsString( "esc( currency + ' ' + row.price )", $js );
		// No raw API value (date/price) may be concatenated straight into markup.
		$this->assertStringNotContainsString( "data-date=\"' + date +", $js );
		$this->assertStringNotContainsString( "+ date +", $js );
		$this->assertStringNotContainsString( "+ d.price", $js );
	}

	public function test_map_js_exists_and_inits_leaflet(): void {
		$path = dirname( __DIR__, 3 ) . '/public/assets/js/map.js';
		$this->assertFileExists( $path );
		$js = (string) file_get_contents( $path );
		$this->assertStringContainsString( 'data-wh-map', $js );
		$this->assertStringContainsString( 'L.map', $js );
	}
}
