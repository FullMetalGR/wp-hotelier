<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

use Brain\Monkey\Functions;

final class ShortcodesRegisterTest extends WH_Public_TestCase {

	public function test_registers_all_26_shortcodes(): void {
		$tags = array();
		Functions\when( 'add_shortcode' )->alias(
			static function ( $tag, $cb ) use ( &$tags ) {
				$tags[] = $tag;
			}
		);

		$sc = new WH_Shortcodes( $this->mock_settings() );
		$sc->register();

		$expected = array(
			'wh_search_form',
			'wh_quick_search',
			'wh_booking_flow',
			'wh_availability',
			'wh_search_results',
			'wh_map',
			'wh_calendar',
			'wh_flex_calendar',
			'wh_bar',
			'wh_price_from',
			'wh_property',
			'wh_property_terms',
			'wh_rooms',
			'wh_room',
			'wh_gallery',
			'wh_facilities',
			'wh_rates',
			'wh_rate',
			'wh_extras',
			'wh_offers',
			'wh_offer',
			'wh_voucher_form',
			'wh_book_button',
			'wh_booking_engine',
			'wh_booking_lookup',
			'wh_my_bookings',
		);

		foreach ( $expected as $tag ) {
			$this->assertContains( $tag, $tags, "Shortcode not registered: {$tag}" );
		}
		$this->assertCount( count( $expected ), array_unique( $tags ) );
	}
}
