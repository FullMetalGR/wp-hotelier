<?php
/**
 * Plugin Name:       WebHotelier for WordPress
 * Plugin URI:        https://github.com/FullMetalGR/wp-hotelier
 * Description:        Wraps the entire WebHotelier Integration REST API: settings, admin dashboards, API explorer, and a full frontend booking flow via shortcodes.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ADS Solutions
 * Author URI:        https://ads-solutions.gr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webhotelier
 * Domain Path:       /languages
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WH_VERSION', '1.0.0' );
define( 'WH_FILE', __FILE__ );
define( 'WH_PATH', plugin_dir_path( __FILE__ ) );
define( 'WH_URL', plugin_dir_url( __FILE__ ) );
define( 'WH_BASENAME', plugin_basename( __FILE__ ) );

require_once WH_PATH . 'includes/class-wh-plugin.php';

/**
 * Run on plugin activation: seed default settings, flush rewrite rules.
 *
 * @return void
 */
function wh_activate() {
	require_once WH_PATH . 'includes/class-wh-settings.php';
	$existing = get_option( 'wh_settings', array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	$defaults = WH_Settings::defaults();
	update_option( 'wh_settings', array_merge( $defaults, $existing ) );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wh_activate' );

/**
 * Run on plugin deactivation: flush rewrite rules. Options are preserved.
 *
 * @return void
 */
function wh_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wh_deactivate' );

/**
 * Boot the plugin once all plugins are loaded.
 *
 * @return WH_Plugin
 */
function wh() {
	return WH_Plugin::instance();
}

add_action( 'plugins_loaded', 'wh' );

/**
 * Load the plugin text domain so translations under /languages take effect.
 *
 * @return void
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'webhotelier', false, dirname( WH_BASENAME ) . '/languages' );
	}
);
