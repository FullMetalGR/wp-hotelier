<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class ActivatorTest extends WH_UnitTestCase {

	public function test_create_pages_inserts_pages_and_wires_results_page(): void {
		Functions\when( '__' )->returnArg( 1 );

		// No tracked pages and no stored settings yet.
		Functions\when( 'get_option' )->justReturn( array() );
		// No existing page at any target slug.
		Functions\when( 'get_page_by_path' )->justReturn( null );

		// Newly created pages get sequential ids.
		$next = 100;
		Functions\when( 'wp_insert_post' )->alias(
			static function () use ( &$next ) {
				return $next++;
			}
		);

		$captured = array();
		Functions\when( 'update_option' )->alias(
			static function ( $name, $value ) use ( &$captured ) {
				$captured[ $name ] = $value;
				return true;
			}
		);

		\WH_Activator::create_pages();

		// All three pages are created and tracked, in order.
		$this->assertSame( array( 'booking', 'results', 'lookup' ), array_keys( $captured['wh_pages'] ) );
		$this->assertSame( 100, $captured['wh_pages']['booking'] );
		// The results page id is wired into the results_page setting.
		$this->assertSame( $captured['wh_pages']['results'], $captured['wh_settings']['results_page'] );
	}

	public function test_create_pages_is_idempotent_for_tracked_pages(): void {
		Functions\when( '__' )->returnArg( 1 );

		Functions\when( 'get_option' )->alias(
			static function ( $key ) {
				if ( \WH_Activator::PAGES_OPTION === $key ) {
					return array(
						'booking' => 11,
						'results' => 12,
						'lookup'  => 13,
					);
				}
				return array( 'results_page' => 12 );
			}
		);
		// Every tracked page still exists.
		Functions\when( 'get_post' )->justReturn( (object) array( 'ID' => 1 ) );

		// Nothing new should be created.
		Functions\expect( 'wp_insert_post' )->never();
		Functions\expect( 'get_page_by_path' )->never();

		$captured = array();
		Functions\when( 'update_option' )->alias(
			static function ( $name, $value ) use ( &$captured ) {
				$captured[ $name ] = $value;
				return true;
			}
		);

		\WH_Activator::create_pages();

		// Tracking is preserved untouched.
		$this->assertSame(
			array(
				'booking' => 11,
				'results' => 12,
				'lookup'  => 13,
			),
			$captured['wh_pages']
		);
	}
}
