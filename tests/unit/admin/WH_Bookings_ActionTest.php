<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

/**
 * Halt exception simulating wp_die() / wp_safe_redirect()+exit.
 */
class WH_BA_Halt extends \Exception {
	public $kind; public $data;
	public function __construct( $kind, $data = null ) { parent::__construct( 'halt' ); $this->kind = $kind; $this->data = $data; }
}

class WH_Bookings_ActionTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is needed here (Patchwork
		// cannot redefine the already-defined is_wp_error()).
		Functions\when( 'wp_die' )->alias( function ( $msg = '' ) { throw new WH_BA_Halt( 'die', $msg ); } );
		Functions\when( 'wp_safe_redirect' )->alias( function ( $url ) { throw new WH_BA_Halt( 'redirect', $url ); } );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'add_query_arg' )->alias( function ( $args, $url = '' ) { return $url . '?' . http_build_query( (array) $args ); } );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	}

	private function make_page( $bookings ) {
		return new \WH_Bookings_Page( $bookings );
	}

	public function test_action_rejects_bad_nonce() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'cancel' )->never();
		$page = $this->make_page( $bookings );

		$this->stub_capability( true );
		$this->stub_nonce_valid( false );

		$this->expectException( WH_BA_Halt::class );
		$page->handle_action( array( 'wh_action' => 'cancel', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
	}

	public function test_action_rejects_without_capability() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'cancel' )->never();
		$page = $this->make_page( $bookings );

		$this->stub_capability( false );
		$this->stub_nonce_valid( true );

		$this->expectException( WH_BA_Halt::class );
		$page->handle_action( array( 'wh_action' => 'cancel', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
	}

	public function test_cancel_calls_api_and_redirects_with_success() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'cancel' )->once()->with( 'R1' )->andReturn( array( 'res_id' => 'R1', 'status' => 'cancelled' ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'cancel', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertSame( 'redirect', $e->kind );
			$this->assertStringContainsString( 'wh_notice=cancel_ok', $e->data );
		}
	}

	public function test_confirmation_email_calls_api() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'confirmation_email' )->once()->with( array( 'res_id' => 'R1' ) )->andReturn( array( 'sent' => true ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'confirmation_email', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=email_ok', $e->data );
		}
	}

	public function test_mark_synced_calls_api() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'mark_synced' )->once()->with( 'R1' )->andReturn( array( 'synced' => true ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'mark_synced', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=sync_ok', $e->data );
		}
	}

	public function test_purge_requires_confirm_flag() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'purge' )->never();
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		// Missing confirm_purge => must NOT call purge; redirect with guard notice.
		try {
			$page->handle_action( array( 'wh_action' => 'purge', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=purge_guard', $e->data );
		}
	}

	public function test_purge_executes_with_confirm_flag() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'purge' )->once()->with( 'R1' )->andReturn( array( 'purged' => true ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'purge', 'res_id' => 'R1', 'confirm_purge' => '1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=purge_ok', $e->data );
		}
	}

	public function test_api_error_redirects_with_error_notice() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'cancel' )->once()->andReturn( new \WP_Error( 'NOT_ALLOWED', 'Cannot cancel.' ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'cancel', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=error', $e->data );
			$this->assertStringContainsString( 'wh_err=NOT_ALLOWED', $e->data );
		}
	}

	public function test_unknown_action_redirects_with_error() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'frobnicate', 'res_id' => 'R1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_BA_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=error', $e->data );
		}
	}
}
