<?php
/**
 * Production HTTP transport using wp_remote_request.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_WP_Http.
 */
class WH_WP_Http implements WH_Http {

	/** @var int Request timeout in seconds. */
	private $timeout;

	/**
	 * @param int $timeout Request timeout in seconds.
	 */
	public function __construct( $timeout = 15 ) {
		$this->timeout = (int) $timeout;
	}

	/**
	 * Perform the HTTP request via wp_remote_request.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $url     Full URL.
	 * @param array       $headers Request headers.
	 * @param string|null $body    Request body.
	 * @return array{code:int,body:string}
	 */
	public function request( string $method, string $url, array $headers, ?string $body ): array {
		$args = array(
			'method'      => $method,
			'headers'     => $headers,
			'timeout'     => $this->timeout,
			'redirection' => 0,
		);
		if ( null !== $body ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			// Preserve the underlying reason (DNS/SSL/timeout) so the client can
			// surface it instead of a blank "could not reach" message.
			return array(
				'code'  => 0,
				'body'  => '',
				'error' => $response->get_error_message(),
			);
		}

		return array(
			'code' => (int) wp_remote_retrieve_response_code( $response ),
			'body' => (string) wp_remote_retrieve_body( $response ),
		);
	}
}
