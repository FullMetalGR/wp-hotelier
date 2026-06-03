<?php
/**
 * Booking CTA button template.
 *
 * @var string $url   Handoff URL.
 * @var string $label Button label.
 * @var string $open  redirect|newtab|iframe.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

$wh_target = ( 'newtab' === $open ) ? ' target="_blank"' : '';
$wh_rel    = ( 'newtab' === $open ) ? ' rel="noopener"' : '';
?>
<a class="wh-btn wh-book-button <?php echo esc_attr( $class ); ?>"
	href="<?php echo esc_url( $url ); ?>"
	data-open="<?php echo esc_attr( $open ); ?>"<?php echo $wh_target . $wh_rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literals. ?>>
	<?php echo esc_html( $label ); ?>
</a>
