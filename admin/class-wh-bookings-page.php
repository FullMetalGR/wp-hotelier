<?php
/**
 * Bookings management screen: search reservations, view detail, run guarded actions.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Admin Bookings screen: search, view, cancel, resend confirmation, and mark bookings synced.
 */
class WH_Bookings_Page {

	const CAP   = 'manage_options';
	const NONCE = 'wh_booking_action';

	/** Allowed reservation status filter values. */
	const STATUSES = array( 'confirmed', 'cancelled', 'pending', 'modified', 'noshow', 'checkedout' );

	/** @var WH_Bookings_API */
	protected $bookings;

	/**
	 * Constructor. Injects the bookings API.
	 */
	public function __construct( $bookings ) {
		$this->bookings = $bookings;
	}

	/**
	 * Build a sanitized filter array from a query map.
	 *
	 * @param array $query Usually $_GET.
	 * @return array
	 */
	public function build_filters( $query ) {
		$filters = array();

		foreach ( array( 'checkin_from', 'checkin_to' ) as $date_key ) {
			if ( ! empty( $query[ $date_key ] ) ) {
				$d = sanitize_text_field( (string) $query[ $date_key ] );
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
					$filters[ $date_key ] = $d;
				}
			}
		}

		if ( ! empty( $query['status'] ) ) {
			$status = sanitize_text_field( (string) $query['status'] );
			if ( in_array( $status, self::STATUSES, true ) ) {
				$filters['status'] = $status;
			}
		}

		if ( ! empty( $query['lastName'] ) ) {
			$filters['lastName'] = sanitize_text_field( trim( (string) $query['lastName'] ) );
		}
		if ( ! empty( $query['email'] ) ) {
			$filters['email'] = sanitize_text_field( trim( (string) $query['email'] ) );
		}
		if ( ! empty( $query['source'] ) ) {
			$filters['source'] = sanitize_text_field( trim( (string) $query['source'] ) );
		}

