<?php
/**
 * Multi-property search results template.
 *
 * @var array  $results  Property results (multi mode).
 * @var bool   $single   True when falling back to single-property cards.
 * @var array  $data     Single-property availability data (single fallback).
 * @var array  $rates    Single-property rates (single fallback).
 * @var string $currency Currency (single fallback).
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_single = ! empty( $single );
?>
<div class="wh-search-results <?php echo esc_attr( $class ); ?>">
	<?php if ( $wh_single ) : ?>
		<?php
		$wh_rates = isset( $rates ) ? $rates : array();
		$wh_curr  = isset( $currency ) ? $currency : 'EUR';
		if ( empty( $wh_rates ) ) :
			?>
			<p class="wh-search-results__empty"><?php echo esc_html__( 'No availability found.', 'webhotelier' ); ?></p>
			<?php
		else :
			foreach ( $wh_rates as $wh_rate ) {
				echo WH_Render::template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self-escaping template.
					'rate-card',
					array(
						'rate'     => $wh_rate,
						'currency' => $wh_curr,
					)
				);
			}
		endif;
		?>
	<?php elseif ( empty( $results ) ) : ?>
		<p class="wh-search-results__empty"><?php echo esc_html__( 'No properties match your search.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<?php foreach ( $results as $wh_r ) : ?>
			<?php
			$wh_name  = isset( $wh_r['name'] ) ? $wh_r['name'] : '';
			$wh_eng   = isset( $wh_r['url']['engine'] ) ? $wh_r['url']['engine'] : '';
			$wh_price = isset( $wh_r['price'] ) ? $wh_r['price'] : null;
			$wh_curr  = isset( $wh_r['currency'] ) ? $wh_r['currency'] : 'EUR';
			$wh_photo = isset( $wh_r['photo'] ) ? $wh_r['photo'] : '';
			?>
			<article class="wh-result-card" data-code="<?php echo esc_attr( isset( $wh_r['code'] ) ? (string) $wh_r['code'] : '' ); ?>">
				<?php if ( '' !== $wh_photo ) : ?>
					<div class="wh-result-card__media">
						<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_photo, 600, 400, 85 ) ); ?>" alt="<?php echo esc_attr( $wh_name ); ?>" loading="lazy" />
					</div>
				<?php endif; ?>
				<div class="wh-result-card__body">
					<h3 class="wh-result-card__name"><?php echo esc_html( $wh_name ); ?></h3>
					<?php if ( null !== $wh_price ) : ?>
						<span class="wh-result-card__price">
							<?php
							/* translators: %s: formatted price. */
							echo esc_html( sprintf( __( 'from %s', 'webhotelier' ), WH_I18n::money( $wh_price, $wh_curr ) ) );
							?>
						</span>
					<?php endif; ?>
					<?php if ( '' !== $wh_eng ) : ?>
						<a class="wh-btn wh-result-card__book" href="<?php echo esc_url( $wh_eng ); ?>" rel="noopener"><?php echo esc_html__( 'View & Book', 'webhotelier' ); ?></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
