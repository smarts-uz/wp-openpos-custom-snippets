<?php
/**
 * WC_PB_MMI_Display class
 *
 * @package  WooCommerce Product Bundles
 * @since    6.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display-related functions and filters.
 *
 * @class    WC_PB_MMI_Display
 * @version  6.22.6
 */
class WC_PB_MMI_Display {

	/**
	 * Setup hooks.
	 */
	public static function init() {

		// Validation script.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );

		// Add the MMI script as a dependency to the main Bundle/Composite script to ensure that `on` handlers are added before any `trigger` actions of the main script.
		add_filter( 'woocommerce_pb_script_dependencies', array( __CLASS__, 'add_script_dependency' ) );
		add_filter( 'woocommerce_composite_script_dependencies', array( __CLASS__, 'add_script_dependency' ) );

		// Add min/max data to template for use by validation script.
		add_filter( 'woocommerce_bundle_price_data', array( __CLASS__, 'script_data' ), 10, 2 );
	}

	/*
	|--------------------------------------------------------------------------
	| Filter/action hooks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Validation script.
	 */
	public static function enqueue_script() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-pb-min-max-items-add-to-cart', WC_PB()->plugin_url() . '/assets/js/frontend/add-to-cart-bundle-min-max-items' . $suffix . '.js', array( 'jquery', 'wc-add-to-cart-variation' ), WC_PB()->version, true );
		wp_script_add_data( 'wc-pb-min-max-items-add-to-cart', 'strategy', 'defer' );

		$params = array(
			'i18n_min_zero_max_qty_error_singular' => __( 'Please choose an item.', 'woocommerce-product-bundles' ),
			/* translators: Details */
			'i18n_min_max_qty_error_singular'      => sprintf( __( 'Please choose 1 item.%s', 'woocommerce-product-bundles' ), '' ),
			/* translators: Details */
			'i18n_min_qty_error_singular'          => sprintf( __( 'Please choose at least 1 item.%s', 'woocommerce-product-bundles' ), '' ),
			/* translators: Details */
			'i18n_max_qty_error_singular'          => sprintf( __( 'Please choose up to 1 item.%s', 'woocommerce-product-bundles' ), '' ),
			/* translators: %1$s: Item count, %2$s: Details */
			'i18n_min_qty_error_plural'            => sprintf( __( 'Please choose at least %1$s items.%2$s', 'woocommerce-product-bundles' ), '%q', '' ),
			/* translators: %1$s: Item count, %2$s: Details */
			'i18n_max_qty_error_plural'            => sprintf( __( 'Please choose up to %1$s items.%2$s', 'woocommerce-product-bundles' ), '%q', '' ),
			/* translators: %1$s: Item count, %2$s: Details */
			'i18n_min_max_qty_error_plural'        => sprintf( __( 'Please choose %1$s items.%2$s', 'woocommerce-product-bundles' ), '%q', '' ),
			/* translators: Item count */
			'i18n_qty_error_plural'                => __( '%s items selected', 'woocommerce-product-bundles' ),
			'i18n_qty_error_singular'              => __( '1 item selected', 'woocommerce-product-bundles' ),
			/* translators: Status */
			'i18n_qty_error_status_format'         => _x( '<span class="bundled_items_selection_status">%s</span>', 'validation error status format', 'woocommerce-product-bundles' ), // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
		);

		wp_localize_script( 'wc-pb-min-max-items-add-to-cart', 'wc_pb_min_max_items_params', $params );
	}

	/**
	 * Add the MMI script as a dependency to the main Bundle/Composite script.
	 *
	 * @param array $dependencies Script dependencies.
	 */
	public static function add_script_dependency( $dependencies ) {
		$dependencies[] = 'wc-pb-min-max-items-add-to-cart';
		return $dependencies;
	}

	/**
	 * Pass min/max container values to the single-product script.
	 *
	 * @param  array             $data  Script data.
	 * @param  WC_Product_Bundle $product Product.
	 * @return array
	 */
	public static function script_data( $data, $product ) {

		$min = $product->get_min_bundle_size();
		$max = $product->get_max_bundle_size();

		if ( '' !== $min || '' !== $max ) {
			$data['size_min'] = $min;
			$data['size_max'] = $max;
		}

		return $data;
	}
}

WC_PB_MMI_Display::init();
