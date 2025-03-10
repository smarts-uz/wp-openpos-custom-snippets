<?php
/**
 * WC_PB_Stock_Manager_Item classes
 *
 * @package  WooCommerce Product Bundles
 * @since    4.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a product/variation in the collection to the item managing stock for it.
 * These 2 will differ only if stock for a variation is managed by its parent.
 *
 * @class    WC_PB_Stock_Manager_Item
 * @version  5.10.0
 * @since    4.8.7
 */
class WC_PB_Stock_Manager_Item {

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	public $product_id;
	/**
	 * Variation ID.
	 *
	 * @var int
	 */
	public $variation_id;
	/**
	 * Quantity.
	 *
	 * @var int
	 */
	public $quantity;
	/**
	 * Bundled item.
	 *
	 * @var WC_Product_Bundle_Item|false
	 */
	public $bundled_item;

	/**
	 * ID of the product managing stock for this item.
	 *
	 * @var int
	 */
	public $managed_by_id;

	/**
	 * Constructor.
	 *
	 * @param WC_Product|int                 $product Product object or ID.
	 * @param false|WC_Product_Variation|int $variation Variation object or ID.
	 * @param integer                        $quantity Quantity.
	 * @param array                          $args Additional arguments.
	 */
	public function __construct( $product, $variation = false, $quantity = 1, $args = array() ) {

		$this->product_id   = is_object( $product ) ? $product->get_id() : $product;
		$this->variation_id = is_object( $variation ) ? $variation->get_id() : $variation;
		$this->quantity     = $quantity;
		$this->bundled_item = isset( $args['bundled_item'] ) ? $args['bundled_item'] : false;
		$variation          = is_object( $variation ) ? $variation : wc_get_product( $variation );

		if ( $this->variation_id && $variation ) {
			$this->managed_by_id = $variation->get_stock_managed_by_id();
		} else {
			$this->managed_by_id = $this->product_id;
		}
	}
}
