<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_SY_Halt extends \Exception {
	public $kind; public $data;
	public function __construct( $kind, $data = null ) { parent::__construct( 'halt' ); $this->kind = $kind; $this->data = $data; }
}

class WH_Sync_PageTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is needed here (Patchwork
		// cannot redefine the already-defined is_wp_error()). We therefore use the
		// real WP_Error double in place of the plan's WH_Fake_WP_Error; behaviour
		// (get_error_code/get_error_message) is identical.
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'add_query_arg' )->alias( function ( $a, $u = '' ) { return $u . '?' . http_build_query( (array) $a ); } );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_die' )->alias( function ( $m = '' ) { throw new WH_SY_Halt( 'die', $m ); } );
		Functions\when( 'wp_safe_redirect' )->alias( function ( $u ) { throw new WH_SY_Halt( 'redirect', $u ); } );
	}

	private function make_page( $bookings ) {
		return new \WH_Sync_Page( $bookings );
	}

	public function test_render_lists_pending_and_sources() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'pending' )->once()->andReturn( array(
			'data' => array(
				array( 'res_id' => 'P1', 'lastName' => 'Alpha', 'checkin' => '2026-08-01' ),
				array( 'res_id' => 'P2', 'lastName' => 'Beta', 'checkin' => '2026-08-05' ),
			),
		) );
		// Live /sources returns an OBJECT envelope: { sources: [ {id,name,...} ] }.
		$bookings->shouldReceive( 'sources' )->once()->andReturn( array(
			'sources' => array(
				array( 'is_channel' => 1, 'id' => 926, 'is_public' => 0, 'name' => '18-24 Travel', 'parent_id' => null ),
				array( 'is_channel' => 0, 'id' => 12, 'is_public' => 1, 'name' => 'Website', 'parent_id' => null ),
			),
		) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );

		ob_start();
		$page->render( array() );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'P1', $html );
		$this->assertStringContainsString( 'Alpha', $html );
		// Source rows render id + name from the { sources: [...] } object shape.
		$this->assertStringContainsString( '926', $html );
		$this->assertStringContainsString( '18-24 Travel', $html );
		$this->assertStringContainsString( 'Website', $html );
		// The previous bug rendered a single empty source row (the bare list was
		// iterated as if it were a row). It must not appear: there is no "empty
		// sources" notice when real rows exist.
		$this->assertStringNotContainsString( 'No sources.', $html );
		// Push/ping tool present.
		$this->assertStringContainsString( 'wh_action', $html );
		$this->assertStringContainsString( 'pushPing', $html );
	}

	public function test_render_handles_pending_error() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'pending' )->once()->andReturn( new \WP_Error( 'NOT_ALLOWED', 'No sync access.' ) );
		$bookings->shouldReceive( 'sources' )->once()->andReturn( array( 'data' => array() ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );

		ob_start();
		$page->render( array() );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No sync access.', $html );
	}

	public function test_action_rejects_bad_nonce() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'markSynced' )->never();
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( false );

		$this->expectException( WH_SY_Halt::class );
		$page->handle_action( array( 'wh_action' => 'markSynced', 'res_id' => 'P1', '_wpnonce' => 'x' ) );
	}

	public function test_action_rejects_without_capability() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'markSynced' )->never();
		$page = $this->make_page( $bookings );
		$this->stub_capability( false );
		$this->stub_nonce_valid( true );

		$this->expectException( WH_SY_Halt::class );
		$page->handle_action( array( 'wh_action' => 'markSynced', 'res_id' => 'P1', '_wpnonce' => 'x' ) );
	}

	public function test_mark_synced_calls_api() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'markSynced' )->once()->with( 'P1' )->andReturn( array( 'synced' => true ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'markSynced', 'res_id' => 'P1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_SY_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=sync_ok', $e->data );
		}
	}

	public function test_push_ping_calls_api_with_params() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'pushPing' )
			->once()
			->with( Mockery::on( function ( $p ) { return isset( $p['res_id'] ) && 'P1' === $p['res_id']; } ) )
			->andReturn( array( 'ping' => 'ok' ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'pushPing', 'res_id' => 'P1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_SY_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=ping_ok', $e->data );
		}
	}

	public function test_action_error_redirects_with_error() {
		$bookings = Mockery::mock( 'WH_Bookings_API' );
		$bookings->shouldReceive( 'markSynced' )->once()->andReturn( new \WP_Error( 'NOT_FOUND', 'Missing.' ) );
		$page = $this->make_page( $bookings );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_action( array( 'wh_action' => 'markSynced', 'res_id' => 'P1', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_SY_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=error', $e->data );
			$this->assertStringContainsString( 'wh_err=NOT_FOUND', $e->data );
		}
	}
}
