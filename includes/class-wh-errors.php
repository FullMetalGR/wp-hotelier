<?php
/**
 * Maps API error envelopes to WP_Error with friendly, translatable messages.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Errors.
 */
class WH_Errors {

	/**
	 * Friendly i18n message map, keyed by API error_code.
	 *
	 * @return array<string,string>
	 */
	public static function map() {
		return array(
			'OK'               => __( 'OK.', 'webhotelier' ),
			'NO_AVAILABILITY'  => __( 'No availability for the selected dates.', 'webhotelier' ),
			'NO_HOTELS_FOUND'  => __( 'No properties matched your search.', 'webhotelier' ),
			'INVALID_METHOD'   => __( 'This request is not supported.', 'webhotelier' ),
			'INVALID_PARAM'    => __( 'One or more search parameters are invalid. Please review and try again.', 'webhotelier' ),
			'ZERO_RESULTS_GEO' => __( 'No properties were found for that location.', 'webhotelier' ),
			'NOT_ALLOWED'      => __( 'This action is not allowed for your account.', 'webhotelier' ),
			'NOT_FOUND'        => __( 'The requested item could not be found.', 'webhotelier' ),
			'NO_AUTH'          => __( 'Authentication is required to access this resource.', 'webhotelier' ),
			'INVALID_AUTH'     => __( 'The API credentials were rejected, or this method is not available for your account type.', 'webhotelier' ),
			'FORBIDDEN'        => __( 'You do not have permission to perform this action.', 'webhotelier' ),
			'ALLOT_DEPLETED'   => __( 'This rate is no longer available — the allotment has been depleted.', 'webhotelier' ),
			'INVALID_PRICE'    => __( 'The price has changed since you searched. Please retry your booking.', 'webhotelier' ),
			'GEO_OVER_QUOTA'   => __( 'Location search is temporarily unavailable. Please try again later.', 'webhotelier' ),
			'NO_PRIVILEGES'    => __( 'Your API account does not have permission for this operation.', 'webhotelier' ),
			'PROPERTY_NOT_FOUND' => __( 'The requested property could not be found.', 'webhotelier' ),
		);
	}

	/**
	 * Whether a code is present in the map.
	 *
	 * @param string $code Error code.
	 * @return bool
	 */
	public static function has( $code ) {
		return array_key_exists( (string) $code, self::map() );
	}

	/**
	 * Friendly message for a given error code.
	 *
	 * Known codes always return their mapped message. Unknown codes prefer the
	 * supplied raw fallback, then a generic message.
	 *
	 * @param string $code     Error code.
	 * @param string $fallback Raw server message (used only for unknown codes).
	 * @return string
	 */
	public static function message( $code, $fallback = '' ) {
		$map  = self::map();
		$code = (string) $code;
		if ( isset( $map[ $code ] ) ) {
			return $map[ $code ];
		}
		if ( '' !== (string) $fallback ) {
			return (string) $fallback;
		}
		return __( 'An unexpected error occurred. Please try again.', 'webhotelier' );
	}

	/**
	 * Convert a parsed envelope into a WP_Error.
	 *
	 * @param array $envelope Parsed envelope.
	 * @return WP_Error
	 */
	public static function to_wp_error( array $envelope ) {
		$code    = isset( $envelope['error_code'] ) && '' !== $envelope['error_code'] ? (string) $envelope['error_code'] : 'wh_unknown';
		$raw     = isset( $envelope['error_msg'] ) ? (string) $envelope['error_msg'] : '';
		$message = self::message( $code, $raw );
		$data    = array(
			'http_code' => isset( $envelope['http_code'] ) ? (int) $envelope['http_code'] : 0,
			'raw'       => $envelope,
		);
		return new WP_Error( $code, $message, $data );
	}
}
