<?php
/**
 * Booking flow: review step.
 *
 * @var array  $rate     Selected rate.
 * @var string $currency Currency code.
 * @var bool   $native   Whether the native non-card guest form is shown.
 * @var array  $query    Current flow query.
 * @var string $nonce    Flow nonce.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_room  = isset( $rate['room'] ) ? $rate['room'] : ( isset( $rate['type'] ) ? $rate['type'] : '' );
$wh_name  = isset( $rate['rate'] ) ? $rate['rate'] : '';
$wh_price = isset( $rate['pricing']['price'] ) ? $rate['pricing']['price'] : null;
$wh_ci    = isset( $query['checkin'] ) ? (string) $query['checkin'] : '';
$wh_co    = isset( $query['checkout'] ) ? (string) $query['checkout'] : '';
?>
<div class="wh-flow-review <?php echo esc_attr( $class ); ?>">
	<h3 class="wh-flow-review__title"><?php echo esc_html__( 'Review your selection', 'webhotelier' ); ?></h3>
	<ul class="wh-flow-review__summary">
		<li><?php echo esc_html( $wh_room ); ?></li>
		<li><?php echo esc_html( $wh_name ); ?></li>
		<li><?php echo esc_html( $wh_ci ); ?> &ndash; <?php echo esc_html( $wh_co ); ?></li>
		<?php if ( null !== $wh_price ) : ?>
			<li class="wh-flow-review__price"><?php echo esc_html( WH_I18n::money( $wh_price, $currency ) ); ?></li>
		<?php endif; ?>
	</ul>

	<form class="wh-flow-review__form" method="post" data-wh-flow-form>
		<input type="hidden" name="wh_step" value="complete" />
		<input type="hidden" name="wh_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<input type="hidden" name="checkin" value="<?php echo esc_attr( $wh_ci ); ?>" />
		<input type="hidden" name="checkout" value="<?php echo esc_attr( $wh_co ); ?>" />
		<input type="hidden" name="rate" value="<?php echo esc_attr( isset( $rate['id'] ) ? (string) $rate['id'] : '' ); ?>" />
		<?php if ( null !== $wh_price ) : ?>
			<input type="hidden" name="price" value="<?php echo esc_attr( (string) $wh_price ); ?>" />
		<?php endif; ?>

		<?php if ( $native ) : ?>
			<div class="wh-field"><label><?php echo esc_html__( 'First name', 'webhotelier' ); ?>
				<input type="text" name="firstname" required /></label></div>
			<div class="wh-field"><label><?php echo esc_html__( 'Last name', 'webhotelier' ); ?>
				<input type="text" name="lastname" required /></label></div>
			<div class="wh-field"><label><?php echo esc_html__( 'Email', 'webhotelier' ); ?>
				<input type="email" name="email" required /></label></div>
			<div class="wh-field"><label><?php echo esc_html__( 'Phone', 'webhotelier' ); ?>
				<input type="tel" name="phone" /></label></div>
			<div class="wh-field"><label><?php echo esc_html__( 'Payment method', 'webhotelier' ); ?>
				<select name="payment_method" required>
					<option value="CHKIN"><?php echo esc_html__( 'Pay at check-in', 'webhotelier' ); ?></option>
					<option value="BANK"><?php echo esc_html__( 'Bank transfer', 'webhotelier' ); ?></option>
				</select></label></div>
			<p class="wh-flow-review__nocard"><?php echo esc_html__( 'No card details are collected on this site.', 'webhotelier' ); ?></p>
		<?php endif; ?>

		<button type="submit" class="wh-btn wh-flow-review__submit">
			<?php echo $native ? esc_html__( 'Confirm booking', 'webhotelier' ) : esc_html__( 'Continue to secure booking', 'webhotelier' ); ?>
		</button>
	</form>
</div>
