<?php
/**
 * Rooms grid template.
 *
 * @var array  $rooms    List of room arrays.
 * @var int    $columns  Grid columns.
 * @var string $link     Optional base link for room detail pages.
 * @var string $property Property code.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;
?>
<div class="wh-rooms wh-rooms--cols-<?php echo esc_attr( (string) (int) $columns ); ?> <?php echo esc_attr( $class ); ?>">
	<?php if ( empty( $rooms ) ) : ?>
		<p class="wh-empty"><?php echo esc_html__( 'No rooms available.', 'webhotelier' ); ?></p>
	<?php else : ?>
		<?php foreach ( $rooms as $wh_room ) : ?>
			<?php
			$wh_code = isset( $wh_room['code'] ) ? $wh_room['code'] : '';
			$wh_name = isset( $wh_room['name'] ) ? $wh_room['name'] : $wh_code;
			$wh_desc = isset( $wh_room['description'] ) ? $wh_room['description'] : '';
			$wh_max  = isset( $wh_room['max_persons'] ) ? (int) $wh_room['max_persons'] : 0;
			$wh_pho  = '';
			if ( isset( $wh_room['photos'][0] ) ) {
				$wh_pho = WH_Photo::resize( (string) $wh_room['photos'][0], 600, 400, 85 );
			}
			$wh_href = '';
			if ( '' !== $link && '' !== $wh_code ) {
				$wh_href = add_query_arg( array( 'wh_room' => $wh_code ), $link );
			}
			?>
			<article class="wh-room-card" data-room="<?php echo esc_attr( $wh_code ); ?>">
				<?php if ( '' !== $wh_pho ) : ?>
					<div class="wh-room-card__media">
						<img src="<?php echo esc_url( $wh_pho ); ?>" alt="<?php echo esc_attr( $wh_name ); ?>" loading="lazy" />
					</div>
				<?php endif; ?>
				<div class="wh-room-card__body">
					<h3 class="wh-room-card__name">
						<?php if ( '' !== $wh_href ) : ?>
							<a href="<?php echo esc_url( $wh_href ); ?>"><?php echo esc_html( $wh_name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $wh_name ); ?>
						<?php endif; ?>
					</h3>
					<?php if ( $wh_max > 0 ) : ?>
						<p class="wh-room-card__cap">
							<?php
							/* translators: %d: maximum guests. */
							echo esc_html( sprintf( __( 'Sleeps up to %d', 'webhotelier' ), $wh_max ) );
							?>
						</p>
					<?php endif; ?>
					<?php if ( '' !== $wh_desc ) : ?>
						<div class="wh-room-card__desc"><?php echo wp_kses_post( $wh_desc ); ?></div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
