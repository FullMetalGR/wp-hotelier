<?php
/**
 * Sync screen: pending bookings, mark-synced, push/ping tool, sources viewer.
 * Manual only — no scheduler.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Admin Sync screen: pending bookings, mark-synced, push/ping tool, and booking-sources viewer.
 */
class WH_Sync_Page {

	const CAP   = 'manage_options';
	const NONCE = 'wh_sync_action';

	/** Allowed sync actions. */
	const ACTIONS = array( 'mark_synced', 'push_ping' );

	/** @var WH_Bookings_API */
	protected $bookings;

	/**
	 * Constructor. Injects the bookings API.
	 */
	public function __construct( $bookings ) {
		$this->bookings = $bookings;
	}

	/**
	 * Render the sync screen.
	 *
	 * @param array|null $query Defaults to $_GET.
	 */
	public function render( $query = null ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) );
		}
		if ( null === $query ) {
			$query = isset( $_GET ) ? wp_unslash( $_GET ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		}

		$error = '';

		$pending = $this->bookings->pending();
		if ( is_wp_error( $pending ) ) {
			$error   = $pending->get_error_message();
			$pending = array();
		}

		$sources = $this->bookings->sources();
		if ( is_wp_error( $sources ) ) {
			if ( '' === $error ) {
				$error = $sources->get_error_message();
			}
			$sources = array();
		}

		$nonce  = wp_create_nonce( self::NONCE );
		$action = admin_url( 'admin-post.php' );

		$view = __DIR__ . '/views/sync-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		$this->render_inline( $pending, $sources, $error, $nonce, $action );
	}

	/**
	 * Inline fallback renderer.
	 */
	protected function render_inline( $pending, $sources, $error, $nonce, $action ) {
		echo '<div class="wrap wh-admin wh-sync"><h1>' . esc_html__( 'Sync', 'webhotelier' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Manual sync tools. No automatic scheduler runs.', 'webhotelier' ) . '</p>';

		if ( '' !== $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}

		$prows = ( is_array( $pending ) && isset( $pending['data'] ) && is_array( $pending['data'] ) ) ? $pending['data'] : ( is_array( $pending ) ? $pending : array() );

		echo '<h2>' . esc_html__( 'Pending bookings', 'webhotelier' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Res ID', 'webhotelier' ) . '</th><th>' . esc_html__( 'Guest', 'webhotelier' ) . '</th><th>' . esc_html__( 'Check-in', 'webhotelier' ) . '</th><th></th></tr></thead><tbody>';
		if ( empty( $prows ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No pending bookings.', 'webhotelier' ) . '</td></tr>';
		}
		foreach ( $prows as $p ) {
			$rid = isset( $p['res_id'] ) ? (string) $p['res_id'] : '';
			echo '<tr><td>' . esc_html( $rid ) . '</td><td>' . esc_html( (string) ( $p['lastName'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $p['checkin'] ?? '' ) ) . '</td><td>';
			echo $this->action_form( $action, $nonce, 'mark_synced', $rid, __( 'Mark synced', 'webhotelier' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- action_form() returns markup escaped with esc_url/esc_attr/esc_html.
			echo $this->action_form( $action, $nonce, 'push_ping', $rid, __( 'Push / ping', 'webhotelier' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- action_form() returns markup escaped with esc_url/esc_attr/esc_html.
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		// Standalone push/ping tool (arbitrary res_id).
		echo '<h2>' . esc_html__( 'Push / ping tool', 'webhotelier' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( $action ) . '">';
		echo '<input type="hidden" name="action" value="wh_sync_action" />';
		echo '<input type="hidden" name="wh_action" value="push_ping" />';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<label>' . esc_html__( 'Reservation ID', 'webhotelier' ) . ' <input type="text" name="res_id" /></label> ';
		echo '<button class="button">' . esc_html__( 'Send push/ping', 'webhotelier' ) . '</button>';
		echo '</form>';

		// Sources viewer. Live /sources returns an OBJECT envelope:
		// { "sources": [ { id, name, is_channel, is_public, parent_id }, ... ] }.
		$d     = $sources;
		$srows = ( is_array( $d ) && isset( $d['sources'] ) && is_array( $d['sources'] ) )
			? $d['sources']
			: ( ( is_array( $d ) && isset( $d['data'] ) && is_array( $d['data'] ) )
				? $d['data']
				: ( is_array( $d ) ? $d : array() ) );
		echo '<h2>' . esc_html__( 'Booking sources', 'webhotelier' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'ID', 'webhotelier' ) . '</th><th>' . esc_html__( 'Name', 'webhotelier' ) . '</th><th>' . esc_html__( 'Channel', 'webhotelier' ) . '</th><th>' . esc_html__( 'Public', 'webhotelier' ) . '</th></tr></thead><tbody>';
		if ( empty( $srows ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No sources.', 'webhotelier' ) . '</td></tr>';
		}
		foreach ( $srows as $s ) {
			if ( ! is_array( $s ) ) {
				continue;
			}
			$is_channel = ! empty( $s['is_channel'] ) ? __( 'Yes', 'webhotelier' ) : __( 'No', 'webhotelier' );
			$is_public  = ! empty( $s['is_public'] ) ? __( 'Yes', 'webhotelier' ) : __( 'No', 'webhotelier' );
			echo '<tr><td>' . esc_html( (string) ( $s['id'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $s['name'] ?? '' ) ) . '</td><td>' . esc_html( $is_channel ) . '</td><td>' . esc_html( $is_public ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Build a single-action admin-post form (returns markup).
	 */
	protected function action_form( $action, $nonce, $act, $rid, $label ) {
		$html  = '<form method="post" action="' . esc_url( $action ) . '" style="display:inline-block;margin-right:4px;">';
		$html .= '<input type="hidden" name="action" value="wh_sync_action" />';
		$html .= '<input type="hidden" name="wh_action" value="' . esc_attr( $act ) . '" />';
		$html .= '<input type="hidden" name="res_id" value="' . esc_attr( $rid ) . '" />';
		$html .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
		$html .= '<button class="button button-small">' . esc_html( $label ) . '</button>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Handle a sync action POST (admin_post_wh_sync_action).
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

		$act    = isset( $post['wh_action'] ) ? sanitize_text_field( (string) $post['wh_action'] ) : '';
		$res_id = isset( $post['res_id'] ) ? sanitize_text_field( (string) $post['res_id'] ) : '';

		$notice = 'error';
		$err    = '';
		$result = null;

		switch ( $act ) {
			case 'mark_synced':
				$result = $this->bookings->mark_synced( $res_id );
				$notice = is_wp_error( $result ) ? 'error' : 'sync_ok';
				break;

			case 'push_ping':
				$result = $this->bookings->push_ping( array( 'res_id' => $res_id ) );
				$notice = is_wp_error( $result ) ? 'error' : 'ping_ok';
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
			'page'      => 'webhotelier-sync',
			'wh_notice' => $notice,
		);
		if ( '' !== $err ) {
			$args['wh_err'] = $err;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
