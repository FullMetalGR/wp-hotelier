<?php

namespace WH\Tests\Unit;

use Brain\Monkey\Functions;

final class EndpointsTest extends WH_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	public function test_registry_is_non_empty_and_well_formed(): void {
		$all = ( new \WH_Endpoints() )->all();
		$this->assertNotEmpty( $all );
		$seen_keys = array();
		foreach ( $all as $entry ) {
			foreach ( array( 'key', 'label', 'method', 'path_template', 'params', 'category', 'resource', 'resource_method' ) as $field ) {
				$this->assertArrayHasKey( $field, $entry, "Missing field {$field}" );
			}
			$this->assertNotSame( '', (string) $entry['key'] );
			$this->assertContains( $entry['method'], array( 'GET', 'POST', 'DELETE' ), "Bad method for {$entry['key']}" );
			$this->assertIsArray( $entry['params'] );
			$this->assertStringStartsWith( '/', $entry['path_template'], "Path must start with / for {$entry['key']}" );
			$this->assertNotContains( $entry['key'], $seen_keys, "Duplicate key {$entry['key']}" );
			$seen_keys[] = $entry['key'];
		}
	}

	public function test_every_path_placeholder_is_declared_as_a_param(): void {
		// The API Explorer renders an input per declared param and resolve_path()
		// fills {placeholders} from those params. A placeholder missing from
		// params means the Explorer can never supply it (it resolves to '').
		foreach ( ( new \WH_Endpoints() )->all() as $entry ) {
			if ( ! preg_match_all( '/\{(\w+)\}/', (string) $entry['path_template'], $m ) ) {
				continue;
			}
			foreach ( $m[1] as $placeholder ) {
				$this->assertContains(
					$placeholder,
					$entry['params'],
					"Path placeholder {{$placeholder}} is not declared in params for {$entry['key']}"
				);
			}
		}
	}

	public function test_registry_covers_all_six_categories(): void {
		$categories = array();
		foreach ( ( new \WH_Endpoints() )->all() as $entry ) {
			$categories[ $entry['category'] ] = true;
		}
		foreach ( array( 'property', 'availability', 'offers', 'bookings', 'vouchers', 'statistics' ) as $cat ) {
			$this->assertArrayHasKey( $cat, $categories, "Category {$cat} missing from registry" );
		}
	}

	public function test_registry_has_expected_endpoint_count(): void {
		// 7 property + 8 availability + 3 offers + 10 bookings + 3 vouchers + 3 statistics = 34.
		$this->assertCount( 34, ( new \WH_Endpoints() )->all() );
	}

	public function test_get_by_key_returns_entry_or_null(): void {
		$entry = ( new \WH_Endpoints() )->get( 'property.info' );
		$this->assertIsArray( $entry );
		$this->assertSame( 'GET', $entry['method'] );
		$this->assertSame( '/property/{code}', $entry['path_template'] );
		$this->assertNull( ( new \WH_Endpoints() )->get( 'does.not.exist' ) );
	}

	public function test_by_category_groups_entries(): void {
		$property = ( new \WH_Endpoints() )->by_category( 'property' );
		$this->assertCount( 7, $property );
	}

	/**
	 * Parity: every registry entry references a real public method on its resource class
	 * (only checked for resource classes that already exist).
	 */
	public function test_registry_methods_exist_on_resource_classes(): void {
		$all = ( new \WH_Endpoints() )->all();
		$this->assertNotEmpty( $all ); // Guard so the test is never risky while resource classes are pending.
		foreach ( $all as $entry ) {
			$class  = $entry['resource'];
			$method = $entry['resource_method'];
			if ( ! class_exists( $class ) ) {
				continue; // Resource class not yet implemented; skip until its task lands.
			}
			$this->assertTrue(
				method_exists( $class, $method ),
				"Registry entry {$entry['key']} expects {$class}::{$method}()"
			);
		}
	}
}
