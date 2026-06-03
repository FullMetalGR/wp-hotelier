<?php
/**
 * Public-layer bootstrapper. Wires proxy, shortcodes, and assets.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

class WH_Public {

	/** @var WH_Settings|null */
	private $settings;

	/** @var WH_Shortcodes|null */
	private $shortcodes;

	/** @var WH_Proxy|null */
	private $proxy;

	/**
	 * @param WH_Settings|null $settings Settings (injected for tests).
	 */
	public function __construct( $settings = null ) {
		$this->settings = $settings;
	}

	/** Register all public hooks. */
	public function boot() {
		$this->shortcodes = new WH_Shortcodes( $this->settings );
		$this->proxy      = new WH_Proxy( $this->settings );

		add_action( 'rest_api_init', array( $this->proxy, 'register_routes' ) );
		add_action( 'init', array( $this->shortcodes, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this->shortcodes, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this->shortcodes, 'enqueue_assets' ) );
	}

	/** @return WH_Shortcodes|null */
	public function shortcodes() {
		return $this->shortcodes;
	}

	/** @return WH_Proxy|null */
	public function proxy() {
		return $this->proxy;
	}
}
