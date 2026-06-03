<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';

use Brain\Monkey\Functions;

final class ProxyRegisterTest extends WH_Public_TestCase {

	public function test_registers_every_contract_route(): void {
		$registered = array();

		Functions\when( 'register_rest_route' )->alias(
			static function ( $ns, $route, $args ) use ( &$registered ) {
				$methods = isset( $args['methods'] ) ? $args['methods'] : '';
				$registered[] = $ns . '|' . $route . '|' . ( is_array( $methods ) ? implode( ',', $methods ) : $methods );
				return true;
			}
		);

		$proxy = new WH_Proxy();
		$proxy->register_routes();

		$expected = array(
			'webhotelier/v1|/availability|GET',
			'webhotelier/v1|/property|GET',
			'webhotelier/v1|/rooms|GET',
			'webhotelier/v1|/room|GET',
			'webhotelier/v1|/rates|GET',
			'webhotelier/v1|/offers|GET',
			'webhotelier/v1|/calendar|GET',
			'webhotelier/v1|/bar|GET',
			'webhotelier/v1|/extras|GET',
			'webhotelier/v1|/book|POST',
			'webhotelier/v1|/lookup|GET',
		);

		foreach ( $expected as $route ) {
			$this->assertContains( $route, $registered, "Missing route: {$route}" );
		}
	}

	public function test_public_read_permission_requires_valid_nonce(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$proxy = new WH_Proxy();
		$req   = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'bad' );

		$ok = $proxy->check_public_nonce( $req );
		$this->assertFalse( $ok );
	}

	public function test_public_read_permission_passes_with_valid_nonce(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );

		$proxy = new WH_Proxy();
		$req   = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'good' );

		$this->assertTrue( $proxy->check_public_nonce( $req ) );
	}

	public function test_write_permission_requires_capability_and_nonce(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$proxy = new WH_Proxy();
		$req   = \Mockery::mock( 'WP_REST_Request' );
		$req->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'good' );

		$this->assertFalse( $proxy->check_write_permission( $req ) );
	}
}
