<?php
/**
 * "From price" badge template.
 *
 * @var mixed  $price    Cheapest price.
 * @var string $currency Currency code.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<span class="wh-price-from <?php echo esc_attr( $class ); ?>">
	<?php
	/* translators: %s: formatted price per night. */
	echo esc_html( sprintf( __( 'from %s / night', 'webhotelier' ), WH_I18n::money( $price, $currency ) ) );
	?>
</span>
