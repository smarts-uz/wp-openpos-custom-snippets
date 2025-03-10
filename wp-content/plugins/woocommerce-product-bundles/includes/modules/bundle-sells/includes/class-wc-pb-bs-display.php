<?php
/**
 * WC_PB_BS_Display class
 *
 * @package  WooCommerce Product Bundles
 * @since    5.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display-related functions and filters.
 *
 * @class    WC_PB_BS_Display
 * @version  6.22.6
 */
class WC_PB_BS_Display {

	/**
	 * Setup hooks.
	 */
	public static function init() {

		// Add hooks to display Bundle-Sells.
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'add_bundle_sells_display_hooks' ) );

		// Item data.
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'bundle_sell_data' ), 10, 2 );
	}

	/*
	|--------------------------------------------------------------------------
	| Application layer functions.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Adds logic for overriding bundled-item template file locations.
	 *
	 * @return void
	 */
	public static function apply_bundled_item_template_overrides() {
		add_filter( 'wc_get_template', array( __CLASS__, 'get_bundled_item_template_location' ), 10, 4 );
	}

	/**
	 * Resets all added logic for overriding bundled-item template file locations.
	 *
	 * @return void
	 */
	public static function reset_bundled_item_template_overrides() {
		remove_filter( 'wc_get_template', array( __CLASS__, 'get_bundled_item_template_location' ), 10, 4 );
	}

	/*
	|--------------------------------------------------------------------------
	| Filter/action hooks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add hooks necessary to display Bundle-Sells in single-product templates.
	 */
	public static function add_bundle_sells_display_hooks() {

		global $product;

		if ( $product->is_type( 'variable' ) ) {
			add_action( 'woocommerce_single_variation', array( __CLASS__, 'display_bundle_sells' ), 19 );
		} else {
			add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'display_bundle_sells' ), 1000 );
		}
	}

	/**
	 * Displays Bundle-Sells above the add-to-cart button.
	 *
	 * @return void
	 */
	public static function display_bundle_sells() {

		global $product;

		$bundle_sell_ids = WC_PB_BS_Product::get_bundle_sell_ids( $product );

		if ( ! empty( $bundle_sell_ids ) ) {
			/*
			 * This is not a Bundle-type product.
			 * But if it was, then we could re-use the PB templates... without writing new code.
			 * Let's "fake" it.
			 */
			$bundle = WC_PB_BS_Product::get_bundle( $bundle_sell_ids, $product );

			if ( ! $bundle->get_bundled_items() ) {
				return;
			}

			// Syncing at this point will prevent infinite loops in some edge cases.
			$bundle->sync();

			if ( false === wp_style_is( 'wc-bundle-css', 'enqueued' ) ) {
				wp_enqueue_style( 'wc-bundle-css' );
			}

			if ( false === wp_script_is( 'wc-add-to-cart-bundle', 'enqueued' ) ) {
				wp_enqueue_script( 'wc-add-to-cart-bundle' );
			}

			/*
			 * Show Bundle-Sells section title.
			 */
			$bundle_sells_title = WC_PB_BS_Product::get_bundle_sells_title( $product );

			if ( $bundle_sells_title ) {

				$bundle_sells_title_proc = do_shortcode( wp_kses( $bundle_sells_title, WC_PB_Helpers::get_allowed_html( 'inline' ) ) );

				wc_get_template(
					'single-product/bundle-sells-section-title.php',
					array(
						'wrap'  => $bundle_sells_title_proc === $bundle_sells_title,
						'title' => $bundle_sells_title_proc === $bundle_sells_title ? $bundle_sells_title_proc : wpautop( $bundle_sells_title_proc ),
					),
					false,
					WC_PB()->plugin_path() . '/includes/modules/bundle-sells/templates/'
				);
			}

			/**
			 * 'woocommerce_before_bundled_items' action.
			 *
			 * @since 5.8.0
			 * @param  WC_Product_Bundle  $bundle
			 */
			do_action( 'woocommerce_before_bundled_items', $bundle );

			/*
			 * Show Bundle-Sells.
			 */
			?>
			<div class="bundle_form bundle_sells_form">
			<?php

			foreach ( $bundle->get_bundled_items() as $bundled_item ) {
				// Neat, isn't it?
				self::apply_bundled_item_template_overrides();
				/**
				 * 'woocommerce_bundled_item_details' action.
				 *
				 * @since 5.0.0
				 * @param  WC_Product_Bundle_Item  $bundled_item
				 * @param  WC_Product_Bundle       $bundle
				 */
				do_action( 'woocommerce_bundled_item_details', $bundled_item, $bundle );
				self::reset_bundled_item_template_overrides();
			}

			?>
				<div class="bundle_data bundle_data_<?php echo esc_attr( $bundle->get_id() ); ?>" data-bundle_form_data="<?php echo wc_esc_json( wp_json_encode( $bundle->get_bundle_form_data() ) ); ?>" data-bundle_id="<?php echo esc_attr( $bundle->get_id() ); ?>">
					<div class="bundle_wrap">
						<div class="bundle_error" style="display:none">
							<?php
							if ( ! is_admin() && ! WC_PB_Core_Compatibility::is_rest_api_request() ) {
								wc_print_notice( '<ul class="msg"></ul>', 'notice' ); }
							?>
						</div>
					</div>
				</div>
			</div>
			<?php

			/**
			 * 'woocommerce_after_bundled_items' action.
			 *
			 * @since 5.8.0
			 * @param  WC_Product_Bundle  $bundle
			 */
			do_action( 'woocommerce_after_bundled_items', $bundle );
		}
	}

	/**
	 * Filters the default bundled-item template file location for use in bundle-selling context.
	 *
	 * @param  string $template Default template location.
	 * @param  string $template_name Template name.
	 * @param  string $template_args Template args.
	 * @param  string $template_path Template path.
	 * @return string
	 */
	public static function get_bundled_item_template_location( $template, $template_name, $template_args, $template_path ) {

		if ( false === strpos( $template_path, WC_PB()->plugin_path() . '/includes/modules/bundle-sells' ) ) {

			if ( 'single-product/bundled-item-quantity.php' === $template_name ) {

				$template = wc_locate_template( 'single-product/bundle-sell-quantity.php', '', WC_PB()->plugin_path() . '/includes/modules/bundle-sells/templates/' );

			} else {

				/**
				 * 'wc_pb_bundle_sell_template_name' filter.
				 *
				 * Use this to override the PB templates with new ones when used in Bundle-Sells context.
				 *
				 * @since 6.6.0
				 * @param  string  $template_name  Original template name.
				 */
				$template_name_override = apply_filters( 'wc_pb_bundle_sell_template_name', $template_name );

				if ( $template_name_override !== $template_name ) {
					$template = wc_locate_template( $template_name_override, '', WC_PB()->plugin_path() . '/includes/modules/bundle-sells/templates/' );
				}
			}
		}

		return $template;
	}

	/**
	 * Add "Discount applied:" cart item data to bundle sells.
	 *
	 * @param  array $data Default cart item data.
	 * @param  array $cart_item Cart item data.
	 * @return array
	 */
	public static function bundle_sell_data( $data, $cart_item ) {
		$parent_item_key = wc_pb_get_bundle_sell_cart_item_container( $cart_item, false, true );
		if ( $parent_item_key ) {

			if ( ! empty( $cart_item['bundle_sell_discount'] ) ) {

				$parent_item = WC()->cart->cart_contents[ $parent_item_key ];
				/**
				 * Filter the parent item permalink.
				 *
				 * @since  6.6.0
				 *
				 * @param  string  $parent_item_permalink
				 * @param  array   $parent_item
				 * @param  string  $parent_item_key
				 */
				$parent_item_permalink = apply_filters( 'woocommerce_cart_item_permalink', $parent_item['data']->is_visible() ? $parent_item['data']->get_permalink( $parent_item ) : '', $parent_item, $parent_item_key );
				$parent_item_name      = $parent_item['data']->get_title();

				if ( $parent_item_permalink ) {
					/**
					 * Filter the parent item name.
					 *
					 * @since  6.6.0
					 *
					 * @param  string  $parent_item_name
					 * @param  array   $parent_item
					 * @param  string  $parent_item_key
					 */
					$parent_item_name = wp_kses_post( apply_filters( 'woocommerce_bundle_sell_parent_name', sprintf( '<a href="%s">%s</a>', esc_url( $parent_item_permalink ), $parent_item_name ), $parent_item, $parent_item_key ) );
				} else {
					/**
					 * Filter the parent item name.
					 *
					 * @since  6.6.0
					 *
					 * @param  string  $parent_item_name
					 * @param  array   $parent_item
					 * @param  string  $parent_item_key
					 */
					$parent_item_name = wp_kses_post( apply_filters( 'woocommerce_bundle_sell_parent_name', $parent_item_name, $parent_item, $parent_item_key ) );
				}

				/**
				 * Filter bundle-sell discount value.
				 *
				 * @since  6.6.0
				 *
				 * @param  array   $cart_item
				 * @param  array   $parent_item
				 * @param  string  $parent_item_name
				 */
				$bundle_sell_discount = apply_filters(
					'wc_pb_bundle_sell_discount_cart_item_meta_value',
					/* translators: %1$s: Discount % (Use encoded value when translating the % character. Use &#37; instead of %.), %2$s: Product title */
					sprintf( _x( '%1$s&#37; (applied by %2$s)', 'bundle-sell discount', 'woocommerce-product-bundles' ), round( (float) $cart_item['bundle_sell_discount'], 1 ), $parent_item_name ),
					$cart_item,
					$parent_item,
					$parent_item_name
				);

				if ( $bundle_sell_discount ) {

					$data[] = array(
						'key'   => __( 'Discount', 'woocommerce-product-bundles' ),
						'value' => $bundle_sell_discount,
					);
				}
			}
		}

		return $data;
	}
}

WC_PB_BS_Display::init();
