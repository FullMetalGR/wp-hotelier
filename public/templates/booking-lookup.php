<?php
/**
 * Booking lookup form template.
 *
 * @var string $endpoint     REST lookup endpoint URL.
 * @var string $nonce        REST nonce.
 * @var bool   $allow_cancel Whether to show a cancel control after lookup.
 * @var string $class        Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;
?>
<div class="wh-booking-lookup <?php echo esc_attr( $class ); ?>"
	data-wh-lookup
	data-endpoint="<?php echo esc_url( $endpoint ); ?>"
	data-nonce="<?php echo esc_attr( $nonce ); ?>"
	data-allow-cancel="<?php echo esc_attr( $allow_cancel ? '1' : '0' ); ?>">
	<form class="wh-booking-lookup__form">
		<div class="wh-field wh-field--resid">
			<label for="wh-resid"><?php echo esc_html__( 'Reservation number', 'webhotelier' ); ?></label>
			<input type="text" id="wh-resid" name="res_id" required autocomplete="off" />
		</div>
		<div class="wh-field wh-field--email">
			<label for="wh-lookup-email"><?php echo esc_html__( 'Email on the booking', 'webhotelier' ); ?></label>
			<input type="email" id="wh-lookup-email" name="email" autocomplete="off" />
		</div>
		<div class="wh-field wh-field--lastname">
			<label for="wh-lookup-last"><?php echo esc_html__( 'or Last name', 'webhotelier' ); ?></label>
			<input type="text" id="wh-lookup-last" name="lastName" autocomplete="off" />
		</div>
		<button type="submit" class="wh-btn wh-booking-lookup__submit">
			<?php echo esc_html__( 'Find my reservation', 'webhotelier' ); ?>
		</button>
	</form>
	<div class="wh-booking-lookup__result" aria-live="polite"></div>
</div>
