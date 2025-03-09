<?php
/**
 * Tax Exemptions
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * Tax Exemptions service
 */
class TaxExemptions {
	/**
	 * Tax exemption possible values
	 *
	 * @var string[]
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-taxability_override
	 */
	protected $tax_exemption_values = array(
		'none',
		'customer_exempt',
		'reverse_charge',
	);

	/**
	 * Tax exemption default value
	 *
	 * @var string
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-taxability_override
	 */
	protected $tax_exemption_default_value = 'none';

	/**
	 * Get tax exemption from user profile
	 *
	 * @param int $customer_id WooCommerce customer id.
	 *
	 * @return string
	 */
	public function get_tax_exeption( $customer_id ) {
		if ( ! $customer_id ) {
			return $this->tax_exemption_default_value;
		}

		$tax_exemption_value = get_the_author_meta( 'tax_exemption', $customer_id );

		if ( in_array( $tax_exemption_value, $this->tax_exemption_values, true ) ) {
			return $tax_exemption_value;
		}

		return $this->tax_exemption_default_value;
	}

	/**
	 * Custom user profile fields.
	 *
	 * @param object $user User object.
	 *
	 * @return void
	 */
	public function custom_user_profile_fields( $user ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		include STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_PARTS_DIR . 'admin-profile-custom-fields.php';
	}

	/**
	 * Save custom user profile fields
	 *
	 * @param int $user_id User id.
	 *
	 * @return void
	 */
	public function save_custom_user_profile_fields( $user_id ) {
		check_admin_referer( 'update-user_' . $user_id );
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			exit;
		}

		if ( isset( $_POST['tax_exemption'] ) && current_user_can( 'edit_user', $user_id ) ) {
			update_user_meta( $user_id, 'tax_exemption', sanitize_text_field( wp_unslash( $_POST['tax_exemption'] ) ) );
		}
	}

	/**
	 * Get tax exemption link
	 *
	 * @return string
	 */
	private function get_tax_exemption_link(): string {
		return 'https://stripe.com/docs/tax/zero-tax';
	}
}
