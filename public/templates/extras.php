<?php
/**
 * Extras list template.
 *
 * @var array  $extras List of extra service arrays.
 * @var string $class  Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-extras <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $extras ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No extras available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<ul class="wh-extras__list">
			<?php foreach ( $extras as $wh_x ) : ?>
				<?php
				$wh_name  = isset( $wh_x['name'] ) ? $wh_x['name'] : '';
				$wh_desc  = isset( $wh_x['description'] ) ? $wh_x['description'] : '';
				$wh_price = isset( $wh_x['price'] ) ? $wh_x['price'] : null;
				$wh_curr  = isset( $wh_x['currency'] ) ? $wh_x['currency'] : 'EUR';
				?>
				<li class="wh-extras__item">
					<span class="wh-extras__name"><?php echo esc_html( $wh_name ); ?></span>
					<?php if ( null !== $wh_price ) : ?>
						<span class="wh-extras__price"><?php echo esc_html( WH_I18n::money( $wh_price, $wh_curr ) ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $wh_desc ) : ?>
						<div class="wh-extras__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
