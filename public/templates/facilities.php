<?php
/**
 * Facilities list template.
 *
 * @var array  $facilities List of strings or {name} arrays.
 * @var string $class      Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-facilities <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $facilities ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No facilities listed.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<ul class="wh-facilities__list">
			<?php foreach ( $facilities as $wh_f ) : ?>
				<?php $wh_label = is_array( $wh_f ) && isset( $wh_f['name'] ) ? $wh_f['name'] : (string) $wh_f; ?>
				<li class="wh-facilities__item"><?php echo esc_html( $wh_label ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
