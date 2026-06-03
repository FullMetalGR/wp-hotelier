<?php
/**
 * Booking-flow state machine for [wh_booking_flow].
 *
 * Steps progress search -> results -> review -> complete -> confirmation,
 * tracked via the `wh_step` query param. Transitions into review and beyond
 * are nonce-checked. Hosted mode hands off to the engine at `complete`;
 * native_noncard mode posts to the proxy /book route instead.
 *
 * @package webhotelier
 */

defined( 'ABSPATH' ) || defined( 'WH_PATH' ) || defined( 'WH_TESTS_DIR' ) || exit;

/**
 * Booking-flow state machine: search → results → review → complete → confirmation.
 */
class WH_Booking_Flow {

	const STEPS        = array( 'search', 'results', 'review', 'complete', 'confirmation' );
	const NONCE_ACTION = 'wh_booking_flow';

	/** @var WH_Settings|null */
	private $settings;

	/** @var object|null Injected API container (tests). */
	private $apis;

	/** @var WH_Shortcodes|null */
	private $shortcodes;

	/**
	 * @param WH_Settings|null   $settings   Settings (injected for tests).
	 * @param object|null        $apis       Optional API container.
	 * @param WH_Shortcodes|null $shortcodes Shortcodes facade (for reusing renderers).
	 */
	public function __construct( $settings = null, $apis = null, $shortcodes = null ) {
		$this->settings   = $settings;
		$this->apis       = $apis;
		$this->shortcodes = $shortcodes;
	}

	/**
	 * Resolve the effective step from the query, applying guard rails.
	 *
	 * @param array<string,mixed> $query Request query (e.g. $_GET).
	 * @return string One of self::STEPS.
	 */
	public function resolve_step( array $query ) {
		$requested = isset( $query['wh_step'] ) ? sanitize_key( (string) $query['wh_step'] ) : 'search';
		if ( ! in_array( $requested, self::STEPS, true ) ) {
			return 'search';
		}

		// search is always reachable.
		if ( 'search' === $requested ) {
			return 'search';
		}

		// results requires valid dates; otherwise back to search.
		if ( ! $this->has_dates( $query ) ) {
			return 'search';
		}
		if ( 'results' === $requested ) {
			return 'results';
		}

		// review/complete/confirmation require a selected rate.
		$has_rate = isset( $query['rate'] ) && '' !== (string) $query['rate'];
		if ( ! $has_rate ) {
			return 'results';
		}

		// review/complete/confirmation require a valid nonce.
		if ( ! $this->verify_nonce( $query ) ) {
			return 'results';
		}

		return $requested;
	}

