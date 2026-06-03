<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';

final class PublicJsExistsTest extends WH_Public_TestCase {

	private function js(): string {
		$path = dirname( __DIR__, 3 ) . '/public/assets/js/public.js';
		$this->assertFileExists( $path );
		return (string) file_get_contents( $path );
	}

	public function test_bundle_reads_proxy_config(): void {
		$js = $this->js();
		$this->assertStringContainsString( 'WH_Public', $js );
		$this->assertStringContainsString( 'X-WP-Nonce', $js );
	}

	public function test_bundle_handles_search_lookup_and_handoff(): void {
		$js = $this->js();
		$this->assertStringContainsString( 'data-wh-search', $js );
		$this->assertStringContainsString( 'data-wh-lookup', $js );
		$this->assertStringContainsString( 'data-wh-handoff', $js );
		$this->assertStringContainsString( '/availability', $js );
		$this->assertStringContainsString( '/lookup', $js );
	}
}
