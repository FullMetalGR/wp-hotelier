<?php
/**
 * Activation routines: seed settings, create front-end pages, flush rewrite rules.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Handles one-time setup performed when the plugin is activated.
 */
class WH_Activator {

	/**
	 * Option key tracking the IDs of the pages we auto-create.
	 */
	const PAGES_OPTION = 'wh_pages';

	/**
	 * Run every activation step.
	 *
	 * @return void
	 */
	public static function activate() {
		self::seed_settings();
		self::create_pages();
		flush_rewrite_rules();
	}

	/**
	 * Merge default settings under any existing stored option.
	 *
	 * @return void
	 */
	public static function seed_settings() {
		$existing = get_option( WH_Settings::OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		update_option( WH_Settings::OPTION, array_merge( WH_Settings::defaults(), $existing ) );
	}

	/**
	 * Definitions of the front-end pages created on activation. Each entry maps
	 * to a title, slug, the shortcode placed in the page body, and an optional
	 * settings key wired to the new page id.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function page_defs() {
		return array(
			'booking' => array(
				'title'     => __( 'Book Now', 'webhotelier' ),
				'slug'      => 'book',
				'shortcode' => '[wh_booking_flow]',
				'setting'   => '',
			),
			'results' => array(
				'title'     => __( 'Booking Search Results', 'webhotelier' ),
				'slug'      => 'booking-results',
				'shortcode' => '[wh_search_results]',
				'setting'   => 'results_page',
			),
			'lookup'  => array(
				'title'     => __( 'Find My Booking', 'webhotelier' ),
				'slug'      => 'find-my-booking',
				'shortcode' => '[wh_booking_lookup]',
				'setting'   => '',
			),
		);
	}

	/**
	 * Create the front-end pages if they do not already exist. Idempotent: the
	 * created ids are tracked in the wh_pages option, and a page already living
	 * at a target slug is reused rather than duplicated. The results page is
	 * wired into the results_page setting when that setting is still unset.
	 *
	 * @return void
	 */
	public static function create_pages() {
		$tracked = get_option( self::PAGES_OPTION, array() );
		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}

		$settings = get_option( WH_Settings::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		foreach ( self::page_defs() as $key => $def ) {
			// Already created and still present? Leave it alone.
			if ( ! empty( $tracked[ $key ] ) && get_post( $tracked[ $key ] ) ) {
				continue;
			}

			// Reuse a page already published at the target slug.
			$existing = get_page_by_path( $def['slug'] );
			if ( $existing ) {
				$page_id = (int) $existing->ID;
			} else {
				$page_id = wp_insert_post(
					array(
						'post_title'   => $def['title'],
						'post_name'    => $def['slug'],
						'post_content' => $def['shortcode'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
					)
				);
			}

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$tracked[ $key ] = (int) $page_id;
				if ( 'results_page' === $def['setting'] && empty( $settings['results_page'] ) ) {
					$settings['results_page'] = (int) $page_id;
				}
			}
		}

		update_option( self::PAGES_OPTION, $tracked );
		update_option( WH_Settings::OPTION, $settings );
	}
}
