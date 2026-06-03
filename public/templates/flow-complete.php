<?php
/**
 * Booking flow: complete step (hosted handoff).
 *
 * JS performs the redirect/new-tab/iframe transition; this provides a
 * no-JS fallback link.
 *
 * @var string $url   Handoff URL.
 * @var string $open  redirect|newtab|iframe.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-flow-complete <?php echo esc_attr( $class ); ?>"
	data-wh-handoff
	data-open="<?php echo esc_attr( $open ); ?>"
	data-url="<?php echo esc_url( $url ); ?>">
	<?php if ( 'iframe' === $open ) : ?>
		<iframe class="wh-flow-complete__frame" src="<?php echo esc_url( $url ); ?>"
			title="<?php echo esc_attr__( 'Secure booking', 'webhotelier' ); ?>" loading="eager"
			style="width:100%;min-height:800px;border:0"></iframe>
	<?php else : ?>
		<p class="wh-flow-complete__msg"><?php echo esc_html__( 'Redirecting you to secure booking…', 'webhotelier' ); ?></p>
		<a class="wh-btn wh-flow-complete__link" href="<?php echo esc_url( $url ); ?>" rel="noopener"<?php echo 'newtab' === $open ? ' target="_blank"' : ''; ?>>
			<?php echo esc_html__( 'Continue to secure booking', 'webhotelier' ); ?>
		</a>
	<?php endif; ?>
</div>
