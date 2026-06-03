<?php
/**
 * Availability results template.
 *
 * @var array  $data     Availability data envelope.
 * @var array  $rates    Filtered flat rate list.
 * @var string $currency Currency code.
 * @var string $layout   Layout key (cards|list).
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-availability wh-availability--<?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $rates ) ) : ?>
		<p class="wh-availability__empty">
			<?php echo esc_html__( 'No availability for the selected dates. Please try different dates.', 'webhotelier' ); ?>
		</p>
	<?php else : ?>
		<?php
		foreach ( $rates as $wh_rate ) {
			$wh_card = WH_Render::template(
				'rate-card',
				array(
					'rate'     => $wh_rate,
					'currency' => $currency,
				)
			);
			echo $wh_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rate-card template escapes its own dynamic output.
		}
		?>
	<?php endif; ?>
</div>
