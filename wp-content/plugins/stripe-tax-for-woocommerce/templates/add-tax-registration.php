<?php
/**
 * Template file for Stripe Tax Registrations table.
 *
 * @package Stripe\StripeTaxForWooCommerce\templates
 */

global $current_section;

defined( 'ABSPATH' ) || exit;

use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxRegistrations;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ErrorRenderer;
use Stripe\StripeTaxForWooCommerce\WooCommerce\StripeTax;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;
use Stripe\StripeTaxForWooCommerce\WordPress\TaxRegistrationsListTable;

$stripe_tax_for_woocommerce_key        = Options::get_live_mode_key();
$stripe_tax_for_woocommerce_mode_label = __( 'Add Registration', 'stripe-tax-for-woocommerce' );

$stripe_tax_for_woocommerce_tax_registration_selected_country  = 'US';
$stripe_tax_for_woocommerce_tax_registration_selected_us_state = 'AL';

$stripe_tax_for_woocommerce_tax_registrations = new TaxRegistrations( $stripe_tax_for_woocommerce_key );
$tax_registrations                            = $stripe_tax_for_woocommerce_tax_registrations->get_registrations();

$stripe_tax_for_woocommerce_tax_maybe_hide_us_state            = '';
$stripe_tax_for_woocommerce_tax_maybe_hide_lease_and_amusement = '';
$stripe_tax_for_woocommerce_tax_maybe_hide_communications      = '';

if ( 'US' !== $stripe_tax_for_woocommerce_tax_registration_selected_country ) {
	$stripe_tax_for_woocommerce_tax_maybe_hide_us_state       = 'stripe_tax_for_woocommerce_hidden';
	$stripe_tax_for_woocommerce_tax_maybe_hide_communications = 'stripe_tax_for_woocommerce_hidden';
}

$stripe_tax_for_woocommerce_tax_maybe_hide_eu = '';
if ( ! in_array( $stripe_tax_for_woocommerce_tax_registration_selected_country, StripeTaxPluginHelper::get_tax_registration_eu_countries(), true ) ) {
	$stripe_tax_for_woocommerce_tax_maybe_hide_eu = 'stripe_tax_for_woocommerce_hidden';
}

$stripe_tax_for_woocommerce_tax_maybe_hide_ca = '';
if ( 'CA' !== $stripe_tax_for_woocommerce_tax_registration_selected_country ) {
	$stripe_tax_for_woocommerce_tax_maybe_hide_ca = 'stripe_tax_for_woocommerce_hidden';
}

if ( ! in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, StripeTaxPluginHelper::get_tax_registration_lease_and_amusement_tax_us_states(), true ) ) {
	$stripe_tax_for_woocommerce_tax_maybe_hide_lease_and_amusement = 'stripe_tax_for_woocommerce_hidden';
}

if ( ! in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, StripeTaxPluginHelper::get_tax_registration_local_communications_tax_us_states(), true ) ) {
	$stripe_tax_for_woocommerce_tax_maybe_hide_communications = 'stripe_tax_for_woocommerce_hidden';
}

$stripe_tax_for_woocommerce_allowed_countries = StripeTaxPluginHelper::get_allowed_tax_registration_countries();
$stripe_tax_for_woocommerce_allowed_us_states = StripeTaxPluginHelper::get_allowed_tax_registration_us_states();

// $locks array contains prepared for easy check list of already added tax registrations.
// In this case it used to prerender and lock checkboxes as checked and disabled.
$locks = $stripe_tax_for_woocommerce_tax_registrations->get_locks();

$disable_domestic = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_country, $locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ], true ) ) {
	$disable_domestic = 'disabled="disabled" checked="checked"';
}

$disable_oss_union = '';
if ( $locks[ StripeTaxPluginHelper::LOCK_OSS_UNION ] ) {
	$disable_oss_union = 'disabled="disabled" checked="checked"';
}

