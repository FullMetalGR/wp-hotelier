<?php
/**
 * Single availability rate card.
 *
 * The Book button targets the verified rate.url.engine deep link, which is
 * already prefilled with checkin/checkout/party/lang/rate.
 *
 * @var array  $rate     Rate object.
 * @var string $currency Currency code.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_room   = isset( $rate['room'] ) ? $rate['room'] : ( isset( $rate['type'] ) ? $rate['type'] : '' );
$wh_name   = isset( $rate['rate'] ) ? $rate['rate'] : '';
$wh_desc   = isset( $rate['rate_desc'] ) ? $rate['rate_desc'] : '';
$wh_board  = isset( $rate['board'] ) ? $rate['board'] : '';
$wh_rem    = isset( $rate['remaining'] ) ? (int) $rate['remaining'] : null;
$wh_min    = isset( $rate['min_stay'] ) ? (int) $rate['min_stay'] : null;
$wh_cancel = isset( $rate['cancellation_policy'] ) ? $rate['cancellation_policy'] : '';
$wh_engine = isset( $rate['url']['engine'] ) ? $rate['url']['engine'] : '';
$wh_photo  = isset( $rate['url']['photo'] ) ? $rate['url']['photo'] : '';

$wh_price = null;
$wh_curr  = $currency;
if ( isset( $rate['pricing']['price'] ) ) {
	$wh_price = $rate['pricing']['price'];
	if ( isset( $rate['pricing']['currency'] ) ) {
		$wh_curr = $rate['pricing']['currency'];
	}
}
?>
<article class="wh-rate-card" data-rate="<?php echo esc_attr( isset( $rate['id'] ) ? (string) $rate['id'] : '' ); ?>" data-room="<?php echo esc_attr( isset( $rate['type'] ) ? (string) $rate['type'] : '' ); ?>">
	<?php if ( '' !== $wh_photo ) : ?>
		<div class="wh-rate-card__media">
			<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_photo, 600, 400, 85 ) ); ?>" alt="<?php echo esc_attr( $wh_room ); ?>" loading="lazy" />
		</div>
	<?php endif; ?>
	<div class="wh-rate-card__body">
		<?php if ( '' !== $wh_room ) : ?>
			<h3 class="wh-rate-card__room"><?php echo esc_html( $wh_room ); ?></h3>
		<?php endif; ?>
		<?php if ( '' !== $wh_name ) : ?>
			<p class="wh-rate-card__rate"><?php echo esc_html( $wh_name ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $wh_board ) : ?>
			<span class="wh-rate-card__board"><?php echo esc_html( $wh_board ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $wh_desc ) : ?>
			<div class="wh-rate-card__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
		<?php endif; ?>
		<ul class="wh-rate-card__meta">
			<?php if ( null !== $wh_rem && $wh_rem > 0 ) : ?>
				<li class="wh-rate-card__remaining">
					<?php
					/* translators: %d: units left. */
					echo esc_html( sprintf( _n( '%d left', '%d left', $wh_rem, 'webhotelier' ), $wh_rem ) );
					?>
				</li>
			<?php endif; ?>
			<?php if ( null !== $wh_min && $wh_min > 1 ) : ?>
				<li class="wh-rate-card__minstay">
					<?php
					/* translators: %d: minimum nights. */
					echo esc_html( sprintf( __( 'Min stay %d nights', 'webhotelier' ), $wh_min ) );
					?>
				</li>
			<?php endif; ?>
		</ul>
		<?php if ( '' !== $wh_cancel ) : ?>
			<details class="wh-rate-card__policy">
				<summary><?php echo esc_html__( 'Cancellation policy', 'webhotelier' ); ?></summary>
				<?php echo wp_kses_post( $wh_cancel ); ?>
			</details>
		<?php endif; ?>
	</div>
	<div class="wh-rate-card__footer">
		<?php if ( null !== $wh_price ) : ?>
			<span class="wh-rate-card__price"><?php echo esc_html( WH_I18n::money( $wh_price, $wh_curr ) ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $wh_engine ) : ?>
			<a class="wh-btn wh-rate-card__book" href="<?php echo esc_url( $wh_engine ); ?>" rel="noopener" target="_blank">
				<?php echo esc_html__( 'Book', 'webhotelier' ); ?>
			</a>
		<?php endif; ?>
	</div>
</article>
