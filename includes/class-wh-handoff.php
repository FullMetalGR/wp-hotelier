<?php
/**
 * Booking-engine handoff URL helper. Prefers the API-provided per-rate engine URL,
 * with a fallback builder that merges params onto the property-level engine URL.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Handoff.
 */
class WH_Handoff {

	/**
	 * Return the verbatim per-rate engine deep link, or '' if absent.
	 *
	 * @param array $rate Rate object (as found in availability data.rates[]).
	 * @return string
	 */
	public static function from_rate( array $rate ) {
		if ( isset( $rate['url']['engine'] ) && '' !== (string) $rate['url']['engine'] ) {
			return esc_url_raw( (string) $rate['url']['engine'] );
		}
		return '';
	}

	/**
	 * Build a handoff URL by merging params onto a base engine URL.
	 *
	 * Existing query params on the base are preserved unless overridden by $params.
	 *
	 * @param string $engine_url Base engine URL (e.g. data.url.engine).
	 * @param array  $params     Params to set/override (rate, room, lang, checkin, voucher, party, ...).
	 * @return string Empty string if the base URL is empty.
	 */
	public static function build( $engine_url, array $params ) {
		$engine_url = (string) $engine_url;
		if ( '' === trim( $engine_url ) ) {
			return '';
		}

		$parts = wp_parse_url_compat( $engine_url );
		$query = array();
		if ( isset( $parts['query'] ) && '' !== $parts['query'] ) {
			parse_str( $parts['query'], $query );
		}

		foreach ( $params as $key => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = json_encode( $value );
			}
			$query[ (string) $key ] = (string) $value;
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$host   = isset( $parts['host'] ) ? $parts['host'] : '';
		$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$path   = isset( $parts['path'] ) ? $parts['path'] : '/';

		$rebuilt = $scheme . $host . $port . $path;
		if ( ! empty( $query ) ) {
			// Build query manually to keep JSON brackets readable (engine accepts them).
			$pairs = array();
			foreach ( $query as $k => $v ) {
				$pairs[] = rawurlencode( $k ) . '=' . rawurlencode( $v );
			}
			$rebuilt .= '?' . implode( '&', $pairs );
		}

		return esc_url_raw( $rebuilt );
	}
}

if ( ! function_exists( 'wp_parse_url_compat' ) ) {
	/**
	 * Thin wrapper over WordPress wp_parse_url() with a PHP parse_url() fallback for tests.
	 *
	 * @param string $url URL.
	 * @return array<string,mixed>
	 */
	function wp_parse_url_compat( $url ) {
		if ( function_exists( 'wp_parse_url' ) ) {
			$parsed = wp_parse_url( $url );
		} else {
			$parsed = parse_url( $url );
		}
		return is_array( $parsed ) ? $parsed : array();
	}
}
