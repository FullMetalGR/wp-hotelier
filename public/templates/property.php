<?php
/**
 * Property info card template.
 *
 * @var array  $data  Property data (name, description, location, url, contact).
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

$wh_name = isset( $data['name'] ) ? $data['name'] : '';
$wh_desc = isset( $data['description'] ) ? $data['description'] : '';
$wh_loc  = isset( $data['location'] ) && is_array( $data['location'] ) ? $data['location'] : array();
$wh_eng  = isset( $data['url']['engine'] ) ? $data['url']['engine'] : '';
?>
<section class="wh-property <?php echo esc_attr( $class ); ?>">
	<?php if ( '' !== $wh_name ) : ?>
		<h2 class="wh-property__name"><?php echo esc_html( $wh_name ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $wh_desc ) : ?>
		<div class="wh-property__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
	<?php endif; ?>

	<?php if ( isset( $wh_loc['lat'], $wh_loc['lon'] ) ) : ?>
		<p class="wh-property__geo"
			data-lat="<?php echo esc_attr( (string) $wh_loc['lat'] ); ?>"
			data-lon="<?php echo esc_attr( (string) $wh_loc['lon'] ); ?>">
			<?php echo esc_html__( 'Location', 'webhotelier' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $wh_eng ) : ?>
		<a class="wh-btn wh-property__book" href="<?php echo esc_url( $wh_eng ); ?>" rel="noopener">
			<?php echo esc_html__( 'Book now', 'webhotelier' ); ?>
		</a>
	<?php endif; ?>
</section>
