<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_Explorer_FormTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( function ( $d, $opts = 0, $depth = 512 ) { return json_encode( $d, $opts, $depth ); } );
	}

	private function registry() {
		$endpoints = Mockery::mock( 'WH_Endpoints' );
		$endpoints->shouldReceive( 'all' )->andReturn( array(
			array( 'key' => 'property.info', 'label' => 'Property info', 'method' => 'GET', 'path_template' => '/property/{code}', 'params' => array( 'code', 'include' ), 'category' => 'Property' ),
			array( 'key' => 'availability.single', 'label' => 'Availability', 'method' => 'GET', 'path_template' => '/availability/{code}', 'params' => array( 'code', 'checkin', 'checkout', 'adults' ), 'category' => 'Availability' ),
			array( 'key' => 'bookings.create', 'label' => 'Create booking', 'method' => 'POST', 'path_template' => '/book/{code}', 'params' => array( 'code', 'rateID' ), 'category' => 'Bookings' ),
		) );
		return $endpoints;
	}

	private function make_page( $endpoints ) {
		$client = Mockery::mock( 'WH_Client' );
		return new \WH_Explorer_Page( $endpoints, $client );
	}

	public function test_render_lists_endpoints_grouped_by_category() {
		$page = $this->make_page( $this->registry() );
		$this->stub_capability( true );

		ob_start();
		$page->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'property.info', $html );
		$this->assertStringContainsString( 'availability.single', $html );
		$this->assertStringContainsString( 'bookings.create', $html );
		// Categories appear as optgroups.
		$this->assertStringContainsString( 'Property', $html );
		$this->assertStringContainsString( 'Availability', $html );
		$this->assertStringContainsString( 'Bookings', $html );
		// Method + path template surfaced for JS form-building.
		$this->assertStringContainsString( '/property/{code}', $html );
		$this->assertStringContainsString( 'data-params', $html );
		// Run button + nonce.
		$this->assertStringContainsString( 'wh-explorer-run', $html );
	}

	public function test_endpoints_json_emitted_for_js() {
		$page = $this->make_page( $this->registry() );
		$this->stub_capability( true );

		ob_start();
		$page->render();
		$html = ob_get_clean();

		// A JSON blob of the registry is embedded for the JS form builder.
		$this->assertStringContainsString( 'wh-explorer-registry', $html );
		$this->assertStringContainsString( '"path_template":"\/property\/{code}"', $html );
	}
}
