<?php
/**
 * Declarative registry of every documented endpoint. Drives the API Explorer,
 * the proxy, and a parity test against resource-class methods.
 *
 * Each entry: key, label, method, path_template, params[], category, resource, resource_method.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_Endpoints.
 */
class WH_Endpoints {

	/**
	 * Full endpoint registry.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function all() {
		return array(
			// --- Property (7) ---
			array(
				'key'             => 'property.info',
				'label'           => __( 'Property info', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/property/{code}',
				'params'          => array( 'include' ),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'info',
			),
			array(
				'key'             => 'property.search',
				'label'           => __( 'Property search (multi)', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/property',
				'params'          => array( 'destination', 'checkin', 'checkout' ),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'search',
			),
			array(
				'key'             => 'property.rooms',
				'label'           => __( 'Rooms list', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/room/{code}',
				'params'          => array(),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'rooms',
			),
			array(
				'key'             => 'property.room',
				'label'           => __( 'Room detail', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/room/{code}/{room}',
				'params'          => array(),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'room',
			),
			array(
				'key'             => 'property.rates',
				'label'           => __( 'Rates list', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/rate/{code}',
				'params'          => array( 'room' ),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'rates',
			),
			array(
				'key'             => 'property.rate',
				'label'           => __( 'Rate detail', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/rate/{code}/{room}/{rateId}',
				'params'          => array(),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'rate',
			),
			array(
				'key'             => 'property.extras',
				'label'           => __( 'Extras list', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/extra/{code}',
				'params'          => array(),
				'category'        => 'property',
				'resource'        => 'WH_Property_API',
				'resource_method' => 'extras',
			),

			// --- Availability (8) ---
			array(
				'key'             => 'availability.single',
				'label'           => __( 'Availability (single)', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability/{code}',
				'params'          => array( 'checkin', 'checkout', 'adults', 'children', 'rooms', 'voucher', 'offline' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'single',
			),
			array(
				'key'             => 'availability.multi',
				'label'           => __( 'Availability (multi)', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability',
				'params'          => array( 'destination', 'checkin', 'checkout', 'adults', 'children' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'multi',
			),
			array(
				'key'             => 'availability.breakdown',
				'label'           => __( 'Availability breakdown', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability/{code}/breakdown',
				'params'          => array( 'checkin', 'checkout', 'rate' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'breakdown',
			),
			array(
				'key'             => 'availability.calendar',
				'label'           => __( 'Availability calendar', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability/{code}/calendar',
				'params'          => array( 'fromd', 'tod', 'adults', 'children', 'room', 'rate' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'calendar',
			),
			array(
				'key'             => 'availability.flexibleCalendar',
				'label'           => __( 'Flexible calendar', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability/{code}/flexible-calendar',
				'params'          => array( 'month', 'nights' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'flexibleCalendar',
			),
			array(
				'key'             => 'availability.bar',
				'label'           => __( 'Best available rate', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/bar/{code}',
				'params'          => array( 'checkin', 'checkout' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'bar',
			),
			array(
				'key'             => 'availability.extras',
				'label'           => __( 'Availability extras', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/availability/{code}/extras/{rateId}',
				'params'          => array( 'checkin', 'checkout' ),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'extras',
			),
			array(
				'key'             => 'availability.ical',
				'label'           => __( 'iCal feed', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/ical/{code}-{room}.ics',
				'params'          => array(),
				'category'        => 'availability',
				'resource'        => 'WH_Availability_API',
				'resource_method' => 'ical',
			),

			// --- Offers (3) ---
			array(
				'key'             => 'offers.multi',
				'label'           => __( 'Offers (multi)', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/offers',
				'params'          => array( 'destination' ),
				'category'        => 'offers',
				'resource'        => 'WH_Offers_API',
				'resource_method' => 'multi',
			),
			array(
				'key'             => 'offers.single',
				'label'           => __( 'Offers (single)', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/offers/{code}',
				'params'          => array(),
				'category'        => 'offers',
				'resource'        => 'WH_Offers_API',
				'resource_method' => 'single',
			),
			array(
				'key'             => 'offers.info',
				'label'           => __( 'Offer detail', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/offers/{code}/{offerId}',
				'params'          => array(),
				'category'        => 'offers',
				'resource'        => 'WH_Offers_API',
				'resource_method' => 'info',
			),

			// --- Bookings (10) ---
			array(
				'key'             => 'bookings.create',
				'label'           => __( 'Create booking', 'webhotelier' ),
				'method'          => 'POST',
				'path_template'   => '/book/{code}',
				'params'          => array( 'rate', 'checkin', 'checkout', 'adults', 'payment_method' ),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'create',
			),
			array(
				'key'             => 'bookings.purge',
				'label'           => __( 'Purge booking', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/purge/{resId}',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'purge',
			),
			array(
				'key'             => 'bookings.cancel',
				'label'           => __( 'Cancel booking', 'webhotelier' ),
				'method'          => 'POST',
				'path_template'   => '/reservation/cancel/{resId}',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'cancel',
			),
			array(
				'key'             => 'bookings.confirmationEmail',
				'label'           => __( 'Resend confirmation email', 'webhotelier' ),
				'method'          => 'POST',
				'path_template'   => '/reservation/confirmation_email',
				'params'          => array( 'res_id', 'email' ),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'confirmationEmail',
			),
			array(
				'key'             => 'bookings.search',
				'label'           => __( 'Search reservations', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/reservation',
				'params'          => array( 'checkin', 'checkout', 'status', 'lastname', 'email', 'source' ),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'search',
			),
			array(
				'key'             => 'bookings.retrieve',
				'label'           => __( 'Retrieve reservation', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/reservation/{resId}',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'retrieve',
			),
			array(
				'key'             => 'bookings.pending',
				'label'           => __( 'Pending reservations', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/reservation/new',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'pending',
			),
			array(
				'key'             => 'bookings.markSynced',
				'label'           => __( 'Mark reservation synced', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/reservation/sync/{resId}',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'markSynced',
			),
			array(
				'key'             => 'bookings.pushPing',
				'label'           => __( 'Push / ping', 'webhotelier' ),
				'method'          => 'POST',
				'path_template'   => '/push/ping',
				'params'          => array( 'channel' ),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'pushPing',
			),
			array(
				'key'             => 'bookings.sources',
				'label'           => __( 'Booking sources', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/sources',
				'params'          => array(),
				'category'        => 'bookings',
				'resource'        => 'WH_Bookings_API',
				'resource_method' => 'sources',
			),

			// --- Vouchers (3) ---
			array(
				'key'             => 'vouchers.bundles',
				'label'           => __( 'Voucher bundles', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/voucher',
				'params'          => array(),
				'category'        => 'vouchers',
				'resource'        => 'WH_Vouchers_API',
				'resource_method' => 'bundles',
			),
			array(
				'key'             => 'vouchers.codes',
				'label'           => __( 'Voucher codes', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/voucher/{bundleId}',
				'params'          => array(),
				'category'        => 'vouchers',
				'resource'        => 'WH_Vouchers_API',
				'resource_method' => 'codes',
			),
			array(
				'key'             => 'vouchers.manageCode',
				'label'           => __( 'Manage voucher code', 'webhotelier' ),
				'method'          => 'POST',
				'path_template'   => '/voucher/{bundleId}/{code}',
				'params'          => array( 'action', 'value' ),
				'category'        => 'vouchers',
				'resource'        => 'WH_Vouchers_API',
				'resource_method' => 'manageCode',
			),

			// --- Statistics (3) ---
			array(
				'key'             => 'statistics.summary',
				'label'           => __( 'Performance summary', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/statistics/performance_summary/{code}',
				'params'          => array( 'date_from', 'date_to' ),
				'category'        => 'statistics',
				'resource'        => 'WH_Stats_API',
				'resource_method' => 'summary',
			),
			array(
				'key'             => 'statistics.perDay',
				'label'           => __( 'Performance per day', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/statistics/performance_per_day/{code}',
				'params'          => array( 'date_from', 'date_to' ),
				'category'        => 'statistics',
				'resource'        => 'WH_Stats_API',
				'resource_method' => 'perDay',
			),
			array(
				'key'             => 'statistics.perCountry',
				'label'           => __( 'Performance per country', 'webhotelier' ),
				'method'          => 'GET',
				'path_template'   => '/statistics/performance_per_country/{code}',
				'params'          => array( 'date_from', 'date_to' ),
				'category'        => 'statistics',
				'resource'        => 'WH_Stats_API',
				'resource_method' => 'perCountry',
			),
		);
	}

	/**
	 * Fetch a single entry by key.
	 *
	 * @param string $key Endpoint key.
	 * @return array<string,mixed>|null
	 */
	public function get( $key ) {
		foreach ( $this->all() as $entry ) {
			if ( $entry['key'] === $key ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Fetch all entries in a category.
	 *
	 * @param string $category Category slug.
	 * @return array<int,array<string,mixed>>
	 */
	public function by_category( $category ) {
		$out = array();
		foreach ( $this->all() as $entry ) {
			if ( $entry['category'] === $category ) {
				$out[] = $entry;
			}
		}
		return $out;
	}
}
