<?php
/**
 * Voucher entry form template.
 *
 * @var string $action   Form action URL.
 * @var string $property Property code (single mode).
 * @var string $mode     single|multi.
 * @var string $label    Field label.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<form class="wh-voucher-form <?php echo esc_attr( $class ); ?>" method="get" action="<?php echo esc_url( $action ); ?>" data-wh-voucher>
	<?php if ( 'single' === $mode && '' !== $property ) : ?>
		<input type="hidden" name="property" value="<?php echo esc_attr( $property ); ?>" />
	<?php endif; ?>
	<div class="wh-field wh-field--voucher">
		<label for="wh-voucher"><?php echo esc_html( $label ); ?></label>
		<input type="text" id="wh-voucher" name="voucher" autocomplete="off" />
	</div>
	<button type="submit" class="wh-btn wh-voucher-form__submit">
		<?php echo esc_html__( 'Apply', 'webhotelier' ); ?>
	</button>
</form>
