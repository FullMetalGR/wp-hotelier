<?php
/**
 * Price/availability calendar template (server-side rendered; no-JS friendly).
 *
 * Live shape: $days is a list of { date, allot, price, initial, min_stay,
 * max_stay, checkin }. A legacy map of YYYY-MM-DD => { price, available } is
 * also accepted. Availability is allot > 0 && checkin (live) or `available`.
 *
 * @var array  $days     List/map of calendar days.
 * @var string $currency Currency code.
 * @var string $property Property code (for the live-refresh JS).
 * @var string $fromd    Window start (Y-m-d).
 * @var string $tod      Window end (Y-m-d).
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_property = isset( $property ) ? (string) $property : '';
$wh_fromd    = isset( $fromd ) ? (string) $fromd : '';
$wh_tod      = isset( $tod ) ? (string) $tod : '';
?>
<div class="wh-calendar <?php echo esc_attr( $class ); ?>" data-wh-calendar data-property="<?php echo esc_attr( $wh_property ); ?>" data-fromd="<?php echo esc_attr( $wh_fromd ); ?>" data-tod="<?php echo esc_attr( $wh_tod ); ?>">
	<?php if ( empty( $days ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No calendar data available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<div class="wh-calendar__grid">
			<?php foreach ( $days as $wh_key => $wh_day ) : ?>
				<?php
				if ( ! is_array( $wh_day ) ) {
					continue;
				}
				// Date: live rows carry an explicit `date`; the legacy map keys by date.
				$wh_date = isset( $wh_day['date'] ) ? (string) $wh_day['date'] : (string) $wh_key;

				// Availability: live = allot > 0 && checkin; legacy = `available`.
				if ( array_key_exists( 'allot', $wh_day ) || array_key_exists( 'checkin', $wh_day ) ) {
					$wh_avail = ( (int) ( $wh_day['allot'] ?? 0 ) > 0 ) && ! empty( $wh_day['checkin'] );
				} else {
					$wh_avail = ! empty( $wh_day['available'] );
				}

				$wh_price = isset( $wh_day['price'] ) ? $wh_day['price'] : null;
				$wh_cls   = $wh_avail ? 'wh-calendar__day--available' : 'wh-calendar__day--unavailable';
				?>
				<div class="wh-calendar__day <?php echo esc_attr( $wh_cls ); ?>" data-date="<?php echo esc_attr( $wh_date ); ?>">
					<span class="wh-calendar__date"><?php echo esc_html( $wh_date ); ?></span>
					<?php if ( $wh_avail && null !== $wh_price ) : ?>
						<span class="wh-calendar__price"><?php echo esc_html( WH_I18n::money( $wh_price, $currency ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
