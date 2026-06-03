<?php
/**
 * Booking flow: confirmation step (native path).
 *
 * @var string $res_id     Reservation id.
 * @var string $summary_url Booking summary URL.
 * @var array  $data       Full booking data.
 * @var string $class      Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-flow-confirmation <?php echo esc_attr( $class ); ?>">
	<h3 class="wh-flow-confirmation__title"><?php echo esc_html__( 'Booking confirmed', 'webhotelier' ); ?></h3>
	<?php if ( '' !== $res_id ) : ?>
		<p class="wh-flow-confirmation__resid">
			<?php
			/* translators: %s: reservation id. */
			echo esc_html( sprintf( __( 'Your reservation number is %s', 'webhotelier' ), $res_id ) );
			?>
		</p>
	<?php endif; ?>
	<?php if ( '' !== $summary_url ) : ?>
		<p class="wh-flow-confirmation__summary">
			<a href="<?php echo esc_url( $summary_url ); ?>" rel="noopener" target="_blank">
				<?php echo esc_html__( 'View your booking summary', 'webhotelier' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
