<?php
/**
 * Settings model: typed getters over the wh_settings option, defaults,
 * and a sanitize() callback implementing the password "keep current" pattern.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Settings.
 */
class WH_Settings {

	const OPTION = 'wh_settings';

	/**
	 * Cached merged settings (defaults + stored).
	 *
	 * @var array<string,mixed>|null
	 */
	private $data = null;

	/**
	 * Default settings values.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'api_user'               => '',
			'api_pass'               => '',
			'api_base'               => 'https://rest.reserve-online.net',
			'mode'                   => 'single',
			'default_property'       => '',
			'currency'               => 'EUR',
			'locale'                 => '',
			'completion_mode'        => 'hosted',
			'engine_open'            => 'redirect',
			'cache_ttl_content'      => 1800,
			'cache_ttl_availability' => 60,
			'results_page'           => 0,
			'market_country'         => '',
			'device'                 => '',
			'map_provider'           => 'leaflet',
			'map_api_key'            => '',
			'debug'                  => false,
		);
	}

	/**
	 * Allowed enum values per key.
	 *
	 * @return array<string,array<int,string>>
	 */
	private static function enums() {
		return array(
			'mode'            => array( 'single', 'multi' ),
			'completion_mode' => array( 'hosted', 'native_noncard' ),
			'engine_open'     => array( 'redirect', 'newtab', 'iframe' ),
		);
	}

	/**
	 * Load and memoize merged settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		if ( null === $this->data ) {
			$stored = get_option( self::OPTION, array() );
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}
			$this->data = array_merge( self::defaults(), $stored );
			// Validate enums on read so a corrupt option never yields an invalid value.
			foreach ( self::enums() as $key => $allowed ) {
				if ( ! in_array( $this->data[ $key ], $allowed, true ) ) {
					$defaults           = self::defaults();
					$this->data[ $key ] = $defaults[ $key ];
				}
			}
		}
		return $this->data;
	}

	/**
	 * Read a single key with default fallback.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Value returned when the key is absent.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/** @return string */
	public function api_user() {
		return (string) $this->get( 'api_user' );
	}

	/** @return string */
	public function api_pass() {
		return (string) $this->get( 'api_pass' );
	}

	/** @return string */
	public function api_base() {
		return rtrim( (string) $this->get( 'api_base' ), '/' );
	}

	/** @return string */
	public function mode() {
		return (string) $this->get( 'mode' );
	}

	/** @return string */
	public function default_property() {
		return (string) $this->get( 'default_property' );
	}

	/** @return string */
	public function currency() {
		return (string) $this->get( 'currency' );
	}

	/** @return string */
	public function locale() {
		return (string) $this->get( 'locale' );
	}

	/** @return string */
	public function completion_mode() {
		return (string) $this->get( 'completion_mode' );
	}

	/** @return string */
	public function engine_open() {
		return (string) $this->get( 'engine_open' );
	}

	/** @return int */
	public function cache_ttl_content() {
		return (int) $this->get( 'cache_ttl_content' );
	}

	/** @return int */
	public function cache_ttl_availability() {
		return (int) $this->get( 'cache_ttl_availability' );
	}

	/** @return int */
	public function results_page() {
		return (int) $this->get( 'results_page' );
	}

	/** @return string */
	public function market_country() {
		return (string) $this->get( 'market_country' );
	}

	/** @return string */
	public function device() {
		return (string) $this->get( 'device' );
	}

	/** @return string */
	public function map_provider() {
		return (string) $this->get( 'map_provider' );
	}

	/** @return string */
	public function map_api_key() {
		return (string) $this->get( 'map_api_key' );
	}

	/** @return bool */
	public function debug() {
		return (bool) $this->get( 'debug' );
	}

	/**
	 * Sanitize submitted settings. Implements password "keep current" and enum/type coercion.
	 *
	 * @param array<string,mixed> $input Raw submitted values.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$current  = $this->all();
		$defaults = self::defaults();
		$enums    = self::enums();
		$out      = array();

		// Strings (sanitize_text_field).
		foreach ( array( 'api_user', 'default_property', 'currency', 'locale', 'market_country', 'device', 'map_api_key' ) as $key ) {
			$out[ $key ] = isset( $input[ $key ] )
				? sanitize_text_field( $input[ $key ] )
				: $current[ $key ];
		}

		// URL.
		$out['api_base'] = isset( $input['api_base'] ) && '' !== trim( (string) $input['api_base'] )
			? esc_url_raw( $input['api_base'] )
			: $current['api_base'];

		// Password keep-current: blank means "keep existing".
		$submitted_pass  = isset( $input['api_pass'] ) ? (string) $input['api_pass'] : '';
		$out['api_pass'] = '' === trim( $submitted_pass ) ? $current['api_pass'] : $submitted_pass;

		// Enums.
		foreach ( $enums as $key => $allowed ) {
			$val         = isset( $input[ $key ] ) ? (string) $input[ $key ] : $current[ $key ];
			$out[ $key ] = in_array( $val, $allowed, true ) ? $val : $defaults[ $key ];
		}

		// map_provider (free string but defaulted).
		$out['map_provider'] = isset( $input['map_provider'] ) && '' !== trim( (string) $input['map_provider'] )
			? sanitize_key( $input['map_provider'] )
			: $current['map_provider'];

		// Integers.
		$out['cache_ttl_content']      = isset( $input['cache_ttl_content'] ) ? absint( $input['cache_ttl_content'] ) : $current['cache_ttl_content'];
		$out['cache_ttl_availability'] = isset( $input['cache_ttl_availability'] ) ? absint( $input['cache_ttl_availability'] ) : $current['cache_ttl_availability'];
		$out['results_page']           = isset( $input['results_page'] ) ? absint( $input['results_page'] ) : $current['results_page'];

		// Boolean.
		$out['debug'] = ! empty( $input['debug'] );

		return $out;
	}
}
