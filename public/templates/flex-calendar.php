<?php
/**
 * Flexible-stay availability template.
 *
 * @var array  $stays    List of {checkin, nights, price}.
 * @var string $currency Currency code.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;
?>
<div class="wh-flex-calendar <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $stays ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No flexible stays available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<table class="wh-flex-calendar__table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Check-in', 'webhotelier' ); ?></th>
					<th><?php echo esc_html__( 'Nights', 'webhotelier' ); ?></th>
					<th><?php echo esc_html__( 'Price', 'webhotelier' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $stays as $wh_s ) : ?>
					<tr class="wh-flex-calendar__row">
						<td><?php echo esc_html( isset( $wh_s['checkin'] ) ? (string) $wh_s['checkin'] : '' ); ?></td>
						<td><?php echo esc_html( isset( $wh_s['nights'] ) ? (string) (int) $wh_s['nights'] : '' ); ?></td>
						<td>
							<?php if ( isset( $wh_s['price'] ) ) : ?>
								<?php echo esc_html( WH_I18n::money( $wh_s['price'], $currency ) ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
