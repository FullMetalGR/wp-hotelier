<?php
/**
 * "My bookings" template.
 *
 * @var bool   $logged_in    Whether the current user is logged in.
 * @var array  $reservations List of reservation arrays.
 * @var string $class        Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;
?>
<div class="wh-my-bookings <?php echo esc_attr( $class ); ?>">
	<?php if ( ! $logged_in ) : ?>
		<p class="wh-my-bookings__login">
			<?php echo esc_html__( 'Please log in to see your bookings, or use the reservation lookup form.', 'webhotelier' ); ?>
		</p>
	<?php elseif ( empty( $reservations ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'You have no bookings yet.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<ul class="wh-my-bookings__list">
			<?php foreach ( $reservations as $wh_r ) : ?>
				<li class="wh-my-bookings__item">
					<span class="wh-my-bookings__resid"><?php echo esc_html( isset( $wh_r['res_id'] ) ? (string) $wh_r['res_id'] : '' ); ?></span>
					<span class="wh-my-bookings__property"><?php echo esc_html( isset( $wh_r['property'] ) ? (string) $wh_r['property'] : '' ); ?></span>
					<span class="wh-my-bookings__checkin"><?php echo esc_html( isset( $wh_r['checkin'] ) ? (string) $wh_r['checkin'] : '' ); ?></span>
					<span class="wh-my-bookings__status"><?php echo esc_html( isset( $wh_r['status'] ) ? (string) $wh_r['status'] : '' ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
