<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

/**
 * Exception thrown by our wp_send_json_* stubs to halt execution like WP does.
 */
class WH_TC_JsonHalt extends \Exception {
	public $payload;
	public $status;
	public $success;
	public function __construct( $success, $payload, $status = null ) {
		parent::__construct( 'json-halt' );
		$this->success = $success;
		$this->payload = $payload;
		$this->status  = $status;
	}
}

class WH_TestConnectionTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_send_json_success' )->alias( function ( $payload = null ) {
			throw new WH_TC_JsonHalt( true, $payload );
		} );
		Functions\when( 'wp_send_json_error' )->alias( function ( $payload = null, $status = null ) {
			throw new WH_TC_JsonHalt( false, $payload, $status );
		} );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is needed here.
	}

	private function make_page( $mode, $property ) {
		$settings = Mockery::mock( 'WH_Settings' );
		$settings->shouldReceive( 'get' )->with( 'default_property', Mockery::any() )->andReturn( 'DEMO' );
		$settings->shouldReceive( 'get' )->with( 'mode', Mockery::any() )->andReturn( $mode );
		$settings->shouldReceive( 'get' )->andReturn( '' ); // any other key
		return new \WH_Settings_Page( $settings, $property, $mode );
	}

	public function test_rejects_when_capability_missing() {
		$property = Mockery::mock( 'WH_Property_API' );
		$page = $this->make_page( 'single', $property );
		$this->stub_nonce_valid( true );
		$this->stub_capability( false );

		try {
			$page->ajax_test_connection();
			$this->fail( 'expected halt' );
		} catch ( WH_TC_JsonHalt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 403, $e->status );
		}
	}

	public function test_rejects_when_nonce_invalid() {
		$property = Mockery::mock( 'WH_Property_API' );
		$page = $this->make_page( 'single', $property );
		$this->stub_nonce_valid( false );
		$this->stub_capability( true );

		try {
			$page->ajax_test_connection();
			$this->fail( 'expected halt' );
		} catch ( WH_TC_JsonHalt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 403, $e->status );
		}
	}

	public function test_single_mode_probes_property_info_and_returns_account() {
		$property = Mockery::mock( 'WH_Property_API' );
		$property->shouldReceive( 'info' )
			->once()
			->with( 'DEMO' )
			->andReturn( array(
				'code'     => 'DEMO',
				'name'     => 'Demo Hotel',
				'type'     => 'Villas',
				'currency' => 'EUR',
			) );

		$page = $this->make_page( 'single', $property );
		$this->stub_nonce_valid( true );
		$this->stub_capability( true );

		try {
			$page->ajax_test_connection();
			$this->fail( 'expected halt' );
		} catch ( WH_TC_JsonHalt $e ) {
			$this->assertTrue( $e->success );
			$this->assertSame( 'single', $e->payload['mode'] );
			$this->assertSame( 'DEMO', $e->payload['property']['code'] );
			$this->assertSame( 'Demo Hotel', $e->payload['property']['name'] );
		}
	}

	public function test_multi_mode_probes_property_search() {
		$property = Mockery::mock( 'WH_Property_API' );
		$property->shouldReceive( 'search' )
			->once()
			->with( array() )
			->andReturn( array(
				'count' => 3,
				'data'  => array( array( 'code' => 'A' ), array( 'code' => 'B' ), array( 'code' => 'C' ) ),
			) );

		$page = $this->make_page( 'multi', $property );
		$this->stub_nonce_valid( true );
		$this->stub_capability( true );

		try {
			$page->ajax_test_connection();
			$this->fail( 'expected halt' );
		} catch ( WH_TC_JsonHalt $e ) {
			$this->assertTrue( $e->success );
			$this->assertSame( 'multi', $e->payload['mode'] );
			$this->assertArrayHasKey( 'property_count', $e->payload );
		}
	}

	public function test_returns_error_code_on_wp_error() {
		// Use the repo's WP_Error double (tests/unit/wp-stubs.php); is_wp_error()
		// already recognises it.
		$err = new \WP_Error( 'INVALID_AUTH', 'The credentials are invalid.' );

		$property = Mockery::mock( 'WH_Property_API' );
		$property->shouldReceive( 'info' )->once()->andReturn( $err );

		$page = $this->make_page( 'single', $property );
		$this->stub_nonce_valid( true );
		$this->stub_capability( true );

		try {
			$page->ajax_test_connection();
			$this->fail( 'expected halt' );
		} catch ( WH_TC_JsonHalt $e ) {
			$this->assertFalse( $e->success );
			$this->assertSame( 'INVALID_AUTH', $e->payload['error_code'] );
			$this->assertSame( 'The credentials are invalid.', $e->payload['error_msg'] );
		}
	}
}
