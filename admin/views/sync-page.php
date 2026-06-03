<?php
/**
 * Sync screen template.
 *
 * @package webhotelier
 * @var array  $pending
 * @var array  $sources
 * @var string $error
 * @var string $nonce
 * @var string $action
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit; }
$this->render_inline( $pending, $sources, $error, $nonce, $action );
