<?php
/**
 * WC_PB_CP_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    4.14.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composite Products Compatibility.
 *
 * @version 8.1.0
 */
class WC_PB_CP_Compatibility {

	/**
	 * Context-setting Component.
	 *
	 * @var WC_CP_Component
	 */
	private static $current_component = false;

	/**
	 * Add hooks.
	 */
	public static function init() {
		/*
		 * Form Data.
		 */

		add_filter( 'woocommerce_rebuild_posted_composite_form_data', array( __CLASS__, 'rebuild_composited_bundle_form_data' ), 10, 3 );
		add_filter( 'woocommerce_posted_composite_configuration', array( __CLASS__, 'get_composited_bundle_configuration' ), 10, 3 );

		/*
		 * Prices.
		 */

		add_filter( 'woocommerce_get_composited_product_price', array( __CLASS__, 'composited_bundle_price' ), 10, 3 );

		// Create composite context for bundled cart items - 'filters' method implementation.
		if ( 'filters' === WC_PB_Product_Prices::get_bundled_cart_item_discount_method() ) {
			add_filter( 'woocommerce_bundled_cart_item', array( __CLASS__, 'bundled_cart_item_reference' ) );
		}

		add_filter( 'woocommerce_bundles_update_price_meta', array( __CLASS__, 'bundles_update_price_meta' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_discount', array( __CLASS__, 'bundled_item_discount' ), 10, 3 );

		/*
		 * Shipping.
		 */

		// Inheritance.
		add_filter( 'woocommerce_bundled_item_is_priced_individually', array( __CLASS__, 'bundled_item_is_priced_individually' ), 10, 2 );
		add_filter( 'woocommerce_bundle_contains_shipped_items', array( __CLASS__, 'bundle_contains_shipped_items' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_is_shipped_individually', array( __CLASS__, 'bundled_item_is_shipped_individually' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_has_bundled_weight', array( __CLASS__, 'bundled_item_has_bundled_weight' ), 10, 4 );

		// Value & weight aggregation in packages.
		add_filter( 'woocommerce_bundle_container_cart_item', array( __CLASS__, 'composited_bundle_container_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_composited_package_item', array( __CLASS__, 'composited_bundle_container_package_item' ), 10, 3 );

		/*
		 * Templates.
		 */

		// Composited Bundle template.
		add_action( 'woocommerce_composited_product_bundle', array( __CLASS__, 'composited_product_bundle' ), 10 );

		/*
		 * Cart and Orders.
		 */

		// Extend PB group modes to support group modes of composited bundles.
		add_filter( 'woocommerce_bundles_group_mode_options_data', array( __CLASS__, 'composited_group_mode_options_data' ) );
		add_filter( 'woocommerce_bundle_container_cart_item', array( __CLASS__, 'composited_bundle_group_mode' ), 0, 3 );

		// Inherit component discounts - 'props' method implementation.
		if ( 'props' === WC_PB_Product_Prices::get_bundled_cart_item_discount_method() ) {
			add_action( 'woocommerce_bundles_before_set_bundled_cart_item', array( __CLASS__, 'bundled_cart_item_before_price_modification' ) );
			add_action( 'woocommerce_bundles_after_set_bundled_cart_item', array( __CLASS__, 'bundled_cart_item_after_price_modification' ) );
		}

		// Validate bundle type component selections.
		add_action( 'woocommerce_composite_component_validation_add_to_cart', array( __CLASS__, 'validate_component_configuration' ), 10, 8 );
		add_action( 'woocommerce_composite_component_validation_add_to_order', array( __CLASS__, 'validate_component_configuration' ), 10, 8 );

		// Apply component prefix to bundle input fields.
		add_filter( 'woocommerce_product_bundle_field_prefix', array( __CLASS__, 'bundle_field_prefix' ), 10, 2 );

		// Hook into composited product add-to-cart action to add bundled items since 'woocommerce-add-to-cart' action cannot be used recursively.
		add_action( 'woocommerce_composited_add_to_cart', array( __CLASS__, 'add_bundle_to_cart' ), 10, 6 );

		// Link bundled cart/order items with composite.
		add_filter( 'woocommerce_cart_item_is_child_of_composite', array( __CLASS__, 'bundled_cart_item_is_child_of_composite' ), 10, 5 );
		add_filter( 'woocommerce_order_item_is_child_of_composite', array( __CLASS__, 'bundled_order_item_is_child_of_composite' ), 10, 4 );

		// Tweak the appearance of bundle container items in various templates.
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'composited_bundle_in_cart_item_title' ), 9, 3 );
		add_filter( 'woocommerce_composite_container_cart_item_data_value', array( __CLASS__, 'composited_bundle_cart_item_data_value' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'composited_bundle_in_cart_item_quantity' ), 11, 2 );
		add_filter( 'woocommerce_composited_cart_item_quantity_html', array( __CLASS__, 'composited_bundle_checkout_item_quantity' ), 10, 2 );
		add_filter( 'woocommerce_order_item_visible', array( __CLASS__, 'composited_bundle_order_item_visible' ), 10, 2 );
		add_filter( 'woocommerce_order_item_name', array( __CLASS__, 'composited_bundle_order_table_item_title' ), 9, 2 );
		add_filter( 'woocommerce_component_order_item_meta_description', array( __CLASS__, 'composited_bundle_order_item_description' ), 10, 3 );
		add_filter( 'woocommerce_composited_order_item_quantity_html', array( __CLASS__, 'composited_bundle_order_table_item_quantity' ), 11, 2 );

		// Disable edit-in-cart feature if part of a composite.
		add_filter( 'woocommerce_bundle_is_editable_in_cart', array( __CLASS__, 'composited_bundle_not_editable_in_cart' ), 10, 3 );

		// Use custom callback to add bundles to orders in 'WC_CP_Order::add_composite_to_order'.
		add_filter( 'woocommerce_add_component_to_order_callback', array( __CLASS__, 'add_composited_bundle_to_order_callback' ), 10, 6 );
		add_action( 'woocommerce_bundle_cart_stamp_changed', array( __CLASS__, 'sync_bundle_cart_stamp' ) );

		/*
		 * REST API.
		 */

		add_filter( 'woocommerce_parsed_rest_composite_order_item_configuration', array( __CLASS__, 'parse_composited_rest_bundle_configuration' ), 10, 3 );

		/*
		 * Store API.
		 */

		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'filter_store_api_cart_item_data' ), 20, 3 );
		add_filter( 'woocommerce_hydration_request_after_callbacks', array( __CLASS__, 'filter_store_api_cart_item_data' ), 20, 3 );

		/*
		 * Analytics.
		 */

		if ( version_compare( WC_PB()->plugin_version( true, WC_CP()->version ), '8.3.0' ) >= 0 ) {
			add_filter( 'woocommerce_analytics_clauses_from_composites_stats_total', array( __CLASS__, 'setup_analytics_base_table' ) );
			add_filter( 'woocommerce_analytics_clauses_from_composites_stats_interval', array( __CLASS__, 'setup_analytics_base_table' ) );
			add_filter( 'woocommerce_analytics_clauses_from_composites_subquery', array( __CLASS__, 'setup_analytics_base_table' ) );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Permalink Args.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add form data for composited bundles to support cart-item editing and order-item editing in CP.
	 *
	 * @since  5.8.0
	 *
	 * @param  array $form_data Form data.
	 * @param  array $configuration Configuration.
	 * @return array
	 */
	public static function rebuild_composited_bundle_form_data( $form_data, $configuration ) {

		if ( ! empty( $configuration ) && is_array( $configuration ) ) {
			foreach ( $configuration as $component_id => $component_configuration ) {

				if ( isset( $component_configuration['type'] ) && 'bundle' === $component_configuration['type'] && ! empty( $component_configuration['stamp'] ) && is_array( $component_configuration['stamp'] ) ) {

					$bundle_args = WC_PB()->cart->rebuild_posted_bundle_form_data( $component_configuration['stamp'] );

					foreach ( $bundle_args as $key => $value ) {
						$form_data[ 'component_' . $component_id . '_' . $key ] = $value;
					}
				}
			}
		}

		return $form_data;
	}

	/**
	 * Get posted data for composited bundles.
	 *
	 * @since  5.8.0
	 *
	 * @param  array                $configuration Configuration.
	 * @param  WC_Product_Composite $composite Composite product.
	 * @return array
	 */
	public static function get_composited_bundle_configuration( $configuration, $composite ) {

		if ( empty( $configuration ) || ! is_array( $configuration ) ) {
			return $configuration;
		}

		foreach ( $configuration as $component_id => $component_configuration ) {

			if ( empty( $component_configuration['product_id'] ) ) {
				continue;
			}

			$component_option = $composite->get_component_option( $component_id, $component_configuration['product_id'] );

			if ( ! $component_option ) {
				continue;
			}

			$composited_product = $component_option->get_product();

			if ( ! $composited_product->is_type( 'bundle' ) ) {
				continue;
			}

			WC_PB_Compatibility::$bundle_prefix = $component_id;

			$configuration[ $component_id ]['stamp'] = WC_PB()->cart->get_posted_bundle_configuration( $composited_product );

			if ( doing_filter( 'woocommerce_add_cart_item_data' ) ) {
				foreach ( $configuration[ $component_id ]['stamp'] as $bundled_item_id => $bundled_item_configuration ) {
					/**
					 * Filter the cart item identifier for a bundled item.
					 *
					 * @since 6.0.0
					 * @param array $bundled_item_configuration Bundled item configuration.
					 * @param int   $bundled_item_id Bundled item ID.
					 * @param int   $composited_product_id Composited product ID.
					 */
					$configuration[ $component_id ]['stamp'][ $bundled_item_id ] = apply_filters( 'woocommerce_bundled_item_cart_item_identifier', $bundled_item_configuration, $bundled_item_id, $composited_product->get_id() );
				}
			}

			WC_PB_Compatibility::$bundle_prefix = '';
		}

		return $configuration;
	}

	/*
	|--------------------------------------------------------------------------
	| Prices.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Composited bundle price.
	 *
	 * @param  double        $price Price.
	 * @param  array         $args Args.
	 * @param  WC_CP_Product $composited_product Composited product.
	 * @return double
	 */
	public static function composited_bundle_price( $price, $args, $composited_product ) {

		$product = $composited_product->get_product();

		if ( 'bundle' === $product->get_type() ) {

			$composited_product->add_filters();

			$price = $product->calculate_price( $args );

			if ( '' === $price ) {
				if ( $product->contains( 'priced_individually' ) && isset( $args['min_or_max'] ) && 'max' === $args['min_or_max'] && INF === $product->get_max_raw_price() ) {
					$price = INF;
				} else {
					$price = 0.0;
				}
			}

			$composited_product->remove_filters();
		}

		return $price;
	}

	/**
	 * Create component reference to aggregate discount of component into bundled item - 'filters' method implementation.
	 *
	 * @see bundled_item_discount
	 *
	 * @param  array $cart_item Cart item.
	 * @return array
	 */
	public static function bundled_cart_item_reference( $cart_item ) {

		if ( ! is_null( WC_PB()->product_data->get( $cart_item['data'], 'bundled_cart_item' ) ) ) {

			$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
			if ( $bundle_container_item ) {
				$composite_container_item = wc_cp_get_composited_cart_item_container( $bundle_container_item );
				if ( $composite_container_item ) {

					$bundle           = $bundle_container_item['data'];
					$composite        = $composite_container_item['data'];
					$component_id     = $bundle_container_item['composite_item'];
					$component_option = $composite->get_component_option( $component_id, $bundle->get_id() );

					if ( $component_option ) {
						$bundled_cart_item                       = WC_PB()->product_data->get( $cart_item['data'], 'bundled_cart_item' );
						$bundled_cart_item->composited_cart_item = $component_option;
						WC_PB()->product_data->set( $cart_item['data'], 'bundled_cart_item', $bundled_cart_item );
					}
				}
			}
		}

		return $cart_item;
	}

	/**
	 * Filters 'woocommerce_bundled_item_discount' to include component + bundled item discounts.
	 *
	 * @param  mixed           $bundled_discount Discount.
	 * @param  WC_Bundled_Item $bundled_item Bundled item.
	 * @param  string          $context Context.
	 * @return mixed
	 */
	public static function bundled_item_discount( $bundled_discount, $bundled_item, $context ) {

		if ( 'cart' !== $context ) {
			return $bundled_discount;
		}

		$component_option = false;
		$bundle           = $bundled_item->get_bundle();

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( isset( $bundled_item->composited_cart_item ) ) {
			$component_option = $bundled_item->composited_cart_item;
		} elseif ( $bundle && class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option && ( $component_option instanceof WC_CP_Product ) ) {

			$discount = $component_option->get_discount();

			if ( ! $bundled_discount ) {
				return $discount;
			}

			// If discount is allowed on the component sale price use both the component + bundled item discount. Else, use the component discount.
			if ( $component_option->is_discount_allowed_on_sale_price() ) {

				// If component discount is set use both component + bundled item discount. Else, use only the bundled item discount.
				if ( $discount ) {
					$bundled_discount = $discount + $bundled_discount - ( $bundled_discount * $discount ) / 100;
				}
			} elseif ( $discount ) {

					$bundled_discount = $discount;
			}
		}

		return $bundled_discount;
	}

	/**
	 * Component discounts should not trigger bundle price updates.
	 *
	 * @param  boolean           $update Update.
	 * @param  WC_Product_Bundle $bundle Bundle.
	 * @return boolean
	 */
	public static function bundles_update_price_meta( $update, $bundle ) {

		$component_option = false;

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			$update = false;
		}

		return $update;
	}

	/**
	 * If a component is not priced individually, this should force bundled items to return a zero price.
	 *
	 * @since  6.2.0
	 *
	 * @param  boolean         $is_priced_individually Is priced individually.
	 * @param  WC_Bundled_Item $bundled_item Bundled item.
	 * @return boolean
	 */
	public static function bundled_item_is_priced_individually( $is_priced_individually, $bundled_item ) {

		$component_option = false;
		$bundle           = $bundled_item->get_bundle();

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( isset( $bundled_item->composited_cart_item ) ) {
			$component_option = $bundled_item->composited_cart_item;
		} elseif ( $bundle && class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			if ( ! $component_option->is_priced_individually() ) {
				$is_priced_individually = false;
			}
		}

		return $is_priced_individually;
	}

	/**
	 * If a component is not priced individually, this should force bundled items to return a zero price.
	 *
	 * @since  6.2.0
	 *
	 * @param  boolean           $contains Contains.
	 * @param  WC_Product_Bundle $bundle Bundle.
	 * @return boolean
	 */
	public static function bundle_contains_priced_items( $contains, $bundle ) {

		$component_option = false;

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			if ( ! $component_option->is_priced_individually() ) {
				$contains = false;
			}
		}

		return $contains;
	}

