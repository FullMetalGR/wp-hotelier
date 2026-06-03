<?php
namespace WH\Tests\Admin;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_AssetsTest extends WH_Admin_TestCase {

	private function admin_dir() {
		// tests/unit/admin -> plugin root /admin
		return dirname( __DIR__, 3 ) . '/admin';
	}

	public function test_css_file_exists_and_has_expected_selectors() {
		$css = $this->admin_dir() . '/assets/css/admin.css';
		$this->assertFileExists( $css );
		$contents = file_get_contents( $css );
		$this->assertStringContainsString( '.wh-admin', $contents );
		$this->assertStringContainsString( '.wh-chart', $contents );
		$this->assertStringContainsString( '.wh-explorer-output', $contents );
	}

	public function test_js_file_exists_and_references_actions() {
		$js = $this->admin_dir() . '/assets/js/admin.js';
		$this->assertFileExists( $js );
		$contents = file_get_contents( $js );
		// Wires both AJAX actions and reads localized config.
		$this->assertStringContainsString( 'wh_test_connection', $contents );
		$this->assertStringContainsString( 'wh_explorer_run', $contents );
		$this->assertStringContainsString( 'WHAdmin', $contents );
		$this->assertStringContainsString( 'wh-explorer-registry', $contents );
	}

	public function test_js_passes_node_syntax_check_when_node_available() {
		$js = $this->admin_dir() . '/assets/js/admin.js';
		$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
		if ( '' === $node ) {
			$this->markTestSkipped( 'node not available for JS syntax check' );
		}
		$out = array();
		$code = 0;
		exec( escapeshellarg( $node ) . ' --check ' . escapeshellarg( $js ) . ' 2>&1', $out, $code );
		$this->assertSame( 0, $code, 'admin.js must pass node --check: ' . implode( "\n", $out ) );
	}
}
