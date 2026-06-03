<?php
/**
 * Vouchers resource: list bundles, list codes in a bundle, create/update/disable a code.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Vouchers_API.
 */
class WH_Vouchers_API {

	/** @var WH_Client */
	private $client;

	/**
	 * @param WH_Client $client API transport.
	 */
	public function __construct( WH_Client $client ) {
		$this->client = $client;
	}

	/**
	 * GET /voucher
	 *
	 * @return array|WP_Error
	 */
	public function bundles() {
		return $this->client->get( '/voucher', array() );
	}

	/**
	 * GET /voucher/{bundleId}
	 *
	 * @param string|int $bundle_id Bundle id.
	 * @return array|WP_Error
	 */
	public function codes( $bundle_id ) {
		return $this->client->get( '/voucher/' . rawurlencode( (string) $bundle_id ), array() );
	}

	/**
	 * POST /voucher/{bundleId}/{code}  (create/update/disable).
	 *
	 * @param string|int $bundle_id Bundle id.
	 * @param string     $code     Voucher code.
	 * @param array      $params   Params (action, value, ...).
	 * @return array|WP_Error
	 */
	public function manage_code( $bundle_id, $code, array $params ) {
		$path = '/voucher/' . rawurlencode( (string) $bundle_id ) . '/' . rawurlencode( (string) $code );
		return $this->client->post( $path, $params );
	}
}
