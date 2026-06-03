<?php
/**
 * Bookings screen template. Delegates to the controller's protected renderers
 * for the table/detail to avoid markup drift; this file owns the page chrome.
 *
 * @package webhotelier
 * @var string     $res_id
 * @var array      $filters
 * @var array|null $detail
 * @var array|null $results
 * @var string     $error
 * @var string     $nonce
 * @var string     $action
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit; }

// The controller's render_inline() already produces full, escaped markup and is
// the single source of truth for the table/detail. Reuse it here.
$this->render_inline( $res_id, $filters, $detail, $results, $error, $nonce, $action );