	/**
	 * Build a nonce for the flow forms/links.
	 *
	 * @return string
	 */
	public function nonce() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * Whether the query has both valid ISO dates.
	 *
	 * @param array<string,mixed> $query Query.
	 * @return bool
	 */
	private function has_dates( array $query ) {
		$ci = isset( $query['checkin'] ) ? (string) $query['checkin'] : '';
		$co = isset( $query['checkout'] ) ? (string) $query['checkout'] : '';
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ci )
			&& (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $co );
	}

	/**
	 * Verify the flow nonce from the query.
	 *
	 * @param array<string,mixed> $query Query.
	 * @return bool
	 */
	private function verify_nonce( array $query ) {
		$nonce = isset( $query['wh_nonce'] ) ? (string) $query['wh_nonce'] : '';
		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Render the booking flow for the current step.
	 *
	 * @param array<string,mixed> $query Request query.
	 * @param array<string,mixed> $atts  Shortcode attributes (property, class).
	 * @return string
	 */
	public function render( array $query, array $atts ) {
		$step  = $this->resolve_step( $query );
		$class = isset( $atts['class'] ) ? (string) $atts['class'] : '';

		switch ( $step ) {
			case 'results':
				$body = $this->render_results( $query, $atts );
				break;
			case 'review':
				$body = $this->render_review( $query, $atts );
				break;
			case 'complete':
				$body = $this->render_complete( $query );
				break;
			case 'confirmation':
				// Confirmation requires a completed booking; without one, restart.
				$body = $this->render_search( $query, $atts );
				$step = 'search';
				break;
			case 'search':
			default:
				$body = $this->render_search( $query, $atts );
				$step = 'search';
				break;
		}

		return '<div class="wh-booking-flow wh-booking-flow--' . esc_attr( $step ) . ' ' . esc_attr( $class ) . '" data-wh-flow data-step="' . esc_attr( $step ) . '">'
			. $body
			. '</div>';
	}

	/**
	 * Render the search step (reuses the search-form template).
	 *
	 * @param array $query Query.
	 * @param array $atts  Atts.
	 * @return string
	 */
	private function render_search( array $query, array $atts ) {
		$property = isset( $atts['property'] ) ? (string) $atts['property'] : '';
		if ( '' === $property && 'single' === $this->settings()->mode() ) {
			$property = $this->settings()->default_property();
		}
		$action = '';
		$page   = (int) $this->settings()->results_page();
		if ( $page > 0 ) {
			$action = (string) get_permalink( $page );
		}
		return WH_Render::template(
			'search-form',
			array(
				'action'         => $action,
				'compact'        => false,
				'property'       => $property,
				'mode'           => $this->settings()->mode(),
				'rooms_max'      => 4,
				'adults_default' => 2,
				'nonce'          => $this->nonce(),
				'class'          => 'wh-booking-flow__search',
			)
		);
	}

	/**
	 * Render the results step (rate cards).
	 *
	 * @param array $query Query.
	 * @param array $atts  Atts.
	 * @return string
	 */
	private function render_results( array $query, array $atts ) {
		$code = isset( $atts['property'] ) && '' !== $atts['property']
			? (string) $atts['property']
			: $this->settings()->default_property();

		$data = $this->availability_api()->single( $code, $this->availability_query( $query ) );
		if ( is_wp_error( $data ) ) {
			return WH_Render::error( $data );
		}
		$rates    = isset( $data['rates'] ) && is_array( $data['rates'] ) ? $data['rates'] : array();
		$currency = isset( $data['currency'] ) ? $data['currency'] : 'EUR';

		return WH_Render::template(
			'availability',
			array(
				'data'     => $data,
				'rates'    => $rates,
				'currency' => $currency,
				'layout'   => 'cards',
				'class'    => 'wh-booking-flow__results',
			)
		);
	}

	/**
	 * Render the review step (selected rate + guest form for native mode).
	 *
	 * @param array $query Query.
	 * @param array $atts  Atts.
	 * @return string
	 */
	private function render_review( array $query, array $atts ) {
		$code = isset( $atts['property'] ) && '' !== $atts['property']
			? (string) $atts['property']
			: $this->settings()->default_property();

		$data = $this->availability_api()->single( $code, $this->availability_query( $query ) );
		if ( is_wp_error( $data ) ) {
			return WH_Render::error( $data );
		}
		$rate = $this->find_rate( isset( $data['rates'] ) ? (array) $data['rates'] : array(), (string) ( $query['rate'] ?? '' ) );
		if ( null === $rate ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => __( 'That rate is no longer available. Please search again.', 'webhotelier' ),
				)
			);
		}

		return WH_Render::template(
			'flow-review',
			array(
				'rate'     => $rate,
				'currency' => isset( $data['currency'] ) ? $data['currency'] : 'EUR',
				'native'   => 'native_noncard' === $this->settings()->completion_mode(),
				'query'    => $query,
				'nonce'    => $this->nonce(),
				'class'    => 'wh-booking-flow__review',
			)
		);
	}

	/**
	 * Render the complete step (runs complete() and surfaces the directive).
	 *
	 * @param array $query Query.
	 * @return string
	 */
	private function render_complete( array $query ) {
		$directive = $this->complete( $query );

		if ( 'error' === $directive['action'] ) {
			return WH_Render::template(
				'notice',
				array(
					'type'    => 'error',
					'message' => $directive['message'],
				)
			);
		}
		if ( 'confirmation' === $directive['action'] ) {
			return $this->render_confirmation( $directive );
		}
		// handoff
		return WH_Render::template(
			'flow-complete',
			array(
				'url'   => $directive['url'],
				'open'  => $directive['open'],
				'class' => 'wh-booking-flow__complete',
			)
		);
	}

	/**
	 * Render the confirmation step (native path).
	 *
	 * @param array<string,mixed> $directive Confirmation directive.
	 * @return string
	 */
	public function render_confirmation( array $directive ) {
		return WH_Render::template(
			'flow-confirmation',
			array(
				'res_id'      => isset( $directive['res_id'] ) ? $directive['res_id'] : '',
				'summary_url' => isset( $directive['summaryUrl'] ) ? $directive['summaryUrl'] : '',
				'data'        => isset( $directive['data'] ) ? $directive['data'] : array(),
				'class'       => 'wh-booking-flow__confirmation',
			)
		);
	}

	/**
	 * Execute the terminal booking action for the current selection.
	 *
	 * Returns a directive array:
	 * - hosted:           ['action'=>'handoff','open'=>..,'url'=>..]
	 * - native success:   ['action'=>'confirmation','res_id'=>..,'summaryUrl'=>..,'data'=>..]
	 * - any failure:      ['action'=>'error','code'=>..,'message'=>..]
	 *
	 * @param array<string,mixed> $query Flow query (sanitized upstream).
	 * @return array<string,mixed>
	 */
	public function complete( array $query ) {
		$code = isset( $query['property'] ) && '' !== $query['property']
			? (string) $query['property']
			: $this->settings()->default_property();

		$rate_id = isset( $query['rate'] ) ? (string) $query['rate'] : '';

		$params = $this->availability_query( $query );
		$data   = $this->availability_api()->single( $code, $params );
		if ( is_wp_error( $data ) ) {
			return $this->error_directive( $data->get_error_code(), $data->get_error_message() );
		}

		$rate = $this->find_rate( isset( $data['rates'] ) ? (array) $data['rates'] : array(), $rate_id );
		if ( null === $rate ) {
			return $this->error_directive(
				'NO_AVAILABILITY',
				__( 'That rate is no longer available. Please search again.', 'webhotelier' )
			);
		}

		if ( 'native_noncard' === $this->settings()->completion_mode() ) {
			return $this->complete_native( $code, $rate, $query );
		}
		return $this->complete_hosted( $rate );
	}

	/**
	 * Hosted handoff directive using the verified rate.url.engine.
	 *
	 * @param array $rate Live rate.
	 * @return array<string,mixed>
	 */
	private function complete_hosted( array $rate ) {
		$engine = isset( $rate['url']['engine'] ) ? (string) $rate['url']['engine'] : '';
		if ( '' === $engine ) {
			return $this->error_directive(
				'NOT_FOUND',
				__( 'Booking link unavailable. Please try again.', 'webhotelier' )
			);
		}
		return array(
			'action' => 'handoff',
			'open'   => $this->settings()->engine_open(),
			'url'    => $engine,
		);
	}

	/**
	 * Native non-card booking with price guard and CHKIN/BANK enforcement.
	 *
	 * @param string $code  Property code.
	 * @param array  $rate  Live rate.
	 * @param array  $query Flow query.
	 * @return array<string,mixed>
	 */
	private function complete_native( $code, array $rate, array $query ) {
		$method = strtoupper( isset( $query['payment_method'] ) ? (string) $query['payment_method'] : '' );
		if ( ! in_array( $method, array( 'CHKIN', 'BANK' ), true ) ) {
			return $this->error_directive(
				'wh_invalid_payment',
				__( 'Only check-in or bank-transfer payment is allowed here.', 'webhotelier' )
			);
		}

		// Price guard: echoed price must match the live price.
		$live_price = isset( $rate['pricing']['price'] ) ? (float) $rate['pricing']['price'] : null;
		$echo_price = isset( $query['price'] ) && '' !== $query['price'] ? (float) $query['price'] : null;
		if ( null !== $echo_price && null !== $live_price && abs( $echo_price - $live_price ) > 0.001 ) {
			return $this->error_directive(
				'INVALID_PRICE',
				__( 'The price changed since you selected this rate. Please retry.', 'webhotelier' )
			);
		}

		$book = array(
			'rate'           => isset( $rate['id'] ) ? (string) $rate['id'] : '',
			'payment_method' => $method,
			'price'          => null !== $live_price ? $live_price : '',
		);
		foreach ( array( 'checkin', 'checkout', 'firstname', 'lastname', 'phone', 'country', 'voucher', 'remarks' ) as $f ) {
			if ( isset( $query[ $f ] ) && '' !== $query[ $f ] ) {
				$book[ $f ] = (string) $query[ $f ];
			}
		}
		if ( isset( $query['email'] ) ) {
			$book['email'] = (string) $query['email'];
		}
		foreach ( array( 'adults', 'children' ) as $n ) {
			if ( isset( $query[ $n ] ) ) {
				$book[ $n ] = (int) $query[ $n ];
			}
		}

		$result = $this->bookings_api()->create( $code, $book );
		if ( is_wp_error( $result ) ) {
			return $this->error_directive( $result->get_error_code(), $result->get_error_message() );
		}

		return array(
			'action'     => 'confirmation',
			'res_id'     => isset( $result['res_id'] ) ? (string) $result['res_id'] : '',
			'summaryUrl' => isset( $result['summaryUrl'] ) ? (string) $result['summaryUrl'] : '',
			'data'       => $result,
		);
	}

	/**
	 * Locate a rate by id in a flat rate list.
	 *
	 * @param array  $rates   Flat rate list.
	 * @param string $rate_id Rate id.
	 * @return array|null
	 */
	private function find_rate( array $rates, $rate_id ) {
		foreach ( $rates as $r ) {
			if ( isset( $r['id'] ) && (string) $r['id'] === (string) $rate_id ) {
				return $r;
			}
		}
		return null;
	}

	/**
	 * Build availability params from a flow query.
	 *
	 * @param array<string,mixed> $query Query.
	 * @return array<string,mixed>
	 */
	private function availability_query( array $query ) {
		$params = array();
		foreach ( array( 'checkin', 'checkout' ) as $d ) {
			if ( isset( $query[ $d ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $query[ $d ] ) ) {
				$params[ $d ] = (string) $query[ $d ];
			}
		}
		$adults = isset( $query['adults'] ) ? (int) $query['adults'] : 0;
		if ( $adults > 0 ) {
			$params['adults'] = $adults;
		}
		$children = isset( $query['children'] ) ? (int) $query['children'] : 0;
		if ( $children > 0 ) {
			$params['children'] = $children;
		}
		if ( isset( $query['voucher'] ) && '' !== $query['voucher'] ) {
			$params['voucher'] = (string) $query['voucher'];
		}
		return $params;
	}

	/**
	 * Build an error directive.
	 *
	 * @param string $code    Error code.
	 * @param string $message Friendly message.
	 * @return array<string,mixed>
	 */
	private function error_directive( $code, $message ) {
		return array(
			'action'  => 'error',
			'code'    => $code,
			'message' => $message,
		);
	}

	/** @return WH_Settings */
	protected function settings() {
		if ( null === $this->settings ) {
			$this->settings = WH_Plugin::instance()->settings();
		}
		return $this->settings;
	}

	/** @return WH_Availability_API */
	protected function availability_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'availability_api' ) && $this->apis->availability_api() ) {
			return $this->apis->availability_api();
		}
		return new WH_Availability_API( WH_Plugin::instance()->client() );
	}

	/** @return WH_Bookings_API */
	protected function bookings_api() {
		if ( is_object( $this->apis ) && method_exists( $this->apis, 'bookings_api' ) && $this->apis->bookings_api() ) {
			return $this->apis->bookings_api();
		}
		return new WH_Bookings_API( WH_Plugin::instance()->client() );
	}
}
