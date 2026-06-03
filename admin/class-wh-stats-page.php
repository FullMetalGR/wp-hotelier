<?php
/**
 * Statistics screen: summary/per_day/per_country with date-range + inline-SVG charts.
 *
 * @package webhotelier
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit;
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

/**
 * Admin Statistics screen: summary, per-day and per-country figures rendered as inline-SVG charts.
 */
class WH_Stats_Page {

	const CAP = 'manage_options';

	/** @var WH_Stats_API */
	protected $stats;
	/** @var WH_Settings */
	protected $settings;

	/**
	 * Constructor. Injects the stats API and the settings store.
	 */
	public function __construct( $stats, $settings ) {
		$this->stats    = $stats;
		$this->settings = $settings;
	}

	/**
	 * Render a label=>value series as an inline-SVG bar chart. Pure & static.
	 *
	 * @param array  $series  Ordered map of label => numeric value.
	 * @param string $title   Chart title.
	 * @param int    $width   SVG width in px.
	 * @param int    $height  SVG plot height in px.
	 * @return string Escaped SVG markup (safe to echo).
	 */
	public static function svg_bar_chart( $series, $title = '', $width = 720, $height = 220 ) {
		$series = is_array( $series ) ? $series : array();

		if ( empty( $series ) ) {
			return '<div class="wh-chart wh-chart--empty"><strong>' . esc_html( $title ) . '</strong> &mdash; ' . esc_html__( 'No data', 'webhotelier' ) . '</div>';
		}

		$pad_left   = 40;
		$pad_bottom = 40;
		$pad_top    = 24;
		$plot_w     = max( 1, $width - $pad_left - 10 );
		$plot_h     = max( 1, $height - $pad_top - $pad_bottom );

		$values = array_map( 'floatval', array_values( $series ) );
		$max    = max( $values );
		if ( $max <= 0 ) {
			$max = 1; // avoid division by zero; flat zero bars.
		}

		$n     = count( $series );
		$gap   = 6;
		$bar_w = max( 1, ( $plot_w - ( $gap * ( $n - 1 ) ) ) / $n );

		$svg  = '<svg class="wh-chart" width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '" viewBox="0 0 ' . esc_attr( (string) $width ) . ' ' . esc_attr( (string) $height ) . '" role="img" aria-label="' . esc_attr( $title ) . '">';
		$svg .= '<title>' . esc_html( $title ) . '</title>';
		// Title text.
		$svg .= '<text x="' . esc_attr( (string) $pad_left ) . '" y="16" font-size="13" font-weight="600">' . esc_html( $title ) . '</text>';
		// Baseline axis.
		$baseline_y = $pad_top + $plot_h;
		$svg       .= '<line x1="' . esc_attr( (string) $pad_left ) . '" y1="' . esc_attr( (string) $baseline_y ) . '" x2="' . esc_attr( (string) ( $pad_left + $plot_w ) ) . '" y2="' . esc_attr( (string) $baseline_y ) . '" stroke="#c3c4c7" />';

		$i = 0;
		foreach ( $series as $label => $value ) {
			$v = (float) $value;
			$h = ( $v / $max ) * $plot_h;
			$x = $pad_left + ( $i * ( $bar_w + $gap ) );
			$y = $pad_top + ( $plot_h - $h );

			$svg .= '<rect x="' . esc_attr( (string) round( $x, 2 ) ) . '" y="' . esc_attr( (string) round( $y, 2 ) ) . '" width="' . esc_attr( (string) round( $bar_w, 2 ) ) . '" height="' . esc_attr( (string) round( $h, 2 ) ) . '" fill="#2271b1"><title>' . esc_html( (string) $label . ': ' . $value ) . '</title></rect>';

			// X label (rotated for density).
			$lx   = $x + ( $bar_w / 2 );
			$ly   = $baseline_y + 12;
			$svg .= '<text x="' . esc_attr( (string) round( $lx, 2 ) ) . '" y="' . esc_attr( (string) $ly ) . '" font-size="9" text-anchor="end" transform="rotate(-45 ' . esc_attr( (string) round( $lx, 2 ) ) . ' ' . esc_attr( (string) $ly ) . ')">' . esc_html( (string) $label ) . '</text>';

			++$i;
		}

		$svg .= '</svg>';
		return $svg;
	}

	/**
	 * Resolve a validated {from,to} date range from a query map.
	 * Defaults to the last 30 days. Invalid dates fall back to defaults.
	 *
	 * @param array $query Usually $_GET.
	 * @return array{from:string,to:string}
	 */
	public function resolve_range( $query ) {
		$default_to   = gmdate( 'Y-m-d' );
		$default_from = gmdate( 'Y-m-d', time() - ( 30 * DAY_IN_SECONDS ) );

		$from = ( ! empty( $query['from'] ) && self::is_date( (string) $query['from'] ) ) ? (string) $query['from'] : $default_from;
		$to   = ( ! empty( $query['to'] ) && self::is_date( (string) $query['to'] ) ) ? (string) $query['to'] : $default_to;

		if ( $from > $to ) {
			$tmp  = $from;
			$from = $to;
			$to   = $tmp;
		}

		return array(
			'from' => $from,
			'to'   => $to,
		);
	}

