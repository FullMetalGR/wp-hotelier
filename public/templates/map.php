<?php
/**
 * Map container template.
 *
 * @var string $provider leaflet|google.
 * @var int    $zoom     Initial zoom.
 * @var int    $height   Container height in px.
 * @var array  $markers  List of {lat,lon,label}.
 * @var string $api_key  Optional provider API key.
 * @var string $class    Extra CSS classes.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || exit;

$wh_markers_json = wp_json_encode( array_values( $markers ) );
?>
<div class="wh-map <?php echo esc_attr( $class ); ?>"
	data-wh-map
	data-provider="<?php echo esc_attr( $provider ); ?>"
	data-zoom="<?php echo esc_attr( (string) $zoom ); ?>"
	data-api-key="<?php echo esc_attr( $api_key ); ?>"
	data-markers="<?php echo esc_attr( (string) $wh_markers_json ); ?>"
	style="height:<?php echo esc_attr( (string) $height ); ?>px">
	<noscript>
		<?php foreach ( $markers as $wh_m ) : ?>
			<p><?php echo esc_html( $wh_m['label'] ); ?> (<?php echo esc_html( (string) $wh_m['lat'] ); ?>, <?php echo esc_html( (string) $wh_m['lon'] ); ?>)</p>
		<?php endforeach; ?>
	</noscript>
</div>
