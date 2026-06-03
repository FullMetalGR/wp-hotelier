<?php
/**
 * WebHotelier CDN photo URL helper (on-the-fly resizing).
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

class WH_Photo {

	/**
	 * Build a resized CDN photo URL.
	 *
	 * Inserts the `w=<W>:h=<H>:q=<Q>` segment immediately after `/photos/` on
	 * cdn.webhotelier.net URLs. Replaces an existing dimension segment if one
	 * is already present. Non-CDN or empty URLs are returned unchanged.
	 *
	 * @param string $src    Source URL.
	 * @param int    $width  Target width.
	 * @param int    $height Target height.
	 * @param int    $q      Quality 1-100.
	 * @return string
	 */
	public static function resize( $src, $width, $height, $q = 85 ) {
		$src = (string) $src;
		if ( '' === $src ) {
			return '';
		}
		if ( false === strpos( $src, 'cdn.webhotelier.net/photos/' ) ) {
			return $src;
		}

		$width  = max( 1, (int) $width );
		$height = max( 1, (int) $height );
		$q      = min( 100, max( 1, (int) $q ) );
		$seg    = 'w=' . $width . ':h=' . $height . ':q=' . $q;

		// Replace an existing dimension segment if present.
		$pattern = '#(/photos/)(w=\d+:h=\d+:q=\d+/)?#';
		return preg_replace( $pattern, '${1}' . $seg . '/', $src, 1 );
	}
}
