<?php
/**
 * Settings screen: registers wh_settings via the Settings API, renders all keys,
 * masks the password, and sanitizes/validates on save.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Admin Settings screen: renders and saves the plugin settings, with a live Test Connection.
 */
class WH_Settings_Page {

	const GROUP   = 'wh_settings_group';
	const OPTION  = 'wh_settings';
	const SECTION = 'wh_settings_section';
	const CAP     = 'manage_options';

	/** @var WH_Settings */
	protected $settings;
	/** @var WH_Property_API */
	protected $property;
	/** @var string */
	protected $mode;

	/** Enum allow-lists. */
	const ENUMS = array(
		'mode'            => array( 'single', 'multi' ),
		'completion_mode' => array( 'hosted', 'native_noncard' ),
		'engine_open'     => array( 'redirect', 'newtab', 'iframe' ),
	);

	/** Field id => type. Order defines render order. */
	const FIELDS = array(
		'api_user'               => 'text',
		'api_pass'               => 'password',
		'api_base'               => 'url',
		'mode'                   => 'enum',
		'default_property'       => 'text',
		'currency'               => 'text',
		'locale'                 => 'text',
		'completion_mode'        => 'enum',
		'engine_open'            => 'enum',
		'cache_ttl_content'      => 'int',
		'cache_ttl_availability' => 'int',
		'results_page'           => 'page',
		'market_country'         => 'text',
		'device'                 => 'text',
		'map_provider'           => 'text',
		'map_api_key'            => 'text',
		'debug'                  => 'bool',
	);

	/**
	 * Translatable field labels, keyed by field id.
	 *
	 * Each label is wrapped in __() at the call site (not via a variable) so the
	 * strings are extractable by translation tooling.
	 *
	 * @return array<string,string>
	 */
	public static function labels() {
		return array(
			'api_user'               => __( 'API username', 'webhotelier' ),
			'api_pass'               => __( 'API password', 'webhotelier' ),
			'api_base'               => __( 'API base URL', 'webhotelier' ),
			'mode'                   => __( 'Mode', 'webhotelier' ),
			'default_property'       => __( 'Default property code', 'webhotelier' ),
			'currency'               => __( 'Display currency', 'webhotelier' ),
			'locale'                 => __( 'Locale', 'webhotelier' ),
			'completion_mode'        => __( 'Booking completion', 'webhotelier' ),
			'engine_open'            => __( 'Open engine via', 'webhotelier' ),
			'cache_ttl_content'      => __( 'Content cache TTL (s)', 'webhotelier' ),
			'cache_ttl_availability' => __( 'Availability cache TTL (s)', 'webhotelier' ),
			'results_page'           => __( 'Results page', 'webhotelier' ),
			'market_country'         => __( 'Market country', 'webhotelier' ),
			'device'                 => __( 'Device', 'webhotelier' ),
			'map_provider'           => __( 'Map provider', 'webhotelier' ),
			'map_api_key'            => __( 'Map API key', 'webhotelier' ),
			'debug'                  => __( 'Debug mode', 'webhotelier' ),
		);
	}

	/**
	 * Constructor. Injects the settings store, the property API, and the current mode.
	 */
	public function __construct( $settings, $property, $mode = 'single' ) {
		$this->settings = $settings;
		$this->property = $property;
		$this->mode     = $mode;
	}

