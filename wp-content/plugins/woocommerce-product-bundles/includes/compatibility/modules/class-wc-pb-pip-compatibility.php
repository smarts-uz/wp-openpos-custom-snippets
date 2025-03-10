<?php
/**
 * WC_PB_PIP_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    4.14.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print Invoices & Packing Lists Integration.
 *
 * @version  6.21.0
 */
class WC_PB_PIP_Compatibility {

	/**
	 * The document being processed.
	 *
	 * @var WC_PIP_Document
	 */
	public static $document;

	/**
	 * Flag to control internal flow in 'items_count'.
	 *
	 * @var bool
	 */
	private static $recounting_items = false;

	/**
	 * Add hooks.
	 */
	public static function init() {

		// Fires off before rendering the PIP document body.
		add_action( 'wc_pip_before_body', array( __CLASS__, 'before_body' ), 10, 4 );

		// Fires off after rendering the PIP document body.
		add_action( 'wc_pip_after_body', array( __CLASS__, 'after_body' ), 10, 4 );

		// Filter items count.
		add_filter( 'wc_pip_order_items_count', array( __CLASS__, 'items_count' ), 1000 );

		// Temporarily add order item data to array.
		add_filter( 'wc_pip_document_table_row_item_data', array( __CLASS__, 'row_item_data' ), 10, 5 );

		// Re-sort PIP table rows so that bundled items are always below their container.
		add_filter( 'wc_pip_document_table_rows', array( __CLASS__, 'table_rows' ), 52, 5 );

		// Add 'bundled-product' class to pip row classes.
		add_filter( 'wc_pip_document_table_product_class', array( __CLASS__, 'bundled_item_class' ), 10, 4 );

		// Filter PIP item titles.
		add_filter( 'wc_pip_order_item_name', array( __CLASS__, 'bundled_item_name' ), 10, 6 );

		// Add assembly info to bundled item meta.
		add_action( 'wc_pip_order_item_meta_end', array( __CLASS__, 'add_assembled_order_item_meta' ), 10, 2 );

		// Ensure bundle container line items are always dislpayed.
		add_filter( 'wc_pip_packing-list_hide_virtual_item', array( __CLASS__, 'hide_item' ), 10, 4 );

		// Prevent bundled order items from being sorted/categorized.
		add_filter( 'wc_pip_packing-list_list_group_item_as_uncategorized', array( __CLASS__, 'group_bundled_items_as_uncategorized' ), 10, 3 );

		// Add bundled item class CSS rule.
		add_action( 'wc_pip_styles', array( __CLASS__, 'add_styles' ) );

		if ( class_exists( 'WC_PB_CP_Compatibility' ) ) {
			add_filter( 'wc_pip_order_item_name', array( 'WC_PB_CP_Compatibility', 'composited_bundle_order_table_item_title' ), 9, 2 );
		}
	}

	/**
	 * Rendering a PIP document?
	 *
	 * @since  5.5.0
	 *
	 * @param  string $type Document type.
	 * @return boolean
	 */
	public static function rendering_document( $type = '' ) {
		return ! is_null( self::$document ) && ( '' === $type || $type === self::$document->type );
	}

	/**
	 * Fires off before rendering the PIP document body.
	 *
	 * @since  5.5.0
	 *
	 * @param  string          $type Document type.
	 * @param  string          $action Document action.
	 * @param  WC_PIP_Document $document PIP document.
	 * @param  WC_Order        $order Order object.
	 * @return void
	 */
	public static function before_body( $type, $action, $document, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		self::$document = $document;
		if ( in_array( $document->type, array( 'packing-list' ), true ) ) {
			self::add_filters();
		}
	}

	/**
	 * Fires off before rendering the PIP document body.
	 *
	 * @since  5.5.0
	 *
	 * @param  string          $type Document type.
	 * @param  string          $action Document action.
	 * @param  WC_PIP_Document $document PIP document.
	 * @param  WC_Order        $order Order object.
	 * @return void
	 */
	public static function after_body( $type, $action, $document, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( in_array( $document->type, array( 'packing-list' ), true ) ) {
			self::remove_filters();
		}
		self::$document = null;
	}

