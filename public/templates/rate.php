<?php
/**
 * Single rate detail template.
 *
 * @var array  $data  Rate data.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_name   = isset( $data['rate'] ) ? $data['rate'] : '';
$wh_desc   = isset( $data['rate_desc'] ) ? $data['rate_desc'] : '';
$wh_board  = isset( $data['board'] ) ? $data['board'] : '';
$wh_cancel = isset( $data['cancellation_policy'] ) ? $data['cancellation_policy'] : '';
$wh_pay    = isset( $data['payment_policy'] ) ? $data['payment_policy'] : '';
?>
<div class="wh-rate <?php echo esc_attr( $class ); ?>">
	<?php if ( '' !== $wh_name ) : ?>
		<h3 class="wh-rate__name"><?php echo esc_html( $wh_name ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== $wh_board ) : ?>
		<p class="wh-rate__board"><?php echo esc_html( $wh_board ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $wh_desc ) : ?>
		<div class="wh-rate__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
	<?php endif; ?>
	<?php if ( '' !== $wh_pay ) : ?>
		<div class="wh-rate__payment">
			<h4><?php echo esc_html__( 'Payment policy', 'webhotelier' ); ?></h4>
			<?php echo wp_kses_post( $wh_pay ); ?>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $wh_cancel ) : ?>
		<div class="wh-rate__cancellation">
			<h4><?php echo esc_html__( 'Cancellation policy', 'webhotelier' ); ?></h4>
			<?php echo wp_kses_post( $wh_cancel ); ?>
		</div>
	<?php endif; ?>
</div>
