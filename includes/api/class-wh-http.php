<?php
/**
 * Pluggable HTTP transport contract. Allows tests to inject a fake transport.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Interface WH_Http.
 */
interface WH_Http {

	/**
	 * Perform an HTTP request.
	 *
	 * @param string      $method  HTTP method (GET/POST/...).
	 * @param string      $url     Fully-qualified URL.
	 * @param array       $headers Associative array of request headers.
	 * @param string|null $body    Raw request body (null for GET).
	 * @return array{code:int,body:string,error?:string} Response with integer
	 *         status code and raw body. On a transport-level failure the code is
	 *         0 and an optional `error` key carries the underlying reason.
	 */
	public function request( string $method, string $url, array $headers, ?string $body ): array;
}
