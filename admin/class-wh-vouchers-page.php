<?php
/**
 * Vouchers screen: list bundles -> list codes -> create/update/disable a code.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Admin Vouchers screen: lists voucher bundles and codes, and manages individual codes.
 */
class WH_Vouchers_Page {

	const CAP   = 'manage_options';
	const NONCE = 'wh_voucher_manage';

	/** Allowed manage operations. */
	const OPS = array( 'create', 'update', 'disable' );

	/** @var WH_Vouchers_API */
	protected $vouchers;

	/**
	 * Constructor. Injects the vouchers API.
	 */
	public function __construct( $vouchers ) {
		$this->vouchers = $vouchers;
	}

	/**
	 * Render the vouchers screen.
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

		$bundle_id = ! empty( $query['bundle'] ) ? sanitize_text_field( (string) $query['bundle'] ) : '';

		$bundles = $this->vouchers->bundles();
		$error   = '';
		if ( is_wp_error( $bundles ) ) {
			$error   = $bundles->get_error_message();
			$bundles = array();
		}

		$codes = null;
		if ( '' !== $bundle_id ) {
			$codes = $this->vouchers->codes( $bundle_id );
			if ( is_wp_error( $codes ) ) {
				$error = $codes->get_error_message();
				$codes = null;
			}
		}

		$nonce  = wp_create_nonce( self::NONCE );
		$action = admin_url( 'admin-post.php' );

		$view = __DIR__ . '/views/vouchers-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		$this->render_inline( $bundle_id, $bundles, $codes, $error, $nonce, $action );
	}

	/**
	 * Inline fallback renderer.
	 */
	protected function render_inline( $bundle_id, $bundles, $codes, $error, $nonce, $action ) {
		echo '<div class="wrap wh-admin wh-vouchers"><h1>' . esc_html__( 'Vouchers', 'webhotelier' ) . '</h1>';

		if ( '' !== $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}

		$brows = ( is_array( $bundles ) && isset( $bundles['data'] ) && is_array( $bundles['data'] ) ) ? $bundles['data'] : ( is_array( $bundles ) ? $bundles : array() );

		echo '<h2>' . esc_html__( 'Bundles', 'webhotelier' ) . '</h2><ul class="wh-bundle-list">';
		if ( empty( $brows ) ) {
			echo '<li>' . esc_html__( 'No bundles found.', 'webhotelier' ) . '</li>';
		}
		foreach ( $brows as $b ) {
			$bid   = isset( $b['id'] ) ? (string) $b['id'] : '';
			$bname = isset( $b['name'] ) ? (string) $b['name'] : $bid;
			$url   = add_query_arg(
				array(
					'page'   => 'webhotelier-vouchers',
					'bundle' => $bid,
				),
				admin_url( 'admin.php' )
			);
			printf( '<li><a href="%s">%s</a> <code>%s</code></li>', esc_url( $url ), esc_html( $bname ), esc_html( $bid ) );
		}
		echo '</ul>';

		if ( '' === $bundle_id ) {
			echo '</div>';
			return;
		}

		echo '<h2>' . esc_html( sprintf( /* translators: %s bundle id */ __( 'Codes in bundle %s', 'webhotelier' ), $bundle_id ) ) . '</h2>';

		$crows = ( is_array( $codes ) && isset( $codes['data'] ) && is_array( $codes['data'] ) ) ? $codes['data'] : ( is_array( $codes ) ? $codes : array() );
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Code', 'webhotelier' ) . '</th><th>' . esc_html__( 'Status', 'webhotelier' ) . '</th><th>' . esc_html__( 'Discount', 'webhotelier' ) . '</th><th></th></tr></thead><tbody>';
		if ( empty( $crows ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No codes in this bundle.', 'webhotelier' ) . '</td></tr>';
		}
		foreach ( $crows as $c ) {
			$code   = isset( $c['code'] ) ? (string) $c['code'] : '';
			$status = isset( $c['status'] ) ? (string) $c['status'] : '';
			$disc   = isset( $c['discount'] ) ? (string) $c['discount'] : '';
			echo '<tr><td>' . esc_html( $code ) . '</td><td>' . esc_html( $status ) . '</td><td>' . esc_html( $disc ) . '</td><td>';
			// Disable button (admin-post form).
			echo '<form method="post" action="' . esc_url( $action ) . '" style="display:inline">';
			echo '<input type="hidden" name="action" value="wh_voucher_manage" />';
			echo '<input type="hidden" name="bundle" value="' . esc_attr( $bundle_id ) . '" />';
			echo '<input type="hidden" name="code" value="' . esc_attr( $code ) . '" />';
			echo '<input type="hidden" name="op" value="disable" />';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
			echo '<button class="button button-small">' . esc_html__( 'Disable', 'webhotelier' ) . '</button>';
			echo '</form></td></tr>';
		}
		echo '</tbody></table>';

		// Create/update form.
		echo '<h3>' . esc_html__( 'Create or update a code', 'webhotelier' ) . '</h3>';
		echo '<form method="post" action="' . esc_url( $action ) . '">';
		echo '<input type="hidden" name="action" value="wh_voucher_manage" />';
		echo '<input type="hidden" name="bundle" value="' . esc_attr( $bundle_id ) . '" />';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<p><label>' . esc_html__( 'Code', 'webhotelier' ) . ' <input type="text" name="code" required /></label></p>';
		echo '<p><label>' . esc_html__( 'Discount', 'webhotelier' ) . ' <input type="text" name="discount" /></label></p>';
		echo '<p><label>' . esc_html__( 'Operation', 'webhotelier' ) . ' <select name="op">';
		echo '<option value="create">' . esc_html__( 'Create', 'webhotelier' ) . '</option>';
		echo '<option value="update">' . esc_html__( 'Update', 'webhotelier' ) . '</option>';
		echo '</select></label></p>';
		echo '<p><button class="button button-primary">' . esc_html__( 'Save code', 'webhotelier' ) . '</button></p>';
		echo '</form></div>';
	}

	/**
	 * Handle the manage POST (admin_post_wh_voucher_manage).
	 *
	 * @param array|null $post Defaults to $_POST.
	 */
	public function handle_manage( $post = null ) {
		if ( null === $post ) {
			$post = isset( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
		}

		$nonce = isset( $post['_wpnonce'] ) ? (string) $post['_wpnonce'] : '';
		if ( ! current_user_can( self::CAP ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_die( esc_html__( 'Permission denied or expired token.', 'webhotelier' ) );
		}

		$bundle = isset( $post['bundle'] ) ? sanitize_text_field( (string) $post['bundle'] ) : '';
		$code   = isset( $post['code'] ) ? sanitize_text_field( (string) $post['code'] ) : '';
		$op     = isset( $post['op'] ) ? sanitize_text_field( (string) $post['op'] ) : 'create';
		if ( ! in_array( $op, self::OPS, true ) ) {
			$op = 'create';
		}

		$params = array( 'action' => $op );
		if ( isset( $post['discount'] ) && '' !== $post['discount'] ) {
			$params['value'] = sanitize_text_field( (string) $post['discount'] );
		}

		$result = $this->vouchers->manage_code( $bundle, $code, $params );

		$notice = is_wp_error( $result ) ? 'error' : 'voucher_ok';
		$args   = array(
			'page'      => 'webhotelier-vouchers',
			'bundle'    => $bundle,
			'wh_notice' => $notice,
		);
		if ( is_wp_error( $result ) ) {
			$args['wh_err'] = $result->get_error_code();
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
