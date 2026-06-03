<?php
/**
 * Minimal WordPress class/function doubles for unit tests where WP core is not loaded.
 * Only the pieces our code relies on are implemented.
 *
 * @package WebHotelier
 */

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error double mirroring the subset of the WordPress API we use.
	 */
	class WP_Error {

		/** @var array<string,array<int,string>> */
		protected $errors = array();

		/** @var array<string,mixed> */
		protected $error_data = array();

		/**
		 * @param string|int $code    Error code.
		 * @param string     $message Error message.
		 * @param mixed      $data    Optional error data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( '' === $code && '' === $message ) {
				return;
			}
			$this->errors[ $code ][] = $message;
			if ( '' !== $data ) {
				$this->error_data[ $code ] = $data;
			}
		}

		/** @return string|int */
		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return empty( $codes ) ? '' : $codes[0];
		}

		/** @return string */
		public function get_error_message() {
			$code = $this->get_error_code();
			if ( '' === $code || empty( $this->errors[ $code ] ) ) {
				return '';
			}
			return $this->errors[ $code ][0];
		}

		/** @return mixed */
		public function get_error_data( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value to check.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	/**
	 * Unit tests run outside an admin request; the container therefore never
	 * loads admin classes nor boots WH_Admin in this context.
	 *
	 * @return bool
	 */
	function is_admin() {
		return false;
	}
}
