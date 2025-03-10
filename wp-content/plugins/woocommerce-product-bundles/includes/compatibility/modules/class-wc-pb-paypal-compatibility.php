<?php
/**
 * WC_PB_PayPal_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    7.1.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal Compatibility.
 *
 * @version 7.1.2
 */
class WC_PB_PayPal_Compatibility {

	/**
	 * Setup hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_paypal_payments_product_supports_payment_request_button', array( __CLASS__, 'handle_smart_buttons' ), 10, 2 );
	}

	/**
	 * Hide smart buttons in product pages when Bundle is not static.
	 *
	 * @param  bool       $is_supported Whether the smart button is supported.
	 * @param  WC_Product $product  The product.
	 *
	 * @return bool
	 */
	public static function handle_smart_buttons( $is_supported, $product ) {
		// If the smart button is not supported by some other plugin, respect that.
		if ( ! $is_supported ) {
			return $is_supported;
		}

		return WC_PB_Compatibility::supports_express_checkout_on_product_page( $product );
	}
}

WC_PB_PayPal_Compatibility::init();
