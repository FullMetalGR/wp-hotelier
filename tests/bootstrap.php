<?php
/**
 * PHPUnit bootstrap. Loads Composer dev autoloader (Brain Monkey, Mockery, PHPUnit)
 * and the plugin's include classes via the classmap autoloader.
 *
 * @package WebHotelier
 */

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
	fwrite( STDERR, "Composer dependencies are not installed. Run 'composer install' first.\n" );
	exit( 1 );
}

require_once $autoload;

if ( ! defined( 'WH_TESTS_DIR' ) ) {
	define( 'WH_TESTS_DIR', __DIR__ );
}

if ( ! defined( 'WH_PLUGIN_DIR' ) ) {
	define( 'WH_PLUGIN_DIR', dirname( __DIR__ ) );
}

if ( ! defined( 'WH_FIXTURES_DIR' ) ) {
	define( 'WH_FIXTURES_DIR', __DIR__ . '/fixtures' );
}

require_once __DIR__ . '/unit/wp-stubs.php';
