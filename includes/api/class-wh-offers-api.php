<?php
/**
 * Offers resource: multi-property offers, single-property offers, single offer detail.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Offers_API.
 */
class WH_Offers_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * GET /offers  (multi-property accounts).
	 *
	 * @param array $params Query params.
	 * @return array|WP_Error
	 */
	public function multi( array $params ) {
		return $this->client->get( '/offers', $params );
	}

	/**
	 * GET /offers/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function single( $code, array $params ) {
		return $this->client->get( '/offers/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /offers/{code}/{offerId}
	 *
	 * @param string     $code    Property code.
	 * @param string|int $offer_id Offer id.
	 * @return array|WP_Error
	 */
	public function info( $code, $offer_id ) {
		$path = '/offers/' . rawurlencode( $code ) . '/' . rawurlencode( (string) $offer_id );
		return $this->client->get( $path, array() );
	}
}
