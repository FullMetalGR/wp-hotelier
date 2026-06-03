<?php
/**
 * Settings screen template. Rendered by WH_Settings_Page::render().
 *
 * @package webhotelier
 * @var string $test_nonce
 */
if ( ! defined( 'ABSPATH' ) && ! defined( 'WH_TESTING' ) && ! defined( 'WH_TESTS_DIR' ) ) {
	exit; }
?>
<div class="wrap wh-admin wh-settings">
	<h1><?php echo esc_html__( 'WebHotelier Settings', 'webhotelier' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( WH_Settings_Page::GROUP );
		do_settings_sections( WH_Settings_Page::OPTION );
		submit_button();
		?>
	</form>

	<hr />

	<h2><?php echo esc_html__( 'Test Connection', 'webhotelier' ); ?></h2>
	<p class="description"><?php echo esc_html__( 'Save your credentials first. The password field is write-only — leave blank to keep current password.', 'webhotelier' ); ?></p>
	<p>
		<button type="button" class="button button-secondary" id="wh-test-connection"
			data-nonce="<?php echo esc_attr( $test_nonce ); ?>">
			<?php echo esc_html__( 'Test Connection', 'webhotelier' ); ?>
		</button>
	</p>
	<div id="wh-test-result" class="wh-test-result" aria-live="polite"></div>
</div>
