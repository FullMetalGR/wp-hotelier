<?php
/**
 * Best-available-rate widget template.
 *
 * @var mixed  $price    BAR price (or null).
 * @var string $currency Currency code.
 * @var string $engine   Booking engine URL.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-bar <?php echo esc_attr( $class ); ?>">
	<?php if ( null === $price ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'Best rate not available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<span class="wh-bar__label"><?php echo esc_html__( 'Best available rate', 'webhotelier' ); ?></span>
		<span class="wh-bar__price"><?php echo esc_html( WH_I18n::money( $price, $currency ) ); ?></span>
		<?php if ( '' !== $engine ) : ?>
			<a class="wh-btn wh-bar__book" href="<?php echo esc_url( $engine ); ?>" rel="noopener"><?php echo esc_html__( 'Book', 'webhotelier' ); ?></a>
		<?php endif; ?>
	<?php endif; ?>
</div>