	/**
	 * If a component is not shipped individually, this should force bundled items to comply.
	 *
	 * @since  6.2.0
	 *
	 * @param  boolean         $is_shipped_individually Is shipped individually.
	 * @param  WC_Bundled_Item $bundled_item Bundled item.
	 * @return boolean
	 */
	public static function bundled_item_is_shipped_individually( $is_shipped_individually, $bundled_item ) {

		$component_option = false;
		$bundle           = $bundled_item->get_bundle();

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( isset( $bundled_item->composited_cart_item ) ) {
			$component_option = $bundled_item->composited_cart_item;
		} elseif ( $bundle && class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			if ( ! $component_option->is_shipped_individually() ) {
				$is_shipped_individually = false;
			}
		}

		return $is_shipped_individually;
	}

	/**
	 * If a component is not shipped individually, this should force bundled items to comply.
	 *
	 * @since  6.2.0
	 *
	 * @param  boolean           $has Has bundled weight.
	 * @param  WC_Product        $bundled_cart_item Bundled product.
	 * @param  int               $bundled_item_id Bundled item ID.
	 * @param  WC_Product_Bundle $bundle Bundle.
	 * @return boolean
	 */
	public static function bundled_item_has_bundled_weight( $has, $bundled_cart_item, $bundled_item_id, $bundle ) {

		$component_option = false;

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			if ( ! $component_option->is_shipped_individually() && ! $component_option->is_weight_aggregated() ) {
				$has = false;
			}
		}