	/**
	 * Validate a YYYY-MM-DD date string.
	 */
	protected static function is_date( $s ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s );
	}

	/**
	 * Extract a label=>value series from a per_day/per_country response.
	 *
	 * @param mixed  $resp       API data (array with optional 'data').
	 * @param string $label_key  Field holding the label.
	 * @param string $value_key  Field holding the numeric value.
	 * @return array
	 */
	protected function series_from( $resp, $label_key, $value_key ) {
		$rows = array();
		if ( is_array( $resp ) ) {
			if ( isset( $resp['data'] ) && is_array( $resp['data'] ) ) {
				$rows = $resp['data'];
			} else {
				$rows = $resp;
			}
		}
		$series = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = isset( $row[ $label_key ] ) ? (string) $row[ $label_key ] : '';
			$value = isset( $row[ $value_key ] ) ? (float) $row[ $value_key ] : 0.0;
			if ( '' !== $label ) {
				$series[ $label ] = $value;
			}
		}
		return $series;
	}

	/**
	 * Render the statistics screen.
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

		$range  = $this->resolve_range( $query );
		$code   = (string) $this->settings->get( 'default_property', '' );
		$params = array(
			'date_from' => $range['from'],
			'date_to'   => $range['to'],
		);

		$summary  = $this->stats->summary( $code, $params );
		$per_day  = $this->stats->per_day( $code, $params );
		$per_ctry = $this->stats->per_country( $code, $params );

		$errors = array();
		if ( is_wp_error( $summary ) ) {
			$errors[] = $summary->get_error_message();
			$summary  = array(); }
		if ( is_wp_error( $per_day ) ) {
			$errors[] = $per_day->get_error_message();
			$per_day  = array(); }
		if ( is_wp_error( $per_ctry ) ) {
			$errors[] = $per_ctry->get_error_message();
			$per_ctry = array(); }

		$day_series     = $this->series_from( $per_day, 'date', 'revenue' );
		$country_series = $this->series_from( $per_ctry, 'country', 'revenue' );

		$day_chart     = self::svg_bar_chart( $day_series, __( 'Revenue per day', 'webhotelier' ) );
		$country_chart = self::svg_bar_chart( $country_series, __( 'Revenue per country', 'webhotelier' ) );

		$view = __DIR__ . '/views/stats-page.php';
		if ( file_exists( $view ) ) {
			include $view;
			return;
		}
		$this->render_inline( $range, $summary, $day_chart, $country_chart, $errors );
	}

	/**
	 * Inline fallback renderer.
	 */
	protected function render_inline( $range, $summary, $day_chart, $country_chart, $errors ) {
		echo '<div class="wrap wh-admin wh-stats"><h1>' . esc_html__( 'Statistics', 'webhotelier' ) . '</h1>';

		foreach ( $errors as $msg ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $msg ) . '</p></div>';
		}

		echo '<form method="get"><input type="hidden" name="page" value="webhotelier-stats" />';
		echo '<label>' . esc_html__( 'From', 'webhotelier' ) . ' <input type="date" name="from" value="' . esc_attr( $range['from'] ) . '" /></label> ';
		echo '<label>' . esc_html__( 'To', 'webhotelier' ) . ' <input type="date" name="to" value="' . esc_attr( $range['to'] ) . '" /></label> ';
		echo '<button class="button">' . esc_html__( 'Apply', 'webhotelier' ) . '</button></form>';

		echo '<div class="wh-stats-summary"><table class="widefat striped"><tbody>';
		$labels = array(
			'revenue'    => __( 'Revenue', 'webhotelier' ),
			'bookings'   => __( 'Bookings', 'webhotelier' ),
			'roomnights' => __( 'Room nights', 'webhotelier' ),
			'adr'        => __( 'ADR', 'webhotelier' ),
		);
		if ( is_array( $summary ) ) {
			foreach ( $labels as $k => $label ) {
				if ( isset( $summary[ $k ] ) ) {
					echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( (string) $summary[ $k ] ) . '</td></tr>';
				}
			}
		}
		echo '</tbody></table></div>';

		echo '<div class="wh-stats-charts">';
		echo $day_chart;     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- svg_bar_chart() escapes every dynamic value (esc_attr/esc_html).
		echo $country_chart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- svg_bar_chart() escapes every dynamic value (esc_attr/esc_html).
		echo '</div></div>';
	}
}
