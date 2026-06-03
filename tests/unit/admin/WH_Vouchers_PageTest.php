<?php
namespace WH\Tests\Admin;

use Brain\Monkey\Functions;
use Mockery;

require_once __DIR__ . '/WH_Admin_TestCase.php';

class WH_VM_Halt extends \Exception {
	public $kind; public $data;
	public function __construct( $kind, $data = null ) { parent::__construct( 'halt' ); $this->kind = $kind; $this->data = $data; }
}

class WH_Vouchers_PageTest extends WH_Admin_TestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->stub_wp_escaping();
		// is_wp_error() is provided by tests/unit/wp-stubs.php and recognises the
		// repo's WP_Error double, so no Brain Monkey alias is used here (Patchwork
		// cannot redefine the already-defined is_wp_error()).
		Functions\when( 'wp_create_nonce' )->justReturn( 'n' );
		Functions\when( 'admin_url' )->returnArg( 1 );
		Functions\when( 'add_query_arg' )->alias( function ( $a, $u = '' ) { return $u . '?' . http_build_query( (array) $a ); } );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_die' )->alias( function ( $m = '' ) { throw new WH_VM_Halt( 'die', $m ); } );
		Functions\when( 'wp_safe_redirect' )->alias( function ( $u ) { throw new WH_VM_Halt( 'redirect', $u ); } );
	}

	private function make_page( $vouchers ) {
		return new \WH_Vouchers_Page( $vouchers );
	}

	public function test_render_lists_bundles() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'bundles' )->once()->andReturn( array(
			'data' => array(
				array( 'id' => 'B1', 'name' => 'Summer 2026' ),
				array( 'id' => 'B2', 'name' => 'Loyalty' ),
			),
		) );
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );

		ob_start();
		$page->render( array() );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Summer 2026', $html );
		$this->assertStringContainsString( 'Loyalty', $html );
	}

	public function test_render_lists_codes_when_bundle_selected() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'bundles' )->once()->andReturn( array( 'data' => array( array( 'id' => 'B1', 'name' => 'Summer' ) ) ) );
		$vouchers->shouldReceive( 'codes' )->once()->with( 'B1' )->andReturn( array(
			'data' => array(
				array( 'code' => 'SAVE10', 'status' => 'active', 'discount' => '10%' ),
				array( 'code' => 'VIP', 'status' => 'disabled', 'discount' => '20%' ),
			),
		) );
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );

		ob_start();
		$page->render( array( 'bundle' => 'B1' ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'SAVE10', $html );
		$this->assertStringContainsString( 'VIP', $html );
		// The create/manage form is present.
		$this->assertStringContainsString( 'wh_voucher_manage', $html );
	}

	public function test_manage_rejects_bad_nonce() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'manageCode' )->never();
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );
		$this->stub_nonce_valid( false );

		$this->expectException( WH_VM_Halt::class );
		$page->handle_manage( array( 'bundle' => 'B1', 'code' => 'X', 'op' => 'create', '_wpnonce' => 'x' ) );
	}

	public function test_manage_rejects_without_capability() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'manageCode' )->never();
		$page = $this->make_page( $vouchers );
		$this->stub_capability( false );
		$this->stub_nonce_valid( true );

		$this->expectException( WH_VM_Halt::class );
		$page->handle_manage( array( 'bundle' => 'B1', 'code' => 'X', 'op' => 'create', '_wpnonce' => 'x' ) );
	}

	public function test_manage_create_calls_api_with_params() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'manageCode' )
			->once()
			->with( 'B1', 'SAVE10', Mockery::on( function ( $p ) {
				return isset( $p['action'] ) && 'create' === $p['action'] && '10' === (string) $p['value'];
			} ) )
			->andReturn( array( 'code' => 'SAVE10' ) );
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_manage( array( 'bundle' => 'B1', 'code' => 'SAVE10', 'op' => 'create', 'discount' => '10', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_VM_Halt $e ) {
			$this->assertSame( 'redirect', $e->kind );
			$this->assertStringContainsString( 'wh_notice=voucher_ok', $e->data );
		}
	}

	public function test_manage_disable_sets_op_disable() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'manageCode' )
			->once()
			->with( 'B1', 'VIP', Mockery::on( function ( $p ) { return 'disable' === $p['action']; } ) )
			->andReturn( array( 'code' => 'VIP', 'status' => 'disabled' ) );
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_manage( array( 'bundle' => 'B1', 'code' => 'VIP', 'op' => 'disable', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_VM_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=voucher_ok', $e->data );
		}
	}

	public function test_manage_error_redirects_with_error() {
		$vouchers = Mockery::mock( 'WH_Vouchers_API' );
		$vouchers->shouldReceive( 'manageCode' )->once()->andReturn( new \WP_Error( 'INVALID_PARAM', 'Bad code.' ) );
		$page = $this->make_page( $vouchers );
		$this->stub_capability( true );
		$this->stub_nonce_valid( true );

		try {
			$page->handle_manage( array( 'bundle' => 'B1', 'code' => 'X', 'op' => 'create', '_wpnonce' => 'x' ) );
			$this->fail( 'expected redirect' );
		} catch ( WH_VM_Halt $e ) {
			$this->assertStringContainsString( 'wh_notice=error', $e->data );
			$this->assertStringContainsString( 'wh_err=INVALID_PARAM', $e->data );
		}
	}
}
