<?php
/**
 * Search form template.
 *
 * @var string $action         Form action URL (results page).
 * @var bool   $compact        Whether to use the compact layout.
 * @var string $property       Property code (single mode).
 * @var string $mode           single|multi.
 * @var int    $rooms_max      Max rooms selector.
 * @var int    $adults_default Default adults value.
 * @var string $nonce          REST nonce for JS-driven submit.
 * @var string $class          Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_compact_cls = $compact ? ' wh-search-form--compact' : '';
?>
<form class="wh-search-form<?php echo esc_attr( $wh_compact_cls ); ?> <?php echo esc_attr( $class ); ?>"
	method="get"
	action="<?php echo esc_url( $action ); ?>"
	data-wh-search
	data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<?php if ( 'single' === $mode && '' !== $property ) : ?>
		<input type="hidden" name="property" value="<?php echo esc_attr( $property ); ?>" />
	<?php endif; ?>

	<div class="wh-field wh-field--checkin">
		<label for="wh-checkin"><?php echo esc_html__( 'Check-in', 'webhotelier' ); ?></label>
		<input type="date" id="wh-checkin" name="checkin" required />
	</div>

	<div class="wh-field wh-field--checkout">
		<label for="wh-checkout"><?php echo esc_html__( 'Check-out', 'webhotelier' ); ?></label>
		<input type="date" id="wh-checkout" name="checkout" required />
	</div>

	<div class="wh-field wh-field--adults">
		<label for="wh-adults"><?php echo esc_html__( 'Adults', 'webhotelier' ); ?></label>
		<input type="number" id="wh-adults" name="adults" min="1" max="30" value="<?php echo esc_attr( (string) $adults_default ); ?>" />
	</div>

	<div class="wh-field wh-field--children">
		<label for="wh-children"><?php echo esc_html__( 'Children', 'webhotelier' ); ?></label>
		<input type="number" id="wh-children" name="children" min="0" max="30" value="0" />
	</div>

	<?php if ( $rooms_max > 1 ) : ?>
		<div class="wh-field wh-field--rooms">
			<label for="wh-rooms"><?php echo esc_html__( 'Rooms', 'webhotelier' ); ?></label>
			<select id="wh-rooms" name="rooms">
				<?php for ( $wh_i = 1; $wh_i <= $rooms_max; $wh_i++ ) : ?>
					<option value="<?php echo esc_attr( (string) $wh_i ); ?>"><?php echo esc_html( (string) $wh_i ); ?></option>
				<?php endfor; ?>
			</select>
		</div>
	<?php endif; ?>

	<button type="submit" class="wh-btn wh-search-form__submit">
		<?php echo esc_html__( 'Search', 'webhotelier' ); ?>
	</button>
</form>
