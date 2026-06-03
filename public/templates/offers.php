<?php
/**
 * Offers list template.
 *
 * @var array  $offers List of offer arrays.
 * @var string $class  Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-offers <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $offers ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No offers available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<?php foreach ( $offers as $wh_o ) : ?>
			<?php
			$wh_name  = isset( $wh_o['name'] ) ? $wh_o['name'] : '';
			$wh_desc  = isset( $wh_o['description'] ) ? $wh_o['description'] : '';
			$wh_photo = isset( $wh_o['photo'] ) ? $wh_o['photo'] : '';
			$wh_eng   = isset( $wh_o['url']['engine'] ) ? $wh_o['url']['engine'] : '';
			?>
			<article class="wh-offer-card" data-offer="<?php echo esc_attr( isset( $wh_o['id'] ) ? (string) $wh_o['id'] : '' ); ?>">
				<?php if ( '' !== $wh_photo ) : ?>
					<div class="wh-offer-card__media">
						<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_photo, 600, 400, 85 ) ); ?>" alt="<?php echo esc_attr( $wh_name ); ?>" loading="lazy" />
					</div>
				<?php endif; ?>
				<div class="wh-offer-card__body">
					<h3 class="wh-offer-card__name"><?php echo esc_html( $wh_name ); ?></h3>
					<?php if ( '' !== $wh_desc ) : ?>
						<div class="wh-offer-card__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
					<?php endif; ?>
					<?php if ( '' !== $wh_eng ) : ?>
						<a class="wh-btn wh-offer-card__book" href="<?php echo esc_url( $wh_eng ); ?>" rel="noopener"><?php echo esc_html__( 'Book this offer', 'webhotelier' ); ?></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
