<?php
/**
 * Single room detail template.
 *
 * @var array  $data  Room data.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_name = isset( $data['name'] ) ? $data['name'] : '';
$wh_desc = isset( $data['description'] ) ? $data['description'] : '';
$wh_max  = isset( $data['max_persons'] ) ? (int) $data['max_persons'] : 0;
$wh_fac  = isset( $data['facilities'] ) && is_array( $data['facilities'] ) ? $data['facilities'] : array();
$wh_pho  = isset( $data['photos'] ) && is_array( $data['photos'] ) ? $data['photos'] : array();
$wh_eng  = isset( $data['url']['engine'] ) ? $data['url']['engine'] : '';
?>
<article class="wh-room <?php echo esc_attr( $class ); ?>">
	<?php if ( '' !== $wh_name ) : ?>
		<h2 class="wh-room__name"><?php echo esc_html( $wh_name ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $wh_pho ) ) : ?>
		<div class="wh-room__gallery">
			<?php foreach ( $wh_pho as $wh_p ) : ?>
				<img src="<?php echo esc_url( WH_Photo::resize( (string) $wh_p, 1024, 683, 85 ) ); ?>"
					alt="<?php echo esc_attr( $wh_name ); ?>" loading="lazy" />
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $wh_max > 0 ) : ?>
		<p class="wh-room__cap">
			<?php
			/* translators: %d: maximum guests. */
			echo esc_html( sprintf( __( 'Sleeps up to %d', 'webhotelier' ), $wh_max ) );
			?>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $wh_desc ) : ?>
		<div class="wh-room__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
	<?php endif; ?>

	<?php if ( ! empty( $wh_fac ) ) : ?>
		<ul class="wh-room__facilities">
			<?php foreach ( $wh_fac as $wh_f ) : ?>
				<li><?php echo esc_html( is_array( $wh_f ) && isset( $wh_f['name'] ) ? $wh_f['name'] : (string) $wh_f ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( '' !== $wh_eng ) : ?>
		<a class="wh-btn wh-room__book" href="<?php echo esc_url( $wh_eng ); ?>" rel="noopener">
			<?php echo esc_html__( 'Book this villa', 'webhotelier' ); ?>
		</a>
	<?php endif; ?>
</article>