		return $filters;
	}

	/**
	 * Render the screen. With a res_id, shows detail; otherwise the search list.
	 *
	 * @param array|null $query Defaults to $_GET.
	 */
	public function render( $query = null ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) );
		}
		if ( null === $query ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only listing.
			$query = isset( $_GET ) ? wp_unslash( $_GET ) : array();
		}

		$res_id  = ! empty( $query['res_id'] ) ? sanitize_text_field( (string) $query['res_id'] ) : '';
		$filters = $this->build_filters( $query );
		$detail  = null;
		$results = null;
		$error   = '';

		if ( '' !== $res_id ) {
			$detail = $this->bookings->retrieve( $res_id );
			if ( is_wp_error( $detail ) ) {
				$error  = $detail->get_error_message();
				$detail = null;
			}
		} else {
			$results = $this->bookings->search( $filters );
			if ( is_wp_error( $results ) ) {
				$error   = $results->get_error_message();
				$results = null;
			}
		}

		$nonce  = wp_create_nonce( self::NONCE );
		$action = admin_url( 'admin-post.php' );

		$view = __DIR__ . '/views/bookings-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		$this->render_inline( $res_id, $filters, $detail, $results, $error, $nonce, $action );
	}

	/**
	 * Inline fallback renderer (keeps the controller testable without the view file).
	 */
	protected function render_inline( $res_id, $filters, $detail, $results, $error, $nonce, $action ) {
		echo '<div class="wrap wh-admin wh-bookings"><h1>' . esc_html__( 'Bookings', 'webhotelier' ) . '</h1>';

		if ( '' !== $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}

		if ( is_array( $detail ) ) {
			$this->render_detail( $detail, $nonce, $action );
			echo '</div>';
			return;
		}

		// Search form.
		echo '<form method="get"><input type="hidden" name="page" value="webhotelier-bookings" />';
		echo '<input type="text" name="lastName" placeholder="' . esc_attr__( 'Last name', 'webhotelier' ) . '" value="' . esc_attr( $filters['lastName'] ?? '' ) . '" /> ';
		echo '<input type="text" name="email" placeholder="' . esc_attr__( 'Email', 'webhotelier' ) . '" value="' . esc_attr( $filters['email'] ?? '' ) . '" /> ';
		echo '<input type="date" name="checkin_from" value="' . esc_attr( $filters['checkin_from'] ?? '' ) . '" /> ';
		echo '<input type="date" name="checkin_to" value="' . esc_attr( $filters['checkin_to'] ?? '' ) . '" /> ';
		echo '<select name="status"><option value="">' . esc_html__( 'Any status', 'webhotelier' ) . '</option>';
		foreach ( self::STATUSES as $s ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $s ), selected( $filters['status'] ?? '', $s, false ), esc_html( $s ) );
		}
		echo '</select> ';
		echo '<input type="text" name="source" placeholder="' . esc_attr__( 'Source', 'webhotelier' ) . '" value="' . esc_attr( $filters['source'] ?? '' ) . '" /> ';
		echo '<button class="button">' . esc_html__( 'Search', 'webhotelier' ) . '</button></form>';

		$rows = ( is_array( $results ) && isset( $results['data'] ) && is_array( $results['data'] ) ) ? $results['data'] : ( is_array( $results ) ? $results : array() );

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Res ID', 'webhotelier' ) . '</th><th>' . esc_html__( 'Guest', 'webhotelier' ) . '</th><th>' . esc_html__( 'Status', 'webhotelier' ) . '</th><th>' . esc_html__( 'Check-in', 'webhotelier' ) . '</th><th>' . esc_html__( 'Check-out', 'webhotelier' ) . '</th><th>' . esc_html__( 'Total', 'webhotelier' ) . '</th><th></th>';
		echo '</tr></thead><tbody>';
		if ( empty( $rows ) ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No reservations found.', 'webhotelier' ) . '</td></tr>';
		}
		foreach ( $rows as $r ) {
			$rid        = isset( $r['res_id'] ) ? (string) $r['res_id'] : '';
			$detail_url = add_query_arg(
				array(
					'page'   => 'webhotelier-bookings',
					'res_id' => $rid,
				),
				admin_url( 'admin.php' )
			);
			printf(
				'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s %s</td><td><a class="button button-small" href="%1$s">%s</a></td></tr>',
				esc_url( $detail_url ),
				esc_html( $rid ),
				esc_html( (string) ( $r['lastName'] ?? '' ) ),
				esc_html( (string) ( $r['status'] ?? '' ) ),
				esc_html( (string) ( $r['checkin'] ?? '' ) ),
				esc_html( (string) ( $r['checkout'] ?? '' ) ),
				esc_html( (string) ( $r['total'] ?? '' ) ),
				esc_html( (string) ( $r['currency'] ?? '' ) ),
				esc_html__( 'View', 'webhotelier' )
			);
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Render a single reservation detail block + action buttons.
	 */
	protected function render_detail( $d, $nonce, $action ) {
		$rid = isset( $d['res_id'] ) ? (string) $d['res_id'] : '';
		echo '<h2>' . esc_html( sprintf( /* translators: %s res id */ __( 'Reservation %s', 'webhotelier' ), $rid ) ) . '</h2>';

		echo '<table class="form-table"><tbody>';
		$fields = array(
			'firstName'           => __( 'First name', 'webhotelier' ),
			'lastName'            => __( 'Last name', 'webhotelier' ),
			'email'               => __( 'Email', 'webhotelier' ),
			'status'              => __( 'Status', 'webhotelier' ),
			'room'                => __( 'Room', 'webhotelier' ),
			'rate'                => __( 'Rate', 'webhotelier' ),
			'board'               => __( 'Board', 'webhotelier' ),
			'checkin'             => __( 'Check-in', 'webhotelier' ),
			'checkout'            => __( 'Check-out', 'webhotelier' ),
			'total'               => __( 'Total', 'webhotelier' ),
			'currency'            => __( 'Currency', 'webhotelier' ),
			'payment_policy'      => __( 'Payment policy', 'webhotelier' ),
			'cancellation_policy' => __( 'Cancellation policy', 'webhotelier' ),
		);
		foreach ( $fields as $k => $label ) {
			if ( ! isset( $d[ $k ] ) || '' === $d[ $k ] ) {
				continue;
			}
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( (string) $d[ $k ] ) . '</td></tr>';
		}
		echo '</tbody></table>';

		// Guarded action buttons (each is a separate admin-post form).
		$this->action_button( $action, $nonce, $rid, 'cancel', __( 'Cancel reservation', 'webhotelier' ), true );
		$this->action_button( $action, $nonce, $rid, 'confirmation_email', __( 'Resend confirmation email', 'webhotelier' ), false );
		$this->action_button( $action, $nonce, $rid, 'mark_synced', __( 'Mark synced', 'webhotelier' ), false );
		$this->purge_button( $action, $nonce, $rid );
	}

	/**
	 * Output a single-action admin-post form.
	 */
	protected function action_button( $action, $nonce, $rid, $act, $label, $confirm ) {
		echo '<form method="post" action="' . esc_url( $action ) . '" style="display:inline-block;margin-right:6px;"'
			. ( $confirm ? ' onsubmit="return confirm(\'' . esc_attr__( 'Are you sure?', 'webhotelier' ) . '\');"' : '' ) . '>';
		echo '<input type="hidden" name="action" value="wh_booking_action" />';
		echo '<input type="hidden" name="wh_action" value="' . esc_attr( $act ) . '" />';
		echo '<input type="hidden" name="res_id" value="' . esc_attr( $rid ) . '" />';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<button class="button">' . esc_html( $label ) . '</button>';
		echo '</form>';
	}

	/**
	 * Purge requires a confirm_purge=1 checkbox (extra guard).
	 */
	protected function purge_button( $action, $nonce, $rid ) {
		echo '<form method="post" action="' . esc_url( $action ) . '" style="display:inline-block;margin-right:6px;" onsubmit="return confirm(\'' . esc_attr__( 'Purge permanently deletes the reservation. Continue?', 'webhotelier' ) . '\');">';
		echo '<input type="hidden" name="action" value="wh_booking_action" />';
		echo '<input type="hidden" name="wh_action" value="purge" />';
		echo '<input type="hidden" name="res_id" value="' . esc_attr( $rid ) . '" />';
		echo '<input type="hidden" name="confirm_purge" value="1" />';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<button class="button button-link-delete">' . esc_html__( 'Purge', 'webhotelier' ) . '</button>';
		echo '</form>';
	}

	/**
	 * Handle a booking action POST (admin_post_wh_booking_action).
	 * Verifies nonce + capability, dispatches, then redirects with a notice.
	 *
	 * @param array|null $post Defaults to $_POST.
	 */
	public function handle_action( $post = null ) {
		if ( null === $post ) {
			$post = isset( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
		}

		$nonce = isset( $post['_wpnonce'] ) ? (string) $post['_wpnonce'] : '';
		if ( ! current_user_can( self::CAP ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_die( esc_html__( 'Permission denied or expired token.', 'webhotelier' ) );
		}

		$action = isset( $post['wh_action'] ) ? sanitize_text_field( (string) $post['wh_action'] ) : '';
		$res_id = isset( $post['res_id'] ) ? sanitize_text_field( (string) $post['res_id'] ) : '';

		$notice = 'error';
		$err    = '';
		$result = null;

		switch ( $action ) {
			case 'cancel':
				$result = $this->bookings->cancel( $res_id );
				$notice = is_wp_error( $result ) ? 'error' : 'cancel_ok';
				break;

			case 'confirmation_email':
				$result = $this->bookings->confirmation_email( array( 'res_id' => $res_id ) );
				$notice = is_wp_error( $result ) ? 'error' : 'email_ok';
				break;

			case 'mark_synced':
				$result = $this->bookings->mark_synced( $res_id );
				$notice = is_wp_error( $result ) ? 'error' : 'sync_ok';
				break;

			case 'purge':
				if ( empty( $post['confirm_purge'] ) ) {
					$notice = 'purge_guard';
					break;
				}
				$result = $this->bookings->purge( $res_id );
				$notice = is_wp_error( $result ) ? 'error' : 'purge_ok';
				break;

			default:
				$notice = 'error';
				$err    = 'unknown_action';
				break;
		}

		if ( 'error' === $notice && is_wp_error( $result ) ) {
			$err = $result->get_error_code();
		}

		$args = array(
			'page'      => 'webhotelier-bookings',
			'wh_notice' => $notice,
		);
		if ( '' !== $res_id ) {
			$args['res_id'] = $res_id;
		}
		if ( '' !== $err ) {
			$args['wh_err'] = $err;
		}

		$redirect = add_query_arg( $args, admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