	/**
	 * Modify the returned order items and products to return the correct items/weights/values for shipping.
	 *
	 * @since  5.5.0
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_get_items', array( WC_PB()->order, 'get_order_items' ), 10, 2 );
		add_filter( 'woocommerce_order_item_product', array( WC_PB()->order, 'get_product_from_item' ), 10, 2 );
	}

	/**
	 * Remove filters above.
	 *
	 * @since  5.5.0
	 */
	public static function remove_filters() {
		remove_filter( 'woocommerce_order_get_items', array( WC_PB()->order, 'get_order_items' ), 10 );
		remove_filter( 'woocommerce_order_item_product', array( WC_PB()->order, 'get_product_from_item' ), 10 );
	}

	/**
	 * Recounts items excluding bundle containers.
	 *
	 * @param  int $count Items count.
	 * @return int
	 */
	public static function items_count( $count ) {

		if ( false === self::$recounting_items && self::$document ) {
			self::$recounting_items = true;
			$count                  = self::$document->get_items_count();
			self::$recounting_items = false;
		}

		return $count;
	}

	/**
	 * Temporarily add order item data to array.
	 *
	 * @param  array      $item_data Array of item data.
	 * @param  array      $item     Order item.
	 * @param  WC_Product $product  Product object.
	 * @param  string     $order_id     Order ID.
	 * @param  string     $type         Document type.
	 * @return array
	 */
	public static function row_item_data( $item_data, $item, $product, $order_id, $type ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$item_data['wc_pb_item_data'] = $item;
		return $item_data;
	}

	/**
	 * Re-sort PIP table rows so that bundled items are always below their container.
	 *
	 * @param  array           $table_rows Array of table rows.
	 * @param  array           $items Order items.
	 * @param  string          $order_id Order ID.
	 * @param  string          $type Document type.
	 * @param  WC_PIP_Document $pip_document PIP document.
	 * @return array
	 */
	public static function table_rows( $table_rows, $items, $order_id, $type, $pip_document = null ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$order = is_null( $pip_document ) ? wc_get_order( $order_id ) : $pip_document->order;

		$filtered_table_rows = array();

		if ( ! empty( $table_rows ) ) {

			foreach ( $table_rows as $table_row_key => $table_row_data ) {

				$filtered_table_rows[ $table_row_key ] = $table_row_data;

				if ( empty( $table_row_data['items'] ) ) {
					continue;
				}

				$sorted_rows = array();

				foreach ( $table_row_data['items'] as $row_item ) {

					if ( isset( $row_item['wc_pb_item_data'] ) && isset( $row_item['wc_pb_item_data']['bundled_items'] ) ) {

						$show_parent    = true;
						$virtual_parent = false;
						$group_mode     = $row_item['wc_pb_item_data']['bundle_group_mode'];
						$group_mode     = $group_mode ? $group_mode : 'parent';

						// Virtual parent items should be hidden in packing lists when the corresponding PIP option is active.
						if ( self::$document && 'packing-list' === self::$document->type ) {
							if ( 'yes' === $row_item['wc_pb_item_data']['wc_pb_container_item_virtual'] ) {
								$virtual_parent = true;
							}
						}

						// By default, nothing should be hidden in invoices, but here's an exception.
						if ( false === WC_Product_Bundle::group_mode_has( $group_mode, 'parent_item' ) || WC_Product_Bundle::group_mode_has( $group_mode, 'component_multiselect' ) ) {
							$show_parent = false;
						}

						if ( $show_parent ) {

							if ( $virtual_parent ) {
								$row_item['quantity'] = str_replace( 'class="quantity', 'class="quantity virtual-container', $row_item['quantity'] );
								$row_item['weight']   = str_replace( 'class="weight', 'class="weight virtual-container', $row_item['weight'] );
							}

							$sorted_rows[] = $row_item;
						}

						$children = wc_pb_get_bundled_order_items( $row_item['wc_pb_item_data'], $order );

						// Look for its children in all table rows and bring them over in the original order.
						if ( ! empty( $children ) ) {
							foreach ( $children as $child_order_item ) {

								if ( empty( $child_order_item['bundle_cart_key'] ) ) {
									continue;
								}

								// Look for the child in all table rows and bring it over.
								foreach ( $table_rows as $table_row_key_inner => $table_row_data_inner ) {
									foreach ( $table_row_data_inner['items'] as $row_item_inner ) {

										$is_child = false;

										if ( isset( $row_item_inner['wc_pb_item_data'] ) && isset( $row_item_inner['wc_pb_item_data']['bundle_cart_key'] ) ) {
											$is_child = $row_item_inner['wc_pb_item_data']['bundle_cart_key'] === $child_order_item['bundle_cart_key'];
										}

										if ( $is_child ) {

											if ( ! $show_parent ) {
												$row_item_inner['product'] = str_replace( 'bundled-product ', '', $row_item_inner['product'] );
											}

											$sorted_rows[] = $row_item_inner;
										}
									}
								}
							}
						}
					} elseif ( ! isset( $row_item['wc_pb_item_data'] ) || ! isset( $row_item['wc_pb_item_data']['bundled_by'] ) ) {
						$sorted_rows[] = $row_item;
					}
				}

				// Unset our (now redundant) data.
				foreach ( $sorted_rows as $sorted_row_item => $sorted_row_item_data ) {
					if ( isset( $sorted_row_item_data['wc_pb_item_data'] ) ) {
						unset( $sorted_rows[ $sorted_row_item ]['wc_pb_item_data'] );
					}
				}

				$filtered_table_rows[ $table_row_key ]['items'] = $sorted_rows;
			}

			// Ensure empty categories are not displayed at all.
			foreach ( $filtered_table_rows as $table_row_key => $table_row_data ) {
				if ( empty( $table_row_data['items'] ) && isset( $table_row_data['headings'] ) && isset( $table_row_data['headings']['breadcrumbs'] ) ) {
					unset( $filtered_table_rows[ $table_row_key ] );
				}
			}
		}

		return $filtered_table_rows;
	}

