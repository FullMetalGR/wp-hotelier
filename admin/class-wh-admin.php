<?php
/**
 * Admin bootstrap: top-level WebHotelier menu, submenus, asset enqueue, AJAX wiring.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	// Allow loading under PHPUnit (WH_TESTING / WH_TESTS_DIR) without WordPress.
	exit;
}

/**
 * Admin bootstrap: registers the WebHotelier menu, submenu pages, asset enqueue, and AJAX/admin-post actions.
 */
class WH_Admin {

	const MENU_SLUG = 'webhotelier';
	const CAP       = 'manage_options';

	/** @var WH_Plugin */
	protected $plugin;

	/** @var WH_Settings_Page */
	protected $settings_page;
	/** @var WH_Bookings_Page */
	protected $bookings_page;
	/** @var WH_Stats_Page */
	protected $stats_page;
	/** @var WH_Vouchers_Page */
	protected $vouchers_page;
	/** @var WH_Sync_Page */
	protected $sync_page;
	/** @var WH_Explorer_Page */
	protected $explorer_page;

	/** @var string[] Page hook suffixes owned by this plugin (for asset gating). */
	protected $hook_suffixes = array();

	/**
	 * Constructor. Stores the injected plugin container, builds page controllers, and registers admin hooks.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->build_pages();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		$this->register_actions();
	}

	/**
	 * Construct page controllers, injecting their API collaborators from the container.
	 * Page classes are loaded lazily by the main plugin loader; guarded for test isolation.
	 */
	protected function build_pages() {
		if ( class_exists( 'WH_Settings_Page' ) ) {
			$this->settings_page = new WH_Settings_Page( $this->plugin->settings(), $this->plugin->property_api(), $this->plugin->settings()->get( 'mode', 'single' ) );
		}
		if ( class_exists( 'WH_Bookings_Page' ) ) {
			$this->bookings_page = new WH_Bookings_Page( $this->plugin->bookings_api() );
		}
		if ( class_exists( 'WH_Stats_Page' ) ) {
			$this->stats_page = new WH_Stats_Page( $this->plugin->stats_api(), $this->plugin->settings() );
		}
		if ( class_exists( 'WH_Vouchers_Page' ) ) {
			$this->vouchers_page = new WH_Vouchers_Page( $this->plugin->vouchers_api() );
		}
		if ( class_exists( 'WH_Sync_Page' ) ) {
			$this->sync_page = new WH_Sync_Page( $this->plugin->bookings_api() );
		}
		if ( class_exists( 'WH_Explorer_Page' ) ) {
			$this->explorer_page = new WH_Explorer_Page( $this->plugin->endpoints(), $this->plugin->client() );
		}
	}

	/**
	 * Register AJAX + admin-post handlers exposed by the page controllers.
	 */
	protected function register_actions() {
		if ( $this->settings_page ) {
			add_action( 'admin_init', array( $this->settings_page, 'register_settings' ) );
			add_action( 'wp_ajax_wh_test_connection', array( $this->settings_page, 'ajax_test_connection' ) );
		}
		if ( $this->bookings_page ) {
			add_action( 'admin_post_wh_booking_action', array( $this->bookings_page, 'handle_action' ) );
		}
		if ( $this->vouchers_page ) {
			add_action( 'admin_post_wh_voucher_manage', array( $this->vouchers_page, 'handle_manage' ) );
		}
		if ( $this->sync_page ) {
			add_action( 'admin_post_wh_sync_action', array( $this->sync_page, 'handle_action' ) );
		}
		if ( $this->explorer_page ) {
			add_action( 'wp_ajax_wh_explorer_run', array( $this->explorer_page, 'ajax_run' ) );
		}
	}

	/**
	 * Register the top-level menu and six submenus. Called on admin_menu.
	 */
	public function register_menu() {
		$top                   = add_menu_page(
			__( 'WP Hotelier', 'webhotelier' ),
			__( 'WP Hotelier', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_settings' ),
			'dashicons-building',
			58
		);
		$this->hook_suffixes[] = $top;

		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'webhotelier' ),
			__( 'Settings', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_settings' )
		);
		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Bookings', 'webhotelier' ),
			__( 'Bookings', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG . '-bookings',
			array( $this, 'render_bookings' )
		);
		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Statistics', 'webhotelier' ),
			__( 'Statistics', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG . '-stats',
			array( $this, 'render_stats' )
		);
		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Vouchers', 'webhotelier' ),
			__( 'Vouchers', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG . '-vouchers',
			array( $this, 'render_vouchers' )
		);
		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Sync', 'webhotelier' ),
			__( 'Sync', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG . '-sync',
			array( $this, 'render_sync' )
		);
		$this->hook_suffixes[] = add_submenu_page(
			self::MENU_SLUG,
			__( 'API Explorer', 'webhotelier' ),
			__( 'API Explorer', 'webhotelier' ),
			self::CAP,
			self::MENU_SLUG . '-explorer',
			array( $this, 'render_explorer' )
		);
	}

	/**
	 * Enqueue CSS/JS only on this plugin's admin screens.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! $this->is_plugin_screen( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'wh-admin',
			WH_URL . 'admin/assets/css/admin.css',
			array(),
			WH_VERSION
		);
		wp_enqueue_script(
			'wh-admin',
			WH_URL . 'admin/assets/js/admin.js',
			array(),
			WH_VERSION,
			true
		);
		wp_localize_script(
			'wh-admin',
			'WHAdmin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'testNonce'     => wp_create_nonce( 'wh_test_connection' ),
				'explorerNonce' => wp_create_nonce( 'wh_explorer_run' ),
			)
		);
	}

	/**
	 * True when $hook belongs to one of our pages.
	 *
	 * @param string $hook
	 * @return bool
	 */
	protected function is_plugin_screen( $hook ) {
		if ( in_array( $hook, $this->hook_suffixes, true ) ) {
			return true;
		}
		// Fallback for early enqueue / test contexts: match by slug substring.
		return ( is_string( $hook ) && strpos( $hook, self::MENU_SLUG ) !== false );
	}

	/* ---- Menu render callbacks delegate to page controllers ---- */

	public function render_settings() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->settings_page->render();
	}
	/**
	 * Render the Bookings admin screen (capability-checked).
	 */
	public function render_bookings() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->bookings_page->render();
	}
	/**
	 * Render the Statistics admin screen (capability-checked).
	 */
	public function render_stats() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->stats_page->render();
	}
	/**
	 * Render the Vouchers admin screen (capability-checked).
	 */
	public function render_vouchers() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->vouchers_page->render();
	}
	/**
	 * Render the Sync admin screen (capability-checked).
	 */
	public function render_sync() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->sync_page->render();
	}
	/**
	 * Render the API Explorer admin screen (capability-checked).
	 */
	public function render_explorer() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) ); }
		$this->explorer_page->render();
	}
}
