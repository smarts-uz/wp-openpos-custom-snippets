<?php
/**
 * Add extra functionality to WooCommerce Order Item Tax class.
 *
 * @package Stripe\StripeTaxForWooCommerce\WooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce\WooCommerce;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * Extends WooCommerce Order Item Tax class with support of Stripe Taxes.
 */
class StripeOrderItemTax extends \WC_Order_Item_Tax {

	/**
	 * Determines if passed options names(rate_id/rate) belongs to Stripe Tax plugin.
	 *
	 * @param string $str Processing string.
	 */
	protected function stripe_tax_for_woocommerce_is_stripe( $str ): bool {
		if ( is_string( $str ) && strpos( $str, 'stripe_tax_for_woocommerce_' ) === 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Sets the WooCommerce Tax rate ID.
	 *
	 * @param string $value Rate id value.
	 */
	public function set_rate_id( $value ) {
		if ( $this->stripe_tax_for_woocommerce_is_stripe( $value ) ) {
			$this->set_prop( 'rate_id', $value );

			return;
		}
		parent::set_rate_id( $value );
	}

	/**
	 * Set all needed Woocommerce tax rate parameters.
	 *
	 * @param string $tax_rate_id Tax rate id.
	 */
	public function set_rate( $tax_rate_id ) {
		if ( $this->stripe_tax_for_woocommerce_is_stripe( $tax_rate_id ) ) {
			$this->set_rate_id( $tax_rate_id );
			$this->set_rate_code( $tax_rate_id );
			$this->set_label( explode( '__', $tax_rate_id )[3] );
			$this->set_compound( false );
			$this->set_rate_percent( (float) ( explode( '__', $tax_rate_id )[2] ) );

			return;
		}
		parent::set_rate( $tax_rate_id );
	}
}
