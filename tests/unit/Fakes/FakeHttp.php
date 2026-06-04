<?php

namespace WH\Tests\Unit\Fakes;

/**
 * In-memory WH_Http implementation for unit tests. Records the last request and
 * returns a queued response. No network is touched.
 */
class FakeHttp implements \WH_Http {

	/** @var array<int,array{code:int,body:string}> */
	private $queue = array();

	/** @var array<string,mixed>|null */
	public $last_request = null;

	/** @var array<int,array<string,mixed>> */
	public $requests = array();

	/**
	 * Queue a response to be returned by the next request() call.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $body Raw response body.
	 * @return self
	 */
	public function queue( $code, $body ) {
		$this->queue[] = array(
			'code' => $code,
			'body' => $body,
		);
		return $this;
	}

	/**
	 * Queue a JSON envelope response.
	 *
	 * @param int                 $http_code HTTP status code.
	 * @param array<string,mixed> $envelope  Envelope array (will be json-encoded).
	 * @return self
	 */
	public function queue_json( $http_code, array $envelope ) {
		return $this->queue( $http_code, (string) wp_json_encode_test( $envelope ) );
	}

	/**
	 * Queue a transport-level failure (code 0) carrying a reason, mirroring what
	 * the production transport returns when wp_remote_request yields a WP_Error.
	 *
	 * @param string $message Underlying transport error message.
	 * @return self
	 */
	public function queue_error( $message ) {
		$this->queue[] = array(
			'code'  => 0,
			'body'  => '',
			'error' => (string) $message,
		);
		return $this;
	}

	/**
	 * @param string      $method  HTTP method.
	 * @param string      $url     Full URL.
	 * @param array       $headers Request headers.
	 * @param string|null $body    Request body.
	 * @return array{code:int,body:string}
	 */
	public function request( string $method, string $url, array $headers, ?string $body ): array {
		$this->last_request = array(
			'method'  => $method,
			'url'     => $url,
			'headers' => $headers,
			'body'    => $body,
		);
		$this->requests[] = $this->last_request;

		if ( empty( $this->queue ) ) {
			return array(
				'code' => 0,
				'body' => '',
			);
		}
		return array_shift( $this->queue );
	}
}

if ( ! function_exists( 'WH\Tests\Unit\Fakes\wp_json_encode_test' ) ) {
	/**
	 * Local JSON encoder for the fake (avoids depending on a stubbed wp_json_encode).
	 *
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_json_encode_test( $data ) {
		return json_encode( $data );
	}
}