	/**
	 * Register the option + section + one field per key. Hooked on admin_init.
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			self::SECTION,
			__( 'WebHotelier API & behaviour', 'webhotelier' ),
			'__return_false',
			self::OPTION
		);

		$labels = self::labels();
		foreach ( self::FIELDS as $key => $type ) {
			add_settings_field(
				'wh_field_' . $key,
				isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
				array( $this, 'render_field' ),
				self::OPTION,
				self::SECTION,
				array(
					'key'  => $key,
					'type' => $type,
				)
			);
		}
	}

	/**
	 * Sanitize + validate the submitted option array.
	 *
	 * @param array $input Raw $_POST['wh_settings'].
	 * @return array Clean option array.
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		$out['api_user'] = isset( $input['api_user'] ) ? sanitize_text_field( trim( (string) $input['api_user'] ) ) : '';

		// Password: blank => keep current stored value.
		$new_pass        = isset( $input['api_pass'] ) ? (string) $input['api_pass'] : '';
		$out['api_pass'] = ( '' === trim( $new_pass ) )
			? (string) $this->settings->get( 'api_pass', '' )
			: $new_pass;

		$base            = isset( $input['api_base'] ) ? esc_url_raw( trim( (string) $input['api_base'] ) ) : '';
		$out['api_base'] = ( '' !== $base ) ? $base : 'https://rest.reserve-online.net';

		$out['mode']             = $this->enum( $input, 'mode', 'single' );
		$out['default_property'] = isset( $input['default_property'] ) ? sanitize_text_field( strtoupper( trim( (string) $input['default_property'] ) ) ) : 'DEMO';
		$out['currency']         = isset( $input['currency'] ) ? strtoupper( sanitize_text_field( trim( (string) $input['currency'] ) ) ) : 'EUR';
		$out['locale']           = isset( $input['locale'] ) ? sanitize_text_field( trim( (string) $input['locale'] ) ) : '';
		$out['completion_mode']  = $this->enum( $input, 'completion_mode', 'hosted' );
		$out['engine_open']      = $this->enum( $input, 'engine_open', 'redirect' );

		$out['cache_ttl_content']      = isset( $input['cache_ttl_content'] ) ? max( 0, (int) $input['cache_ttl_content'] ) : 1800;
		$out['cache_ttl_availability'] = isset( $input['cache_ttl_availability'] ) ? max( 0, (int) $input['cache_ttl_availability'] ) : 60;

		$out['results_page']   = isset( $input['results_page'] ) ? (int) $input['results_page'] : 0;
		$out['market_country'] = isset( $input['market_country'] ) ? sanitize_text_field( trim( (string) $input['market_country'] ) ) : '';
		$out['device']         = isset( $input['device'] ) ? sanitize_text_field( trim( (string) $input['device'] ) ) : '';
		$out['map_provider']   = isset( $input['map_provider'] ) ? sanitize_text_field( trim( (string) $input['map_provider'] ) ) : 'leaflet';
		$out['map_api_key']    = isset( $input['map_api_key'] ) ? sanitize_text_field( trim( (string) $input['map_api_key'] ) ) : '';
		$out['debug']          = ! empty( $input['debug'] );

		return $out;
	}

	/**
	 * Allow-list an enum value, falling back to default for unknown input.
	 */
	protected function enum( $input, $key, $default ) {
		$val = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
		return in_array( $val, self::ENUMS[ $key ], true ) ? $val : $default;
	}

	/**
	 * Render a single settings field (Settings API callback).
	 *
	 * @param array $args { key, type }
	 */
	public function render_field( $args ) {
		$key   = $args['key'];
		$type  = $args['type'];
		$name  = self::OPTION . '[' . $key . ']';
		$value = $this->settings->get( $key, '' );

		switch ( $type ) {
			case 'password':
				printf(
					'<input type="password" class="regular-text" name="%s" value="" autocomplete="new-password" /> <span class="description">%s</span>',
					esc_attr( $name ),
					esc_html__( 'Leave blank to keep current password.', 'webhotelier' )
				);
				break;

			case 'bool':
				printf(
					'<label><input type="checkbox" name="%s" value="1"%s /> %s</label>',
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html__( 'Enabled', 'webhotelier' )
				);
				break;

			case 'enum':
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( self::ENUMS[ $key ] as $opt ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $opt ),
						selected( (string) $value, $opt, false ),
						esc_html( $opt )
					);
				}
				echo '</select>';
				break;

			case 'int':
				printf(
					'<input type="number" min="0" class="small-text" name="%s" value="%s" />',
					esc_attr( $name ),
					esc_attr( (string) ( '' === $value ? '' : (int) $value ) )
				);
				break;

