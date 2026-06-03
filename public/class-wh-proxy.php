<?php
/**
 * REST proxy: the only surface the browser talks to.
 *
 * All routes verify a wp_rest nonce (X-WP-Nonce). Read routes are public;
 * write routes additionally require manage_options. Inputs are sanitized,
 * forwarded to the Plan 01 API resource classes, and returned as JSON.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

/**
 * Nonce-protected REST proxy: the only bridge the browser uses to reach credentialed WebHotelier calls.
 */
class WH_Proxy {

	const NS = 'webhotelier/v1';

	/** @var WH_Settings|null */
	private $settings;

	/** @var object|null Lazy resource container. */
	private $apis;

	/**
	 * @param WH_Settings|null $settings Optional injected settings (tests).
	 * @param object|null      $apis     Optional injected API container (tests).
	 */
	public function __construct( $settings = null, $apis = null ) {
		$this->settings = $settings;
		$this->apis     = $apis;
	}

	/** Hook registration. */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/** Register every proxy route. */
	public function register_routes() {
		$public = array( $this, 'check_public_nonce' );
		$write  = array( $this, 'check_write_permission' );

		register_rest_route(
			self::NS,
			'/availability',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_availability' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/property',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_property' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/rooms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_rooms' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/room',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_room' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/rates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_rates' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/offers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_offers' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/calendar',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_calendar' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/bar',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_bar' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/extras',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_extras' ),
				'permission_callback' => $public,
			)
		);
		register_rest_route(
			self::NS,
			'/book',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'route_book' ),
				'permission_callback' => $write,
			)
		);
		register_rest_route(
			self::NS,
			'/lookup',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'route_lookup' ),
				'permission_callback' => $public,
			)
		);
	}

	/*
	----------------------------------------------------------------- */
	/*
	Permission callbacks                                              */
	/* ----------------------------------------------------------------- */

	/**
	 * Public read routes: a valid wp_rest nonce is required.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function check_public_nonce( $request ) {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Write routes: valid nonce + manage_options.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function check_write_permission( $request ) {
		if ( ! $this->check_public_nonce( $request ) ) {
			return false;
		}
		return (bool) current_user_can( 'manage_options' );
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: availability                                              */
	/* ----------------------------------------------------------------- */

	/**
	 * GET /availability — single or multi per mode.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_availability( $request ) {
		$settings = $this->settings();
		$mode     = $settings->mode();
		$params   = $this->sanitize_availability_params( $request );

		$api = $this->availability_api();

		if ( 'multi' === $mode && empty( $params['property'] ) ) {
			$result = $api->multi( $params );
		} else {
			$code   = $this->resolve_property( $request );
			$result = $api->single( $code, $params );
		}

		return $this->respond( $result );
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: property / rooms / room / rates                            */
	/* ----------------------------------------------------------------- */

	/**
	 * GET /property — property info card.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_property( $request ) {
		$code    = $this->resolve_property( $request );
		$include = sanitize_text_field( (string) $request->get_param( 'include' ) );
		$include = '' === $include ? null : $include;
		return $this->respond( $this->property_api()->info( $code, $include ) );
	}

	/**
	 * GET /rooms — room/villa list.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_rooms( $request ) {
		$code = $this->resolve_property( $request );
		return $this->respond( $this->property_api()->rooms( $code ) );
	}

	/**
	 * GET /room — single room detail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_room( $request ) {
		$room = sanitize_text_field( (string) $request->get_param( 'room' ) );
		if ( '' === $room ) {
			return $this->error( 'wh_missing_param', __( 'A room code is required.', 'webhotelier' ) );
		}
		$code = $this->resolve_property( $request );
		return $this->respond( $this->property_api()->room( $code, $room ) );
	}

	/**
	 * GET /rates — rate listing for a room (room optional).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_rates( $request ) {
		$room = sanitize_text_field( (string) $request->get_param( 'room' ) );
		$room = '' === $room ? null : $room;
		$code = $this->resolve_property( $request );
		return $this->respond( $this->property_api()->rates( $code, $room ) );
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: offers                                                     */
	/* ----------------------------------------------------------------- */

	/**
	 * GET /offers — offers for a property.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_offers( $request ) {
		$code    = $this->resolve_property( $request );
		$params  = array();
		$checkin = sanitize_text_field( (string) $request->get_param( 'checkin' ) );
		if ( $this->is_date( $checkin ) ) {
			$params['checkin'] = $checkin;
		}
		return $this->respond( $this->offers_api()->single( $code, $params ) );
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: calendar / bar / extras                                    */
	/* ----------------------------------------------------------------- */

	/**
	 * GET /calendar — price/availability calendar.
	 *
	 * The live endpoint requires a fromd/tod date window (tod must be within
	 * fromd + 3 months); a `months` count is accepted as a convenience and
	 * converted to fromd=today / tod=fromd+months (capped at 3 months).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_calendar( $request ) {
		$code   = $this->resolve_property( $request );
		$params = array();

		$fromd = sanitize_text_field( (string) $request->get_param( 'fromd' ) );
		$tod   = sanitize_text_field( (string) $request->get_param( 'tod' ) );

		if ( ! $this->is_date( $fromd ) ) {
			$fromd = '';
		}
		if ( ! $this->is_date( $tod ) ) {
			$tod = '';
		}

		// Fall back to a months window when explicit dates were not supplied.
		if ( '' === $fromd || '' === $tod ) {
			$months = absint( $request->get_param( 'months' ) );
			$window = self::date_window( $fromd, $months );
			$fromd  = $window['fromd'];
			$tod    = '' !== $tod ? $tod : $window['tod'];
		}

		// Enforce the 3-month cap regardless of how tod was derived.
		$capped = self::cap_tod( $fromd, $tod );

		$params['fromd'] = $capped['fromd'];
		$params['tod']   = $capped['tod'];

		$adults   = absint( $request->get_param( 'adults' ) );
		$children = absint( $request->get_param( 'children' ) );
		if ( $adults > 0 ) {
			$params['adults'] = $adults;
		}
		if ( $children > 0 ) {
			$params['children'] = $children;
		}

		$room = sanitize_text_field( (string) $request->get_param( 'room' ) );
		if ( '' !== $room ) {
			$params['room'] = $room;
		}
		$rate = sanitize_text_field( (string) $request->get_param( 'rate' ) );
		if ( '' !== $rate ) {
			$params['rate'] = $rate;
		}

		return $this->respond( $this->availability_api()->calendar( $code, $params ) );
	}

	/**
	 * Compute a fromd/tod window from an optional start date + months count.
	 *
	 * @param string $fromd  Start date (Y-m-d) or '' for today.
	 * @param int    $months Number of months (1..3; default 1).
	 * @return array{fromd:string,tod:string}
	 */
	public static function date_window( $fromd, $months ) {
		$start  = ( '' !== $fromd ) ? $fromd : gmdate( 'Y-m-d' );
		$months = (int) $months;
		if ( $months < 1 ) {
			$months = 1;
		}
		if ( $months > 3 ) {
			$months = 3;
		}
		$ts = strtotime( $start );
		if ( false === $ts ) {
			$start = gmdate( 'Y-m-d' );
			$ts    = strtotime( $start );
		}
		$tod = gmdate( 'Y-m-d', strtotime( '+' . $months . ' months', $ts ) );
		return array(
			'fromd' => $start,
			'tod'   => $tod,
		);
	}

	/**
	 * Clamp tod so it never exceeds fromd + 3 months (API constraint).
	 *
	 * @param string $fromd Start date (Y-m-d).
	 * @param string $tod   End date (Y-m-d).
	 * @return array{fromd:string,tod:string}
	 */
	public static function cap_tod( $fromd, $tod ) {
		$from_ts = strtotime( $fromd );
		$tod_ts  = strtotime( $tod );
		if ( false === $from_ts || false === $tod_ts ) {
			return array(
				'fromd' => $fromd,
				'tod'   => $tod,
			);
		}
		$max_ts = strtotime( '+3 months', $from_ts );
		if ( $tod_ts > $max_ts ) {
			$tod = gmdate( 'Y-m-d', $max_ts );
		}
		return array(
			'fromd' => $fromd,
			'tod'   => $tod,
		);
	}

	/**
	 * GET /bar — best-available-rate.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_bar( $request ) {
		$code   = $this->resolve_property( $request );
		$params = $this->sanitize_availability_params( $request );
		return $this->respond( $this->availability_api()->bar( $code, $params ) );
	}

	/**
	 * GET /extras — extras for a specific rate id.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_extras( $request ) {
		$rate = sanitize_text_field( (string) $request->get_param( 'rate' ) );
		if ( '' === $rate ) {
			return $this->error( 'wh_missing_param', __( 'A rate id is required.', 'webhotelier' ) );
		}
		$code   = $this->resolve_property( $request );
		$params = $this->sanitize_availability_params( $request );
		return $this->respond( $this->availability_api()->extras( $code, $rate, $params ) );
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: book (native non-card only)                                */
	/* ----------------------------------------------------------------- */

	/** Card-related field names that are never allowed through the proxy. */
	const CARD_FIELDS = array(
		'card_number',
		'cardnumber',
		'card',
		'cc_number',
		'ccnumber',
		'cvv',
		'cvc',
		'cvv2',
		'card_cvc',
		'card_expiry',
		'card_exp',
		'exp_month',
		'exp_year',
		'card_holder',
		'cardholder',
		'card_type',
		'pan',
	);

	/** Allowed non-card payment methods. */
	const PAYMENT_METHODS = array( 'CHKIN', 'BANK' );

	/**
	 * POST /book — only allowed when completion_mode === native_noncard.
	 *
	 * Rejects any card field, restricts payment_method to CHKIN/BANK, and
	 * forwards a sanitized guest payload to WH_Bookings_API::create().
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_book( $request ) {
		if ( 'native_noncard' !== $this->settings()->completion_mode() ) {
			return $this->error( 'wh_book_disabled', __( 'Native booking is disabled; use the hosted booking engine.', 'webhotelier' ) );
		}

		$raw = $request->get_json_params();
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		// Reject any card-bearing field outright (case-insensitive).
		foreach ( array_keys( $raw ) as $key ) {
			$norm = strtolower( str_replace( array( '-', ' ' ), '_', (string) $key ) );
			if ( in_array( $norm, self::CARD_FIELDS, true ) ) {
				return $this->error( 'wh_card_rejected', __( 'Card data must never be sent to this site; use the hosted engine.', 'webhotelier' ) );
			}
		}

		$method = strtoupper( sanitize_text_field( (string) ( $raw['payment_method'] ?? '' ) ) );
		if ( ! in_array( $method, self::PAYMENT_METHODS, true ) ) {
			return $this->error( 'wh_invalid_payment', __( 'Only check-in or bank-transfer payment is allowed here.', 'webhotelier' ) );
		}

		$params                   = $this->sanitize_book_params( $raw );
		$params['payment_method'] = $method;

		$code = sanitize_text_field( (string) ( $raw['property'] ?? '' ) );
		if ( '' === $code ) {
			$code = $this->settings()->default_property();
		}

		return $this->respond( $this->bookings_api()->create( $code, $params ) );
	}

	/**
	 * Sanitize a native-book payload into a flat, card-free param array.
	 *
	 * @param array<string,mixed> $raw Raw JSON body.
	 * @return array<string,mixed>
	 */
	private function sanitize_book_params( array $raw ) {
		$out = array();

		$text_fields = array(
			'rate',
			'room',
			'checkin',
			'checkout',
			'firstname',
			'lastname',
			'phone',
			'country',
			'voucher',
			'remarks',
			'source',
			'res_id',
			'price',
			'currency',
		);
		foreach ( $text_fields as $field ) {
			if ( isset( $raw[ $field ] ) && '' !== $raw[ $field ] ) {
				$out[ $field ] = sanitize_text_field( (string) $raw[ $field ] );
			}
		}

		if ( isset( $raw['email'] ) ) {
			$out['email'] = sanitize_email( (string) $raw['email'] );
		}

		foreach ( array( 'adults', 'children' ) as $num ) {
			if ( isset( $raw[ $num ] ) ) {
				$out[ $num ] = absint( $raw[ $num ] );
			}
		}

		// Pass-through party JSON if provided (already a JSON string).
		if ( isset( $raw['party'] ) && is_string( $raw['party'] ) ) {
			$out['party'] = sanitize_text_field( $raw['party'] );
		}

		return $out;
	}

	/*
	----------------------------------------------------------------- */
	/*
	Route: lookup (anti-enumeration)                                  */
	/* ----------------------------------------------------------------- */

	/**
	 * GET /lookup — retrieve a reservation only when res_id matches a
	 * supplied email or last name. Any mismatch (or upstream error) yields a
	 * uniform "not found" so reservation ids cannot be enumerated.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function route_lookup( $request ) {
		$res_id = sanitize_text_field( (string) $request->get_param( 'res_id' ) );
		if ( '' === $res_id ) {
			return $this->error( 'wh_missing_param', __( 'A reservation id is required.', 'webhotelier' ) );
		}

		$email     = sanitize_email( (string) $request->get_param( 'email' ) );
		$last_name = sanitize_text_field( (string) $request->get_param( 'lastName' ) );
		if ( '' === $email && '' === $last_name ) {
			return $this->error( 'wh_missing_param', __( 'Provide the email or last name on the booking.', 'webhotelier' ) );
		}

		$result = $this->bookings_api()->retrieve( $res_id );
		if ( is_wp_error( $result ) ) {
			// Uniform response — never leak whether the id exists.
			return $this->error( 'wh_not_found', __( 'No matching reservation was found.', 'webhotelier' ) );
		}

		if ( ! $this->identity_matches( $result, $email, $last_name ) ) {
			return $this->error( 'wh_not_found', __( 'No matching reservation was found.', 'webhotelier' ) );
		}

		return $this->respond( $result );
	}

	/**
	 * Confirm the supplied identity matches the reservation record.
	 *
	 * @param array  $record    Reservation data.
	 * @param string $email     Supplied email (may be '').
	 * @param string $last_name Supplied last name (may be '').
	 * @return bool
	 */
	private function identity_matches( array $record, $email, $last_name ) {
		$rec_email = isset( $record['email'] ) ? strtolower( trim( (string) $record['email'] ) ) : '';
		$rec_last  = '';
		foreach ( array( 'lastname', 'lastName', 'last_name', 'surname' ) as $key ) {
			if ( isset( $record[ $key ] ) && '' !== $record[ $key ] ) {
				$rec_last = strtolower( trim( (string) $record[ $key ] ) );
				break;
			}
		}

		if ( '' !== $email && strtolower( trim( $email ) ) === $rec_email ) {
			return true;
		}
		if ( '' !== $last_name && '' !== $rec_last && strtolower( trim( $last_name ) ) === $rec_last ) {
			return true;
		}
		return false;
	}

	/*
	----------------------------------------------------------------- */
	/*
	Shared helpers                                                    */
	/* ----------------------------------------------------------------- */

	/**
	 * Resolve the property code from request or settings default.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	private function resolve_property( $request ) {
		$code = sanitize_text_field( (string) $request->get_param( 'property' ) );
		if ( '' === $code ) {
			$code = $this->settings()->default_property();
		}
		return $code;
	}

	/**
	 * Sanitize availability/search params from a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string,mixed>
	 */
	private function sanitize_availability_params( $request ) {
		$out = array();

		$checkin  = sanitize_text_field( (string) $request->get_param( 'checkin' ) );
		$checkout = sanitize_text_field( (string) $request->get_param( 'checkout' ) );
		if ( $this->is_date( $checkin ) ) {
			$out['checkin'] = $checkin;
		}
		if ( $this->is_date( $checkout ) ) {
			$out['checkout'] = $checkout;
		}

		$adults   = absint( $request->get_param( 'adults' ) );
		$children = absint( $request->get_param( 'children' ) );
		$rooms    = absint( $request->get_param( 'rooms' ) );
		if ( $adults > 0 ) {
			$out['adults'] = $adults;
		}
		if ( $children > 0 ) {
			$out['children'] = $children;
		}
		if ( $rooms > 0 ) {
			$out['rooms'] = $rooms;
		}

		$voucher = sanitize_text_field( (string) $request->get_param( 'voucher' ) );
		if ( '' !== $voucher ) {
			$out['voucher'] = $voucher;
		}

		$property = sanitize_text_field( (string) $request->get_param( 'property' ) );
		if ( '' !== $property ) {
			$out['property'] = $property;
		}

		return $out;
	}

	/**
	 * Wrap a client result (data array | WP_Error) into a REST response.
	 *
	 * @param array|WP_Error $result Client result.
	 * @return WP_REST_Response|WP_Error
	 */
	private function respond( $result ) {
		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			if ( is_array( $data ) && isset( $data['status'] ) ) {
				$status = (int) $data['status'];
			} elseif ( is_array( $data ) && isset( $data['http_code'] ) ) {
				$status = (int) $data['http_code'];
			} else {
				$status = is_array( $data ) ? 0 : (int) $data;
			}
			if ( $status < 400 || $status > 599 ) {
				$status = 400;
			}
			$result->add_data( array( 'status' => $status ) );
			return $result;
		}
		return rest_ensure_response( array( 'data' => $result ) );
	}

	/**
	 * Naive ISO date guard (YYYY-MM-DD).
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	private function is_date( $value ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value );
	}

	/** @return WH_Settings */
	private function settings() {
		if ( null === $this->settings ) {
			$this->settings = WH_Plugin::instance()->settings();
		}
		return $this->settings;
	}

	/** @return WH_Availability_API */
	private function availability_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'availability_api' ) ) {
			return $this->apis->availability_api();
		}
		return new WH_Availability_API( WH_Plugin::instance()->client() );
	}

	/*
	----------------------------------------------------------------- */
	/*
	API accessors + error factory                                     */
	/* ----------------------------------------------------------------- */

	/**
	 * Construct a WP_Error and stamp a 400 status for REST.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message.
	 * @return WP_Error
	 */
	private function error( $code, $message ) {
		return new WP_Error( $code, $message, array( 'status' => 400 ) );
	}

	/** @return WH_Property_API */
	private function property_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'property_api' ) && $this->apis->property_api() ) {
			return $this->apis->property_api();
		}
		return new WH_Property_API( WH_Plugin::instance()->client() );
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
}
