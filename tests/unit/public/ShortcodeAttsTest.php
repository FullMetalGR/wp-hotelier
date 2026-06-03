<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-shortcodes.php';

final class ShortcodeAttsTest extends WH_Public_TestCase {

	public function test_fills_property_from_default_in_single_mode(): void {
		$sc   = new WH_Shortcodes( $this->mock_settings( array( 'mode' => 'single', 'default_property' => 'DEMO' ) ) );
		$atts = $sc->parse_atts( array(), array( 'room' => '' ) );
		$this->assertSame( 'DEMO', $atts['property'] );
		$this->assertArrayHasKey( 'class', $atts );
		$this->assertArrayHasKey( 'room', $atts );
	}

	public function test_explicit_property_overrides_default(): void {
		$sc   = new WH_Shortcodes( $this->mock_settings() );
		$atts = $sc->parse_atts( array( 'property' => 'ZEN2' ), array() );
		$this->assertSame( 'ZEN2', $atts['property'] );
	}

	public function test_multi_mode_does_not_force_default_property(): void {
		$sc   = new WH_Shortcodes( $this->mock_settings( array( 'mode' => 'multi', 'default_property' => 'DEMO' ) ) );
		$atts = $sc->parse_atts( array(), array() );
		$this->assertSame( '', $atts['property'] );
	}

	public function test_custom_defaults_merge(): void {
		$sc   = new WH_Shortcodes( $this->mock_settings() );
		$atts = $sc->parse_atts( array( 'columns' => '3' ), array( 'columns' => '2', 'show_price' => 'yes' ) );
		$this->assertSame( '3', $atts['columns'] );
		$this->assertSame( 'yes', $atts['show_price'] );
	}
}
