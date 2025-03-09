<?php
/**
 * Template file for Stripe Tax settings page.
 *
 * @package Stripe\StripeTaxForWooCommerce\templates
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WP_Filesystem_Base' ) ) {
	require ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-filesystem-base.php';
}

if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
	require ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-filesystem-direct.php';
}

use Stripe\StripeTaxForWooCommerce\SDK\lib\Stripe;
use Stripe\StripeTaxForWooCommerce\SDK\lib\StripeClient;
use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxCodeList;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxSettings;
use Stripe\StripeTaxForWooCommerce\WooCommerce\Connect;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ErrorRenderer;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;
use Stripe\StripeTaxForWooCommerce\WordPress\TaxRegistrationsListTable;

$stfwc_live_key          = Options::get_live_mode_key();
$stfwc_masked_live_key   = Options::get_live_mode_masked_key();
$stfwc_live_mode_enabled = Options::is_live_mode_enabled();
$stfwc_wc_tax_enabled    = wc_tax_enabled();
$stfwc_filesystem_direct = new \WP_Filesystem_Direct( array() );

if ( $stfwc_live_mode_enabled ) {
	$notice_message = __( 'By checking the checkbox below, you have enabled the Stripe Tax plugin for tax calculation and collection. This means that the tax rates applicable to the customer orders are now provided by Stripe. It also means that the tax rates specified in the WooCommerce tax classes in the Tax tab are ignored.', 'stripe-tax-for-woocommerce' );
} else {
	$notice_message = __( 'If you enable the Stripe Tax calculation and collection, the tax rates specified in WooCommerce tax classes in the Tax tab will be ignored.', 'stripe-tax-for-woocommerce' );
}

ErrorRenderer::set_error_object( 'notice_message', $notice_message, 'info' );
ErrorRenderer::set_error_object( 'stripe_tax_include_in_price_tab', 'You can select if your set prices are inclusive or exclusive of tax in the "<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax' ) . '">Tax</a>" settings tab above.', 'info' );
ErrorRenderer::set_error_object( 'stripe_tax_registration_tab', 'Taxes will only be applied when registrations are added in the relevant jurisdictions.', 'info' );

if ( ! $stfwc_wc_tax_enabled ) {
	ErrorRenderer::set_error_object( 'wc_tax_enabled_required_to_collect_taxes', 'The Stripe Tax plugin requires tax rates and calculations to be enabled. Go to <a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">General</a> and Enable taxes', 'error' );
}

// Init with default values, if settings are not able to receive correctly.
$stfwc_stripe_tax_settings_country = '';
$stfwc_stripe_tax_settings_state   = '';
$stfwc_city_label                  = __( 'City', 'stripe-tax-for-woocommerce' );
$stfwc_postal_code_label           = __( 'Postal code', 'stripe-tax-for-woocommerce' );

$html_list     = StripeTaxPluginHelper::get_admin_allowed_html();
$svg_tags_list = StripeTaxPluginHelper::get_admin_allowed_svg_tags();

try {
	$stfwc_stripe_tax_settings = new TaxSettings( $stfwc_live_key );
	if ( StripeTaxPluginHelper::get_stripe_settings_update_error_flag() ) {
		$stfwc_stripe_tax_settings->set_settings(
			TaxSettings::get_from_post_request( true ),
			true
		);
	}

	$stfwc_stripe_tax_settings_country = $stfwc_stripe_tax_settings->get_country();
	$stfwc_stripe_tax_settings_state   = $stfwc_stripe_tax_settings->get_state();
	if ( ! $stfwc_stripe_tax_settings_country ) {
		$stfwc_stripe_tax_settings_country = 'US';
	}
	$district_text     = __( 'District', 'stripe-tax-for-woocommerce' );
	$town_or_city_text = __( 'Town or City', 'stripe-tax-for-woocommerce' );
	$city_text         = __( 'City', 'stripe-tax-for-woocommerce' );
	$stfwc_city_label  = array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_city_is_district_countries() )
		? $district_text
		: ( array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_city_is_town_or_city_countries() )
			? $town_or_city_text
			: $city_text );

	$eircode_text            = __( 'Eircode', 'stripe-tax-for-woocommerce' );
	$zip_text                = __( 'ZIP', 'stripe-tax-for-woocommerce' );
	$postal_code_text        = __( 'Postal code', 'stripe-tax-for-woocommerce' );
	$stfwc_postal_code_label = array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_postal_code_is_eircode_countries() )
		? $eircode_text
		: ( array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_postal_code_is_zip_countries() )
			? $zip_text
			: $postal_code_text );
	$stripe_client           = new StripeClient( $stfwc_live_key );
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$response = $stripe_client->taxCodes->all( array( 'limit' => 1 ) );
} catch ( Exception $e ) {
	WC_Admin_Settings::add_error( $e->getMessage() );
}
?>
	<div class="stripe_tax_for_woocommerce_step">
		<h2><?php echo esc_html( __( 'Stripe Tax', 'stripe-tax-for-woocommerce' ) ); ?></h2>
		<div>
			<p>
				<?php /* translators: 1. URL link. */ ?>
				<?php printf( esc_html( __( 'Version %s', 'stripe-tax-for-woocommerce' ) ), esc_html( Stripe::getAppInfo()['version'] ) ); ?>
			</p>
		</div>
		<div>
			<p>
				<?php echo esc_html( __( 'The Stripe Tax plugin allows for easy tax calculations and reporting in your shop. Enable the plugin to start automatically calculating sales tax.', 'stripe-tax-for-woocommerce' ) ); ?>
				<br>
				<?php /* translators: 1. URL link. */ ?>
				<?php printf( esc_html( __( 'Review the %s docs for troubleshooting tips and more information about how to configure this connector.', 'stripe-tax-for-woocommerce' ) ), '<a href="https://docs.stripe.com/connectors/woocommerce/configuration" target="_blank">Stripe Tax for WooCommerce</a>' ); ?>
				<br>
				<?php /* translators: 1. URL link. */ ?>
				<?php printf( esc_html( __( 'To set up automatic filing with a Stripe integrated partner, review the %s doc.', 'stripe-tax-for-woocommerce' ) ), '<a href="https://docs.stripe.com/tax/filing#filing-with-stripe" target="_blank">Filing with Stripe</a>' ); ?>
			</p>
		</div>
	</div>

	<?php echo wp_kses( ErrorRenderer::get_rendered_error( 'wc_tax_enabled_required_to_collect_taxes', true ), $html_list ); ?>

	<div class="stripe_tax_for_woocommerce_step">

		<h2><?php echo wp_kses( __( 'Step 1. Connect Stripe account', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h2>
		<h2 class="stripe_tax_for_woocommerce_button_authorize">
		<?php
		if ( ! $stfwc_live_key ) :
			try {
				$connect_url = Connect::get_stripe_oauth_init( admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce' ) );
				?>
					<a id="stripe_tax_for_woocommerce_button_id_connect_with_stripe"
						href="<?php echo esc_url( $connect_url ); ?>"
						class="page-title-action"><?php echo wp_kses( __( 'Connect with', 'stripe-tax-for-woocommerce' ), $html_list ); ?><?php echo wp_kses( $stfwc_filesystem_direct->get_contents( STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_DIR . 'connect_with_stripe.svg' ), $svg_tags_list ); ?></a>
					<?php
					ErrorRenderer::set_error_object( 'connect_with_stripe', Connect::get_woocommerce_connect_last_error(), 'error' );
			} catch ( Exception $e ) {
				ErrorRenderer::set_error_object( 'connect_with_stripe', __( 'Unable to receive Stripe Connect URL. Be sure you\'re not on localhost', 'stripe-tax-for-woocommerce' ), 'error' );
			}
			echo wp_kses( ErrorRenderer::get_rendered_error( 'connect_with_stripe' ), $html_list );
			else :
				?>
				<input autocomplete="off" type="hidden" name="stripe_tax_for_woocommerce_nonce_disconnect_from_stripe"
						value="<?php echo esc_attr( wp_create_nonce( 'stripe_tax_for_woocommerce_disconnect_from_stripe' ) ); ?>">
				<button type="submit" id="stripe_tax_for_woocommerce_button_id_disconnect_from_stripe"
						class="page-title-action"><?php echo wp_kses( __( 'Disconnect from', 'stripe-tax-for-woocommerce' ), $html_list ); ?><?php echo wp_kses( $stfwc_filesystem_direct->get_contents( STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_DIR . 'connect_with_stripe.svg' ), $svg_tags_list ); ?></button>
				<?php
				ErrorRenderer::set_error_object( 'disconnect_from_stripe', Connect::get_woocommerce_connect_last_error(), 'success' );
				echo wp_kses( ErrorRenderer::get_rendered_error( 'disconnect_from_stripe' ), $html_list );
			endif;
			?>
		</h2>
		<?php echo wp_kses( ErrorRenderer::get_rendered_error( 'notice_message' ), $html_list ); ?>
		<table class="form-table stripe_tax_for_woocommerce_settings">
			<tbody>
			<tr valign="top" class="">
				<th scope="row"
					class="titledesc"><?php echo wp_kses( __( 'Enable tax collection', 'stripe-tax-for-woocommerce' ), $html_list ); ?></th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php echo wp_kses( __( 'Enable Stripe Tax. When checked, tax will automatically be calculated and collected on items in your shop. Remember to click “Save Changes” below after checking or unchecking.', 'stripe-tax-for-woocommerce' ), $html_list ); ?><?php echo esc_textarea( __( 'Use live mode Stripe API key for Tax Calculations.', 'stripe-tax-for-woocommerce' ) ); ?></span>
						</legend>
						<label for="stripe_tax_for_woocommerce_id_enable_live_mode">
							<input autocomplete="off" type="hidden" name="stripe_tax_for_woocommerce_enable_live_mode"
									value="0">
							<input autocomplete="off" name="stripe_tax_for_woocommerce_enable_live_mode"
									id="stripe_tax_for_woocommerce_id_enable_live_mode" type="checkbox" class=""
									value="1"<?php checked( $stfwc_live_mode_enabled && $stfwc_wc_tax_enabled ); ?> <?php
									if ( ! $stfwc_live_key || ! $stfwc_wc_tax_enabled ) {
										echo 'disabled="disabled"';
									}
									?>
							>
							<?php echo wp_kses( __( 'Enable Stripe Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						</label>
						<p class="description"><?php echo wp_kses( __( 'When checked, tax will automatically be calculated and collected on items in your shop. Remember to click “Save Changes” below after checking or unchecking.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					</fieldset>
				</td>
			</tr>
			<?php if ( $stfwc_live_key ) : ?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="stripe_tax_for_woocommerce_id_live_key"><?php echo wp_kses( __( 'Secret key', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<span style="line-height: 30px;"><?php echo esc_html( $stfwc_masked_live_key ); ?></span>
					<input autocomplete="off" type="hidden"
							name="stripe_tax_for_woocommerce_nonce_test_connection_live_key"
							value="<?php echo esc_attr( wp_create_nonce( 'stripe_tax_for_woocommerce_test_connection_live_key' ) ); ?>">
					<input autocomplete="off" type="submit"
							id="stripe_tax_for_woocommerce_id_button_test_connection_live_key"
							name="stripe_tax_for_woocommerce_button_test_connection_live_key" class="button action"
							value="<?php echo esc_attr( __( 'Test connection', 'stripe-tax-for-woocommerce' ) ); ?>">
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'test_connection' ), $html_list );
					?>
					<p class="description"><?php echo wp_kses( __( 'For security, this key can’t be revealed.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
				</td>
			</tr>
			<?php endif; ?>
			</tbody>
		</table>

	</div>

<?php
if ( $stfwc_live_key ) :
	// Init Tax Settings with defaults.
	$settings_line1       = '';
	$settings_line2       = '';
	$settings_city        = '';
	$settings_postal_code = '';
	$settings_tax_code    = '';
	try {
		$settings_line1       = $stfwc_stripe_tax_settings->get_line1();
		$settings_line2       = $stfwc_stripe_tax_settings->get_line2();
		$settings_city        = $stfwc_stripe_tax_settings->get_city();
		$settings_postal_code = $stfwc_stripe_tax_settings->get_postal_code();
		$settings_tax_code    = $stfwc_stripe_tax_settings->get_tax_code();
	} catch ( Exception $e ) {
		ErrorRenderer::set_error_object( 'get_tax_settings', $e->getMessage(), 'error' );
		echo wp_kses( ErrorRenderer::get_rendered_error( 'get_tax_settings' ), $html_list );
	}
	?>
	<div class="stripe_tax_for_woocommerce_step">

		<h2><?php echo wp_kses( __( 'Step 2. Configure your sales tax settings', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h2>
		<h3><?php echo wp_kses( __( 'Head office', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h3>
		<div>
			<p>
				<?php echo wp_kses( __( 'The head office is where your business is located or, if you sell physical goods, the address where you\'re shipping goods from. It\'s usually the same as your business address.', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
			</p>
		</div>

		<table class="form-table stripe_tax_for_woocommerce_settings">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc"><label
						for="stripe_tax_for_woocommerce_id_live_mode_country"><?php echo wp_kses( __( 'Country', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_countries(),
						'stripe_tax_for_woocommerce_live_mode_country',
						$stfwc_stripe_tax_settings_country,
						'stripe_tax_for_woocommerce_id_live_mode_country',
						'stripe_tax_for_woocommerce_id_live_mode_country'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_country_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_line1"><?php echo wp_kses( __( 'Address line 1', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						</label>
				</th>
				<td class="forminp forminp-text">
					<input name="stripe_tax_for_woocommerce_live_mode_line1"
							id="stripe_tax_for_woocommerce_id_live_mode_line1"
							type="text" autocomplete="off"
							value="<?php echo esc_attr( $settings_line1 ); ?>"
							class="stripe_tax_for_woocommerce_id_live_mode_line1"
							placeholder="<?php echo wp_kses( __( 'Address line 1', 'stripe-tax-for-woocommerce' ), $html_list ); ?>">
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_line2"><?php echo wp_kses( __( 'Address line 2', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
				</th>
				<td class="forminp forminp-text">
					<input name="stripe_tax_for_woocommerce_live_mode_line2"
							id="stripe_tax_for_woocommerce_id_live_mode_line2"
							type="text" autocomplete="off"
							value="<?php echo esc_attr( $settings_line2 ); ?>"
							class="stripe_tax_for_woocommerce_id_live_mode_line2"
							placeholder="<?php echo wp_kses( __( 'Address line 2', 'stripe-tax-for-woocommerce' ), $html_list ); ?>">
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_city_row <?php echo ( array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_no_city_countries() ) ) ? 'stripe_tax_for_woocommerce_hidden' : ''; ?>">
				<th scope="row" class="titledesc"><label class="stripe_tax_for_woocommerce_city_label"
														for="stripe_tax_for_woocommerce_id_live_mode_city"><?php echo esc_html( $stfwc_city_label ); ?>
						</label>
				</th>
				<td class="forminp forminp-text">
					<input name="stripe_tax_for_woocommerce_live_mode_city"
							id="stripe_tax_for_woocommerce_id_live_mode_city"
							type="text" autocomplete="off"
							value="<?php echo esc_attr( $settings_city ); ?>"
							class="stripe_tax_for_woocommerce_id_live_mode_city"
							placeholder="<?php echo esc_attr( $stfwc_city_label ); ?>">
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_AE <?php echo wp_kses( ( 'AE' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_AE"><?php echo wp_kses( __( 'Province', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_ae_provinces(),
						'stripe_tax_for_woocommerce_live_mode_state[AE]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_AE',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_AU <?php echo wp_kses( ( 'AU' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_AU"><?php echo wp_kses( __( 'State', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_au_states(),
						'stripe_tax_for_woocommerce_live_mode_state[AU]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_AU',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_CA <?php echo wp_kses( ( 'CA' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_CA"><?php echo wp_kses( __( 'Province', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_ca_provinces(),
						'stripe_tax_for_woocommerce_live_mode_state[CA]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_CA',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_ES <?php echo wp_kses( ( 'ES' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_ES"><?php echo wp_kses( __( 'Province', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_es_provinces(),
						'stripe_tax_for_woocommerce_live_mode_state[ES]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_ES',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_HK <?php echo wp_kses( ( 'HK' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_HK"><?php echo wp_kses( __( 'Area', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_hk_areas(),
						'stripe_tax_for_woocommerce_live_mode_state[HK]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_HK',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_IE <?php echo wp_kses( ( 'IE' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_IE"><?php echo wp_kses( __( 'County', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_ie_counties(),
						'stripe_tax_for_woocommerce_live_mode_state[IE]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_IE',
						'stripe_tax_for_woocommerce_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_IT <?php echo wp_kses( ( 'IT' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_IT"><?php echo wp_kses( __( 'Province', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_it_provinces(),
						'stripe_tax_for_woocommerce_live_mode_state[IT]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_IT',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_JP <?php echo wp_kses( ( 'JP' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_JP"><?php echo wp_kses( __( 'Prefecture', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_jp_prefectures(),
						'stripe_tax_for_woocommerce_live_mode_state[JP]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_JP',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_state_row stripe_tax_for_woocommerce_state_row_US <?php echo wp_kses( ( 'US' !== $stfwc_stripe_tax_settings_country ) ? 'stripe_tax_for_woocommerce_hidden' : '', $html_list ); ?>">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_state_US"><?php echo wp_kses( __( 'State', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						StripeTaxPluginHelper::get_allowed_origin_address_us_states(),
						'stripe_tax_for_woocommerce_live_mode_state[US]',
						$stfwc_stripe_tax_settings_state,
						'stripe_tax_for_woocommerce_id_live_mode_state_US',
						'stripe_tax_for_woocommerce_id_live_mode_state'
					);
				?>
					<?php
					echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_state_error' ), $html_list );
					?>
				</td>
			</tr>
			<tr valign="top"
				class="stripe_tax_for_woocommerce_postal_code_row <?php echo ( array_key_exists( $stfwc_stripe_tax_settings_country, StripeTaxPluginHelper::get_no_postal_code_countries() ) ) ? 'stripe_tax_for_woocommerce_hidden' : ''; ?>">
				<th scope="row" class="titledesc"><label class="stripe_tax_for_woocommerce_postal_code_label"
														for="stripe_tax_for_woocommerce_id_live_mode_postal_code"><?php echo esc_html( $stfwc_postal_code_label ); ?>
						<?php if ( 'US' === $stfwc_stripe_tax_settings_country ) : ?>
						<span class="required"> *</span>
						<?php endif; ?>
					</label>
				</th>
				<td class="forminp forminp-text">
					<input name="stripe_tax_for_woocommerce_live_mode_postal_code"
							id="stripe_tax_for_woocommerce_id_live_mode_postal_code"
							type="text" autocomplete="off"
							value="<?php echo esc_attr( $settings_postal_code ); ?>"
							class="stripe_tax_for_woocommerce_id_live_mode_postal_code"
							placeholder="<?php echo esc_attr( $stfwc_postal_code_label ); ?>">
				</td>
			</tr>
			</tbody>
		</table>
		<h3><?php echo wp_kses( __( 'Product tax category', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h3>
		<table class="form-table stripe_tax_for_woocommerce_settings">
			<tbody>
			<tr valign="top" class="">
				<th scope="row" class="titledesc"><label
							for="stripe_tax_for_woocommerce_id_live_mode_tax_code"><?php echo wp_kses( __( 'Preset product tax code', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<span class="required"> *</span></label>
				</th>
				<td class="forminp forminp-select">
				<?php
					Options::stripe_tax_for_woocommerce_output_rendered_select(
						( new TaxCodeList( $stfwc_live_key ) )->get_as_key_value_formatted(),
						'stripe_tax_for_woocommerce_live_mode_tax_code',
						$settings_tax_code,
						'stripe_tax_for_woocommerce_id_live_mode_tax_code'
					);
				?>
					<p><?php echo wp_kses( __( 'Define a preset tax code to calculate the right amount of tax when you don\'t specify on products individually.', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<a href="https://stripe.com/docs/tax/tax-codes"
							target="_blank"><?php echo wp_kses( __( 'Learn more', 'stripe-tax-for-woocommerce' ), $html_list ); ?></a></p>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
		echo wp_kses( ErrorRenderer::get_rendered_error( 'stripe_tax_include_in_price_tab', true ), $html_list );
		echo wp_kses( ErrorRenderer::get_rendered_error( 'setting_tax_behavior_error' ), $html_list );
		?>
	</div>
	<?php
	add_action(
		'woocommerce_after_settings_stripe_tax_for_woocommerce',
		function () {
			$stfwc_live_key            = Options::get_live_mode_key();
			$stfwc_stripe_tax_settings = new TaxSettings( $stfwc_live_key );
			$html_list                 = StripeTaxPluginHelper::get_admin_allowed_html();
			?>
		<form method="post" action="" enctype="multipart/form-data">
			<h2><?php echo wp_kses( __( 'Step 3. Tax Registrations', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h2>
			<div>
				<p>
					<?php echo wp_kses( __( 'Locations where you have a registration and are currently collecting tax with Stripe. When you add a new address and save it, tax calculation works immediately.  When you delete the address, it stops immediately. You can add one or multiple tax registrations.', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
				</p>
				<p>
					<?php /* translators: 1. URL link. */ ?>
					<?php printf( esc_html( __( 'If you need to start collecting tax in a US state, but have not yet registered with the state government, Stripe Tax can register for you. Visit %s to learn more.', 'stripe-tax-for-woocommerce' ) ), '<a href="https://docs.stripe.com/tax/use-stripe-to-register" target="_blank">Stripe Docs</a>' ); ?>
				</p>
				<?php echo wp_kses( ErrorRenderer::get_rendered_error( 'stripe_tax_registration_tab' ), $html_list ); ?>
			</div>

			<?php
			global $current_section;

			$stripe_tax_for_woocommerce_key        = Options::get_live_mode_key();
			$stripe_tax_for_woocommerce_mode_label = __( 'Add New', 'stripe-tax-for-woocommerce' );

			try {

				$tax_registrations_list_table = new TaxRegistrationsListTable();
				$tax_registrations_list_table->set_api_key( $stripe_tax_for_woocommerce_key );
				$tax_registrations_list_table->prepare_items();

				echo '<h2>' . wp_kses( __( 'Tax Registrations', 'stripe-tax-for-woocommerce' ), $html_list ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce&section=' . rawurlencode( $current_section ) . '&add_tax_registration' ) ) . '" class="page-title-action">' . esc_html( $stripe_tax_for_woocommerce_mode_label ) . '</a></h2>';

				$tax_registrations_list_table->display();

			} catch ( Exception $e ) {
				$stripe_tax_error_message = 'Error fetching tax registrations: ' . $e->getMessage();
			}

			wp_nonce_field( 'woocommerce-settings' );
			?>

			<div class="stripe_tax_for_woocommerce_step">

				<h2><?php echo wp_kses( __( 'Step 4. Reporting and Filing', 'stripe-tax-for-woocommerce' ), $html_list ); ?></h2>
				<div>
					<p>
						<?php echo wp_kses( __( 'Stripe\'s financial reports help you understand and reconcile the activity in your account. You can view summary reports in the', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
						<a href="<?php echo esc_url( StripeTaxPluginHelper::get_report_link() ); ?>" target="_blank"><?php echo wp_kses( __( 'Stripe Dashboard', 'stripe-tax-for-woocommerce' ), $html_list ); ?></a>
						<?php echo wp_kses( __( 'by clicking "View report" in the "Tax report" column.', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</p>
					<p>
						<?php /* translators: 1. URL link. */ ?>
						<?php printf( esc_html( __( 'You can use Stripe Apps to file your tax returns automatically. View %s to learn more.', 'stripe-tax-for-woocommerce' ) ), '<a href="https://docs.stripe.com/tax/filing#filing-with-stripe" target="_blank">Stripe Docs</a>' ); ?>
					</p>
				</div>

			</div>
			</form>
			<?php
		}
	);

endif;
