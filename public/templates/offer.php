<?php
/**
 * Single offer detail template.
 *
 * @var array  $data  Offer data.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_name  = isset( $data['name'] ) ? $data['name'] : '';
$wh_desc  = isset( $data['description'] ) ? $data['description'] : '';
$wh_terms = isset( $data['terms'] ) ? $data['terms'] : '';
$wh_photo = isset( $data['photo'] ) ? $data['photo'] : '';
$wh_eng   = isset( $data['url']['engine'] ) ? $data['url']['engine'] : '';
?>
<article class="wh-offer <?php echo esc_attr( $class ); ?>">
	<?php if ( '' !== $wh_photo ) : ?>
		<div class="wh-offer__media">
			<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_photo, 1024, 576, 85 ) ); ?>" alt="<?php echo esc_attr( $wh_name ); ?>" loading="lazy" />
		</div>
	<?php endif; ?>
	<?php if ( '' !== $wh_name ) : ?>
		<h2 class="wh-offer__name"><?php echo esc_html( $wh_name ); ?></h2>
	<?php endif; ?>
	<?php if ( '' !== $wh_desc ) : ?>
		<div class="wh-offer__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
	<?php endif; ?>
	<?php if ( '' !== $wh_terms ) : ?>
		<div class="wh-offer__terms"><?php echo wp_kses_post( $wh_terms ); ?></div>
	<?php endif; ?>
	<?php if ( '' !== $wh_eng ) : ?>
		<a class="wh-btn wh-offer__book" href="<?php echo esc_url( $wh_eng ); ?>" rel="noopener"><?php echo esc_html__( 'Book this offer', 'webhotelier' ); ?></a>
	<?php endif; ?>
</article>
