<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';

final class SmokeTest extends WH_Public_TestCase {
	public function test_brain_monkey_function_stub_works(): void {
		\Brain\Monkey\Functions\when( 'esc_html' )->returnArg();
		$this->assertSame( 'hi', esc_html( 'hi' ) );
	}

	public function test_default_property_helper_available(): void {
		$this->assertSame( 'DEMO', $this->default_property() );
	}
}
