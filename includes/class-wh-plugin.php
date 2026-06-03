<?php
/**
 * Singleton loader / DI container. Loads all include files, wires the transport,
 * settings, cache, and resource APIs, and exposes accessors.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Plugin.
 */
class WH_Plugin {

	/** @var WH_Plugin|null */
	private static $instance = null;

	/** @var WH_Settings|null */
	private $settings = null;

	/** @var WH_Cache|null */
	private $cache = null;

	/** @var WH_Client|null */
	private $client = null;

	/** @var WH_Http|null */
	private $http = null;

	/** @var array<string,object> */
	private $resources = array();

	/** @var WH_Admin|null */
	private $admin = null;

	/** @var WH_Public|null */
	private $public = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return WH_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Load include files (guarded so the test classmap can pre-load them).
	 *
	 * @return void
	 */
	private function load_files() {
		if ( ! defined( 'WH_PATH' ) ) {
			return; // In unit tests, classes are autoloaded via the Composer classmap.
		}
		$files = array(
			'includes/class-wh-settings.php',
			'includes/class-wh-cache.php',
			'includes/class-wh-errors.php',
			'includes/class-wh-handoff.php',
			'includes/class-wh-i18n.php',
			'includes/class-wh-endpoints.php',
			'includes/api/class-wh-http.php',
			'includes/api/class-wh-wp-http.php',
			'includes/api/class-wh-client.php',
			'includes/api/class-wh-property-api.php',
			'includes/api/class-wh-availability-api.php',
			'includes/api/class-wh-offers-api.php',
			'includes/api/class-wh-bookings-api.php',
			'includes/api/class-wh-vouchers-api.php',
			'includes/api/class-wh-stats-api.php',
		);
		foreach ( $files as $file ) {
			require_once WH_PATH . $file;
		}

		// Public (front-of-site) layer: always loaded so shortcodes and the REST
		// proxy register on the frontend (and REST requests, which are not admin).
		require_once WH_PATH . 'public/class-wh-render.php';
		require_once WH_PATH . 'public/class-wh-photo.php';
		require_once WH_PATH . 'public/class-wh-proxy.php';
		require_once WH_PATH . 'public/class-wh-booking-flow.php';
		require_once WH_PATH . 'public/class-wh-shortcodes.php';
		require_once WH_PATH . 'public/class-wh-public.php';

		if ( is_admin() ) {
			require_once WH_PATH . 'admin/class-wh-settings-page.php';
			require_once WH_PATH . 'admin/class-wh-bookings-page.php';
			require_once WH_PATH . 'admin/class-wh-stats-page.php';
			require_once WH_PATH . 'admin/class-wh-vouchers-page.php';
			require_once WH_PATH . 'admin/class-wh-sync-page.php';
			require_once WH_PATH . 'admin/class-wh-explorer-page.php';
			require_once WH_PATH . 'admin/class-wh-admin.php';
		}
	}

	/**
	 * Boot the container.
	 *
	 * @return void
	 */
	private function boot() {
		$this->load_files();
		$this->settings = new WH_Settings();
		$this->cache    = new WH_Cache();
		$this->http     = new WH_WP_Http();
		$this->client   = new WH_Client( $this->settings, $this->http, $this->cache );

		if ( is_admin() ) {
			$this->admin = new WH_Admin( $this );
		}

		// Boot the public layer on the frontend (shortcodes + REST proxy).
		$this->public = new WH_Public( $this->settings() );
		$this->public->boot();
	}

	/**
	 * @return WH_Settings
	 */
	public function settings() {
		return $this->settings;
	}

	/**
	 * @return WH_Cache
	 */
	public function cache() {
		return $this->cache;
	}

	/**
	 * @return WH_Client
	 */
	public function client() {
		return $this->client;
	}

	/**
	 * Lazily build and memoize a resource API.
	 *
	 * @param string $key   Resource key.
	 * @param string $class Resource class name.
	 * @return object
	 */
	private function resource( $key, $class ) {
		if ( ! isset( $this->resources[ $key ] ) ) {
			$this->resources[ $key ] = new $class( $this->client );
		}
		return $this->resources[ $key ];
	}

	/** @return WH_Property_API */
	public function property_api() {
		return $this->resource( 'property', WH_Property_API::class );
	}

	/** @return WH_Availability_API */
	public function availability_api() {
		return $this->resource( 'availability', WH_Availability_API::class );
	}

	/** @return WH_Offers_API */
	public function offers_api() {
		return $this->resource( 'offers', WH_Offers_API::class );
	}

	/** @return WH_Bookings_API */
	public function bookings_api() {
		return $this->resource( 'bookings', WH_Bookings_API::class );
	}

	/** @return WH_Vouchers_API */
	public function vouchers_api() {
		return $this->resource( 'vouchers', WH_Vouchers_API::class );
	}

	/** @return WH_Stats_API */
	public function stats_api() {
		return $this->resource( 'stats', WH_Stats_API::class );
	}

	/** @return WH_Endpoints */
	public function endpoints() {
		if ( ! isset( $this->resources['endpoints'] ) ) {
			$this->resources['endpoints'] = new WH_Endpoints();
		}
		return $this->resources['endpoints'];
	}

	/** @return WH_Admin|null */
	public function admin() {
		return $this->admin;
	}

	/** @return WH_Public|null */
	public function public_layer() {
		return $this->public;
	}
}
