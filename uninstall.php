<?php
/**
 * Uninstall handler for WebHotelier. Removes plugin options and transient caches.
 *
 * @package WebHotelier
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wh_settings' );

global $wpdb;

// Remove all wh: transients (and their timeouts) from the options table.
$like = $wpdb->esc_like( '_transient_wh:' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );

$like_timeout = $wpdb->esc_like( '_transient_timeout_wh:' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_timeout ) );
