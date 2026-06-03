<?php
/**
 * Internationalization helpers: Accept-Language resolution and money formatting.
 *
 * @package WebHotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

/**
 * Class WH_I18n.
 */
class WH_I18n {

	/**
	 * Known currency symbols. Unknown currencies fall back to their ISO code.
	 *
	 * @return array<string,string>
	 */
	private static function symbols() {
		return array(
			'EUR' => '€',
			'USD' => '$',
			'GBP' => '£',
			'JPY' => '¥',
			'CHF' => 'CHF',
			'AUD' => 'A$',
			'CAD' => 'C$',
		);
	}

	/**
	 * Resolve the Accept-Language header value.
	 *
	 * Order: explicit setting → WordPress get_locale() → en_GB.
	 *
	 * @param WH_Settings $settings Settings model.
	 * @return string
	 */
	public static function accept_language( WH_Settings $settings ) {
		$locale = trim( (string) $settings->locale() );
		if ( '' !== $locale ) {
			return $locale;
		}
		if ( function_exists( 'get_locale' ) ) {
			$wp_locale = trim( (string) get_locale() );
			if ( '' !== $wp_locale ) {
				return $wp_locale;
			}
		}
		return 'en_GB';
	}

	/**
	 * Format a money amount for display.
	 *
	 * @param int|float|string $amount   Numeric amount.
	 * @param string           $currency ISO 4217 currency code.
	 * @return string
	 */
	public static function money( $amount, $currency ) {
		$amount    = (float) $amount;
		$currency  = strtoupper( (string) $currency );
		$symbols   = self::symbols();
		$formatted = number_format( $amount, 2, '.', ',' );

		if ( isset( $symbols[ $currency ] ) ) {
			$symbol = $symbols[ $currency ];
			// Symbol-prefix currencies vs spaced codes.
			if ( in_array( $currency, array( 'CHF' ), true ) ) {
				return $symbol . ' ' . $formatted;
			}
			return $symbol . $formatted;
		}

		return $currency . ' ' . $formatted;
	}
}
