<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_EXP_Halt extends \Exception {
	public $success; public $payload; public $status;
	public function __construct( $success, $payload, $status = null ) {
		parent::__construct( 'json' );
		$this->success = $success; $this->payload = $payload; $this->status = $status;
	}
}

class WH_Explorer_RunTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias( function ( $t ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $t ) ); } );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is needed here (Patchwork
		// cannot redefine the already-defined is_wp_error()). We therefore use the
		// real WP_Error double in place of the plan's WH_Fake_WP_Error; behaviour
		// (get_error_code/get_error_message) is identical.
		Functions\when( 'wp_json_encode' )->alias( function ( $d, $o = 0 ) { return json_encode( $d, $o ); } );
		Functions\when( 'wp_send_json_success' )->alias( function ( $p = null ) { throw new WH_EXP_Halt( true, $p ); } );
		Functions\when( 'wp_send_json_error' )->alias( function ( $p = null, $s = null ) { throw new WH_EXP_Halt( false, $p, $s ); } );
	}

	private function registry() {
		$endpoints = Mockery::mock( 'WH_Endpoints' );
		$endpoints->shouldReceive( 'get' )->with( 'property.info' )->andReturn( array(
			'key' => 'property.info', 'label' => 'Property info', 'method' => 'GET',
			'path_template' => '/property/{code}', 'params' => array( 'code', 'include' ), 'category' => 'Property',
		) );
		$endpoints->shouldReceive( 'get' )->with( 'nope' )->andReturn( null );
		return $endpoints;
	}

	private function make_page( $client ) {
		return new \WH_Explorer_Page( $this->registry(), $client );
	}

	public function test_rejects_without_capability() {
		$client = Mockery::mock( 'WH_Client' );
		$client->shouldReceive( 'request' )->never();
		$page = $this->make_page( $client );
		$this->stub_capability( false );
		$this->stub_nonce_valid( true );

		try {
			$page->ajax_run( array( 'endpoint' => 'property.info', 'nonce' => 'x' ) );
			$this->fail( 'expected halt' );
		} catch ( WH_EXP_Halt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 403, $e->status );
		}
	}

	public function test_rejects_bad_nonce() {
		$client = Mockery::mock( 'WH_Client' );
		$client->shouldReceive( 'request' )->never();
		$page = $this->make_page( $client );
		$this->stub_capability( true );
		$this->stub_nonce_valid( false );

		try {
			$page->ajax_run( array( 'endpoint' => 'property.info', 'nonce' => 'x' ) );
			$this->fail( 'expected halt' );
		} catch ( WH_EXP_Halt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 403, $e->status );
		}
	}

	public function test_unknown_endpoint_errors() {
		$client = Mockery::mock( 'WH_Client' );
		$client->shouldReceive( 'request' )->never();
		$page = $this->make_page( $client );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->ajax_run( array( 'endpoint' => 'nope', 'nonce' => 'x' ) );
			$this->fail( 'expected halt' );
		} catch ( WH_EXP_Halt $e ) {
			$this->assertFalse( $e->success );
		}
	}

	public function test_runs_get_resolves_path_and_returns_pretty_json() {
		$client = Mockery::mock( 'WH_Client' );
		$client->shouldReceive( 'request' )
			->once()
			->with(
				'GET',
				'/property/DEMO',
				Mockery::on( function ( $args ) {
					// 'code' consumed by path; 'include' remains as query arg.
					return ! isset( $args['code'] ) && isset( $args['include'] ) && 'photos' === $args['include'];
				} ),
				Mockery::any()
			)
			->andReturn( array( 'code' => 'DEMO', 'name' => 'Demo Hotel' ) );

		$page = $this->make_page( $client );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->ajax_run( array(
				'endpoint' => 'property.info',
				'nonce'    => 'x',
				'params'   => array( 'code' => 'DEMO', 'include' => 'photos' ),
			) );
			$this->fail( 'expected halt' );
		} catch ( WH_EXP_Halt $e ) {
			$this->assertTrue( $e->success );
			$this->assertSame( 'GET', $e->payload['method'] );
			$this->assertSame( '/property/DEMO', $e->payload['url'] );
			$this->assertIsString( $e->payload['json'] );
			$this->assertStringContainsString( 'Demo Hotel', $e->payload['json'] );
			$this->assertArrayHasKey( 'ms', $e->payload );
			$this->assertIsNumeric( $e->payload['ms'] );
		}
	}

	public function test_runs_and_surfaces_wp_error() {
		$client = Mockery::mock( 'WH_Client' );
		$client->shouldReceive( 'request' )->once()->andReturn( new \WP_Error( 'NOT_FOUND', 'Nope.' ) );
		$page = $this->make_page( $client );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->ajax_run( array( 'endpoint' => 'property.info', 'nonce' => 'x', 'params' => array( 'code' => 'X' ) ) );
			$this->fail( 'expected halt' );
		} catch ( WH_EXP_Halt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 'NOT_FOUND', $e->payload['error_code'] );
			$this->assertSame( '/property/X', $e->payload['url'], 'resolved URL still reported on error' );
		}
	}
}