$disable_oss_non_union = '';
if ( $locks[ StripeTaxPluginHelper::LOCK_OSS_NON_UNION ] ) {
	$disable_oss_non_union = 'disabled="disabled" checked="checked"';
}

$disable_ioss = '';
if ( $locks[ StripeTaxPluginHelper::LOCK_IOSS ] ) {
	$disable_ioss = 'disabled="disabled" checked="checked"';
}

$disable_bc = '';
if ( in_array( 'BC', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
	$disable_bc = 'disabled="disabled" checked="checked"';
}

$disable_mb = '';
if ( in_array( 'MB', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
	$disable_mb = 'disabled="disabled" checked="checked"';
}

$disable_sk = '';
if ( in_array( 'SK', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
	$disable_sk = 'disabled="disabled" checked="checked"';
}

$disable_qc = '';
if ( in_array( 'QC', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
	$disable_qc = 'disabled="disabled" checked="checked"';
}

$disable_local_communications = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_LOCAL_COMMUNICATIONS ], true ) ) {
	$disable_local_communications = 'disabled="disabled" checked="checked"';
}

$disable_chicago_lease = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_CHICAGO_LEASE ], true ) ) {
	$disable_chicago_lease = 'disabled="disabled" checked="checked"';
}

$disable_chicago_amusement = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_CHICAGO_AMUSEMENT ], true ) ) {
	$disable_chicago_amusement = 'disabled="disabled" checked="checked"';
}

$disable_bloomington = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_BLOOMINGTON ], true ) ) {
	$disable_bloomington = 'disabled="disabled" checked="checked"';
}

$disable_east_dundee = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_EAST_DUNDEE ], true ) ) {
	$disable_east_dundee = 'disabled="disabled" checked="checked"';
}

$disable_evanston = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_EVANSTON ], true ) ) {
	$disable_evanston = 'disabled="disabled" checked="checked"';
}

$disable_schiller_park = '';
if ( in_array( $stripe_tax_for_woocommerce_tax_registration_selected_us_state, $locks[ StripeTaxPluginHelper::LOCK_SCHILLER_PARK ], true ) ) {
	$disable_schiller_park = 'disabled="disabled" checked="checked"';
}

$html_list = StripeTaxPluginHelper::get_admin_allowed_html();

?>
	<a class="button action"
		href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce' ) ); ?>"><?php echo esc_html( __( 'Back to Stripe Tax settings', 'stripe-tax-for-woocommerce' ) ); ?></a>
	<h2 class="stripe_tax_for_woocommerce_button_authorize"><?php echo esc_html( $stripe_tax_for_woocommerce_mode_label ); ?></h2>
	<p class="description">Click the Stripe Tax tab above to return to the main settings menu.</p>
	<p class="description">Check Stripe Tax’s <a href="https://stripe.com/docs/tax/supported-countries" target="_blank">supported
		countries</a> to make sure your requirements are met when adding new registrations outside the US.</p>
