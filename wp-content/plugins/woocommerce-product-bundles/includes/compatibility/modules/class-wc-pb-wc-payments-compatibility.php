<?php
/**
 * WC_PB_WC_Payments_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    6.13.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooPayments Integration.
 *
 * @version  7.1.2
 */
class WC_PB_WC_Payments_Compatibility {

	/**
	 * Hide quick-pay buttons in Bundle product pages.
	 */
	public static function init() {
		add_filter( 'wcpay_payment_request_is_product_supported', array( __CLASS__, 'handle_express_checkout_buttons' ), 10, 2 );
		add_filter( 'wcpay_woopay_button_is_product_supported', array( __CLASS__, 'handle_express_checkout_buttons' ), 10, 2 );
	}

	/**
	 * Hide quick-pay buttons in Bundle product pages.
	 *
	 * @param  bool       $is_supported - Whether the product is supported.
	 * @param  WC_Product $product  - Product object.
	 * @return bool
	 */
	public static function handle_express_checkout_buttons( $is_supported, $product ) {

		// If the express checkout button is not supported by some other plugin, respect that.
		if ( ! $is_supported ) {
			return $is_supported;
		}

		return WC_PB_Compatibility::supports_express_checkout_on_product_page( $product );
	}
}

WC_PB_WC_Payments_Compatibility::init();
