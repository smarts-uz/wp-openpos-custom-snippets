<?php
/**
 * WC_PB_Addons_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    4.11.4
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Addons Compatibility.
 *
 * @version  8.2.1
 */
class WC_PB_Addons_Compatibility {

	/**
	 * Initialize the module.
	 */
	public static function init() {

		// Add Addons script as a dependency to the Product Bundles script.
		add_filter( 'woocommerce_pb_script_dependencies', array( __CLASS__, 'add_script_dependency' ), 10, 1 );

		// Support for Product Addons.
		add_action( 'woocommerce_bundled_product_add_to_cart', array( __CLASS__, 'addons_support' ), 10, 2 );
		add_action( 'woocommerce_bundled_single_variation', array( __CLASS__, 'addons_support' ), 15, 2 );

		// Prefix form fields.
		add_filter( 'product_addons_field_prefix', array( __CLASS__, 'addons_cart_prefix' ), 10, 2 );

		// Validate add to cart Addons.
		add_filter( 'woocommerce_bundled_item_add_to_cart_validation', array( __CLASS__, 'validate_bundled_item_addons' ), 10, 5 );

		// Add addons identifier to bundled item stamp.
		add_filter( 'woocommerce_bundled_item_cart_item_identifier', array( __CLASS__, 'bundled_item_addons_stamp' ), 10, 2 );

		// Add option to disable Addons at component level.
		add_action( 'woocommerce_bundled_product_admin_advanced_html', array( __CLASS__, 'display_addons_disable_option' ), 15, 4 );

		// Save option to disable Addons at component level.
		add_filter( 'woocommerce_bundles_process_bundled_item_admin_data', array( __CLASS__, 'process_addons_disable_option' ), 10, 4 );

		// Before and after add-to-cart handling.
		add_action( 'woocommerce_bundled_item_before_add_to_cart', array( __CLASS__, 'before_bundled_add_to_cart' ), 10, 5 );
		add_action( 'woocommerce_bundled_item_after_add_to_cart', array( __CLASS__, 'after_bundled_add_to_cart' ), 10, 5 );

		// Load child Addons data from the parent cart item data array.
		add_filter( 'woocommerce_bundled_item_cart_data', array( __CLASS__, 'get_bundled_cart_item_data_from_parent' ), 10, 2 );

		// Separate support for Product Addons in the admin.
		add_action( 'woocommerce_bundle_after_order_item_form', array( __CLASS__, 'admin_render_bundle_addons' ), 10, 4 );
		add_action( 'woocommerce_bundled_product_add_to_cart', array( __CLASS__, 'admin_render_bundled_item_addons' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_add_to_order_validation', array( __CLASS__, 'admin_validate_bundled_item_addons' ), 10, 6 );

		add_filter( 'woocommerce_editing_bundle_in_order_configuration', array( __CLASS__, 'editing_bundled_item_addons' ), 10, 4 );
		add_action( 'woocommerce_before_editing_bundle_in_order', array( __CLASS__, 'store_bundle_item_addons' ), 10, 2 );
		add_action( 'woocommerce_editing_bundle_in_order', array( __CLASS__, 'copy_bundle_item_addons' ), 10, 3 );

		add_action( 'woocommerce_bundle_added_to_order', array( __CLASS__, 'store_bundled_item_addons' ), 10, 5 );

		add_action( 'woocommerce_product_addons_display_editing_in_order_button', array( __CLASS__, 'maybe_hide_edit_button' ), 10, 2 );

		// Special case for addons for bundles inside composite products when manually editing an order.
		add_filter( 'woocommerce_posted_composite_configuration', array( __CLASS__, 'get_composited_bundle_configuration' ), 20, 3 );

		// Enable edit-in-cart feature if any items have addons.
		add_filter( 'woocommerce_is_bundle_container_order_item_editable', array( __CLASS__, 'addon_bundle_editable_in_cart' ), 10, 3 );

		// Allow pre-populate of add-on fields based on cart key.
		add_action( 'woocommerce_product_addons_parse_cart_addons', array( __CLASS__, 'parse_bundle_addons' ), 10, 3 );
		add_filter( 'woocommerce_product_addons_cart_permalink', array( __CLASS__, 'add_cart_key_to_permalink' ), 10, 2 );
		add_filter( 'woocommerce_bundle_cart_permalink_args', array( __CLASS__, 'maybe_add_cart_key_to_permalink_args' ), 10, 2 );

		/*
		 * Aggregate add-ons costs and calculate them after PB has applied discounts.
		 * Also, do not charge anything for add-ons if Priced Individually is disabled and the 'filters' cart pricing method is in use.
		 */
		if ( 'filters' === WC_PB_Product_Prices::get_bundled_cart_item_discount_method() ) {

			// Aggregate add-ons costs and calculate them after PB has applied discounts.
			add_filter( 'woocommerce_bundled_cart_item', array( __CLASS__, 'preprocess_bundled_cart_item_addon_data' ), 0, 2 );

			// Do not let add-ons adjust prices when PB modifies them.
			add_filter( 'woocommerce_product_addons_adjust_price', array( __CLASS__, 'adjust_addons_price' ), 15, 2 );

			// Remove bundled item add-on prices in product bundle pages when bundled items are not Priced Individually.
			add_action( 'woocommerce_bundled_product_price_filters_added', array( __CLASS__, 'add_addon_price_zero_filter' ) );
			add_action( 'woocommerce_bundled_product_price_filters_removed', array( __CLASS__, 'remove_addon_price_zero_filter' ) );
		}
	}

	/**
	 * Add Addons script as a dependency to the Product Bundles script.
	 *
	 * @since  6.16.0
	 *
	 * @param  array $dependencies The script dependencies.
	 * @return array
	 */
	public static function add_script_dependency( $dependencies ) {

		$dependencies[] = 'woocommerce-addons';

		return $dependencies;
	}

	/**
	 * Used to tell if a product has (required) addons.
	 *
	 * @since  5.9.2
	 *
	 * @param  mixed   $product  WC_Product object or product ID.
	 * @param  boolean $required Whether to check for required addons only.
	 * @return boolean
	 */
	public static function has_addons( $product, $required = false ) {

		if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			$product_id = $product->get_id();
		} else {
			$product_id = absint( $product );
		}

		$has_addons = false;
		$cache_key  = 'product_addons_' . $product_id;

		$addons = WC_PB_Helpers::cache_get( $cache_key );

		if ( is_null( $addons ) ) {
			$addons = WC_Product_Addons_Helper::get_product_addons( $product_id, false, false );
			WC_PB_Helpers::cache_set( $cache_key, $addons );
		}

		if ( ! empty( $addons ) ) {

			if ( $required ) {

				foreach ( $addons as $addon ) {

					$type = ! empty( $addon['type'] ) ? $addon['type'] : '';

					if ( 'heading' !== $type && isset( $addon['required'] ) && 1 === absint( $addon['required'] ) ) {
						$has_addons = true;
						break;
					}
				}
			} else {
				$has_addons = true;
			}
		}

		return $has_addons;
	}

	/**
	 * Show option to disable bundled product addons.
	 *
	 * @param  int   $loop The loop index.
	 * @param  int   $product_id The product ID.
	 * @param  array $item_data The item data.
	 * @param  int   $post_id The post ID.
	 * @return void
	 */
	public static function display_addons_disable_option( $loop, $product_id, $item_data, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$disable_addons = isset( $item_data['disable_addons'] ) && 'yes' === $item_data['disable_addons'];

		?>
		<div class="disable_addons">
		<div class="form-field">
			<label for="disable_addons"><?php echo esc_html__( 'Disable Add-Ons', 'woocommerce-product-bundles' ); ?></label>
			<input type="checkbox" class="checkbox"<?php echo( $disable_addons ? ' checked="checked"' : '' ); ?> name="bundle_data[<?php echo esc_attr( $loop ); ?>][disable_addons]" <?php echo( $disable_addons ? 'value="1"' : '' ); ?>/>
			<?php echo wc_help_tip( __( 'Check this option to disable any Product Add-Ons associated with this bundled product.', 'woocommerce-product-bundles' ) ); ?>
		</div>
		</div>
		<?php
	}

	/**
	 * Save option that disables bundled product addons.
	 *
	 * @param  array $item_data The item data.
	 * @param  array $data The data.
	 * @param  mixed $item_id The item ID.
	 * @param  mixed $post_id The post ID.
	 */
	public static function process_addons_disable_option( $item_data, $data, $item_id, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( isset( $data['disable_addons'] ) ) {
			$item_data['disable_addons'] = 'yes';
		} else {
			$item_data['disable_addons'] = 'no';
		}

		return $item_data;
	}

	/**
	 * Support for bundled item addons.
	 *
	 * @param  int             $product_id The product ID.
	 * @param  WC_Bundled_Item $item The bundled item.
	 * @return void
	 */
	public static function addons_support( $product_id, $item ) {

		global $Product_Addon_Display, $product;

		if ( ! empty( $Product_Addon_Display ) ) {

			if ( doing_action( 'wp_ajax_woocommerce_configure_bundle_order_item' ) || doing_action( 'wp_ajax_woocommerce_configure_composite_order_item' ) ) {
				return;
			}

			if ( $item->get_product()->is_type( 'variable' ) && doing_action( 'woocommerce_bundled_product_add_to_cart' ) ) {
				return;
			}

			if ( $item->disable_addons() ) {
				return;
			}

			$product_bak = isset( $product ) ? $product : false;
			$product     = $item->get_product();

			WC_PB_Compatibility::$addons_prefix          = $item->get_id();
			WC_PB_Compatibility::$compat_bundled_product = $item->get_product();

			$Product_Addon_Display->display( $product_id, false );

			WC_PB_Compatibility::$addons_prefix          = '';
			WC_PB_Compatibility::$compat_bundled_product = '';

			if ( $product_bak ) {
				$product = $product_bak;
			}
		}
	}

	/**
	 * Sets a unique prefix for unique add-ons. The prefix is set and re-set globally before validating and adding to cart.
	 *
	 * @param  string $prefix      unique prefixfor add-ons.
	 * @param  int    $product_id  the product idof the bundled item.
	 *
	 * @return string
	 */
	public static function addons_cart_prefix( $prefix, $product_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( ! empty( WC_PB_Compatibility::$addons_prefix ) ) {
			$prefix = WC_PB_Compatibility::$addons_prefix . '-';
		}

		if ( ! empty( WC_PB_Compatibility::$bundle_prefix ) ) {
			$prefix = WC_PB_Compatibility::$bundle_prefix . '-' . WC_PB_Compatibility::$addons_prefix . '-';
		}

		return $prefix;
	}

	/**
	 * Add addons identifier to bundled item stamp, in order to generate new cart ids for bundles with different addons configurations.
	 *
	 * @param  array  $bundled_item_stamp Bundled item stamp.
	 * @param  string $bundled_item_id    Bundled item id.
	 * @return array
	 */
	public static function bundled_item_addons_stamp( $bundled_item_stamp, $bundled_item_id ) {

		global $Product_Addon_Cart;

		// Store bundled item addons add-ons config in stamp to avoid generating the same bundle cart id.
		if ( ! empty( $Product_Addon_Cart ) ) {

			$addon_data = array();

			// Set addons prefix.
			WC_PB_Compatibility::$addons_prefix = $bundled_item_id;

			$bundled_product_id = $bundled_item_stamp['product_id'];

			$addon_data = $Product_Addon_Cart->add_cart_item_data( $addon_data, $bundled_product_id );

			// Reset addons prefix.
			WC_PB_Compatibility::$addons_prefix = '';

			if ( ! empty( $addon_data['addons'] ) ) {
				$bundled_item_stamp['addons'] = $addon_data['addons'];
			}
		}

		return $bundled_item_stamp;
	}

	/**
	 * Validate bundled item addons.
	 *
	 * @param  bool            $add Whether to add the bundled item to the cart.
	 * @param  int             $bundle The bundle product ID.
	 * @param  WC_Bundled_Item $bundled_item The bundled item.
	 * @param  int             $quantity The quantity.
	 * @param  int             $variation_id The variation ID.
	 * @return bool
	 */
	public static function validate_bundled_item_addons( $add, $bundle, $bundled_item, $quantity, $variation_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Ordering again? When ordering again, do not revalidate addons.
		$order_again = isset( $_GET['order_again'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wc_clean( $_GET['_wpnonce'] ), 'woocommerce-order_again' );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		if ( $order_again ) {
			return $add;
		}

		$bundled_item_id = $bundled_item->get_id();
		$product_id      = $bundled_item->get_product_id();

		// Validate add-ons.
		global $Product_Addon_Cart;

		if ( ! empty( $Product_Addon_Cart ) ) {

			WC_PB_Compatibility::$addons_prefix = $bundled_item_id;

			if ( false === $bundled_item->disable_addons() && false === $Product_Addon_Cart->validate_add_cart_item( true, $product_id, $quantity ) ) {
				$add = false;
			}

			WC_PB_Compatibility::$addons_prefix = '';
		}

		return $add;
	}

	/**
	 * Runs before adding a bundled item to the cart.
	 *
	 * @param  int   $product_id Product ID.
	 * @param  int   $quantity  Quantity.
	 * @param  int   $variation_id Variation ID.
	 * @param  array $variations Variations.
	 * @param  array $bundled_item_cart_data Bundled item cart data.
	 * @return void
	 */
	public static function after_bundled_add_to_cart( $product_id, $quantity, $variation_id, $variations, $bundled_item_cart_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		global $Product_Addon_Cart;

		// Reset addons prefix.
		WC_PB_Compatibility::$addons_prefix = '';

		if ( ! empty( $Product_Addon_Cart ) ) {

			add_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );
		}
	}

	/**
	 * Runs after adding a bundled item to the cart.
	 *
	 * @param  int   $product_id Product ID.
	 * @param  int   $quantity Quantity.
	 * @param  int   $variation_id Variation ID.
	 * @param  array $variations Variations.
	 * @param  array $bundled_item_cart_data Bundled item cart data.
	 * @return void
	 */
	public static function before_bundled_add_to_cart( $product_id, $quantity, $variation_id, $variations, $bundled_item_cart_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		global $Product_Addon_Cart;

		// Set addons prefix.
		WC_PB_Compatibility::$addons_prefix = $bundled_item_cart_data['bundled_item_id'];

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it.
		// Not doing so results in issues with file upload validation.

		if ( ! empty( $Product_Addon_Cart ) ) {
			remove_filter( 'woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2 );
		}
	}

	/**
	 * Retrieve child cart item data from the parent cart item data array, if necessary.
	 *
	 * @param  array $bundled_item_cart_data The bundled item cart data.
	 * @param  array $cart_item_data The cart item data.
	 * @return array
	 */
	public static function get_bundled_cart_item_data_from_parent( $bundled_item_cart_data, $cart_item_data ) {

		// Add-ons cart item data is already stored in the composite_data array, so we can grab it from there instead of allowing Addons to re-add it.
		if ( isset( $bundled_item_cart_data['bundled_item_id'] ) && isset( $cart_item_data['stamp'][ $bundled_item_cart_data['bundled_item_id'] ]['addons'] ) ) {
			$bundled_item_cart_data['addons'] = $cart_item_data['stamp'][ $bundled_item_cart_data['bundled_item_id'] ]['addons'];
		}

		return $bundled_item_cart_data;
	}

	/**
	 * Aggregate add-ons costs and calculate them after PB has applied discounts.
	 *
	 * @since  6.0.4
	 *
	 * @param  array             $cart_item The cart item.
	 * @param  WC_Product_Bundle $bundle   The bundle product.
	 * @param  array|bool        $cart_contents The cart contents.
	 * @return array
	 */
	public static function preprocess_bundled_cart_item_addon_data( $cart_item, $bundle, $cart_contents = false ) {

		if ( empty( $cart_item['addons'] ) ) {
			return $cart_item;
		}

		$bundled_item    = WC_PB_Helpers::get_runtime_prop( $cart_item['data'], 'bundled_cart_item' );
		$bundled_item_id = $cart_item['bundled_item_id'];

		if ( is_null( $bundled_item ) ) {
			$bundled_item = $bundle->get_bundled_item( $bundled_item_id );
		}

		if ( ! $bundled_item ) {
			return $cart_item;
		}

		if ( $bundled_item->is_priced_individually() ) {

			// Let PAO handle things on its own.
			$discount = $bundled_item->get_discount( 'cart' );
			if ( ! $discount ) {
				return $cart_item;
			}

			WC_PB()->product_data->set( $cart_item['data'], 'bundled_price_offset_pct', array() );
			WC_PB()->product_data->set( $cart_item['data'], 'bundled_price_offset', 0.0 );

			$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item, $cart_contents );
			if ( $bundle_container_item ) {

				// Read original % values from parent item.
				$addons_data = ! empty( $bundle_container_item['stamp'][ $bundled_item_id ]['addons'] ) ? $bundle_container_item['stamp'][ $bundled_item_id ]['addons'] : array();
				$flat_fees   = 0;

				foreach ( $addons_data as $addon_key => $addon ) {

					$bundled_price_offset     = WC_PB()->product_data->get( $cart_item['data'], 'bundled_price_offset' );
					$bundled_price_offset_pct = WC_PB()->product_data->get( $cart_item['data'], 'bundled_price_offset_pct' );

					// See 'WC_Bundled_Item::filter_get_price'.
					if ( 'percentage_based' === $addon['price_type'] ) {
						$bundled_price_offset_pct[] = $addon['price'];
						// Apply percentage based price on the discounted bundled item price.
						$cart_item['addons'][ $addon_key ]['price'] = ( ( 100 - $discount ) / 100 ) * $addon['price'];

						WC_PB()->product_data->set( $cart_item['data'], 'bundled_price_offset_pct', $bundled_price_offset_pct );
					} elseif ( 'flat_fee' === $addon['price_type'] ) {
						$bundled_price_offset += (float) $addon['price'] / $cart_item['quantity'];
						$flat_fees            += (float) $addon['price'] / $cart_item['quantity'];

						WC_PB()->product_data->set( $cart_item['data'], 'bundled_price_offset', $bundled_price_offset );

					} else {
						$bundled_price_offset += (float) $addon['price'];

						WC_PB()->product_data->set( $cart_item['data'], 'bundled_price_offset', $bundled_price_offset );
					}
				}

				$cart_item['addons_flat_fees_sum'] = $flat_fees;
			}
		} else {

			// Priced Individually disabled? Give add-ons for free.
			foreach ( $cart_item['addons'] as $addon_key => $addon_data ) {
				$cart_item['addons'][ $addon_key ]['price'] = 0.0;
			}
		}

		return $cart_item;
	}

	/**
	 * Do not let add-ons adjust prices when PB modifies them.
	 *
	 * @since  6.0.4
	 *
	 * @param  bool  $adjust Whether to adjust the price.
	 * @param  array $cart_item The cart item.
	 * @return bool
	 */
	public static function adjust_addons_price( $adjust, $cart_item ) {
		$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
		if ( $bundle_container_item ) {

			$adjust       = false;
			$bundled_item = WC_PB_Helpers::get_runtime_prop( $cart_item['data'], 'bundled_cart_item' );

			if ( is_null( $bundled_item ) ) {
				$bundle          = $bundle_container_item['data'];
				$bundled_item_id = $cart_item['bundled_item_id'];
				$bundled_item    = $bundle->get_bundled_item( $bundled_item_id );
			}

			// Only let add-ons adjust prices if PB doesn't modify bundled item prices in any way.
			if ( $bundled_item && $bundled_item->is_priced_individually() && ! $bundled_item->get_discount( 'cart' ) ) {
				$adjust = true;
			}
		}

		return $adjust;
	}

	/**
	 * Adds filter that discards bundled item add-on prices in product bundle pages.
	 *
	 * @since  6.0.4
	 *
	 * @param  WC_Bundled_Item $bundled_item The bundled item.
	 */
	public static function add_addon_price_zero_filter( $bundled_item ) {
		if ( doing_action( 'wp_ajax_woocommerce_configure_bundle_order_item' ) || doing_action( 'wp_ajax_woocommerce_configure_composite_order_item' ) ) {
			return;
		}

		if ( ! $bundled_item->is_priced_individually() ) {
			add_filter( 'woocommerce_product_addons_price_raw', array( __CLASS__, 'option_price_raw_zero_filter' ) );
			add_filter( 'woocommerce_product_addons_option_price_raw', array( __CLASS__, 'option_price_raw_zero_filter' ) );
		}
	}

	/**
	 * Removes filter that discards bundled item add-on prices in product bundle pages.
	 *
	 * @since  6.0.4
	 *
	 * @param  WC_Bundled_Item $bundled_item The bundled item.
	 */
	public static function remove_addon_price_zero_filter( $bundled_item ) {
		if ( doing_action( 'wp_ajax_woocommerce_configure_bundle_order_item' ) || doing_action( 'wp_ajax_woocommerce_configure_composite_order_item' ) ) {
			return;
		}

		if ( ! $bundled_item->is_priced_individually() ) {
			remove_filter( 'woocommerce_product_addons_price_raw', array( __CLASS__, 'option_price_raw_zero_filter' ) );
			remove_filter( 'woocommerce_product_addons_option_price_raw', array( __CLASS__, 'option_price_raw_zero_filter' ) );
		}
	}

	/**
	 * Discards bundled item add-on prices in product bundle pages.
	 *
	 * @since  6.0.4
	 *
	 * @param  mixed $price The price.
	 */
	public static function option_price_raw_zero_filter( $price ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return '';
	}

	/**
	 * Render addons for parent bundle.
	 *
	 * @param WC_Product_Bundle $product The product.
	 * @param WC_Order_Item     $item The item.
	 * @param WC_Order          $order The order.
	 */
	public static function admin_render_bundle_addons( $product, $item, $order ) {
		if ( ! doing_action( 'wp_ajax_woocommerce_configure_bundle_order_item' ) ) {
			return;
		}

		$html = WC_Product_Addons_Admin_Ajax::render_form( $order, $item, $product );

		if ( false !== $html ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="wc-pao-addons-container wc-bundle-addons-container">';
			echo $html;
			echo '</div>';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Support for bundled item addons.
	 *
	 * @param  int             $product_id    The product ID.
	 * @param  WC_Bundled_Item $bundled_item  The bundled item.
	 *
	 * @return void
	 */
	public static function admin_render_bundled_item_addons( $product_id, $bundled_item ) {
		if ( ! doing_action( 'wp_ajax_woocommerce_configure_bundle_order_item' ) && ! doing_action( 'wp_ajax_woocommerce_configure_composite_order_item' ) ) {
			return;
		}

		if ( $bundled_item->disable_addons() ) {
			return;
		}

		try {
			list( $order, $item, $product ) = WC_Product_Addons_Admin_Ajax::validate_request_and_fetch_data();
		} catch ( Exception $e ) {
			return;
		}

		if ( doing_action( 'wp_ajax_woocommerce_configure_composite_order_item' ) ) {
			$composited_order_items = wc_cp_get_composited_order_items( $item, $order );
			$item                   = null;
			foreach ( $composited_order_items as $composited_order_item ) {
				if ( $composited_order_item['product_id'] === $bundled_item->get_bundle_id() ) {
					$item = $composited_order_item;
					break;
				}
			}
		}

		$bundled_order_items = wc_pb_get_bundled_order_items( $item, $order );
		$item_to_render      = null;
		foreach ( $bundled_order_items as $bundled_order_item ) {
			if ( $bundled_order_item['product_id'] === $bundled_item->get_product_id() ) {
				$item_to_render = $bundled_order_item;
				break;
			}
		}

		WC_PB_Compatibility::$addons_prefix = $bundled_item->get_id();

		// addon-start.php from versions 7.2.1 or below require the $product global.
		if ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '7.2.1' ) <= 0 ) {
			$GLOBALS['product'] = $product;
		}

		$html = WC_Product_Addons_Admin_Ajax::render_form( $order, $item_to_render, $bundled_item->get_product() );

		WC_PB_Compatibility::$addons_prefix = '';

		if ( false !== $html ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="wc-pao-addons-container">';
			echo $html;
			echo '</div>';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Add addons to the posted configuration when editing in order.
	 *
	 * @param  array $configuration  The configuration.
	 * @param  array $product        The product.
	 * @param  array $item           The item.
	 * @param  array $order          The order.
	 *
	 * @return array
	 */
	public static function editing_bundled_item_addons( $configuration, $product, $item, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_bundle_in_order' ) ) {
			return $configuration;
		}

		global $Product_Addon_Cart;

		if ( empty( $Product_Addon_Cart ) ) {
			return $configuration;
		}

		foreach ( $configuration as $bundled_item_id => $item_configuration ) {
			WC_PB_Compatibility::$addons_prefix = $bundled_item_id;

			// Pretend we're in some sort of cart so that we can reuse as much code as possible.
			$values = $Product_Addon_Cart->add_cart_item_data( array(), $item_configuration['product_id'] );

			WC_PB_Compatibility::$addons_prefix = '';

			if ( empty( $values['addons'] ) ) {
				continue;
			}

			$configuration[ $bundled_item_id ]['addons'] = $values['addons'];
		}

		return $configuration;
	}

	/**
	 * Validate add to order Addons.
	 *
	 * @throws Exception If the configuration is invalid.
	 *
	 * @param  boolean         $is_configuration_valid  Whether the configuration is valid.
	 * @param  WC_Product      $product                 The product.
	 * @param  WC_Bundled_Item $bundled_item            The bundled item.
	 * @param  int             $quantity                The quantity.
	 * @param  mixed           $bundled_variation_id    The bundled variation ID.
	 * @param  array           $configuration           The configuration.
	 *
	 * @return bool
	 */
	public static function admin_validate_bundled_item_addons( $is_configuration_valid, $product, $bundled_item, $quantity, $bundled_variation_id, $configuration ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_bundle_in_order' ) && ! doing_action( 'wp_ajax_woocommerce_edit_composite_in_order' ) ) {
			return $is_configuration_valid;
		}

		$is_addon_configuration_valid = self::validate_bundled_item_addons( true, $product, $bundled_item, $quantity, $bundled_variation_id );

		if ( $is_addon_configuration_valid ) {
			return $is_configuration_valid;
		}

		// Get first error message.
		$errors = wc_get_notices( 'error' );
		$error  = reset( $errors )['notice'] ?? '';
		wc_clear_notices();

		/* translators: %1$s: Error message */
		$message = sprintf( __( 'The submitted bundle configuration could not be added to this order: %s', 'woocommerce-product-bundles' ), $error );

		throw new Exception( wp_kses( $message, false ) );
	}

	/**
	 * Store addons for parent bundle item, if it has any.
	 *
	 * We need to do this in the `woocommerce_before_editing_bundle_in_order` hook, because
	 * the `woocommerce_editing_bundle_in_order` hook is only run if the bundle configuration changes, which doesn't take
	 * parent bundle item addons into account.
	 *
	 * @throws Exception If storing the addons fails.
	 *
	 * @param WC_Order_Item_Product $item  The container item.
	 * @param WC_Order              $order The order.
	 *
	 * @return void
	 */
	public static function store_bundle_item_addons( $item, $order ) {
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_bundle_in_order' ) && ! doing_action( 'wp_ajax_woocommerce_edit_composite_in_order' ) ) {
			return;
		}

		$product = $item->get_product();

		$product_addons = WC_Product_Addons_Helper::get_product_addons( $item['product_id'] );

		if ( empty( $product_addons ) ) {
			return;
		}

		if ( ! WC_Product_Addons_Admin_Ajax::store_product_addons( $item, $product, $order ) ) {
			// Get first error message.
			$errors = wc_get_notices( 'error' );
			$error  = htmlspecialchars_decode( reset( $errors )['notice'] ?? '', ENT_COMPAT );
			wc_clear_notices();

			/* translators: %1$s: Error message */
			$message = sprintf( __( 'The submitted bundle configuration could not be added to this order: %s', 'woocommerce-product-bundles' ), $error );

			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Copy addons from old item to new item.
	 *
	 * @param WC_Order_Item_Product $new_item The new item.
	 * @param WC_Order_Item_Product $old_item The old item.
	 * @param WC_Order              $order    The order.
	 *
	 * @return void
	 */
	public static function copy_bundle_item_addons( $new_item, $old_item, $order ) {
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_bundle_in_order' ) && ! doing_action( 'wp_ajax_woocommerce_edit_composite_in_order' ) ) {
			return;
		}

		global $Product_Addon_Cart;

		if ( empty( $Product_Addon_Cart ) ) {
			return;
		}

		// New item already has addons, so we're not gonna do anything.
		if ( $new_item->meta_exists( '_pao_ids' ) ) {
			return;
		}

		$pao_ids   = $old_item->get_meta( '_pao_ids', true );
		$pao_total = $old_item->get_meta( '_pao_total', true );
		if ( empty( $pao_ids ) ) {
			return;
		}

		$product = $new_item->get_product();
		foreach ( $pao_ids as $pao_id ) {
			$new_item->add_meta_data( $pao_id['key'], $pao_id['value'] );
		}
		$new_item->add_meta_data( '_pao_ids', $pao_ids );
		$new_item->add_meta_data( '_pao_total', $pao_total );
		$new_item['subtotal'] = wc_get_price_excluding_tax(
			$product,
			array(
				'price' => $product->get_price(),
				'qty'   => $new_item['qty'],
			)
		) + $pao_total;
		$new_item['total']    = $new_item['subtotal'];

		$new_item->save_meta_data();
		$new_item->save();
		// Refresh the internal cache for the order.
		$order->add_item( $new_item );
	}

	/**
	 * Store add to order Addons.
	 *
	 * @param WC_Order_Item     $container_order_item  The container order item.
	 * @param WC_Order          $order                 The order.
	 * @param WC_Product_Bundle $bundle                The bundle product.
	 * @param in                $bundled_item_quantity The bundled item quantity.
	 * @param array             $args                  The arguments.
	 *
	 * @return void
	 */
	public static function store_bundled_item_addons( $container_order_item, $order, $bundle, $bundled_item_quantity, $args ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_bundle_in_order' ) && ! doing_action( 'wp_ajax_woocommerce_edit_composite_in_order' ) ) {
			return;
		}
		global $Product_Addon_Cart;

		if ( empty( $Product_Addon_Cart ) ) {
			return;
		}

		$bundled_order_items = wc_pb_get_bundled_order_items( $container_order_item, $order );

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		/* @var WC_Order_Item_Product $bundled_order_item */
		foreach ( $bundled_order_items as $bundled_order_item ) {
			$bundled_item_id = $bundled_order_item['bundled_item_id'];
			$bundled_product = $bundled_order_item->get_product();
			$bundled_item    = $bundle->get_bundled_item( $bundled_item_id );

			if ( empty( $args['configuration'][ $bundled_item_id ]['addons'] ) ) {
				continue;
			}

			$values = $args['configuration'][ $bundled_item_id ];

			$item_data = array(
				'bundled_item_id' => $bundled_item_id,
				'product_id'      => $bundled_order_item['product_id'],
				'variation_id'    => $bundled_order_item['variation_id'],
				'quantity'        => $bundled_order_item['qty'],
				'data'            => $bundled_product,
				'addons'          => $values['addons'],
				'bundled_by'      => $bundled_order_item['bundled_by'],
				'stamp'           => $bundled_order_item['stamp'],
			);
			$item_data = self::preprocess_bundled_cart_item_addon_data(
				$item_data,
				$bundle,
				array(
					$bundled_order_item['bundled_by'] => $container_order_item,
				)
			);

			// One of the things that `preprocess_bundled_cart_item_addon_data` does is to calculate the total price of
			// the bundled item, including addons. However, it only does this if the bundled item has a discount.
			// If the bundled item doesn't have a discount, we need to calculate the total price of add-ons ourselves.
			// The `addons_flat_fees_sum` key is set by `preprocess_bundled_cart_item_addon_data` if add-on prices are
			// calculated. So, we use that as an indication if we need to calculate or not.
			if ( $bundled_item->is_priced_individually() && ! isset( $item_data['addons_flat_fees_sum'] ) ) {
				$item_data = $Product_Addon_Cart->get_cart_item_from_session( $item_data, $values );
			}

			WC_PB()->product_data->set( $bundled_product, 'bundled_cart_item', $bundled_item );
			$Product_Addon_Cart->order_line_item( $bundled_order_item, null, $item_data );

			$bundled_order_item['subtotal'] = wc_get_price_excluding_tax(
				$bundled_product,
				array(
					'price' => $bundled_product->get_price(),
					'qty'   => $bundled_order_item['qty'],
				)
			);
			$bundled_order_item['total']    = $bundled_order_item['subtotal'];

			$bundled_order_item->save_meta_data();
			$bundled_order_item->save();
			// Refresh the internal cache for the order.
			$order->add_item( $bundled_order_item );
		}
	}