<?php echo wp_kses( ErrorRenderer::get_rendered_error( 'add_tax_registration' ), $html_list ); ?>
	<table class="form-table stripe_tax_for_woocommerce_tax_registrations">
		<tbody>
		<tr valign="top"
			class="stripe_tax_for_woocommerce_id_tax_registration_row_country">
			<th scope="row" class="titledesc"><label
					for="stripe_tax_for_woocommerce_id_tax_registration_country"><?php echo esc_html( __( 'Country', 'stripe-tax-for-woocommerce' ) ); ?></label>
			</th>
			<td class="forminp forminp-select">
				<?php
				Options::stripe_tax_for_woocommerce_output_rendered_select(
					StripeTaxPluginHelper::get_allowed_tax_registration_countries(),
					'stripe_tax_for_woocommerce_tax_registration_country',
					$stripe_tax_for_woocommerce_tax_registration_selected_country,
					'stripe_tax_for_woocommerce_id_tax_registration_country',
					'stripe_tax_for_woocommerce_tax_registration_country'
				);
				?>
			</td>
		</tr>
		<tr valign="top"
			class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_us_state <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_us_state ); ?>">
			<th scope="row" class="titledesc"><label
				for="stripe_tax_for_woocommerce_id_tax_registration_us_state"><?php echo esc_html( __( 'State', 'stripe-tax-for-woocommerce' ) ); ?></label>
			</th>
			<td class="forminp forminp-select">
				<?php
				Options::stripe_tax_for_woocommerce_output_rendered_select(
					StripeTaxPluginHelper::get_allowed_tax_registration_us_states(),
					'stripe_tax_for_woocommerce_tax_registration_us_state',
					$stripe_tax_for_woocommerce_tax_registration_selected_us_state,
					'stripe_tax_for_woocommerce_id_tax_registration_us_state',
					'stripe_tax_for_woocommerce_tax_registration_us_state'
				);
				?>
			</td>
		</tr>
		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_eu <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_eu ); ?>">
			<th scope="row" class="titledesc"><label
						for=""><?php echo esc_html( __( 'Select all registrations applicable to you', 'stripe-tax-for-woocommerce' ) ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo esc_html( __( 'Select all registrations applicable to you.', 'stripe-tax-for-woocommerce' ) ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_standard">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_standard"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_standard" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_domestic ); ?>>
						<span class="tax_registrations_localize_domestic">
						<?php
						// Translators: %s Stands for country.
						printf( esc_html( __( 'Domestic (registered in %s)', 'stripe-tax-for-woocommerce' ) ), esc_html( $stripe_tax_for_woocommerce_allowed_countries[ $stripe_tax_for_woocommerce_tax_registration_selected_country ] ) );
						?>
						</span>
					</label>
					<p class="tax_registrations_localize_domestic_description description">
						<?php
						// Translators: %s.
						printf( esc_html( __( 'Common for businesses selling goods and services to customers in %s.', 'stripe-tax-for-woocommerce' ) ), esc_html( $stripe_tax_for_woocommerce_allowed_countries[ $stripe_tax_for_woocommerce_tax_registration_selected_country ] ) );
						?>
					</p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_oss_union">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_oss_union"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_oss_union" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_oss_union ); ?>>
						<span class="tax_registrations_localize_oss_union"><?php echo wp_kses( __( 'Union One-Stop Shop (OSS)', 'stripe-tax-for-woocommerce' ), $html_list ); ?></span>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling goods and services to customers in the EU.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_oss_non_union">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_oss_non_union"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_oss_non_union" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_oss_non_union ); ?>>
						<?php echo wp_kses( __( 'Non-Union One-Stop Shop (OSS)', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling services to customers in the EU.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_ioss">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_ioss"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_ioss" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_ioss ); ?>>
						<?php echo wp_kses( __( 'Import One-Stop Shop (IOSS)', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses shipping goods below EUR 150 from non-EU countries to EU customers.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_ca <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_ca ); ?>">
			<th scope="row" class="titledesc"><label
				for="stripe_tax_for_woocommerce_id_tax_registration_country"><?php echo wp_kses( __( 'Select the type of registration that\'s applicable to you', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
			</th>
			<td class="forminp forminp-radio">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo wp_kses( __( 'Select the type of registration that\'s applicable to you', 'stripe-tax-for-woocommerce' ), $html_list ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_ca_normal">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_ca"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_ca_normal" type="radio" class=""
							value="standard" checked="checked">
						<?php echo wp_kses( __( 'Normal GST/HST', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Available for anyone doing business in Canada.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_ca_simplified">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_ca"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_ca_simplified" type="radio" class=""
							value="simplified">
						<?php echo wp_kses( __( 'Simplified GST/HST', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Only available to businesses not established in Canada with B2C sales of digital products and services.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
				</fieldset>
			</td>
		</tr>


		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_ca <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_ca ); ?>">
			<th scope="row" class="titledesc"><label
				for=""><?php echo wp_kses( __( 'Select if you have any other Canada registrations:', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
				<p class="description"><?php echo wp_kses( __( 'You must be registered in the relevant provinces to collect and remit PST/QST.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo wp_kses( __( 'Select if you have any other Canada registrations:', 'stripe-tax-for-woocommerce' ), $html_list ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_bc">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_bc"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_bc" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_bc ); ?>>
						<?php echo wp_kses( __( 'PST - British Columbia', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_mb">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_mb"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_mb" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_mb ); ?>>
						<?php echo wp_kses( __( 'RST - Manitoba', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_sk">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_sk"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_sk" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_sk ); ?>>
						<?php echo wp_kses( __( 'PST - Saskatchewan', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_qc">
						<input autocomplete="off" name="stripe_tax_for_woocommerce_tax_registration_type_qc"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_qc" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1"<?php echo esc_attr( $disable_qc ); ?>>
						<?php echo wp_kses( __( 'QST - Quebec', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"></p>
				</fieldset>
			</td>
		</tr>


		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_lease_and_amusement <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_lease_and_amusement ); ?>">
			<th scope="row" class="titledesc"><label
				for=""><?php echo wp_kses( __( 'Lease Taxes', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo wp_kses( __( 'Lease Taxes', 'stripe-tax-for-woocommerce' ), $html_list ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_lease_tax">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_lease_tax"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_lease_tax"
							type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1"<?php echo esc_attr( $disable_chicago_lease ); ?>>
						<?php echo wp_kses( __( 'Chicago Lease Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Personal Property Lease Transaction Tax - common for businesses selling $100,000 or over of software as a service or other leased products into Chicago.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
				</fieldset>
			</td>
		</tr>

		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_lease_and_amusement <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_lease_and_amusement ); ?>">
			<th scope="row" class="titledesc"><label
				for=""><?php echo wp_kses( __( 'Amusement Taxes', 'stripe-tax-for-woocommerce' ), $html_list ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo wp_kses( __( 'Select if you have any other Canada registrations:', 'stripe-tax-for-woocommerce' ), $html_list ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_chicago">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_chicago"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_chicago" type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_chicago_amusement ); ?>>
						<?php echo wp_kses( __( 'Chicago Amusement Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling $100,000 or more of digital entertainment into Chicago, including selling video or audio streaming and online gaming.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_bloomington">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_bloomington"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_bloomington"
							type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_bloomington ); ?>>
						<?php echo wp_kses( __( 'Bloomington Amusement Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling video or audio streaming on a pay-per-use, rental, or subscription basis to customers in Bloomington.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_east_dundee">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_east_dundee"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_east_dundee"
							type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_east_dundee ); ?>>
						<?php echo wp_kses( __( 'East Dundee Amusement Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling video or audio streaming, or remotely-accessed online games on a pay-per-use, rental, or subscription basis to customers in East Dundee.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_evanston">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_evanston"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_evanston"
							type="checkbox"
							class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_evanston ); ?>>
						<?php echo wp_kses( __( 'Evanston Amusement Tax', 'stripe-tax-for-woocommerce' ), $html_list ); ?>
					</label>
					<p class="description"><?php echo wp_kses( __( 'Common for businesses selling video or audio streaming, or remotely-accessed online games on a pay-per-use, rental, or subscription basis to customers in Evanston.', 'stripe-tax-for-woocommerce' ), $html_list ); ?></p>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_schiller_park">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_schiller_park"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_schiller_park"
							type="checkbox" class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_schiller_park ); ?>>
						<?php echo esc_html( __( 'Schiller Park Streaming Services Surcharge', 'stripe-tax-for-woocommerce' ) ); ?>
					</label>
					<p class="description"><?php echo esc_html( __( 'Streaming Services Surcharge - common for businesses selling video or audio streaming, or remotely-accessed online games on a pay-per-use, rental, or subscription basis that are delivered to customers in Schiller Park.', 'stripe-tax-for-woocommerce' ) ); ?></p>
				</fieldset>
			</td>
		</tr>


		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_communications <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_communications ); ?>">
			<th scope="row" class="titledesc"><label
				for=""><?php echo esc_html( __( 'Select any other registrations applicable to you:', 'stripe-tax-for-woocommerce' ) ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php echo esc_html( __( 'Select any other registrations applicable to you:', 'stripe-tax-for-woocommerce' ) ); ?></span>
					</legend>
					<label for="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_communications">
						<input autocomplete="off"
							name="stripe_tax_for_woocommerce_tax_registration_type_us_state_communications"
							id="stripe_tax_for_woocommerce_id_tax_registration_type_us_state_communications"
							type="checkbox" class="stripe_tax_for_woocommerce_tax_registration_input_type"
							value="1" <?php echo esc_attr( $disable_local_communications ); ?>>
						<span class="tax_registrations_localize_local_communications">
							<?php
							// Translators: %s.
							printf( esc_html( __( '%s State and Local Communications Tax', 'stripe-tax-for-woocommerce' ) ), esc_html( $stripe_tax_for_woocommerce_allowed_us_states[ $stripe_tax_for_woocommerce_tax_registration_selected_us_state ] ) );
							?>
						</span>
					</label>
					<p class="tax_registrations_localize_local_communications_description description">
						<?php
						// Translators: %s.
						printf( esc_html( __( 'Common for businesses selling video or audio streaming to customers in %s. This includes the Communications Services Tax, Communications Services Gross Receipts Tax and Local Communications Services Tax.', 'stripe-tax-for-woocommerce' ) ), esc_html( $stripe_tax_for_woocommerce_allowed_us_states[ $stripe_tax_for_woocommerce_tax_registration_selected_us_state ] ) );
						?>
					</p>
				</fieldset>
			</td>
		</tr>


		<tr class="stripe_tax_for_woocommerce_tax_registration_row stripe_tax_for_woocommerce_tax_registration_row_no_sales_tax_us_states <?php echo esc_attr( $stripe_tax_for_woocommerce_tax_maybe_hide_communications ); ?>">
			<th scope="row" class="titledesc">
			</th>
			<td class="forminp">
				<fieldset>
					<p class="tax_registrations_localize_no_sales_description description">
						<?php
						// Translators: %s.
						printf( esc_html( __( 'You don’t need to add a registration in %s because there’s no sales tax in this state.', 'stripe-tax-for-woocommerce' ) ), esc_html( $stripe_tax_for_woocommerce_allowed_us_states[ $stripe_tax_for_woocommerce_tax_registration_selected_us_state ] ) );
						?>
					</p>
				</fieldset>
			</td>
		</tr>
		</tbody>
	</table>
<?php
ErrorRenderer::set_error_object( 'tax_registration_submit', 'By pressing the "Save changes" button I confirm that I’m registered to collect sales tax in this jurisdiction.', 'info' );
echo wp_kses( ErrorRenderer::get_rendered_error( 'tax_registration_submit' ), $html_list );
?>
<?php
add_action(
	'woocommerce_after_settings_stripe_tax_for_woocommerce',
	function () {
		?>
		<form method="post" action="" enctype="multipart/form-data">
			<?php
			global $current_section;

			$stripe_tax_for_woocommerce_key = Options::get_live_mode_key();

			try {
				$tax_registrations_list_table = new TaxRegistrationsListTable();
				$tax_registrations_list_table->set_api_key( $stripe_tax_for_woocommerce_key );
				$tax_registrations_list_table->prepare_items();
				$tax_registrations_list_table->display();
			} catch ( Exception $e ) {
				\WC_Admin_Settings::add_error( $e->getMessage() );
			}

			wp_nonce_field( 'woocommerce-settings' );
			?>
		</form>
		<?php
	}
);