	/**
	 * Add component title to order item title.
	 *
	 * @since  5.9.1
	 *
	 * @param  string        $product_name Product name.
	 * @param  WC_Order_Item $order_item Order item.
	 * @param  boolean       $is_visible Is visible.
	 * @param  string        $type Document type.
	 * @param  WC_Product    $product Product object.
	 * @param  WC_Order      $order Order object.
	 * @return string
	 */
	public static function bundled_item_name( $product_name, $order_item, $is_visible, $type, $product, $order ) {

		if ( wc_pb_is_bundled_order_item( $order_item, $order ) ) {
			$overridden_title = $order_item->get_meta( '_bundled_item_title', true );
			if ( $overridden_title ) {

				if ( $is_visible ) {
					$product_name = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', get_permalink( $product->get_id() ), $product_name );
				}
			}
		}

		return $product_name;
	}

	/**
	 * Add 'bundled-product' class to pip row classes.
	 *
	 * @param  array      $classes Array of classes.
	 * @param  WC_Product $product Product object.
	 * @param  array      $item Order item.
	 * @param  string     $type Document type.
	 * @return array
	 */
	public static function bundled_item_class( $classes, $product, $item, $type ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$parent_item = wc_pb_get_bundled_order_item_container( $item );
		if ( $parent_item ) {

			$group_mode = $parent_item->get_meta( '_bundle_group_mode', true );
			$group_mode = $group_mode ? $group_mode : 'parent';

			if ( WC_Product_Bundle::group_mode_has( $group_mode, 'parent_item' ) ) {
				$classes[] = 'bundled-product';
			}
		}

		return $classes;
	}

