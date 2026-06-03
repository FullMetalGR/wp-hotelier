<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodeCalendarTest extends WH_Public_TestCase {

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
		if ( ! class_exists( 'WH_I18n' ) ) {
			eval( 'class WH_I18n { public static function money( $a, $c ) { return $c . " " . number_format( (float) $a, 2 ); } public static function accept_language() { return "en_GB"; } }' );
		}
	}

	private function shortcodes( $availability ) {
		$apis = new class( $availability ) {
			private $a;
			public function __construct( $a ) {
				$this->a = $a; }
			public function availability_api() {
				return $this->a; }
		};
		return new WH_Shortcodes( $this->mock_settings(), $apis );
	}

	public function test_renders_calendar_days_with_prices(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		// Outbound params must use fromd/tod (never month/months).
		$api->shouldReceive( 'calendar' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $params ) {
				return isset( $params['fromd'], $params['tod'] )
					&& ! isset( $params['month'] ) && ! isset( $params['months'] );
			} )
		)->andReturn(
			array(
				'currency' => 'EUR',
				// Live days shape: list of { date, allot, price, checkin }.
				'days'     => array(
					array( 'date' => '2026-08-01', 'allot' => 3, 'price' => 500, 'checkin' => true ),
					array( 'date' => '2026-08-02', 'allot' => 1, 'price' => 520, 'checkin' => true ),
					array( 'date' => '2026-08-03', 'allot' => 0, 'price' => 0, 'checkin' => false ),
				),
			)
		);

		$html = $this->shortcodes( $api )->render_calendar( array( 'months' => '2' ) );
		$this->assertStringContainsString( 'wh-calendar', $html );
		$this->assertStringContainsString( '2026-08-01', $html );
		// Price is rendered through WH_I18n::money(); assert against whatever the
		// loaded WH_I18n produces (real class or per-test eval stub).
		$this->assertStringContainsString( WH_I18n::money( 500, 'EUR' ), $html );
		// allot 0 / checkin false => unavailable.
		$this->assertStringContainsString( 'wh-calendar__day--unavailable', $html );
		$this->assertStringContainsString( 'wh-calendar__day--available', $html );
	}

	public function test_months_attribute_is_capped_at_three(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$captured = null;
		$api->shouldReceive( 'calendar' )->once()->with(
			'DEMO',
			\Mockery::on( static function ( $params ) use ( &$captured ) {
				$captured = $params;
				return true;
			} )
		)->andReturn( array( 'currency' => 'EUR', 'days' => array() ) );

		$this->shortcodes( $api )->render_calendar( array( 'months' => '12' ) );

		// tod must not exceed fromd + 3 months.
		$max = gmdate( 'Y-m-d', strtotime( '+3 months', strtotime( $captured['fromd'] ) ) );
		$this->assertSame( $max, $captured['tod'] );
	}

	public function test_error_renders_notice(): void {
		$api = \Mockery::mock( 'WH_Availability_API' );
		$err = new \WP_Error( 'NO_AUTH', 'Auth failed' );
		$api->shouldReceive( 'calendar' )->andReturn( $err );

		$html = $this->shortcodes( $api )->render_calendar( array() );
		$this->assertStringContainsString( 'wh-notice--error', $html );
	}
}
