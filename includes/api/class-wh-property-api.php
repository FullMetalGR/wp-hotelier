<?php
/**
 * Property resource: property info/search, rooms, rates, extras.
 * All GETs are cached content (the client applies the content TTL via opts).
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Property_API.
 */
class WH_Property_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * GET /property/{code}
	 *
	 * @param string      $code    Property code.
	 * @param string|null $include Optional include token (e.g. photos).
	 * @return array|WP_Error
	 */
	public function info( $code, $include = null ) {
		$args = array();
		if ( null !== $include && '' !== $include ) {
			$args['include'] = $include;
		}
		return $this->client->get( '/property/' . rawurlencode( $code ), $args );
	}

	/**
	 * GET /property  (multi-property accounts).
	 *
	 * @param array $params Query params.
	 * @return array|WP_Error
	 */
	public function search( array $params ) {
		return $this->client->get( '/property', $params );
	}

	/**
	 * GET /room/{code}
	 *
	 * @param string $code Property code.
	 * @return array|WP_Error
	 */
	public function rooms( $code ) {
		return $this->client->get( '/room/' . rawurlencode( $code ), array() );
	}

	/**
	 * GET /room/{code}/{room}
	 *
	 * @param string $code Property code.
	 * @param string $room Room code.
	 * @return array|WP_Error
	 */
	public function room( $code, $room ) {
		return $this->client->get( '/room/' . rawurlencode( $code ) . '/' . rawurlencode( $room ), array() );
	}

	/**
	 * GET /rate/{code}[/{room}]
	 *
	 * @param string      $code Property code.
	 * @param string|null $room Optional room code.
	 * @return array|WP_Error
	 */
	public function rates( $code, $room = null ) {
		$path = '/rate/' . rawurlencode( $code );
		if ( null !== $room && '' !== $room ) {
			$path .= '/' . rawurlencode( $room );
		}
		return $this->client->get( $path, array() );
	}

	/**
	 * GET /rate/{code}/{room}/{rateId}
	 *
	 * @param string     $code   Property code.
	 * @param string     $room   Room code.
	 * @param string|int $rateId Rate id.
	 * @return array|WP_Error
	 */
	public function rate( $code, $room, $rateId ) {
		$path = '/rate/' . rawurlencode( $code ) . '/' . rawurlencode( $room ) . '/' . rawurlencode( (string) $rateId );
		return $this->client->get( $path, array() );
	}

	/**
	 * GET /extra/{code}
	 *
	 * @param string $code Property code.
	 * @return array|WP_Error
	 */
	public function extras( $code ) {
		return $this->client->get( '/extra/' . rawurlencode( $code ), array() );
	}
}
