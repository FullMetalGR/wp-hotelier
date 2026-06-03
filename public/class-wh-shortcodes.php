<?php
/**
 * Registers every wh_* shortcode and dispatches to renderers.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

class WH_Shortcodes {

	/** @var WH_Settings|null */
	private $settings;

	/** @var object|null Injected API container for tests. */
	private $apis;

	/**
	 * @param WH_Settings|null $settings Settings (injected for tests).
	 * @param object|null      $apis     Optional API container.
	 */
	public function __construct( $settings = null, $apis = null ) {
		$this->settings = $settings;
		$this->apis     = $apis;
	}

	/** Map of shortcode tag => renderer method. */
	private function map() {
		return array(
			'wh_search_form'    => 'render_search_form',
			'wh_quick_search'   => 'render_quick_search',
			'wh_booking_flow'   => 'render_booking_flow',
			'wh_availability'   => 'render_availability',
			'wh_search_results' => 'render_search_results',
			'wh_map'            => 'render_map',
			'wh_calendar'       => 'render_calendar',
			'wh_flex_calendar'  => 'render_flex_calendar',
			'wh_bar'            => 'render_bar',
			'wh_price_from'     => 'render_price_from',
			'wh_property'       => 'render_property',
			'wh_property_terms' => 'render_property_terms',
			'wh_rooms'          => 'render_rooms',
			'wh_room'           => 'render_room',
			'wh_gallery'        => 'render_gallery',
			'wh_facilities'     => 'render_facilities',
			'wh_rates'          => 'render_rates',
			'wh_rate'           => 'render_rate',
			'wh_extras'         => 'render_extras',
			'wh_offers'         => 'render_offers',
			'wh_offer'          => 'render_offer',
			'wh_voucher_form'   => 'render_voucher_form',
			'wh_book_button'    => 'render_book_button',
			'wh_booking_engine' => 'render_booking_engine',
			'wh_booking_lookup' => 'render_booking_lookup',
			'wh_my_bookings'    => 'render_my_bookings',
		);
	}

	/** Register every shortcode. */
	public function register() {
		foreach ( $this->map() as $tag => $method ) {
			add_shortcode(
				$tag,
				function ( $atts, $content = '' ) use ( $method ) {
					return $this->{$method}( is_array( $atts ) ? $atts : array(), $content );
				}
			);
		}
	}

	/**
	 * Merge shortcode attributes with common + custom defaults and resolve the
	 * property code (default property in single mode).
	 *
	 * @param array<string,mixed> $atts            Raw shortcode attributes.
	 * @param array<string,mixed> $custom_defaults Per-shortcode defaults.
	 * @return array<string,mixed>
	 */
	public function parse_atts( $atts, array $custom_defaults = array() ) {
		$common   = array(
			'property' => '',
			'class'    => '',
		);
		$defaults = array_merge( $common, $custom_defaults );
		$merged   = shortcode_atts( $defaults, (array) $atts );

		if ( '' === $merged['property'] && 'single' === $this->settings()->mode() ) {
			$merged['property'] = $this->settings()->default_property();
		}

		return $merged;
	}

	/* ----------------------------------------------------------------- */
	/* Accessors                                                         */
	/* ----------------------------------------------------------------- */

	/** @return WH_Settings */
	private function settings() {
		if ( null === $this->settings ) {
			$this->settings = WH_Plugin::instance()->settings();
		}
		return $this->settings;
	}

	/** @return WH_Property_API */
	private function property_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'property_api' ) && $this->apis->property_api() ) {
			return $this->apis->property_api();
		}
		return new WH_Property_API( WH_Plugin::instance()->client() );
	}

	/** @return WH_Availability_API */
	private function availability_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'availability_api' ) && $this->apis->availability_api() ) {
			return $this->apis->availability_api();
		}
		return new WH_Availability_API( WH_Plugin::instance()->client() );
	}

	/** @return WH_Offers_API */
	private function offers_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'offers_api' ) && $this->apis->offers_api() ) {
			return $this->apis->offers_api();
		}
		return new WH_Offers_API( WH_Plugin::instance()->client() );
	}

	/** @return WH_Bookings_API */
	private function bookings_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'bookings_api' ) && $this->apis->bookings_api() ) {
			return $this->apis->bookings_api();
		}
		return new WH_Bookings_API( WH_Plugin::instance()->client() );
	}

	/**
	 * Render a WP_Error consistently across shortcodes.
	 *
	 * @param WP_Error $error Error.
	 * @return string
	 */
	private function render_error( $error ) {
		return WH_Render::error( $error );
	}

	/* ----------------------------------------------------------------- */
	/* Renderer stubs (filled by later tasks)                            */
	/* ----------------------------------------------------------------- */

	/**
	 * [wh_search_form] — date + occupancy search form.
	 *
	 * @param array  $atts    Attributes (layout, target, rooms_max, adults_default).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_search_form( $atts, $content = '' ) {
		return $this->search_form( $atts, 'full' );
	}

	/**
	 * [wh_quick_search] — compact inline search form.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_quick_search( $atts, $content = '' ) {
		return $this->search_form( $atts, 'compact' );
	}

	/**
	 * Shared search-form renderer.
	 *
	 * @param array  $atts   Attributes.
	 * @param string $layout 'full' or 'compact'.
	 * @return string
	 */
	private function search_form( $atts, $layout ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'layout'         => $layout,
				'target'         => '',
				'rooms_max'      => '4',
				'adults_default' => '2',
			)
		);

		$action = $a['target'];
		if ( '' === $action ) {
			$page = (int) $this->settings()->results_page();
			if ( $page > 0 ) {
				$action = (string) get_permalink( $page );
			}
		}

		return WH_Render::template(
			'search-form',
			array(
				'action'         => $action,
				'compact'        => 'compact' === $layout,
				'property'       => $a['property'],
				'mode'           => $this->settings()->mode(),
				'rooms_max'      => max( 1, (int) $a['rooms_max'] ),
				'adults_default' => max( 1, (int) $a['adults_default'] ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'class'          => $a['class'],
			)
		);
	}

	/**
	 * [wh_booking_flow] — one-page wizard orchestrating search → results →
	 * review → complete → confirmation.
	 *
	 * @param array  $atts    Attributes (property, class).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_booking_flow( $atts, $content = '' ) {
		$a    = $this->parse_atts( $atts );
		$flow = new WH_Booking_Flow( $this->settings(), $this->apis, $this );

		// Read query state; sanitize the keys we use.
		$query = array();
		$keys  = array( 'wh_step', 'wh_nonce', 'checkin', 'checkout', 'adults', 'children', 'rooms', 'property', 'room', 'rate', 'voucher', 'price', 'payment_method', 'firstname', 'lastname', 'email', 'phone', 'country', 'remarks' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only step routing; transitions re-check nonce in WH_Booking_Flow.
		$source = array_merge( $_GET, $_POST );
		foreach ( $keys as $k ) {
			if ( isset( $source[ $k ] ) ) {
				$query[ $k ] = sanitize_text_field( wp_unslash( (string) $source[ $k ] ) );
			}
		}
		if ( isset( $source['email'] ) ) {
			$query['email'] = sanitize_email( wp_unslash( (string) $source['email'] ) );
		}

		return $flow->render( $query, $a );
	}
	/**
	 * [wh_availability] — single-property rate cards.
	 *
	 * @param array  $atts    Attributes (room, rate, layout, max_rates, checkin, checkout, adults, children, voucher).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_availability( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room'      => '',
				'rate'      => '',
				'layout'    => 'cards',
				'max_rates' => '0',
				'checkin'   => '',
				'checkout'  => '',
				'adults'    => '2',
				'children'  => '0',
				'voucher'   => '',
			)
		);

		$params = $this->availability_params_from_atts( $a );
		$data   = $this->availability_api()->single( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		$rates = isset( $data['rates'] ) && is_array( $data['rates'] ) ? $data['rates'] : array();
		$rates = $this->filter_rates( $rates, $a['room'], $a['rate'] );
		$max   = (int) $a['max_rates'];
		if ( $max > 0 ) {
			$rates = array_slice( $rates, 0, $max );
		}

		return WH_Render::template(
			'availability',
			array(
				'data'     => $data,
				'rates'    => $rates,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'layout'   => sanitize_key( $a['layout'] ),
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * Build availability params from parsed shortcode attributes.
	 *
	 * @param array<string,mixed> $a Parsed atts.
	 * @return array<string,mixed>
	 */
	private function availability_params_from_atts( array $a ) {
		$params = array();
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['checkin'] ) ) {
			$params['checkin'] = $a['checkin'];
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['checkout'] ) ) {
			$params['checkout'] = $a['checkout'];
		}
		$adults = (int) $a['adults'];
		if ( $adults > 0 ) {
			$params['adults'] = $adults;
		}
		$children = (int) $a['children'];
		if ( $children > 0 ) {
			$params['children'] = $children;
		}
		if ( '' !== $a['voucher'] ) {
			$params['voucher'] = $a['voucher'];
		}
		return $params;
	}

	/**
	 * Filter a flat rate list by room code and/or rate id.
	 *
	 * @param array  $rates Flat rate list.
	 * @param string $room  Room code filter ('' = all).
	 * @param string $rate  Rate id filter ('' = all).
	 * @return array
	 */
	private function filter_rates( array $rates, $room, $rate ) {
		if ( '' === $room && '' === $rate ) {
			return $rates;
		}
		$out = array();
		foreach ( $rates as $r ) {
			if ( '' !== $room && ( ! isset( $r['type'] ) || (string) $r['type'] !== (string) $room ) ) {
				continue;
			}
			if ( '' !== $rate && ( ! isset( $r['id'] ) || (string) $r['id'] !== (string) $rate ) ) {
				continue;
			}
			$out[] = $r;
		}
		return $out;
	}

	/**
	 * [wh_search_results] — multi-property results list (falls back to single
	 * availability in single mode).
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_search_results( $atts, $content = '' ) {
		$a      = $this->parse_atts(
			$atts,
			array(
				'checkin'  => '',
				'checkout' => '',
				'adults'   => '2',
				'children' => '0',
				'voucher'  => '',
			)
		);
		$params = $this->availability_params_from_atts( $a );

		if ( 'multi' === $this->settings()->mode() ) {
			$data = $this->availability_api()->multi( $params );
			if ( is_wp_error( $data ) ) {
				return $this->render_error( $data );
			}
			$results = isset( $data['results'] ) && is_array( $data['results'] ) ? $data['results'] : array();
			return WH_Render::template(
				'search-results',
				array(
					'results' => $results,
					'class'   => $a['class'],
				)
			);
		}

		// Single mode: render the single-property availability cards instead.
		$data = $this->availability_api()->single( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$rates = isset( $data['rates'] ) && is_array( $data['rates'] ) ? $data['rates'] : array();
		return WH_Render::template(
			'search-results',
			array(
				'results'  => array(),
				'single'   => true,
				'data'     => $data,
				'rates'    => $rates,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * [wh_map] — Leaflet (default) or Google map with property marker(s).
	 *
	 * @param array  $atts    Attributes (provider, zoom, height).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_map( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'provider' => '',
				'zoom'     => '13',
				'height'   => '360',
			)
		);

		$provider = '' !== $a['provider'] ? sanitize_key( $a['provider'] ) : sanitize_key( (string) $this->settings()->map_provider() );
		if ( '' === $provider ) {
			$provider = 'leaflet';
		}

		$data = $this->property_api()->info( $a['property'], null );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		$markers = array();
		if ( isset( $data['location']['lat'], $data['location']['lon'] ) ) {
			$markers[] = array(
				'lat'   => (float) $data['location']['lat'],
				'lon'   => (float) $data['location']['lon'],
				'label' => isset( $data['name'] ) ? (string) $data['name'] : '',
			);
		}

		// Leaflet maps need the Leaflet library + our initializer; the wh-map
		// script depends on wh-leaflet (JS), but the stylesheet must be enqueued
		// explicitly since styles are not pulled in via script dependencies.
		if ( 'leaflet' === $provider ) {
			wp_enqueue_style( 'wh-leaflet' );
			wp_enqueue_script( 'wh-map' );
		}

		return WH_Render::template(
			'map',
			array(
				'provider' => $provider,
				'zoom'     => max( 1, (int) $a['zoom'] ),
				'height'   => max( 100, (int) $a['height'] ),
				'markers'  => $markers,
				'api_key'  => (string) $this->settings()->map_api_key(),
				'class'    => $a['class'],
			)
		);
	}
	/**
	 * [wh_calendar] — price/availability calendar.
	 *
	 * Live endpoint requires a fromd/tod date window (tod within fromd + 3
	 * months). Attributes: months (default 1, cap 3) and optional from/to dates;
	 * fromd defaults to today.
	 *
	 * @param array  $atts    Attributes (room, rate, months, from, to).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_calendar( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room'   => '',
				'rate'   => '',
				'months' => '1',
				'from'   => '',
				'to'     => '',
			)
		);

		$months = (int) $a['months'];
		if ( $months < 1 ) {
			$months = 1;
		}
		if ( $months > 3 ) {
			$months = 3;
		}

		$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['from'] ) ? (string) $a['from'] : '';
		$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['to'] ) ? (string) $a['to'] : '';

		$window = WH_Proxy::date_window( $from, $months );
		$fromd  = $window['fromd'];
		$tod    = '' !== $to ? $to : $window['tod'];
		$capped = WH_Proxy::cap_tod( $fromd, $tod );

		$params = array(
			'fromd' => $capped['fromd'],
			'tod'   => $capped['tod'],
		);
		if ( '' !== $a['room'] ) {
			$params['room'] = $a['room'];
		}
		if ( '' !== $a['rate'] ) {
			$params['rate'] = $a['rate'];
		}

		$data = $this->availability_api()->calendar( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		// The calendar widget can refresh itself live (data-live=1); enqueue its
		// script so that behaviour is available on the front end. It requests the
		// same fromd/tod window and renders the same days shape.
		wp_enqueue_script( 'wh-calendar' );

		$days = isset( $data['days'] ) && is_array( $data['days'] ) ? $data['days'] : array();
		return WH_Render::template(
			'calendar',
			array(
				'days'     => $days,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : ( isset( $data['property']['currency'] ) ? $data['property']['currency'] : $this->settings()->currency() ),
				'property' => (string) $a['property'],
				'fromd'    => $capped['fromd'],
				'tod'      => $capped['tod'],
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * [wh_flex_calendar] — flexible-stay availability.
	 *
	 * @param array  $atts    Attributes (room, rate, checkin, adults, children).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_flex_calendar( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room'     => '',
				'rate'     => '',
				'checkin'  => '',
				'adults'   => '2',
				'children' => '0',
			)
		);

		$params = $this->availability_params_from_atts(
			array_merge( $a, array( 'checkout' => '', 'voucher' => '' ) )
		);
		if ( '' !== $a['room'] ) {
			$params['room'] = $a['room'];
		}

		$data = $this->availability_api()->flexibleCalendar( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		$stays = isset( $data['stays'] ) && is_array( $data['stays'] ) ? $data['stays'] : array();
		return WH_Render::template(
			'flex-calendar',
			array(
				'stays'    => $stays,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * [wh_bar] — best-available-rate widget.
	 *
	 * @param array  $atts    Attributes (checkin, checkout, adults, children).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_bar( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'checkin'  => '',
				'checkout' => '',
				'adults'   => '2',
				'children' => '0',
			)
		);

		$params = $this->availability_params_from_atts( array_merge( $a, array( 'voucher' => '' ) ) );
		$data   = $this->availability_api()->bar( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		return WH_Render::template(
			'bar',
			array(
				'price'    => isset( $data['price'] ) ? $data['price'] : null,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'engine'   => isset( $data['url']['engine'] ) ? $data['url']['engine'] : '',
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * [wh_price_from] — "from €X / night" teaser badge using the BAR price.
	 *
	 * @param array  $atts    Attributes (checkin, checkout, adults).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_price_from( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'checkin'  => '',
				'checkout' => '',
				'adults'   => '2',
				'children' => '0',
			)
		);

		$params = $this->availability_params_from_atts( array_merge( $a, array( 'voucher' => '' ) ) );
		$data   = $this->availability_api()->bar( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return '';
		}
		$price = isset( $data['price'] ) ? $data['price'] : null;
		if ( null === $price ) {
			return '';
		}

		return WH_Render::template(
			'price-from',
			array(
				'price'    => $price,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'class'    => $a['class'],
			)
		);
	}
	/**
	 * [wh_property] — property info card.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Inner content (unused).
	 * @return string
	 */
	public function render_property( $atts, $content = '' ) {
		$a    = $this->parse_atts( $atts, array( 'include' => '' ) );
		$data = $this->property_api()->info( $a['property'], '' === $a['include'] ? null : $a['include'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		return WH_Render::template(
			'property',
			array(
				'data'  => $data,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_property_terms] — general terms block.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_property_terms( $atts, $content = '' ) {
		$a    = $this->parse_atts( $atts );
		$data = $this->property_api()->info( $a['property'], 'general_terms' );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$terms = isset( $data['general_terms'] ) ? $data['general_terms'] : '';
		return WH_Render::template(
			'property-terms',
			array(
				'terms' => $terms,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_rooms] — villa/room grid.
	 *
	 * @param array  $atts    Attributes (columns, show_price, link).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_rooms( $atts, $content = '' ) {
		$a    = $this->parse_atts(
			$atts,
			array(
				'columns'    => '3',
				'show_price' => 'no',
				'link'       => '',
			)
		);
		$data = $this->property_api()->rooms( $a['property'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$rooms = isset( $data['rooms'] ) && is_array( $data['rooms'] ) ? $data['rooms'] : array();
		return WH_Render::template(
			'rooms',
			array(
				'rooms'    => $rooms,
				'columns'  => max( 1, (int) $a['columns'] ),
				'link'     => $a['link'],
				'property' => $a['property'],
				'class'    => $a['class'],
			)
		);
	}

	/**
	 * [wh_room] — single villa/room detail.
	 *
	 * @param array  $atts    Attributes (room required).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_room( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts, array( 'room' => '' ) );
		if ( '' === $a['room'] ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'A room code is required.', 'webhotelier' ),
				)
			);
		}
		$data = $this->property_api()->room( $a['property'], $a['room'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		return WH_Render::template(
			'room',
			array(
				'data'  => $data,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_gallery] — property or room photo slider.
	 *
	 * @param array  $atts    Attributes (room optional).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_gallery( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts, array( 'room' => '' ) );

		if ( '' !== $a['room'] ) {
			$data = $this->property_api()->room( $a['property'], $a['room'] );
		} else {
			$data = $this->property_api()->info( $a['property'], null );
		}
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		$photos = isset( $data['photos'] ) && is_array( $data['photos'] ) ? $data['photos'] : array();
		return WH_Render::template(
			'gallery',
			array(
				'photos' => $photos,
				'class'  => $a['class'],
			)
		);
	}

	/**
	 * [wh_facilities] — facilities list (property or room).
	 *
	 * @param array  $atts    Attributes (room optional).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_facilities( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts, array( 'room' => '' ) );

		if ( '' !== $a['room'] ) {
			$data = $this->property_api()->room( $a['property'], $a['room'] );
		} else {
			$data = $this->property_api()->info( $a['property'], null );
		}
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}

		$facilities = isset( $data['facilities'] ) && is_array( $data['facilities'] ) ? $data['facilities'] : array();
		return WH_Render::template(
			'facilities',
			array(
				'facilities' => $facilities,
				'class'      => $a['class'],
			)
		);
	}
	/**
	 * [wh_rates] — rate listing for a room.
	 *
	 * @param array  $atts    Attributes (room optional).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_rates( $atts, $content = '' ) {
		$a    = $this->parse_atts( $atts, array( 'room' => '' ) );
		$room = '' === $a['room'] ? null : $a['room'];
		$data = $this->property_api()->rates( $a['property'], $room );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$rates = isset( $data['rates'] ) && is_array( $data['rates'] ) ? $data['rates'] : array();
		return WH_Render::template(
			'rates',
			array(
				'rates' => $rates,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_rate] — single rate detail.
	 *
	 * @param array  $atts    Attributes (room + rate required).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_rate( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room' => '',
				'rate' => '',
			)
		);
		if ( '' === $a['room'] || '' === $a['rate'] ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'Both a room and a rate id are required.', 'webhotelier' ),
				)
			);
		}
		$data = $this->property_api()->rate( $a['property'], $a['room'], $a['rate'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		return WH_Render::template(
			'rate',
			array(
				'data'  => $data,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_extras] — bookable extras/services.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_extras( $atts, $content = '' ) {
		$a    = $this->parse_atts( $atts );
		$data = $this->property_api()->extras( $a['property'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$extras = isset( $data['extras'] ) && is_array( $data['extras'] ) ? $data['extras'] : array();
		return WH_Render::template(
			'extras',
			array(
				'extras' => $extras,
				'class'  => $a['class'],
			)
		);
	}
	/**
	 * [wh_offers] — offers list for a property.
	 *
	 * @param array  $atts    Attributes (checkin).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_offers( $atts, $content = '' ) {
		$a      = $this->parse_atts( $atts, array( 'checkin' => '' ) );
		$params = array();
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['checkin'] ) ) {
			$params['checkin'] = $a['checkin'];
		}
		$data = $this->offers_api()->single( $a['property'], $params );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$offers = isset( $data['offers'] ) && is_array( $data['offers'] ) ? $data['offers'] : array();
		return WH_Render::template(
			'offers',
			array(
				'offers' => $offers,
				'class'  => $a['class'],
			)
		);
	}

	/**
	 * [wh_offer] — single offer detail.
	 *
	 * @param array  $atts    Attributes (offer required).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_offer( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts, array( 'offer' => '' ) );
		if ( '' === $a['offer'] ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'An offer id is required.', 'webhotelier' ),
				)
			);
		}
		$data = $this->offers_api()->info( $a['property'], $a['offer'] );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		return WH_Render::template(
			'offer',
			array(
				'data'  => $data,
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_voucher_form] — voucher/secret-code entry; forwards `voucher` to
	 * subsequent availability calls.
	 *
	 * @param array  $atts    Attributes (target, label).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_voucher_form( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'target' => '',
				'label'  => '',
			)
		);

		$action = $a['target'];
		if ( '' === $action ) {
			$page = (int) $this->settings()->results_page();
			if ( $page > 0 ) {
				$action = (string) get_permalink( $page );
			}
		}

		return WH_Render::template(
			'voucher-form',
			array(
				'action'   => $action,
				'property' => $a['property'],
				'mode'     => $this->settings()->mode(),
				'label'    => '' !== $a['label'] ? $a['label'] : __( 'Voucher / secret code', 'webhotelier' ),
				'class'    => $a['class'],
			)
		);
	}
	/**
	 * [wh_book_button] — deep-link CTA to the hosted engine.
	 *
	 * @param array  $atts    Attributes (room, rate, checkin, checkout, adults,
	 *                        children, voucher, label, open, class).
	 * @param string $content Optional inner label.
	 * @return string
	 */
	public function render_book_button( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room'     => '',
				'rate'     => '',
				'checkin'  => '',
				'checkout' => '',
				'adults'   => '2',
				'children' => '0',
				'voucher'  => '',
				'label'    => '',
				'open'     => $this->settings()->engine_open(),
			)
		);

		$data = $this->property_api()->info( $a['property'], null );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$engine = isset( $data['url']['engine'] ) ? $data['url']['engine'] : '';
		if ( '' === $engine ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'Booking is currently unavailable.', 'webhotelier' ),
				)
			);
		}

		$params = $this->engine_params_from_atts( $a );

		$url = WH_Handoff::build( $engine, $params );

		$label = '' !== $a['label'] ? $a['label'] : ( '' !== trim( (string) $content ) ? $content : __( 'Book now', 'webhotelier' ) );

		return WH_Render::template(
			'book-button',
			array(
				'url'   => $url,
				'label' => $label,
				'open'  => sanitize_key( $a['open'] ),
				'class' => $a['class'],
			)
		);
	}

	/**
	 * [wh_booking_engine] — responsive iframe embedding the hosted engine.
	 *
	 * @param array  $atts    Attributes (room, rate, checkin, checkout, adults,
	 *                        children, voucher, height).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_booking_engine( $atts, $content = '' ) {
		$a = $this->parse_atts(
			$atts,
			array(
				'room'     => '',
				'rate'     => '',
				'checkin'  => '',
				'checkout' => '',
				'adults'   => '2',
				'children' => '0',
				'voucher'  => '',
				'height'   => '800',
			)
		);

		$data = $this->property_api()->info( $a['property'], null );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$engine = isset( $data['url']['engine'] ) ? $data['url']['engine'] : '';
		if ( '' === $engine ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'Booking engine is unavailable.', 'webhotelier' ),
				)
			);
		}

		$src = WH_Handoff::build( $engine, $this->engine_params_from_atts( $a ) );

		return WH_Render::template(
			'booking-engine',
			array(
				'src'    => $src,
				'height' => max( 300, (int) $a['height'] ),
				'class'  => $a['class'],
			)
		);
	}

	/**
	 * Build handoff engine params (checkin/checkout/party/room/rate/voucher/lang)
	 * from parsed shortcode attributes.
	 *
	 * @param array<string,mixed> $a Parsed atts.
	 * @return array<string,mixed>
	 */
	private function engine_params_from_atts( array $a ) {
		$params = array();
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['checkin'] ) ) {
			$params['checkin'] = $a['checkin'];
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $a['checkout'] ) ) {
			$params['checkout'] = $a['checkout'];
		}
		$adults = (int) $a['adults'];
		if ( $adults > 0 ) {
			$party    = array( array( 'adults' => $adults ) );
			$children = (int) $a['children'];
			if ( $children > 0 ) {
				$party[0]['children'] = $children;
			}
			$params['party'] = wp_json_encode( $party );
		}
		if ( '' !== $a['room'] ) {
			$params['room'] = $a['room'];
		}
		if ( '' !== $a['rate'] ) {
			$params['rate'] = $a['rate'];
		}
		if ( '' !== $a['voucher'] ) {
			$params['voucher'] = $a['voucher'];
		}
		$params['lang'] = WH_I18n::accept_language( $this->settings() );

		return $params;
	}

	/**
	 * [wh_booking_lookup] — "find my reservation" form.
	 *
	 * The lookup itself runs through the proxy (anti-enumeration guarded);
	 * this shortcode renders the form plus an empty results container.
	 *
	 * @param array  $atts    Attributes (allow_cancel).
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_booking_lookup( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts, array( 'allow_cancel' => 'no' ) );

		return WH_Render::template(
			'booking-lookup',
			array(
				'endpoint'     => rest_url( 'webhotelier/v1/lookup' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'allow_cancel' => ( 'yes' === strtolower( (string) $a['allow_cancel'] ) ),
				'class'        => $a['class'],
			)
		);
	}

	/**
	 * [wh_my_bookings] — list reservations for the logged-in user (matched by
	 * account email).
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Unused.
	 * @return string
	 */
	public function render_my_bookings( $atts, $content = '' ) {
		$a = $this->parse_atts( $atts );

		if ( ! is_user_logged_in() ) {
			return WH_Render::template(
				'my-bookings',
				array(
					'logged_in'    => false,
					'reservations' => array(),
					'class'        => $a['class'],
				)
			);
		}

		$user  = wp_get_current_user();
		$email = isset( $user->user_email ) ? (string) $user->user_email : '';
		if ( '' === $email ) {
			return WH_Render::template(
				'my-bookings',
				array(
					'logged_in'    => true,
					'reservations' => array(),
					'class'        => $a['class'],
				)
			);
		}

		$data = $this->bookings_api()->search( array( 'email' => $email ) );
		if ( is_wp_error( $data ) ) {
			return $this->render_error( $data );
		}
		$reservations = isset( $data['reservations'] ) && is_array( $data['reservations'] ) ? $data['reservations'] : array();

		return WH_Render::template(
			'my-bookings',
			array(
				'logged_in'    => true,
				'reservations' => $reservations,
				'class'        => $a['class'],
			)
		);
	}

	/**
	 * Register public styles/scripts (called on wp_enqueue_scripts).
	 *
	 * Scripts are registered (not forced) so they can be enqueued on demand by
	 * shortcodes that need them; we enqueue the base bundle globally because it
	 * is tiny and the proxy config must be present for any interactive widget.
	 */
	public function register_assets() {
		wp_register_style(
			'wh-public',
			WH_URL . 'public/assets/css/public.css',
			array(),
			WH_VERSION
		);
		// Leaflet is loaded from the public CDN (no library is bundled). Pin the
		// version and use SRI-friendly stable URLs so window.L is defined before
		// wh-map runs.
		wp_register_style(
			'wh-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);
		wp_register_script(
			'wh-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);
		wp_register_script(
			'wh-public',
			WH_URL . 'public/assets/js/public.js',
			array(),
			WH_VERSION,
			true
		);
		wp_register_script(
			'wh-calendar',
			WH_URL . 'public/assets/js/calendar.js',
			array(),
			WH_VERSION,
			true
		);
		wp_register_script(
			'wh-map',
			WH_URL . 'public/assets/js/map.js',
			array( 'wh-leaflet' ),
			WH_VERSION,
			true
		);

		wp_localize_script(
			'wh-public',
			'WH_Public',
			array(
				'root'  => rest_url( 'webhotelier/v1' ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'  => array(
					'searching' => __( 'Searching…', 'webhotelier' ),
					'noResults' => __( 'No availability for the selected dates.', 'webhotelier' ),
					'error'     => __( 'Something went wrong. Please try again.', 'webhotelier' ),
					'notFound'  => __( 'No matching reservation was found.', 'webhotelier' ),
				),
			)
		);
	}

	/**
	 * Enqueue the base public bundle on the front end.
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'wh-public' );
		wp_enqueue_script( 'wh-public' );
	}
}
