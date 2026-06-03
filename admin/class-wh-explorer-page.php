<?php
/**
 * API Explorer: registry-driven form, server-side endpoint runner, pretty JSON.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

class WH_Explorer_Page {

	const CAP   = 'manage_options';
	const NONCE = 'wh_explorer_run';

	/** @var WH_Endpoints */
	protected $endpoints;
	/** @var WH_Client */
	protected $client;

	public function __construct( $endpoints, $client ) {
		$this->endpoints = $endpoints;
		$this->client    = $client;
	}

	/**
	 * Render the explorer screen.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'webhotelier' ) );
		}

		$all = $this->endpoints->all();
		$all = is_array( $all ) ? $all : array();

		// Group by category for the optgroup picker.
		$grouped = array();
		foreach ( $all as $ep ) {
			$cat = isset( $ep['category'] ) ? (string) $ep['category'] : 'Other';
			$grouped[ $cat ][] = $ep;
		}

		$nonce = wp_create_nonce( self::NONCE );

		// JSON registry for the JS form builder. Slashed so the literal {code} survives.
		$registry_json = wp_json_encode( $all );
		if ( false === $registry_json ) {
			$registry_json = '[]';
		}

		$view = __DIR__ . '/views/explorer-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		$this->render_inline( $grouped, $registry_json, $nonce );
	}

	/**
	 * Inline fallback renderer.
	 */
	protected function render_inline( $grouped, $registry_json, $nonce ) {
		echo '<div class="wrap wh-admin wh-explorer"><h1>' . esc_html__( 'API Explorer', 'webhotelier' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Call any documented endpoint server-side and inspect the raw JSON.', 'webhotelier' ) . '</p>';

		echo '<form id="wh-explorer-form">';
		echo '<input type="hidden" id="wh-explorer-nonce" value="' . esc_attr( $nonce ) . '" />';

		echo '<p><label for="wh-explorer-endpoint">' . esc_html__( 'Endpoint', 'webhotelier' ) . '</label><br />';
		echo '<select id="wh-explorer-endpoint" name="endpoint">';
		foreach ( $grouped as $cat => $eps ) {
			echo '<optgroup label="' . esc_attr( $cat ) . '">';
			foreach ( $eps as $ep ) {
				$key    = isset( $ep['key'] ) ? (string) $ep['key'] : '';
				$method = isset( $ep['method'] ) ? (string) $ep['method'] : 'GET';
				$path   = isset( $ep['path_template'] ) ? (string) $ep['path_template'] : '';
				$params = isset( $ep['params'] ) && is_array( $ep['params'] ) ? implode( ',', $ep['params'] ) : '';
				printf(
					'<option value="%s" data-method="%s" data-path="%s" data-params="%s">%s &mdash; %s %s</option>',
					esc_attr( $key ),
					esc_attr( $method ),
					esc_attr( $path ),
					esc_attr( $params ),
					esc_html( $key ),
					esc_html( $method ),
					esc_html( $path )
				);
			}
			echo '</optgroup>';
		}
		echo '</select></p>';

		// Params are injected by JS into this container based on the selected endpoint.
		echo '<div id="wh-explorer-params"></div>';

		echo '<p><button type="button" class="button button-primary" id="wh-explorer-run">' . esc_html__( 'Run', 'webhotelier' ) . '</button></p>';
		echo '</form>';

		echo '<div id="wh-explorer-meta" class="wh-explorer-meta" aria-live="polite"></div>';
		echo '<pre id="wh-explorer-output" class="wh-explorer-output" aria-live="polite"></pre>';

		// Embedded registry for the JS form builder.
		echo '<script type="application/json" id="wh-explorer-registry">' . $registry_json . '</script>';

		echo '</div>';
	}

	/**
	 * AJAX handler for running an endpoint (wp_ajax_wh_explorer_run).
	 * Verifies nonce + capability, resolves the path, calls the client, returns
	 * pretty JSON + resolved URL + elapsed milliseconds.
	 *
	 * @param array|null $request Defaults to $_POST.
	 */
	public function ajax_run( $request = null ) {
		if ( null === $request ) {
			$request = isset( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
		}

		$nonce = isset( $request['nonce'] ) ? (string) $request['nonce'] : '';
		if ( ! current_user_can( self::CAP ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied or expired token.', 'webhotelier' ) ), 403 );
		}

		$key = isset( $request['endpoint'] ) ? sanitize_text_field( (string) $request['endpoint'] ) : '';
		$def = $this->endpoints->get( $key );
		if ( ! is_array( $def ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown endpoint.', 'webhotelier' ) ), 200 );
		}

		$method   = isset( $def['method'] ) ? strtoupper( (string) $def['method'] ) : 'GET';
		$template = isset( $def['path_template'] ) ? (string) $def['path_template'] : '';
		$allowed  = isset( $def['params'] ) && is_array( $def['params'] ) ? $def['params'] : array();

		// Collect + sanitize only allow-listed params.
		$raw    = isset( $request['params'] ) && is_array( $request['params'] ) ? $request['params'] : array();
		$params = array();
		foreach ( $allowed as $p ) {
			if ( isset( $raw[ $p ] ) && '' !== $raw[ $p ] ) {
				$params[ $p ] = sanitize_text_field( (string) $raw[ $p ] );
			}
		}

		// Resolve path placeholders (consumes matching params).
		$path = self::resolve_path( $template, $params );

		$start  = microtime( true );
		$result = $this->client->request( $method, $path, $params, array() );
		$ms     = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'method'     => $method,
				'url'        => $path,
				'ms'         => $ms,
				'error_code' => $result->get_error_code(),
				'error_msg'  => $result->get_error_message(),
				'message'    => $result->get_error_message(),
			), 200 );
		}

		$json = wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			$json = '';
		}

		wp_send_json_success( array(
			'method' => $method,
			'url'    => $path,
			'ms'     => $ms,
			'json'   => $json,
		) );
	}

	/**
	 * Resolve {placeholder} tokens in a path template using submitted params.
	 * Consumed placeholders are removed from $params (so they don't also become query args).
	 *
	 * @param string $template e.g. '/property/{code}'
	 * @param array  $params   reference; consumed keys removed.
	 * @return string resolved path
	 */
	public static function resolve_path( $template, array &$params ) {
		return preg_replace_callback(
			'/\{([a-zA-Z0-9_]+)\}/',
			static function ( $m ) use ( &$params ) {
				$key = $m[1];
				$val = isset( $params[ $key ] ) ? (string) $params[ $key ] : '';
				unset( $params[ $key ] );
				return rawurlencode( $val );
			},
			$template
		);
	}
}
