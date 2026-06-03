<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';

final class PublicCssTest extends WH_Public_TestCase {

	public function test_css_exists_and_defines_core_classes(): void {
		$path = dirname( __DIR__, 3 ) . '/public/assets/css/public.css';
		$this->assertFileExists( $path );
		$css = (string) file_get_contents( $path );
		foreach ( array( '.wh-rate-card', '.wh-search-form', '.wh-notice', '.wh-booking-flow', '.wh-btn' ) as $sel ) {
			$this->assertStringContainsString( $sel, $css, 'Missing selector ' . $sel );
		}
	}
}
