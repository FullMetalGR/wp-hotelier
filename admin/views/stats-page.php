<?php
/**
 * Statistics screen template.
 *
 * @package webhotelier
 * @var array  $range
 * @var array  $summary
 * @var string $day_chart
 * @var string $country_chart
 * @var array  $errors
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit; }
$this->render_inline( $range, $summary, $day_chart, $country_chart, $errors );
