<?php
/**
 * API Explorer screen template.
 *
 * @package webhotelier
 * @var array  $grouped
 * @var string $registry_json
 * @var string $nonce
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) { exit; }
$this->render_inline( $grouped, $registry_json, $nonce );
