<?php
/**
 * Bundle quantity input template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/add-to-cart/bundle-quantity-input.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 6.0.0
 * @package WooCommerce Product Bundles
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

/** Core WC action. */
do_action( 'woocommerce_before_add_to_cart_quantity' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

if ( ! $product->is_sold_individually() ) {

	woocommerce_quantity_input(
		array(
			/** Core WC filter. */
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ), // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
			/** Core WC filter. */
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product ), // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
			'input_value' => ( isset( $_REQUEST['quantity'] ) ? wc_stock_amount( $_REQUEST['quantity'] ) : 1 ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
		)
	);

} else {
	?><input class="qty" type="hidden" name="quantity" value="1" />
	<?php
}
/** Core WC action. */
do_action( 'woocommerce_after_add_to_cart_quantity' );  // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
