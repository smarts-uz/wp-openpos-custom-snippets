<?php
/**
 * WC_PB_Stock_Manager classes
 *
 * @package  WooCommerce Product Bundles
 * @since    4.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Used to create and store a product_id / variation_id representation of a product collection based on the included items' inventory requirements.
 *
 * @class    WC_PB_Stock_Manager
 * @version  6.19.0
 */
class WC_PB_Stock_Manager {

	/**
	 * Stock managed items.
	 *
	 * @var array
	 */
	private $items;

	/**
	 * Product managing the collection.
	 *
	 * @var WC_Product|false
	 */
	public $product;

	/**
	 * Contructor.
	 *
	 * @param boolean|WC_Product $product Product object.
	 */
	public function __construct( $product = false ) {
		$this->product = $product;
		$this->items   = array();
	}

	/**
	 * Add a product to the collection.
	 *
	 * @param  WC_Product|int                 $product Product object or ID.
	 * @param  false|WC_Product_Variation|int $variation Variation object or ID.
	 * @param  integer                        $quantity Quantity.
	 * @param  array                          $args    Additional arguments.
	 */
	public function add_item( $product, $variation = false, $quantity = 1, $args = array() ) {
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-stock-manager-item.php';

		$this->items[] = new WC_PB_Stock_Manager_Item( $product, $variation, $quantity, $args );
	}

	/**
	 * Return the items of this collection.
	 *
	 * @return array
	 */
	public function get_items() {

		if ( ! empty( $this->items ) ) {
			return $this->items;
		}

		return array();
	}

	/**
	 * Merge another collection with this one.
	 *
	 * @param  WC_PB_Stock_Manager $stock Stock manager object.
	 */
	public function add_stock( $stock ) {

		if ( ! is_object( $stock ) ) {
			return false;
		}

		$items_to_add = $stock->get_items();

		if ( ! empty( $items_to_add ) ) {
			foreach ( $items_to_add as $item ) {
				$this->items[] = $item;
			}
			return true;
		}

		return false;
	}

	/**
	 * Return the stock requirements of the items in this collection.
	 * To validate stock accurately, this method is used to add quantities and build a list of product/variation ids to check.
	 * Note that in some cases, stock for a variation might be managed by the parent - this is tracked by the managed_by_id property in WC_PB_Stock_Manager_Item.
	 *
	 * @return array
	 */
	public function get_managed_items() {

		$managed_items = array();

		if ( ! empty( $this->items ) ) {

			foreach ( $this->items as $purchased_item ) {

				$managed_by_id = $purchased_item->managed_by_id;

				if ( isset( $managed_items[ $managed_by_id ] ) ) {

					$managed_items[ $managed_by_id ]['quantity'] += $purchased_item->quantity;

					if ( $purchased_item->bundled_item ) {
						$managed_items[ $managed_by_id ]['is_secret'] = $managed_items[ $managed_by_id ]['is_secret'] && $purchased_item->bundled_item->is_secret();
						$managed_items[ $managed_by_id ]['title']     = $managed_items[ $managed_by_id ]['title'] !== $purchased_item->bundled_item->get_raw_title() ? $purchased_item->bundled_item->product->get_title() : $managed_items[ $managed_by_id ]['title'];
					}
				} else {

					$managed_items[ $managed_by_id ]['quantity']  = $purchased_item->quantity;
					$managed_items[ $managed_by_id ]['is_secret'] = false;
					$managed_items[ $managed_by_id ]['title']     = '';

					if ( $purchased_item->bundled_item ) {
						$managed_items[ $managed_by_id ]['is_secret'] = $purchased_item->bundled_item->is_secret();
						$managed_items[ $managed_by_id ]['title']     = $purchased_item->bundled_item->get_raw_title();
					}

					if ( $purchased_item->variation_id && $purchased_item->variation_id === $managed_by_id ) {
						$managed_items[ $managed_by_id ]['is_variation'] = true;
						$managed_items[ $managed_by_id ]['product_id']   = $purchased_item->product_id;
					} else {
						$managed_items[ $managed_by_id ]['is_variation'] = false;
					}
				}
			}
		}

		return $managed_items;
	}

