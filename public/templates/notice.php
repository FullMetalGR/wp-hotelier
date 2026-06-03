<?php
/**
 * Notice template.
 *
 * @var string $type    One of info|error|success|warning.
 * @var string $message Message text (plain).
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

$wh_type = isset( $type ) ? sanitize_key( $type ) : 'info';
$wh_msg  = isset( $message ) ? $message : '';
?>
<div class="wh-notice wh-notice--<?php echo esc_attr( $wh_type ); ?>" role="status">
	<?php echo esc_html( $wh_msg ); ?>
</div>