			case 'page':
				printf(
					'<input type="number" min="0" class="small-text" name="%s" value="%s" /> <span class="description">%s</span>',
					esc_attr( $name ),
					esc_attr( (string) (int) $value ),
					esc_html__( 'Page ID where the search form submits.', 'webhotelier' )
				);
				break;

			case 'url':
			case 'text':
			default:
				printf(
					'<input type="text" class="regular-text" name="%s" value="%s" />',
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;
		}
	}

	/**
	 * Render the full settings screen.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) );
		}
		$test_nonce = wp_create_nonce( 'wh_test_connection' );
		$view       = __DIR__ . '/views/settings-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		// Fallback inline render (kept testable without the view file).
		echo '<div class="wrap"><h1>' . esc_html__( 'WebHotelier Settings', 'webhotelier' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( self::GROUP );
		do_settings_sections( self::OPTION );
		submit_button();
		echo '</form>';
		echo '<hr/><h2>' . esc_html__( 'Test Connection', 'webhotelier' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Leave blank to keep current password.', 'webhotelier' ) . '</p>';
		echo '<button type="button" class="button button-secondary" id="wh-test-connection" data-nonce="' . esc_attr( $test_nonce ) . '">' . esc_html__( 'Test Connection', 'webhotelier' ) . '</button>';
		echo '<div id="wh-test-result" aria-live="polite"></div>';
		echo '</div>';
	}

	/**
	 * AJAX handler for the Test Connection button.
	 * Verifies nonce + capability, then probes the API per mode.
	 */
	public function ajax_test_connection() {
		if ( ! current_user_can( self::CAP ) || ! check_ajax_referer( 'wh_test_connection', 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied or expired token.', 'webhotelier' ) ),
				403
			);
		}

		$mode = (string) $this->settings->get( 'mode', 'single' );

		if ( 'multi' === $mode ) {
			$result = $this->property->search( array() );
			if ( is_wp_error( $result ) ) {
				$this->send_error_from_wp_error( $result );
			}
			$count = 0;
			if ( is_array( $result ) ) {
				if ( isset( $result['count'] ) ) {
					$count = (int) $result['count'];
				} elseif ( isset( $result['data'] ) && is_array( $result['data'] ) ) {
					$count = count( $result['data'] );
				} else {
					$count = count( $result );
				}
			}
			wp_send_json_success(
				array(
					'mode'           => 'multi',
					'account'        => __( 'Multi-property account', 'webhotelier' ),
					'property_count' => $count,
					'message'        => sprintf(
					/* translators: %d: number of properties */
						__( 'Connected. %d properties available.', 'webhotelier' ),
						$count
					),
				)
			);
		}

		// Single-property probe.
		$code   = (string) $this->settings->get( 'default_property', 'DEMO' );
		$result = $this->property->info( $code );
		if ( is_wp_error( $result ) ) {
			$this->send_error_from_wp_error( $result );
		}

		$name     = isset( $result['name'] ) ? (string) $result['name'] : $code;
		$type     = isset( $result['type'] ) ? (string) $result['type'] : '';
		$currency = isset( $result['currency'] ) ? (string) $result['currency'] : '';

		wp_send_json_success(
			array(
				'mode'     => 'single',
				'account'  => __( 'Single-property account', 'webhotelier' ),
				'property' => array(
					'code'     => isset( $result['code'] ) ? (string) $result['code'] : $code,
					'name'     => $name,
					'type'     => $type,
					'currency' => $currency,
				),
				'message'  => sprintf(
					/* translators: 1: property name, 2: property code */
					__( 'Connected to %1$s (%2$s).', 'webhotelier' ),
					$name,
					$code
				),
			)
		);
	}

	/**
	 * Send a JSON error built from a WP_Error returned by the client.
	 *
	 * @param WP_Error $error
	 */
	protected function send_error_from_wp_error( $error ) {
		wp_send_json_error(
			array(
				'error_code' => $error->get_error_code(),
				'error_msg'  => $error->get_error_message(),
				'message'    => $error->get_error_message(),
			),
			200
		);
	}
}
