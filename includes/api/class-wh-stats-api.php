<?php
/**
 * Statistics resource: performance summary, per-day, per-country.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Stats_API.
 */
class WH_Stats_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * GET /statistics/performance_summary/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params (date_from, date_to).
	 * @return array|WP_Error
	 */
	public function summary( $code, array $params ) {
		return $this->client->get( '/statistics/performance_summary/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /statistics/performance_per_day/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function per_day( $code, array $params ) {
		return $this->client->get( '/statistics/performance_per_day/' . rawurlencode( $code ), $params );
	}

	/**
	 * GET /statistics/performance_per_country/{code}
	 *
	 * @param string $code   Property code.
	 * @param array  $params Query params.
	 * @return array|WP_Error
	 */
	public function per_country( $code, array $params ) {
		return $this->client->get( '/statistics/performance_per_country/' . rawurlencode( $code ), $params );
	}
}
