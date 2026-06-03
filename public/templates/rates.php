<?php
/**
 * Rate listing template.
 *
 * @var array  $rates List of rate arrays.
 * @var string $class Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-rates <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $rates ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No rates available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<?php foreach ( $rates as $wh_rate ) : ?>
			<?php
			$wh_name  = isset( $wh_rate['rate'] ) ? $wh_rate['rate'] : '';
			$wh_desc  = isset( $wh_rate['rate_desc'] ) ? $wh_rate['rate_desc'] : '';
			$wh_board = isset( $wh_rate['board'] ) ? $wh_rate['board'] : '';
			?>
			<div class="wh-rate-row" data-rate="<?php echo esc_attr( isset( $wh_rate['id'] ) ? (string) $wh_rate['id'] : '' ); ?>">
				<h4 class="wh-rate-row__name"><?php echo esc_html( $wh_name ); ?></h4>
				<?php if ( '' !== $wh_board ) : ?>
					<span class="wh-rate-row__board"><?php echo esc_html( $wh_board ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $wh_desc ) : ?>
					<div class="wh-rate-row__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
