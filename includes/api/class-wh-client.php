<?php
/**
 * Transport client for the WebHotelier REST API. Builds auth/headers, performs
 * the request via a pluggable WH_Http, parses the envelope, and maps errors.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Client.
 */
class WH_Client {

	/** Error codes that carry data even though they are not "OK". */
	const SOFT_SUCCESS = array( 'OK', 'NO_AVAILABILITY', 'NO_HOTELS_FOUND' );

	/** @var WH_Settings */
	private $settings;

	/** @var WH_Http */
	private $http;

	/** @var WH_Cache */
	private $cache;

	/**
	 * @param WH_Settings $settings Settings model.
	 * @param WH_Http     $http     Transport.
	 * @param WH_Cache    $cache    Cache.
	 */
	public function __construct( WH_Settings $settings, WH_Http $http, WH_Cache $cache ) {
		$this->settings = $settings;
		$this->http     = $http;
		$this->cache    = $cache;
	}

	/**
	 * Perform an API request.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Endpoint path (leading slash).
	 * @param array  $args   Query (GET) or body (POST) params.
	 * @param array  $opts   Options: cache_ttl (int).
	 * @return array|WP_Error Parsed data array on success, WP_Error on failure.
	 */
	public function request( $method, $path, array $args = array(), array $opts = array() ) {
		$method = strtoupper( $method );
		$base   = $this->settings->api_base();
		$path   = '/' . ltrim( $path, '/' );
		$url    = $base . $path;
		$body   = null;

		$headers = array(
			'Authorization'   => 'Basic ' . base64_encode( $this->settings->api_user() . ':' . $this->settings->api_pass() ),
			'Accept'          => 'application/json',
			'Accept-Language' => $this->accept_language(),
		);

		if ( 'GET' === $method ) {
			if ( ! empty( $args ) ) {
				$url .= '?' . $this->build_query( $args );
			}
		} else {
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
			$body                    = $this->build_query( $args );
		}

		$response = $this->http->request( $method, $url, $headers, $body );
		$code     = isset( $response['code'] ) ? (int) $response['code'] : 0;
		$raw_body = isset( $response['body'] ) ? (string) $response['body'] : '';

		if ( 0 === $code ) {
			return new WP_Error( 'wh_transport', __( 'Could not reach the WebHotelier API.', 'webhotelier' ), array( 'http_code' => 0 ) );
		}

		$envelope = json_decode( $raw_body, true );
		if ( ! is_array( $envelope ) ) {
			return new WP_Error(
				'wh_transport',
				__( 'The WebHotelier API returned an unreadable response.', 'webhotelier' ),
				array( 'http_code' => $code, 'raw' => $raw_body )
			);
		}

		if ( ! isset( $envelope['http_code'] ) ) {
			$envelope['http_code'] = $code;
		}

		$error_code = isset( $envelope['error_code'] ) ? (string) $envelope['error_code'] : '';
		$is_2xx     = $code >= 200 && $code < 300;

		if ( $is_2xx && in_array( $error_code, self::SOFT_SUCCESS, true ) ) {
			return isset( $envelope['data'] ) && is_array( $envelope['data'] ) ? $envelope['data'] : array();
		}

		return WH_Errors::to_wp_error( $envelope );
	}

	/**
	 * GET wrapper with optional caching.
	 *
	 * @param string $path Endpoint path.
	 * @param array  $args Query params.
	 * @param array  $opts Options (cache_ttl).
	 * @return array|WP_Error
	 */
	public function get( $path, array $args = array(), array $opts = array() ) {
		// Default the TTL from the cache policy when the caller did not set one.
		// Only GET is cached, and only when the resolved TTL is > 0.
		$ttl = isset( $opts['cache_ttl'] )
			? (int) $opts['cache_ttl']
			: (int) $this->cache->ttl_for( '/' . ltrim( $path, '/' ), $this->settings );

		if ( $ttl > 0 ) {
			$key    = $this->cache->key( $path, $args, $this->accept_language() );
			$cached = $this->cache->get( $key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$result = $this->request( 'GET', $path, $args, $opts );

		if ( $ttl > 0 && ! is_wp_error( $result ) ) {
			$this->cache->set( $key, $result, $ttl );
		}

		return $result;
	}

	/**
	 * POST wrapper (never cached).
	 *
	 * @param string $path Endpoint path.
	 * @param array  $args Body params.
	 * @param array  $opts Options.
	 * @return array|WP_Error
	 */
	public function post( $path, array $args = array(), array $opts = array() ) {
		return $this->request( 'POST', $path, $args, $opts );
	}

	/**
	 * Resolve the Accept-Language header value from settings.
	 *
	 * @return string
	 */
	private function accept_language() {
		$locale = $this->settings->locale();
		return '' !== $locale ? $locale : 'en_GB';
	}

	/**
	 * Build a query string preserving array params (e.g. party=[{...}]).
	 *
	 * @param array $args Params.
	 * @return string
	 */
	private function build_query( array $args ) {
		$pairs = array();
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}
			$pairs[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
		}
		return implode( '&', $pairs );
	}
}