	/**
	 * Product quantities already in cart.
	 *
	 * @since  5.8.0
	 *
	 * @return array
	 */
	private function get_quantities_in_cart() {

		$quantities_in_cart = WC()->cart->get_cart_item_quantities();

		// If we are updating a bundle in-cart, subtract the bundled item cart quantites that belong to the bundle being updated, since it's going to be removed later on.
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['update-bundle'] ) ) {

			//phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$updating_cart_key = wc_clean( $_POST['update-bundle'] );

			if ( isset( WC()->cart->cart_contents[ $updating_cart_key ] ) ) {

				$bundle_cart_item   = WC()->cart->cart_contents[ $updating_cart_key ];
				$bundled_cart_items = wc_pb_get_bundled_cart_items( $bundle_cart_item );

				if ( isset( $quantities_in_cart[ $bundle_cart_item['product_id'] ] ) ) {
					$quantities_in_cart[ $bundle_cart_item['product_id'] ] -= $bundle_cart_item['quantity'];
					// Unset if 0.
					if ( 0 === absint( $quantities_in_cart[ $bundle_cart_item['product_id'] ] ) ) {
						unset( $quantities_in_cart[ $bundle_cart_item['product_id'] ] );
					}
				}

				if ( ! empty( $bundled_cart_items ) ) {
					foreach ( $bundled_cart_items as $item_key => $item ) {

						$bundled_product_id = $item['data']->is_type( 'variation' ) && true === $item['data']->managing_stock() ? $item['variation_id'] : $item['product_id'];

						if ( isset( $quantities_in_cart[ $bundled_product_id ] ) ) {
							$quantities_in_cart[ $bundled_product_id ] -= $item['quantity'];
							// Unset if 0.
							if ( 0 === absint( $quantities_in_cart[ $bundled_product_id ] ) ) {
								unset( $quantities_in_cart[ $bundled_product_id ] );
							}
						}
					}
				}
			}
		}

		return $quantities_in_cart;
	}

	/**
	 * Validate that all managed items in the collection are in stock.
	 *
	 * @throws Exception If stock validation fails.
	 *
	 * @param  array $args Arguments.
	 * @return boolean
	 */
	public function validate_stock( $args = array() ) {

		$context         = isset( $args['context'] ) ? $args['context'] : 'add-to-cart';
		$throw_exception = isset( $args['throw_exception'] ) && $args['throw_exception'];

		$managed_items = $this->get_managed_items();

		if ( empty( $managed_items ) ) {
			return true;
		}

		$bundle_id    = $this->product->get_id();
		$bundle_title = $this->product->get_title();

		// Stock Validation.
		foreach ( $managed_items as $managed_item_id => $managed_item ) {

			try {

				$quantity = $managed_item['quantity'];

				// Get the product.
				$product_data = wc_get_product( $managed_item_id );

				if ( ! $product_data ) {
					continue;
				}

				$product_title = '' !== $managed_item['title'] ? $managed_item['title'] : $product_data->get_title();

				// Sanity check.
				if ( $product_data->is_sold_individually() && $quantity > 1 ) {

					/* translators: Product name */
					$reason = sprintf( __( 'Only 1 &quot;%s&quot; may be purchased.', 'woocommerce-product-bundles' ), $product_title );

					if ( 'add-to-cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$notice = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} else {
						$notice = $reason;
					}

					throw new Exception( $notice );
				}

				if ( false === $managed_item['is_secret'] && 'variation' === $product_data->get_type() ) {
					$product_title = WC_PB_Helpers::format_product_title( $product_title, '', wc_get_formatted_variation( $product_data, true, false ) );
				}

				// Stock check - only check if we're managing stock and backorders are not allowed.
				if ( ! $product_data->is_in_stock() ) {

					/* translators: Product name */
					$reason = sprintf( __( '&quot;%s&quot; is out of stock.', 'woocommerce-product-bundles' ), $product_title );

					if ( 'add-to-cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$notice = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} elseif ( 'cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$notice = sprintf( __( '&quot;%1$s&quot; cannot be purchased. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} else {
						$notice = $reason;
					}

					throw new Exception( $notice );

				} elseif ( ! $product_data->has_enough_stock( $quantity ) ) {

					/* translators: %1$s: Product name, %2$s: Remaining stock quantity */
					$reason = sprintf( __( 'There is not enough stock of &quot;%1$s&quot; (%2$s remaining).', 'woocommerce-product-bundles' ), $product_title, $product_data->get_stock_quantity() );

					if ( 'add-to-cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$notice = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} elseif ( 'cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$notice = sprintf( __( '&quot;%1$s&quot; cannot be purchased. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} else {
						$notice = $reason;
					}

					throw new Exception( $notice );
				}

				// Stock check - this time accounting for whats already in-cart.
				if ( $product_data->managing_stock() ) {

					$quantities_in_cart = $this->get_quantities_in_cart();

					if ( isset( $quantities_in_cart[ $managed_item_id ] ) && ! $product_data->has_enough_stock( $quantities_in_cart[ $managed_item_id ] + $quantity ) ) {

						/* translators: %1$s: Product name, %2$s: Remaining stock quantity, %2$s: Stock quantity in cart */
						$reason = sprintf( __( 'There is not enough stock of &quot;%1$s&quot; (%2$s in stock, %3$s in your cart).', 'woocommerce-product-bundles' ), $product_title, $product_data->get_stock_quantity(), $quantities_in_cart[ $managed_item_id ] );

						if ( 'add-to-cart' === $context ) {
							/* translators: %1$s: Product name, %2$s: Reason */
							$notice = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
						} elseif ( 'cart' === $context ) {
							/* translators: %1$s: Product name, %2$s: Reason */
							$notice = sprintf( __( '&quot;%1$s&quot; cannot be purchased. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
						} else {
							$notice = $reason;
						}

						$view_cart_string = __( 'View cart', 'woocommerce' );
						$button_class     = wc_pb_wp_theme_get_element_class_name( 'button' );
						$wp_button_class  = $button_class ? ' ' . $button_class : '';
						$error            = sprintf( '<a href="%s" class="button wc-forward%s">%s</a> %s', wc_get_cart_url(), $wp_button_class, $view_cart_string, $notice );

						throw new Exception( $error );
					}
				}
			} catch ( Exception $e ) {

				$error = $e->getMessage();

				if ( $managed_item['is_secret'] ) {

					$reason = __( 'The product is currently unavailable.', 'woocommerce-product-bundles' );

					if ( 'add-to-cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$error = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} elseif ( 'cart' === $context ) {
						/* translators: %1$s: Product name, %2$s: Reason */
						$error = sprintf( __( '&quot;%1$s&quot; cannot be purchased. %2$s', 'woocommerce-product-bundles' ), $bundle_title, $reason );
					} else {
						$error = $reason;
					}
				}

				if ( $throw_exception ) {
					throw new Exception( esc_html( $error ) );
				}

				return false;
			}
		}

		return true;
	}
}
