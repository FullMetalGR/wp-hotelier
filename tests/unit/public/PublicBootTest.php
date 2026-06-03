<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-render.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';
require_once __DIR__ . '/../../../public/class-wh-proxy.php';
require_once __DIR__ . '/../../../public/class-wh-booking-flow.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';
require_once __DIR__ . '/../../../public/class-wh-public.php';

use Brain\Monkey\Functions;

final class PublicBootTest extends WH_Public_TestCase {

	public function test_boot_registers_expected_hooks(): void {
		$added = array();
		Functions\when( 'add_action' )->alias(
			static function ( $hook ) use ( &$added ) {
				$added[] = $hook; }
		);

		$public = new WH_Public( $this->mock_settings() );
		$public->boot();

		$this->assertContains( 'rest_api_init', $added );
		$this->assertContains( 'init', $added );
		$this->assertContains( 'wp_enqueue_scripts', $added );
	}
}
