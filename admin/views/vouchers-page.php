<?php
/**
 * Vouchers screen template.
 *
 * @package webhotelier
 * @var string     $bundle_id
 * @var array      $bundles
 * @var array|null $codes
 * @var string     $error
 * @var string     $nonce
 * @var string     $action
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit; }
$this->render_inline( $bundle_id, $bundles, $codes, $error, $nonce, $action );
