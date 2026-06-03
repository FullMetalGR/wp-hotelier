<?php
/**
 * Bookings resource: create/amend, purge, cancel, confirmation email, search,
 * retrieve, pending, mark-synced, push/ping, sources.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Bookings_API.
 */
class WH_Bookings_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * POST /book/{code}  (also amends when a res_id is supplied in $params).
	 *
	 * @param string $code   Property code.
	 * @param array  $params Booking params.
	 * @return array|WP_Error
	 */
	public function create( $code, array $params ) {
		return $this->client->post( '/book/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /purge/{resId}
	 *
	 * @param string $resId Reservation id.
	 * @return array|WP_Error
	 */
	public function purge( $resId ) {
		return $this->client->get( '/purge/' . rawurlencode( (string) $resId ), array() );
	}

	/**
	 * POST /reservation/cancel/{resId}
	 *
	 * @param string $resId Reservation id.
	 * @return array|WP_Error
	 */
	public function cancel( $resId ) {
		return $this->client->post( '/reservation/cancel/' . rawurlencode( (string) $resId ), array() );
	}

	/**
	 * POST /reservation/confirmation_email
	 *
	 * @param array $params Params (res_id, email).
	 * @return array|WP_Error
	 */
	public function confirmationEmail( array $params ) {
		return $this->client->post( '/reservation/confirmation_email', $params );
	}

	/**
	 * GET /reservation
	 *
	 * @param array $params Filter params.
	 * @return array|WP_Error
	 */
	public function search( array $params ) {
		return $this->client->get( '/reservation', $params );
	}

	/**
	 * GET /reservation/{resId}
	 *
	 * @param string $resId Reservation id.
	 * @return array|WP_Error
	 */
	public function retrieve( $resId ) {
		return $this->client->get( '/reservation/' . rawurlencode( (string) $resId ), array() );
	}

	/**
	 * GET /reservation/new
	 *
	 * @return array|WP_Error
	 */
	public function pending() {
		return $this->client->get( '/reservation/new', array() );
	}

	/**
	 * GET /reservation/sync/{resId}
	 *
	 * @param string $resId Reservation id.
	 * @return array|WP_Error
	 */
	public function markSynced( $resId ) {
		return $this->client->get( '/reservation/sync/' . rawurlencode( (string) $resId ), array() );
	}

	/**
	 * POST /push/ping  (channel management).
	 *
	 * @param array $params Params.
	 * @return array|WP_Error
	 */
	public function pushPing( array $params ) {
		return $this->client->post( '/push/ping', $params );
	}

	/**
	 * GET /sources
	 *
	 * @return array|WP_Error
	 */
	public function sources() {
		return $this->client->get( '/sources', array() );
	}
}
