<?php
/**
 * Photo gallery/slider template.
 *
 * @var array  $photos List of photo URLs.
 * @var string $class  Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-gallery <?php echo esc_attr( $class ); ?>" data-wh-gallery>
	<?php if ( empty( $photos ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No photos available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<div class="wh-gallery__track">
			<?php foreach ( $photos as $wh_i => $wh_photo ) : ?>
				<figure class="wh-gallery__slide<?php echo 0 === $wh_i ? ' is-active' : ''; ?>">
					<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_photo, 1024, 683, 85 ) ); ?>"
						alt="" loading="<?php echo 0 === $wh_i ? 'eager' : 'lazy'; ?>" />
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $photos ) > 1 ) : ?>
			<button type="button" class="wh-gallery__nav wh-gallery__prev" aria-label="<?php echo esc_attr__( 'Previous', 'webhotelier' ); ?>">&#8249;</button>
			<button type="button" class="wh-gallery__nav wh-gallery__next" aria-label="<?php echo esc_attr__( 'Next', 'webhotelier' ); ?>">&#8250;</button>
		<?php endif; ?>
	<?php endif; ?>
</div>
