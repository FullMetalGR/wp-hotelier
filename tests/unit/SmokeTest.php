<?php

namespace WH\Tests\Unit;

/**
 * Sanity check that the test harness, Brain Monkey, and Mockery are wired up.
 */
final class SmokeTest extends WH_UnitTestCase {

	public function test_harness_boots(): void {
		$this->assertTrue( true );
	}

	public function test_brain_monkey_can_stub_a_wp_function(): void {
		\Brain\Monkey\Functions\when( '__' )->returnArg( 1 );
		$this->assertSame( 'hello', __( 'hello', 'webhotelier' ) );
	}

	public function test_mockery_is_available(): void {
		$mock = \Mockery::mock();
		$mock->shouldReceive( 'ping' )->once()->andReturn( 'pong' );
		$this->assertSame( 'pong', $mock->ping() );
	}
}