	/**
	 * Get posted data for addons for composited bundles.
	 *
	 * @since  8.1.0
	 *
	 * @param  array                $configuration The configuration.
	 * @param  WC_Product_Composite $composite    The composite product.
	 *
	 * @return array
	 */
	public static function get_composited_bundle_configuration( $configuration, $composite ) {
		if ( ! doing_action( 'wp_ajax_woocommerce_edit_composite_in_order' ) ) {
			return $configuration;
		}

		global $Product_Addon_Cart;

		if ( empty( $Product_Addon_Cart ) ) {
			return $configuration;
		}

		if ( empty( $configuration ) || ! is_array( $configuration ) ) {
			return $configuration;
		}

		foreach ( $configuration as $component_id => $component_configuration ) {

			if ( empty( $component_configuration['product_id'] ) || empty( $component_configuration['stamp'] ) ) {
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

			foreach ( $component_configuration['stamp'] as $bundled_item_id => $item_configuration ) {
				WC_PB_Compatibility::$addons_prefix = $bundled_item_id;

				// Pretend we're in some sort of cart so that we can reuse as much code as possible.
				$values = $Product_Addon_Cart->add_cart_item_data( array(), $item_configuration['product_id'] );

				WC_PB_Compatibility::$addons_prefix = '';

				if ( empty( $values['addons'] ) ) {
					continue;
				}

				$configuration[ $component_id ]['stamp'][ $bundled_item_id ]['addons'] = $values['addons'];
			}

			WC_PB_Compatibility::$bundle_prefix = '';
		}

		return $configuration;
	}

	/**
	 * Maybe hide the edit button for addons in the order editing screen.
	 *
	 * @param  boolean $display Whether to display the edit button.
	 * @param  array   $item   The item.
	 *
	 * @return boolean
	 */
	public static function maybe_hide_edit_button( $display, $item ) {
		return $display && ! wc_pb_maybe_is_bundled_order_item( $item );
	}

	/**
	 * The bundle should be editable if any items have addons.
	 *
	 * @param  boolean           $editable Whether the bundle is editable.
	 * @param  WC_Product_Bundle $bundle  The bundle product.
	 * @param  array             $cart_item The cart item.
	 * @return boolean
	 */
	public static function addon_bundle_editable_in_cart( $editable, $bundle, $cart_item ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$bundle_items = $bundle->get_bundled_items();
		foreach ( $bundle_items as $bundle_item ) {
			if ( self::has_addons( $bundle_item->get_product() ) ) {
				$editable = true;
				break;
			}
		}

		return $editable;
	}

	/**
	 * Retrieves the add-on data from a cart item for a product bundle contained inside composite products.
	 *
	 * @since 8.2.0
	 *
	 * @param array      $parsed_addons Parsed add-ons.
	 * @param WC_Product $product       The product.
	 * @param array      $cart_item     The cart item.
	 *
	 * @return array
	 */
	private static function parse_bundled_composite_addons( array $parsed_addons, WC_Product $product, array $cart_item ): array {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		global $Product_Addon_Display;

		if ( ! isset( $cart_item['composite_data'] ) || ! $product->is_type( 'composite' ) ) {
			return $parsed_addons;
		}

		foreach ( $cart_item['composite_data'] as $component_id => $composite_data ) {
			if ( ! isset( $composite_data['addons'] ) ) {
				continue;
			}

			if ( empty( $composite_data['product_id'] ) || empty( $composite_data['stamp'] ) ) {
				continue;
			}

			$component_option = $product->get_component_option( $component_id, $composite_data['product_id'] );

			if ( ! $component_option ) {
				continue;
			}

			$composited_product = $component_option->get_product();

			if ( ! $composited_product->is_type( 'bundle' ) ) {
				continue;
			}

			WC_PB_Compatibility::$bundle_prefix = $component_id;

			$parsed_addons = self::parse_bundle_addons( $parsed_addons, $composited_product, $composite_data );

			WC_PB_Compatibility::$bundle_prefix = '';
		}

		return $parsed_addons;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * Retrieves the add-on data from a cart item for a product bundle.
	 *
	 * @since 8.2.0
	 *
	 * @param array      $parsed_addons Parsed add-ons.
	 * @param WC_Product $product       The product.
	 * @param array      $cart_item     The cart item.
	 *
	 * @return array
	 */
	public static function parse_bundle_addons( array $parsed_addons, WC_Product $product, array $cart_item ): array {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		global $Product_Addon_Display;

		if ( ! isset( $cart_item['stamp'] ) || ! $product->is_type( 'bundle' ) ) {
			if ( WC_PB()->compatibility->is_composite_container_cart_item( $cart_item ) ) {
				$parsed_addons = self::parse_bundled_composite_addons( $parsed_addons, $product, $cart_item );
			}

			return $parsed_addons;
		}

		foreach ( $cart_item['stamp'] as $bundled_item_id => $stamp ) {
			if ( ! isset( $stamp['addons'] ) ) {
				continue;
			}

			WC_PB_Compatibility::$addons_prefix = $bundled_item_id;

			$product_addons = WC_Product_Addons_Helper::get_product_addons( $stamp['product_id'] );

			WC_PB_Compatibility::$addons_prefix = '';

			if ( empty( $product_addons ) ) {
				continue;
			}

			$parsed_addons += $Product_Addon_Display->parse_cart_addons( $product_addons, $stamp['addons'] );
		}

		return $parsed_addons;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * Filter to force add-ons to include the correct query parameters in cart item link if any products in the bundle have add-ons.
	 *
	 * @param bool  $allow_cart_key Whether to allow the cart key.
	 * @param array $cart_item      The cart item.
	 *
	 * @return bool
	 */
	public static function add_cart_key_to_permalink( bool $allow_cart_key, array $cart_item ): bool {
		$product = $cart_item['data'];

		if ( ! $product || ! $product->is_type( 'bundle' ) ) {
			return $allow_cart_key;
		}

		return self::addon_bundle_editable_in_cart( $allow_cart_key, $product, $cart_item );
	}

	/**
	 * Adds the PAO key to edit bundle links in the cart.
	 *
	 * @param array $args      The permalink arguments.
	 * @param array $cart_item The cart item.
	 *
	 * @return array
	 */
	public static function maybe_add_cart_key_to_permalink_args( array $args, array $cart_item ): array {
		$product = $cart_item['data'];

		if ( ! $product || ! $product->is_type( 'bundle' ) ) {
			return $args;
		}

		$product_addons = WC_Product_Addons_Helper::get_product_addons( $cart_item['product_id'] );

		if ( ! empty( $product_addons ) || self::addon_bundle_editable_in_cart( false, $product, $cart_item ) ) {
			$args['pao_key']  = $cart_item['key'];
			$args['pao_edit'] = 1;
		}

		return $args;
	}
}

WC_PB_Addons_Compatibility::init();