	/**
	 * Add "assembled" item meta to pick-lists.
	 *
	 * @since  5.5.0
	 *
	 * @param  int           $item_id Order item ID.
	 * @param  WC_Order_Item $item Order item.
	 */
	public static function add_assembled_order_item_meta( $item_id, $item ) {

		if ( self::$document && 'pick-list' === self::$document->type ) {
			$parent_item = wc_pb_get_bundled_order_item_container( $item );
			if ( $parent_item ) {

				// Is it an assembled item?
				if ( 'no' === $item->get_meta( '_bundled_item_needs_shipping', true ) ) {

					$flat = false;

					if ( has_filter( 'wc_pip_document_table_row_item_meta_flat' ) ) {
						$product = $item->get_product();
						/**
						 * Filters whether to display item meta flat.
						 *
						 * @since 5.5.0
						 * @param  boolean    $flat
						 * @param  WC_Product $product
						 * @param  int        $item_id
						 * @param  WC_Order_Item $item
						 * @param  string     $type
						 * @param  WC_Order   $order
						 */
						$flat = apply_filters( 'wc_pip_document_table_row_item_meta_flat', $flat, $product, $item_id, $item, self::$document->type, self::$document->order );
					}

					if ( $flat ) {
						$assembled_item_meta_html = wp_kses_post( __( 'Packaged in:', 'woocommerce-product-bundles' ) . ' ' . wpautop( $parent_item->get_name() ) );
					} else {
						$assembled_item_meta_html = '<dl class="variation assembled"><dt>' . __( 'Packaged in:', 'woocommerce-product-bundles' ) . '</dt><dd>' . $parent_item->get_name() . '</dd></dl>';
					}

					/**
					 * Filters the assembled item meta HTML.
					 *
					 * @since 5.5.0
					 * @param  string     $assembled_item_meta_html
					 * @param  int        $item_id
					 * @param  WC_Order_Item $item
					 * @param  WC_Order_Item $parent_item
					 */
					echo wp_kses_post( apply_filters( 'wc_pip_pick-list_order_item_meta_assembled_in_bundle', $assembled_item_meta_html, $item_id, $item, $parent_item ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				}
			}
		}
	}

	/**
	 * Ensure bundle container line items are always displayed, otherwise we will not be able to collect their children in 'table_rows'.
	 *
	 * @param  boolean    $hide Whether to hide the item.
	 * @param  WC_Product $product Product object.
	 * @param  array      $order_item Order item.
	 * @param  WC_Order   $order Order object.
	 * @return boolean
	 */
	public static function hide_item( $hide, $product, $order_item, $order ) {

		if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {

			$product = wc_get_product( $order_item->get_product_id() );

			if ( ! $product->needs_shipping() ) {

				if ( self::$recounting_items ) {
					$hide = true;
				} else {
					$hide = false;
				}

				$order_item->add_meta_data( '_wc_pb_container_item_virtual', 'yes', true );
			}
		} elseif ( wc_pb_is_bundled_order_item( $order_item, $order ) ) {
			if ( self::$document && 'packing-list' === self::$document->type && 'no' === $order_item->get_meta( '_bundled_item_needs_shipping', true ) ) {
				$hide = true;
			}
		}

		return $hide;
	}

	/**
	 * Prevent bundled order items from being sorted/categorized.
	 *
	 * @param  boolean  $uncategorize Whether to uncategorize the item.
	 * @param  array    $order_item Order item.
	 * @param  WC_Order $order Order object.
	 * @return boolean
	 */
	public static function group_bundled_items_as_uncategorized( $uncategorize, $order_item, $order ) {

		if ( wc_pb_is_bundled_order_item( $order_item, $order ) ) {

			$parent_item = wc_pb_get_bundled_order_item_container( $order_item, $order );

			$group_mode = $parent_item['bundle_group_mode'];
			$group_mode = $group_mode ? $group_mode : 'parent';

			if ( WC_Product_Bundle::group_mode_has( $group_mode, 'parent_item' ) ) {
				$uncategorize = true;
			}
		}

		return $uncategorize;
	}

	/**
	 * Add bundled item class CSS rule.
	 *
	 * @return  void
	 */
	public static function add_styles() {
		?>
		.quantity .virtual-container, .weight .virtual-container {
			display: none;
		}
		.quantity .assembled, .weight .assembled {
			display: none;
		}
		.product .bundled-product {
			padding-left: 2.5em;
		}
		.bundled-product-subtotal {
			font-size: 0.875em;
			padding-right: 2em;
			display: block;
		}
		.product-bundle.product-meta dl {
			margin-top: 0.5em;
		}
		.bundled-product dl.variation.assembled {
			margin-top: 0.5em;
		}
		<?php
	}
}

WC_PB_PIP_Compatibility::init();
