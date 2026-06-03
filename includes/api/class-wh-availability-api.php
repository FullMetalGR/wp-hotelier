<?php
/**
 * Availability resource: single/multi availability, breakdown, calendars, BAR, extras, iCal.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Availability_API.
 */
class WH_Availability_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * GET /availability/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function single( $code, array $params ) {
		return $this->client->get( '/availability/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /availability  (multi-property accounts).
	 *
	 * @param array $params Query params.
	 * @return array|WP_Error
	 */
	public function multi( array $params ) {
		return $this->client->get( '/availability', $params );
	}

	/**
	 * GET /availability/{code}/breakdown
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function breakdown( $code, array $params ) {
		return $this->client->get( '/availability/' . rawurlencode( $code ) . '/breakdown', $params );
	}

	/**
	 * GET /availability/{code}/calendar
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function calendar( $code, array $params ) {
		return $this->client->get( '/availability/' . rawurlencode( $code ) . '/calendar', $params );
	}

	/**
	 * GET /availability/{code}/flexible-calendar
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function flexibleCalendar( $code, array $params ) {
		return $this->client->get( '/availability/' . rawurlencode( $code ) . '/flexible-calendar', $params );
	}

	/**
	 * GET /bar/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function bar( $code, array $params ) {
		return $this->client->get( '/bar/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /availability/{code}/extras/{rateId}
	 *
	 * @param string     $code   Property code.
	 * @param string|int $rateId Rate id.
	 * @param array      $params Query params.
	 * @return array|WP_Error
	 */
	public function extras( $code, $rateId, array $params ) {
		$path = '/availability/' . rawurlencode( $code ) . '/extras/' . rawurlencode( (string) $rateId );
		return $this->client->get( $path, $params );
	}

	/**
	 * GET /ical/{code}-{room}.ics
	 *
	 * @param string $code Property code.
	 * @param string $room Room code.
	 * @return array|string|WP_Error
	 */
	public function ical( $code, $room ) {
		$path = '/ical/' . rawurlencode( $code ) . '-' . rawurlencode( $room ) . '.ics';
		return $this->client->get( $path, array() );
	}
}
