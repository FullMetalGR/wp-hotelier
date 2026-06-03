<?php
/**
 * Responsive booking-engine iframe template.
 *
 * @var string $src    Engine URL.
 * @var int    $height Iframe height in px.
 * @var string $class  Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;
?>
<div class="wh-booking-engine <?php echo esc_attr( $class ); ?>" style="height:<?php echo esc_attr( (string) $height ); ?>px">
	<iframe
		class="wh-booking-engine__frame"
		src="<?php echo esc_url( $src ); ?>"
		title="<?php echo esc_attr__( 'Booking engine', 'webhotelier' ); ?>"
		loading="lazy"
		referrerpolicy="no-referrer-when-downgrade"
		style="width:100%;height:100%;border:0"></iframe>
</div>
