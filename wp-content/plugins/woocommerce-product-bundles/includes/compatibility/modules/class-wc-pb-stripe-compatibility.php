<?php
/**
 * WC_PB_Stripe_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    6.6.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stripe Compatibility.
 *
 * @version  7.1.2
 */
class WC_PB_Stripe_Compatibility {

	/**
	 * Init compatibility.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'wc_stripe_hide_payment_request_on_product_page', array( __CLASS__, 'hide_stripe_quickpay' ), 10, 2 );
	}

	/**
	 * Hide Stripe Quick-pay buttons for non-static Bundles.
	 *
	 * @since 6.6.1
	 * @param  bool   $hide_button Whether to hide the Stripe Quick-pay button.
	 * @param  object $post      The product post object.
	 */
	public static function hide_stripe_quickpay( $hide_button, $post ) {

		global $product;

		// If the button is already hidden by some other plugin, respect that.
		if ( $hide_button ) {
			return $hide_button;
		}

		$the_product = $product && is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $post->ID );

		return ! WC_PB_Compatibility::supports_express_checkout_on_product_page( $the_product );
	}
}

WC_PB_Stripe_Compatibility::init();
