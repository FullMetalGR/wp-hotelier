<?php
/**
 * Transient-backed cache wrapper: stable key hashing (params + locale) and a TTL policy
 * that distinguishes volatile availability endpoints from stable content endpoints.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Cache.
 */
class WH_Cache {

	/**
	 * Endpoint path fragments that should use the (short) availability TTL.
	 *
	 * @var array<int,string>
	 */
	const VOLATILE = array( '/availability', '/bar', 'calendar', 'flexible-calendar' );

	/**
	 * Build a stable cache key from endpoint, params, and locale.
	 *
	 * @param string $endpoint Endpoint path.
	 * @param array  $params   Request params.
	 * @param string $locale   Locale.
	 * @return string
	 */
	public function key( $endpoint, array $params = array(), $locale = '' ) {
		ksort( $params );
		$hash = md5( (string) wp_json_encode( $params ) );
		return 'wh:' . $endpoint . ':' . $hash . ':' . $locale;
	}

	/**
	 * Read a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|false False on miss.
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Store a value.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value.
	 * @param int    $ttl   TTL in seconds.
	 * @return void
	 */
	public function set( $key, $value, $ttl ) {
		set_transient( $key, $value, (int) $ttl );
	}

	/**
	 * Resolve the TTL to use for a given endpoint path under the configured policy.
	 *
	 * @param string      $endpoint Endpoint path.
	 * @param WH_Settings $settings Settings model.
	 * @return int Seconds.
	 */
	public function ttl_for( $endpoint, WH_Settings $settings ) {
		foreach ( self::VOLATILE as $needle ) {
			if ( false !== strpos( $endpoint, $needle ) ) {
				return (int) $settings->cache_ttl_availability();
			}
		}
		return (int) $settings->cache_ttl_content();
	}
}
