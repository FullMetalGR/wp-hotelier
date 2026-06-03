<?php
/**
 * Property general-terms template.
 *
 * @var string $terms HTML terms block.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;
?>
<section class="wh-property-terms <?php echo esc_attr( $class ); ?>">
	<?php if ( '' !== trim( (string) $terms ) ) : ?>
		<?php echo wp_kses_post( $terms ); ?>
	<?php else : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No terms available.', 'webhotelier' ); ?></p>
	<?php endif; ?>
</section>
