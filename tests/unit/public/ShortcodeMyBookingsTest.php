<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeMyBookingsTest extends WH_Public_TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WH_PATH' ) ) {
			define( 'WH_PATH', dirname( __DIR__, 3 ) . '/' );
		}
		// is_wp_error() is pre-defined in tests/unit/wp-stubs.php and cannot be
		// Brain-Monkey aliased (Patchwork DefinedTooEarly); it already returns
		// ( $thing instanceof WP_Error ), which is the behaviour we need.
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nope/child' );
		Functions\when( 'get_template_directory' )->justReturn( '/nope/parent' );
	}

	private function shortcodes( $bookings ) {
		$apis = new class( $bookings ) {
			private $b;
			public function __construct( $b ) {
				$this->b = $b; }
			public function bookings_api() {
				return $this->b; }
		};
		return new WH_Shortcodes( $this->mock_settings(), $apis );
	}

	public function test_guest_sees_login_notice(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		$api = \Mockery::mock( 'WH_Bookings_API' );
		$api->shouldNotReceive( 'search' );

		$html = $this->shortcodes( $api )->render_my_bookings( array() );
		$this->assertStringContainsString( 'wh-my-bookings__login', $html );
	}

	public function test_logged_in_user_lists_bookings_by_email(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$user        = new \stdClass();
		$user->user_email = 'jane@example.com';
		Functions\when( 'wp_get_current_user' )->justReturn( $user );

		$api = \Mockery::mock( 'WH_Bookings_API' );
		$api->shouldReceive( 'search' )->once()->with(
			\Mockery::on( static function ( $p ) {
				return isset( $p['email'] ) && 'jane@example.com' === $p['email'];
			} )
		)->andReturn(
			array(
				'reservations' => array(
					array( 'res_id' => 'R1', 'checkin' => '2026-08-01', 'status' => 'confirmed', 'property' => 'DEMO' ),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_my_bookings( array() );
		$this->assertStringContainsString( 'wh-my-bookings', $html );
		$this->assertStringContainsString( 'R1', $html );
		$this->assertStringContainsString( 'confirmed', $html );
	}

	public function test_logged_in_no_bookings_empty_notice(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$user             = new \stdClass();
		$user->user_email = 'jane@example.com';
		Functions\when( 'wp_get_current_user' )->justReturn( $user );

		$api = \Mockery::mock( 'WH_Bookings_API' );
		$api->shouldReceive( 'search' )->andReturn( array( 'reservations' => array() ) );

		$html = $this->shortcodes( $api )->render_my_bookings( array() );
		$this->assertStringContainsString( 'wh-empty', $html );
	}
}
