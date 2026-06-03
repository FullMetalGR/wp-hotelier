<?php
/**
 * Template resolver and render boundary for the public layer.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

/**
 * Resolves templates child-theme -> parent-theme -> plugin and renders them
 * with extracted variables, capturing output as a string. Escaping happens in
 * the template files themselves; this class only marshals data in.
 */
class WH_Render {

	/**
	 * Locate a template file by name (without extension).
	 *
	 * Search order: child theme /webhotelier/<name>.php, parent theme, plugin.
	 *
	 * @param string $name Template slug.
	 * @return string Absolute path, or '' if not found anywhere.
	 */
	public static function locate( string $name ) {
		$name = ltrim( str_replace( array( '..', "\0" ), '', $name ), '/' );
		$rel  = 'webhotelier/' . $name . '.php';

		$child = trailingslashit( get_stylesheet_directory() ) . $rel;
		if ( is_readable( $child ) ) {
			return $child;
		}

		$parent = trailingslashit( get_template_directory() ) . $rel;
		if ( is_readable( $parent ) ) {
			return $parent;
		}

		$plugin = trailingslashit( self::plugin_templates_dir() ) . $name . '.php';
		if ( is_readable( $plugin ) ) {
			return $plugin;
		}

		return '';
	}

	/**
	 * Render a template to a string.
	 *
	 * @param string              $name Template slug.
	 * @param array<string,mixed> $vars Variables exposed to the template.
	 * @return string Rendered HTML (empty string if the template is missing).
	 */
	public static function template( string $name, array $vars = array() ) {
		$file = self::locate( $name );
		if ( '' === $file ) {
			return '';
		}

		// Make $vars available as named variables inside the template.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );

		ob_start();
		include $file;
		return (string) ob_get_clean();
	}

	/**
	 * Render a WP_Error (or string) as an error notice.
	 *
	 * @param WP_Error|string $error Error to render.
	 * @return string
	 */
	public static function error( $error ) {
		$message = is_object( $error ) && method_exists( $error, 'get_error_message' )
			? $error->get_error_message()
			: (string) $error;

		return self::template(
			'notice',
			array(
				'type'    => 'error',
				'message' => $message,
			)
		);
	}

	/**
	 * Plugin templates directory (overridable for tests via WH_PATH).
	 *
	 * @return string
	 */
	private static function plugin_templates_dir() {
		$base = defined( 'WH_PATH' ) ? WH_PATH : dirname( __DIR__ ) . '/';
		return $base . 'public/templates';
	}
}