		return $has;
	}

	/**
	 * If a component is not shipped individually, this should force bundled items to comply.
	 *
	 * @since  6.2.0
	 *
	 * @param  boolean           $contains Contains shipped items.
	 * @param  WC_Product_Bundle $bundle Bundle.
	 * @return boolean
	 */
	public static function bundle_contains_shipped_items( $contains, $bundle ) {

		$component_option = false;

		if ( is_callable( array( 'WC_CP_Products', 'get_filtered_component_option' ) ) && WC_CP_Products::get_filtered_component_option() ) {
			$component_option = WC_CP_Products::get_filtered_component_option();
		} elseif ( class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $bundle, 'composited_cart_item' ) ) ) {
			$component_option = WC_CP()->product_data->get( $bundle, 'composited_cart_item' );
		}

		if ( $component_option ) {
			if ( ! $component_option->is_shipped_individually() ) {
				$contains = false;
			}
		}

		return $contains;
	}

	/*
	|--------------------------------------------------------------------------
	| Templates.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Hook into 'woocommerce_composited_product_bundle' to show bundle type product content.
	 *
	 * @since  5.10.0
	 *
	 * @param  WC_CP_Product $component_option Component option.
	 * @return void|boolean
	 */
	public static function composited_product_bundle( $component_option ) {

		$product = $component_option->get_product();

		if ( $product->contains( 'subscriptions' ) ) {

			?><div class="woocommerce-error">
			<?php
				echo esc_html__( 'This item cannot be purchased at the moment.', 'woocommerce-product-bundles' );
			?>
			</div>
			<?php

			return false;
		}

		if ( class_exists( 'WC_CP_Admin_Ajax' ) && WC_CP_Admin_Ajax::is_composite_edit_request() ) {
			$product->set_layout( 'tabular' );
		}

		$product_id   = $product->get_id();
		$component    = $component_option->get_component();
		$component_id = $component_option->get_component_id();
		$composite    = $component_option->get_composite();
		$composite_id = $component_option->get_composite_id();

		WC_PB_Compatibility::$compat_product = $product;
		WC_PB_Compatibility::$bundle_prefix  = $component_id;

		$quantity_min = $component_option->get_quantity_min();
		$quantity_max = $component_option->get_quantity_max( true );

		$form_classes = array();

		if ( ! $product->is_in_stock() ) {
			$form_classes[] = 'bundle_out_of_stock';
		}

		if ( 'outofstock' === $product->get_bundled_items_stock_status() ) {
			$form_classes[] = 'bundle_insufficient_stock';
		}

		$form_data = $product->get_bundle_form_data();

		wc_get_template(
			'composited-product/bundle-product.php',
			array(
				'product'            => $product,
				'quantity_min'       => $quantity_min,
				'quantity_max'       => $quantity_max,
				'bundle_form_data'   => $form_data,
				'bundled_items'      => $product->get_bundled_items(),
				'component_id'       => $component_id,
				'composited_product' => $component_option,
				'composite_product'  => $composite,
				'classes'            => implode( ' ', $form_classes ),
				// Back-compat.
				'product_id'         => $product_id,
				'bundle_price_data'  => $form_data,
			),
			false,
			WC_PB()->plugin_path() . '/templates/'
		);

		WC_PB_Compatibility::$compat_product = '';
		WC_PB_Compatibility::$bundle_prefix  = '';
	}

	/*
	|--------------------------------------------------------------------------
	| Cart and Orders.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Modify group mode of composited bundles.
	 *
	 * @since  6.14.0
	 *
	 * @param  array $cart_item Cart item.
	 * @return array
	 */
	public static function composited_bundle_group_mode( $cart_item ) {

		if ( $cart_item['data']->is_type( 'bundle' ) && wc_cp_is_composited_cart_item( $cart_item ) ) {

			if ( 'none' === $cart_item['data']->get_group_mode() ) {
				$cart_item['data']->set_group_mode( 'none_composited' );
			} elseif ( 'noindent' === $cart_item['data']->get_group_mode() ) {
				$cart_item['data']->set_group_mode( 'flat_composited' );
			} else {
				$cart_item['data']->set_group_mode( 'composited' );
			}
		}

		return $cart_item;
	}

	/**
	 * Add hidden Group Modes for composited bundles.
	 *
	 * @param  array $group_mode_data Group mode data.
	 * @return array
	 */
	public static function composited_group_mode_options_data( $group_mode_data ) {

		$group_mode_data['none_composited'] = array(
			'title'      => __( 'Composited None', 'woocommerce-composite-products' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			'features'   => array( 'parent_item', 'child_item_indent', 'aggregated_subtotals', 'component_multiselect' ),
			'is_visible' => false,
		);

		$group_mode_data['flat_composited'] = array(
			'title'      => __( 'Composited Flat', 'woocommerce-composite-products' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			'features'   => array( 'parent_item', 'child_item_indent', 'child_item_meta', 'parent_cart_widget_item_meta' ),
			'is_visible' => false,
		);

		$group_mode_data['composited'] = array(
			'title'      => __( 'Composited Grouped', 'woocommerce-composite-products' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			'features'   => array( 'parent_item', 'child_item_indent', 'aggregated_subtotals', 'parent_cart_widget_item_meta' ),
			'is_visible' => false,
		);

		return $group_mode_data;
	}

	/**
	 * Add filters to modify bundled product prices when parent product is composited and has a discount.
	 *
	 * @param  array $cart_item Cart item.
	 * @return array
	 */
	public static function bundled_cart_item_before_price_modification( $cart_item ) {
		$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
		if ( $bundle_container_item ) {
			$composite_container_item = wc_cp_get_composited_cart_item_container( $bundle_container_item );
			if ( $composite_container_item ) {

				$bundle           = $bundle_container_item['data'];
				$composite        = $composite_container_item['data'];
				$component_id     = $bundle_container_item['composite_item'];
				$component_option = $composite->get_component_option( $component_id, $bundle->get_id() );

				if ( $component_option ) {
					$component_option->add_filters();
				}
			}
		}

		return $cart_item;
	}

	/**
	 * Remove filters that modify bundled product prices when the parent product is composited and has a discount.
	 *
	 * @param  string $cart_item Cart item.
	 * @return array
	 */
	public static function bundled_cart_item_after_price_modification( $cart_item ) {
		$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
		if ( $bundle_container_item ) {
			$composite_container_item = wc_cp_get_composited_cart_item_container( $bundle_container_item );
			if ( $composite_container_item ) {

				$bundle           = $bundle_container_item['data'];
				$composite        = $composite_container_item['data'];
				$component_id     = $bundle_container_item['composite_item'];
				$component_option = $composite->get_component_option( $component_id, $bundle->get_id() );

				if ( $component_option ) {
					$component_option->remove_filters();
				}
			}
		}

		return $cart_item;
	}

	/**
	 * Hook into 'woocommerce_composite_component_add_to_cart_validation' to validate composited bundles.
	 *
	 * @throws Exception If the composited bundle cannot be purchased.
	 *
	 * @param  WC_CP_Component $component Component.
	 * @param  array           $component_validation_data Component validation data.
	 * @param  int             $composite_quantity Composite quantity.
	 * @param  array           $configuration Configuration.
	 * @param  string          $context Context.
	 * @return void
	 */
	public static function validate_component_configuration( $component, $component_validation_data, $composite_quantity, $configuration, $context ) {

		$component_id     = $component->get_id();
		$component_option = $component->get_option( $component_validation_data['product_id'] );

		if ( ! $component_option ) {
			return;
		}

		$composited_product = $component_option->get_product();

		if ( ! $composited_product || ! $composited_product->is_type( 'bundle' ) ) {
			return;
		}

		// Disallow bundles with subscriptions.
		if ( $composited_product->contains( 'subscriptions' ) ) {

			/* translators: %s: Product title */
			$reason = sprintf( __( '&quot;%s&quot; cannot be purchased.', 'woocommerce-composite-products' ), $composited_product->get_title() ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch

			if ( 'add-to-cart' === $context ) {
				/* translators: %1$s: Composite title, %2$s: Reason */
				$notice = sprintf( __( '&quot;%1$s&quot; cannot be added to your cart. %2$s', 'woocommerce-composite-products' ), $component->get_composite()->get_title(), $reason ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			} elseif ( 'cart' === $context ) {
				/* translators: %1$s: Composite title, %2$s: Reason */
				$notice = sprintf( __( '&quot;%1$s&quot; cannot be purchased. %2$s', 'woocommerce-composite-products' ), $component->get_composite()->get_title(), $reason ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			} else {
				$notice = $reason;
			}

			throw new Exception( esc_html( $notice ) );
		}

		if ( ! isset( $component_validation_data['quantity'] ) || ! $component_validation_data['quantity'] > 0 ) {
			return;
		}

		$bundle_quantity      = $component_validation_data['quantity'];
		$bundle_configuration = array();

		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_data = $_POST;

		//phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		if ( empty( $_POST['add-to-cart'] ) && ! empty( $_GET['add-to-cart'] ) ) {
			//phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			$posted_data = $_GET;
		}

		if ( isset( $posted_data['quantity'] ) ) {
			$bundle_quantity = $bundle_quantity * $posted_data['quantity'];
		}

		WC_PB_Compatibility::$bundle_prefix = $component_id;

		if ( isset( $configuration[ $component_id ]['stamp'] ) ) {
			$bundle_configuration = $configuration[ $component_id ]['stamp'];
		} else {
			$bundle_configuration = WC_PB()->cart->get_posted_bundle_configuration( $composited_product );
		}

		add_filter( 'woocommerce_add_error', array( __CLASS__, 'component_bundle_error_message_context' ) );
		self::$current_component = $component;

		$is_valid = WC_PB()->cart->validate_bundle_configuration( $composited_product, $bundle_quantity, $bundle_configuration, $context );

		remove_filter( 'woocommerce_add_error', array( __CLASS__, 'component_bundle_error_message_context' ) );
		self::$current_component = false;

		WC_PB_Compatibility::$bundle_prefix = '';

		if ( ! $is_valid ) {
			throw new Exception();
		}
	}

	/**
	 * Sets a prefix for unique bundles.
	 *
	 * @param  string $prefix Prefix.
	 * @param  int    $product_id Product ID.
	 * @return string
	 */
	public static function bundle_field_prefix( $prefix, $product_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( ! empty( WC_PB_Compatibility::$bundle_prefix ) ) {
			$prefix = 'component_' . WC_PB_Compatibility::$bundle_prefix . '_';
		}

		return $prefix;
	}

	/**
	 * Hook into 'woocommerce_composited_add_to_cart' to trigger 'WC_PB()->cart->bundle_add_to_cart()'.
	 *
	 * @param  string $cart_item_key Cart item key.
	 * @param  int    $product_id Product ID.
	 * @param  int    $quantity Quantity.
	 * @param  int    $variation_id Variation ID.
	 * @param  array  $variation Variation.
	 * @param  array  $cart_item_data Cart item data.
	 */
	public static function add_bundle_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		WC_PB()->cart->bundle_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Used to link bundled cart items with the composite container product.
	 *
	 * @param  boolean $is_child Is child.
	 * @param  string  $cart_item_key Cart item key.
	 * @param  array   $cart_item_data Cart item data.
	 * @param  string  $composite_key Composite key.
	 * @param  array   $composite_data Composite data.
	 * @return boolean
	 */
	public static function bundled_cart_item_is_child_of_composite( $is_child, $cart_item_key, $cart_item_data, $composite_key, $composite_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$parent = wc_pb_get_bundled_cart_item_container( $cart_item_data );
		if ( $parent ) {
			if ( isset( $parent['composite_parent'] ) && $parent['composite_parent'] === $composite_key ) {
				$is_child = true;
			}
		}

		return $is_child;
	}

	/**
	 * Used to link bundled order items with the composite container product.
	 *
	 * @param  boolean  $is_child Is child.
	 * @param  array    $order_item Order item.
	 * @param  array    $composite_item Composite item.
	 * @param  WC_Order $order Order.
	 * @return boolean
	 */
	public static function bundled_order_item_is_child_of_composite( $is_child, $order_item, $composite_item, $order ) {
		$parent = wc_pb_get_bundled_order_item_container( $order_item, $order );
		if ( $parent ) {
			if ( isset( $parent['composite_parent'] ) && $parent['composite_parent'] === $composite_item['composite_cart_key'] ) {
				$is_child = true;
			}
		}

		return $is_child;
	}

	/**
	 * Edit composited bundle container cart title.
	 *
	 * @param  string $content Title.
	 * @param  array  $cart_item Cart item.
	 * @param  string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function composited_bundle_in_cart_item_title( $content, $cart_item, $cart_item_key ) {

		if ( wc_pb_is_bundle_container_cart_item( $cart_item ) && wc_cp_is_composited_cart_item( $cart_item ) ) {

			$hide_title = WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' );

			/**
			 * 'woocommerce_composited_bundle_container_cart_item_hide_title' filter.
			 *
			 * @since 6.0.0
			 * @param  boolean  $hide_title
			 * @param  array    $cart_item
			 * @param  string   $cart_item_key
			 */
			$hide_title = apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_title', $hide_title, $cart_item, $cart_item_key );

			if ( $hide_title ) {

				$bundled_cart_items = wc_pb_get_bundled_cart_items( $cart_item );

				if ( empty( $bundled_cart_items ) ) {
					$content = __( 'No selection', 'woocommerce-product-bundles' );
				} else {
					$content = '';
				}
			}
		}

		return $content;
	}

	/**
	 * Append bundled item data to composited bundle metadata.
	 *
	 * @param  string $title Title.
	 * @param  array  $cart_item Cart item.
	 * @param  string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function composited_bundle_cart_item_data_value( $title, $cart_item, $cart_item_key ) {

		if ( wc_pb_is_bundle_container_cart_item( $cart_item ) && $cart_item['data']->is_type( 'bundle' ) ) {

			/**
			 * 'woocommerce_composited_bundle_container_cart_item_hide_title' filter.
			 *
			 * @since 6.0.0
			 * @param  boolean  $hide_title
			 * @param  array    $cart_item
			 * @param  string   $cart_item_key
			 */
			if ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_title', WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' ), $cart_item, $cart_item_key ) ) {

				$bundled_cart_items = wc_pb_get_bundled_cart_items( $cart_item );

				if ( empty( $bundled_cart_items ) ) {

					$title = __( 'No selection', 'woocommerce-product-bundles' );

				} else {

					$bundle_meta = WC_PB()->display->get_bundle_container_cart_item_data( $cart_item, array( 'aggregated' => false ) );
					$title       = implode( ', ', wp_list_pluck( $bundle_meta, 'value' ) );
				}
			} elseif ( WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'parent_cart_widget_item_meta' ) ) {

				$bundle_meta = WC_PB()->display->get_bundle_container_cart_item_data( $cart_item, array( 'aggregated' => false ) );

				$title .= ' &ndash; ' . implode( ', ', wp_list_pluck( $bundle_meta, 'value' ) );
			}
		}

		return $title;
	}

	/**
	 * Aggregate value and weight of bundled items in shipping packages when an unassembled bundle is composited.
	 *
	 * @param  array      $cart_item Cart item.
	 * @param WC_Product $bundle Bundle.
	 * @return array
	 */
	public static function composited_bundle_container_cart_item( $cart_item, $bundle ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$container_cart_item = wc_cp_get_composited_cart_item_container( $cart_item );
		if ( $container_cart_item ) {

			$component_id     = $cart_item['composite_item'];
			$component_option = $container_cart_item['data']->get_component_option( $component_id, $cart_item['product_id'] );

			if ( ! $component_option ) {
				return $cart_item;
			}

			$composited_value = is_callable( array( 'WC_CP_Products', 'get_composited_cart_item_discount_method' ) ) && 'props' === WC_CP_Products::get_composited_cart_item_discount_method() ? $cart_item['data']->get_price( 'edit' ) : $component_option->get_raw_price( $cart_item['data'], 'cart' );

			WC_CP()->product_data->set( $cart_item['data'], 'composited_value', $composited_value );

			// If the bundle doesn't need shipping at this point, it means it's unassembled.
			if ( false === $cart_item['data']->needs_shipping() ) {
				if ( false === $component_option->is_shipped_individually() ) {
					WC_CP()->product_data->set( $cart_item['data'], 'composited_weight', 0.0 );

					$cart_item['data']->set_aggregate_weight( 'yes' );
				}
			}
		}

		return $cart_item;
	}

	/**
	 * Aggregate value and weight of bundled items in shipping packages when a bundle is composited in an assembled composite.
	 *
	 * @param  array  $cart_item Cart item.
	 * @param  string $cart_item_key Cart item key.
	 * @param  string $container_cart_item_key Container cart item key.
	 * @return array
	 */
	public static function composited_bundle_container_package_item( $cart_item, $cart_item_key, $container_cart_item_key ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// If this isn't an assembled Composite, get out.
		if ( is_null( WC_CP()->product_data->get( $cart_item['data'], 'composited_value' ) ) ) {
			return $cart_item;
		}

		if ( wc_pb_is_bundle_container_cart_item( $cart_item ) ) {

			$composited_bundle_value  = class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $cart_item['data'], 'composited_value' ) ) ? WC_CP()->product_data->get( $cart_item['data'], 'composited_value' ) : 0.0;
			$composited_bundle_weight = class_exists( 'WC_PB_CP_Compatibility' ) && ! is_null( WC_CP()->product_data->get( $cart_item['data'], 'composited_weight' ) ) ? WC_CP()->product_data->get( $cart_item['data'], 'composited_weight' ) : 0.0;

			// Not entirely sure what is happening here, but we don't want to change it for now, so instead we'll ignore the rule.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$bundle     = unserialize( serialize( $cart_item['data'] ) );
			$bundle_qty = $cart_item['quantity'];

			// Aggregate weights and prices.

			$bundled_weight = 0.0;
			$bundled_value  = 0.0;
			$bundle_totals  = array(
				'line_subtotal'     => $cart_item['line_subtotal'],
				'line_total'        => $cart_item['line_total'],
				'line_subtotal_tax' => $cart_item['line_subtotal_tax'],
				'line_tax'          => $cart_item['line_tax'],
				'line_tax_data'     => $cart_item['line_tax_data'],
			);

			foreach ( wc_pb_get_bundled_cart_items( $cart_item, WC()->cart->cart_contents, true ) as $child_item_key ) {

				$child_cart_item_data   = WC()->cart->cart_contents[ $child_item_key ];
				$bundled_product        = $child_cart_item_data['data'];
				$bundled_product_qty    = $child_cart_item_data['quantity'];
				$bundled_product_value  = ! is_null( WC_PB()->product_data->get( $bundled_product, 'bundled_value' ) ) ? WC_PB()->product_data->get( $bundled_product, 'bundled_value' ) : 0.0;
				$bundled_product_weight = ! is_null( WC_PB()->product_data->get( $bundled_product, 'bundled_weight' ) ) ? WC_PB()->product_data->get( $bundled_product, 'bundled_weight' ) : 0.0;

				// Aggregate price.
				if ( $bundled_product_value ) {

					$bundled_value += $bundled_product_value * $bundled_product_qty;

					$bundle_totals['line_subtotal']     += $child_cart_item_data['line_subtotal'];
					$bundle_totals['line_total']        += $child_cart_item_data['line_total'];
					$bundle_totals['line_subtotal_tax'] += $child_cart_item_data['line_subtotal_tax'];
					$bundle_totals['line_tax']          += $child_cart_item_data['line_tax'];

					$child_item_line_tax_data = $child_cart_item_data['line_tax_data'];

					$bundle_totals['line_tax_data']['total']    = array_merge( $bundle_totals['line_tax_data']['total'], $child_item_line_tax_data['total'] );
					$bundle_totals['line_tax_data']['subtotal'] = array_merge( $bundle_totals['line_tax_data']['subtotal'], $child_item_line_tax_data['subtotal'] );
				}

				// Aggregate weight.
				if ( $bundled_product_weight ) {
					$bundled_weight += $bundled_product_weight * $bundled_product_qty;
				}
			}

			$cart_item = array_merge( $cart_item, $bundle_totals );

			WC_CP()->product_data->set( $bundle, 'composited_value', (float) $composited_bundle_value + $bundled_value / $bundle_qty );
			WC_CP()->product_data->set( $bundle, 'composited_weight', (float) $composited_bundle_weight + $bundled_weight / $bundle_qty );

			$cart_item['data'] = $bundle;
		}

		return $cart_item;
	}

	/**
	 * Bundles are not directly editable in cart if part of a composite.
	 * They inherit the setting of their container and can only be edited within that scope of their container - @see 'composited_bundle_permalink_args()'.
	 *
	 * @param  boolean           $editable Editable.
	 * @param  WC_Product_Bundle $bundle Bundle.
	 * @param  array             $cart_item Cart item.
	 * @return boolean
	 */
	public static function composited_bundle_not_editable_in_cart( $editable, $bundle, $cart_item ) {

		if ( is_array( $cart_item ) && wc_cp_is_composited_cart_item( $cart_item ) ) {
			$editable = false;
		}

		return $editable;
	}

	/**
	 * Add some contextual info to bundle validation messages.
	 *
	 * @param  string $message Message.
	 * @return string
	 */
	public static function component_bundle_error_message_context( $message ) {

		if ( false !== self::$current_component ) {
			/* translators: %s: Component title */
			$message = sprintf( __( 'Please check your &quot;%1$s&quot; configuration: %2$s', 'woocommerce-composite-products' ), self::$current_component->get_title( true ), $message ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		}

		return $message;
	}

	/**
	 * Edit composited bundle container cart qty.
	 *
	 * @param  int    $quantity Quantity.
	 * @param  string $cart_item_key Cart item key.
	 * @return int
	 */
	public static function composited_bundle_in_cart_item_quantity( $quantity, $cart_item_key ) {

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$cart_item = WC()->cart->cart_contents[ $cart_item_key ];

			if ( wc_pb_is_bundle_container_cart_item( $cart_item ) && wc_cp_is_composited_cart_item( $cart_item ) ) {

				$hide_qty = WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' );

				/**
				 * 'woocommerce_composited_bundle_container_cart_item_hide_quantity' filter.
				 *
				 * @since 6.0.0
				 * @param  boolean  $hide_qty
				 * @param  array    $cart_item
				 * @param  string   $cart_item_key
				 */
				if ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_quantity', $hide_qty, $cart_item, $cart_item_key ) ) {
					$quantity = '';
				}
			}
		}

		return $quantity;
	}

	/**
	 * Edit composited bundle container cart qty.
	 *
	 * @param  int    $quantity Quantity.
	 * @param  string $cart_item Cart item.
	 * @param  string $cart_item_key Cart item key.
	 * @return int
	 */
	public static function composited_bundle_checkout_item_quantity( $quantity, $cart_item, $cart_item_key = false ) {

		if ( wc_pb_is_bundle_container_cart_item( $cart_item ) ) {

			$hide_qty = WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' );

			/**
			 * 'woocommerce_composited_bundle_container_cart_item_hide_quantity' filter.
			 *
			 * @since 6.0.0
			 * @param  boolean  $hide_qty
			 * @param  array    $cart_item
			 * @param  string   $cart_item_key
			 */
			if ( apply_filters( 'woocommerce_composited_bundle_container_cart_item_hide_quantity', $hide_qty, $cart_item, $cart_item_key ) ) {
				$quantity = '';
			}
		}

		return $quantity;
	}

	/**
	 * Visibility of composited bundle containers in orders.
	 * Hide containers without children and a zero price (all optional).
	 *
	 * @param  boolean $visible Visible.
	 * @param  array   $order_item Order item.
	 * @return boolean
	 */
	public static function composited_bundle_order_item_visible( $visible, $order_item ) {

		if ( wc_pb_is_bundle_container_order_item( $order_item ) && wc_cp_maybe_is_composited_order_item( $order_item ) ) {

			if ( ! empty( $order_item['bundle_group_mode'] ) && WC_Product_Bundle::group_mode_has( $order_item['bundle_group_mode'], 'component_multiselect' ) ) {

				$bundled_items = maybe_unserialize( $order_item['bundled_items'] );

				if ( empty( $bundled_items ) && '0' === $order_item['line_subtotal'] ) {
					$visible = false;
				}
			}
		}

		return $visible;
	}

	/**
	 * Edit composited bundle container order item title.
	 *
	 * @param  string $content Title.
	 * @param  array  $order_item Order item.
	 * @return string
	 */
	public static function composited_bundle_order_table_item_title( $content, $order_item ) {

		if ( wc_pb_is_bundle_container_order_item( $order_item ) && wc_cp_maybe_is_composited_order_item( $order_item ) ) {

			$hide_title = ! empty( $order_item['bundle_group_mode'] ) && WC_Product_Bundle::group_mode_has( $order_item['bundle_group_mode'], 'component_multiselect' );

			/**
			 * 'woocommerce_composited_bundle_container_order_item_hide_title' filter.
			 *
			 * @since 6.0.0
			 * @param  boolean  $hide_title
			 * @param  array    $order_item
			 */
			if ( apply_filters( 'woocommerce_composited_bundle_container_order_item_hide_title', $hide_title, $order_item ) ) {
				$content = '';
			}
		}

		return $content;
	}

	/**
	 * Edit composited bundle container order item qty.
	 *
	 * @param  string $quantity Quantity.
	 * @param  array  $order_item Order item.
	 * @return string
	 */
	public static function composited_bundle_order_table_item_quantity( $quantity, $order_item ) {

		if ( wc_pb_is_bundle_container_order_item( $order_item ) && wc_cp_maybe_is_composited_order_item( $order_item ) ) {

			$hide_qty = ! empty( $order_item['bundle_group_mode'] ) && WC_Product_Bundle::group_mode_has( $order_item['bundle_group_mode'], 'component_multiselect' );

			/**
			 * 'woocommerce_composited_bundle_container_order_item_hide_quantity' filter.
			 *
			 * @since 6.0.0
			 * @param  boolean  $hide_qty
			 * @param  array    $order_item
			 */
			if ( apply_filters( 'woocommerce_composited_bundle_container_order_item_hide_quantity', $hide_qty, $order_item ) ) {
				$quantity = '';
			}
		}

		return $quantity;
	}

	/**
	 * Prevents bundle container item meta from showing up.
	 *
	 * @since  5.8.0
	 *
	 * @param  string        $desc Description.
	 * @param  array         $desc_array Description array.
	 * @param  WC_Order_Item $order_item Order item.
	 * @return string
	 */
	public static function composited_bundle_order_item_description( $desc, $desc_array, $order_item ) {

		$hide_title = ! empty( $order_item['bundle_group_mode'] ) && WC_Product_Bundle::group_mode_has( $order_item['bundle_group_mode'], 'component_multiselect' );

		/**
		 * 'woocommerce_composited_bundle_container_order_item_hide_title' filter.
		 *
		 * @since 6.0.0
		 * @param  boolean  $hide_title
		 * @param  array    $order_item
		 */
		if ( apply_filters( 'woocommerce_composited_bundle_container_order_item_hide_title', $hide_title, $order_item ) ) {
			$desc = '';
		}

		return $desc;
	}

	/**
	 * Use custom callback to add bundles to orders in 'WC_CP_Order::add_composite_to_order'.
	 *
	 * @since  5.8.0
	 *
	 * @param  array                $callback Callback.
	 * @param  WC_CP_Component      $component Component.
	 * @param  WC_Product_Composite $composite Composite.
	 * @param  WC_Order             $order Order.
	 * @param  integer              $quantity Quantity.
	 * @param  array                $args Args.
	 */
	public static function add_composited_bundle_to_order_callback( $callback, $component, $composite, $order, $quantity, $args ) {

		$component_configuration = $args['configuration'][ $component->get_id() ];

		if ( empty( $component_configuration['stamp'] ) ) {
			return $callback;
		}

		$component_option_id = $component_configuration['product_id'];
		$component_option    = $component->get_option( $component_option_id );

		if ( $component_option->get_product()->is_type( 'bundle' ) ) {
			return array( __CLASS__, 'add_composited_bundle_to_order' );
		}

		return $callback;
	}

	/**
	 * Sync composited bundle cart stamp.
	 *
	 * @since  6.12.0
	 *
	 * @param  string $bundle_cart_key Bundle cart key.
	 */
	public static function sync_bundle_cart_stamp( $bundle_cart_key ) {

		$parent_item = isset( WC()->cart->cart_contents[ $bundle_cart_key ] ) ? WC()->cart->cart_contents[ $bundle_cart_key ] : false;
		if ( ! $parent_item ) {
			return;
		}

		if ( wc_cp_maybe_is_composited_cart_item( $parent_item ) && wc_pb_is_bundle_container_cart_item( $parent_item ) ) {

			$component_id = absint( $parent_item['composite_item'] );
			if ( ! $component_id || ! isset( WC()->cart->cart_contents[ $bundle_cart_key ]['composite_data'][ $component_id ] ) ) {
				return;
			}

			$new_stamp = $parent_item['stamp'];
			if ( ! $new_stamp ) {
				return;
			}

			WC()->cart->cart_contents[ $bundle_cart_key ]['composite_data'][ $component_id ]['stamp'] = $new_stamp;

			// Sync composite's stamp.
			$composite_cart_key = wc_cp_get_composited_cart_item_container( $parent_item, false, true );
			if ( ! $composite_cart_key ) {
				return;
			}

			WC()->cart->cart_contents[ $composite_cart_key ]['composite_data'][ $component_id ]['stamp'] = $new_stamp;
			foreach ( wc_cp_get_composited_cart_items( WC()->cart->cart_contents[ $composite_cart_key ], false, true ) as $child_key ) {
				WC()->cart->cart_contents[ $composite_cart_key ]['composite_data'][ $component_id ]['stamp'] = $new_stamp;
			}
		}
	}

	/**
	 * Custom callback for adding bundles to orders in 'WC_CP_Order::add_composite_to_order'.
	 *
	 * @since  5.8.0
	 *
	 * @param  WC_CP_Component      $component Component object.
	 * @param  WC_Product_Composite $composite Composite object.
	 * @param  WC_Order             $order Order object.
	 * @param  integer              $quantity Quantity.
	 * @param  array                $args Args.
	 */
	public static function add_composited_bundle_to_order( $component, $composite, $order, $quantity, $args ) {

		$component_configuration = $args['configuration'][ $component->get_id() ];
		$component_option_id     = $component_configuration['product_id'];
		$component_quantity      = isset( $component_configuration['quantity'] ) ? absint( $component_configuration['quantity'] ) : $component->get_quantity();
		$component_option        = $component->get_option( $component_option_id );

		$bundle_args = array(
			'configuration' => $component_configuration['stamp'],
		);

		WC_PB_Compatibility::$bundle_prefix = $component->get_id();

		$add_to_order = WC_PB()->order->add_bundle_to_order( $component_option->get_product(), $order, $quantity = 1, $bundle_args );

		WC_PB_Compatibility::$bundle_prefix = '';

		return $add_to_order;
	}

	/*
	|--------------------------------------------------------------------------
	| REST API.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Parse posted bundle configuration in composite configuration array.
	 *
	 * @param  array                 $configuration Configuration.
	 * @param  WC_Product_Composite  $composite Composite.
	 * @param  WC_Order_Item_Product $item Order item.
	 * @return array
	 */
	public static function parse_composited_rest_bundle_configuration( $configuration, $composite, $item ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( empty( $configuration ) || ! is_array( $configuration ) ) {
			return $configuration;
		}

		foreach ( $configuration as $component_id => $component_configuration ) {

			if ( empty( $component_configuration['product_id'] ) ) {
				continue;
			}

			$component_option = $composite->get_component_option( $component_id, $component_configuration['product_id'] );

			if ( ! $component_option ) {
				continue;
			}

			$composited_product = $component_option->get_product();

			if ( ! $composited_product->is_type( 'bundle' ) ) {
				continue;
			}

			unset( $configuration[ $component_id ]['bundle_configuration'] );
			$configuration[ $component_id ]['stamp'] = WC_PB_REST_API::parse_posted_bundle_configuration( $composited_product, $component_configuration['bundle_configuration'] );
		}

		return $configuration;
	}

	/*
	|--------------------------------------------------------------------------
	| Store API.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Filter store API responses.
	 *
	 * @since  6.15.1
	 *
	 * @param  WP_REST_Response $response The response object.
	 * @param  WP_REST_Server   $server The server object.
	 * @param WP_REST_Request  $request The request object.
	 * @return WP_REST_Response
	 */
	public static function filter_store_api_cart_item_data( $response, $server, $request ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( strpos( $request->get_route(), 'wc/store' ) === false ) {
			return $response;
		}

		$data = $response->get_data();

		if ( empty( $data['items'] ) ) {
			return $response;
		}

		if ( ! WC()->cart ) {
			return $response;
		}

		$cart = WC()->cart->get_cart();

		foreach ( $data['items'] as &$item_data ) {

			$cart_item_key = $item_data['key'];
			$cart_item     = isset( $cart[ $cart_item_key ] ) ? $cart[ $cart_item_key ] : null;

			if ( is_null( $cart_item ) ) {
				continue;
			}

			/**
			 * StoreAPI returns the following fields as
			 * - object (/wc/store/v1/cart)
			 * - array (/wc/store/v1/cart/extensions)
			 *
			 * Casting them to objects, to avoid PHP8+ fatal errors.
			 *
			 * @see https://github.com/woocommerce/woocommerce-product-bundles/issues/1096
			 * @see https://github.com/woocommerce/woocommerce-blocks/issues/7275
			 */
			$item_data['quantity_limits'] = (object) $item_data['quantity_limits'];
			$item_data['prices']          = (object) $item_data['prices'];
			$item_data['totals']          = (object) $item_data['totals'];
			$item_data['extensions']      = (object) $item_data['extensions'];

			// Is this a composited bundle?
			if ( isset( $item_data['extensions']->composites['composited_item_data'] ) && isset( $item_data['extensions']->bundles['bundle_data'] ) ) {

				// If the subtotal is zero at this point, no aggregation happened.
				if ( empty( $item_data['totals']->line_subtotal ) ) {
					$item_data['extensions']->composites['composited_item_data']['is_subtotal_hidden'] = true;
				}

				// If the price is zero at this point, no aggregation happened.
				if ( empty( $item_data['prices']->raw_prices['price'] ) ) {
					$item_data['extensions']->composites['composited_item_data']['is_price_hidden'] = true;
				}

				if ( ! $cart_item['data']->is_type( 'bundle' ) ) {
					continue;
				}

				if ( WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' ) ) {
					$item_data['extensions']->bundles['bundle_data']['is_title_hidden'] = true;
					$item_data['quantity_limits']->editable                             = false;
				}

				foreach ( $data['items'] as &$bundled_item_data ) {

					if ( ! isset( $bundled_item_data['extensions']->bundles['bundled_by'] ) ) {
						continue;
					}

					if ( $cart_item['key'] === $bundled_item_data['extensions']->bundles['bundled_by'] ) {

						$bundled_item_data['extensions']->bundles['bundled_item_data']['is_composited'] = true;

						if ( WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' ) ) {
							$bundled_item_data['extensions']->bundles['bundled_item_data']['is_ungrouped'] = true;
						}

						// Do not display bundled item prices if aggregated at parent level since we can't nest deeper.
						if ( WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'aggregated_prices' ) ) {
							$bundled_item_data['extensions']->bundles['bundled_item_data']['is_price_hidden'] = true;
						}

						// Do not display bundled item subtotals if aggregated at parent level since we can't nest deeper.
						if ( WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'aggregated_subtotals' ) ) {
							$bundled_item_data['extensions']->bundles['bundled_item_data']['is_subtotal_hidden'] = true;
						}

						// This basically controls the "arrow" that makes subtotals look indented. If the parent composite aggregates its components, then all bundled item subtotals should appear indented as well.
						$bundled_item_data['extensions']->bundles['bundled_item_data']['is_subtotal_aggregated'] = $item_data['extensions']->composites['composited_item_data']['is_subtotal_aggregated'];
					}
				}
			}
		}

		$response->set_data( $data );

		return $response;
	}

	/*
	|--------------------------------------------------------------------------
	| Analytics.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Modify CP analytics table by adding in all bundled items that sold in Composites.
	 *
	 * @since  6.12.0
	 *
	 * @param  array $clauses Clauses.
	 * @return array
	 */
	public static function setup_analytics_base_table( $clauses ) {
		global $wpdb;

		if ( ! class_exists( 'WC_CP_Analytics_Revenue_Data_Store' ) ) {
			return $clauses;
		}

		$table_name = WC_CP_Analytics_Revenue_Data_Store::get_db_table_name();
		$clause_pb  = "SELECT
			`pb`.`order_item_id` as `order_item_id`,
			`cp`.`parent_order_item_id` as `parent_order_item_id`,
			`pb`.`order_id` as `order_id`,
			`cp`.`composite_id` as `composite_id`,
			`pb`.`product_id` as `product_id`,
			`pb`.`variation_id` as `variation_id`,
			`pb`.`customer_id` as `customer_id`,
			`pb`.`date_created` as `date_created`,
			`pb`.`product_qty` as `product_qty`,
			`pb`.`product_net_revenue` as `product_net_revenue`,
			`pb`.`product_gross_revenue` as `product_gross_revenue`,
			`pb`.`coupon_amount` as `coupon_amount`,
			`pb`.`tax_amount` as `tax_amount`
			FROM `{$wpdb->prefix}wc_order_composite_lookup` as `cp`
			INNER JOIN `{$wpdb->prefix}wc_order_bundle_lookup` as `pb` ON `cp`.`order_item_id` = `pb`.`parent_order_item_id`";

		// Replace `from` statement with a union.
		$clauses = array(
			'( SELECT `order_item_id`,`parent_order_item_id`, `order_id`, `composite_id`,`product_id`,`variation_id`,`customer_id`,`date_created`,`product_qty`,`product_net_revenue`,`product_gross_revenue`,`coupon_amount`,`tax_amount` FROM ' . $table_name . ' UNION ' . $clause_pb . " ) AS `$table_name`",
		);

		return $clauses;
	}
}

WC_PB_CP_Compatibility::init();
