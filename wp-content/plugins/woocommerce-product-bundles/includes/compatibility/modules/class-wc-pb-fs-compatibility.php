<?php
/**
 * WC_PB_FS_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    6.3.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flatsome integration.
 *
 * @version  6.22.6
 */
class WC_PB_FS_Compatibility {

	/**
	 * Init compatibility.
	 *
	 * @return void
	 */
	public static function init() {
		// Add hooks if the active parent theme is Flatsome.
		add_action( 'after_setup_theme', array( __CLASS__, 'maybe_add_hooks' ) );
	}

	/**
	 * Add hooks if the active parent theme is Flatsome.
	 */
	public static function maybe_add_hooks() {

		if ( function_exists( 'flatsome_quickview' ) ) {
			// Initialize bundles in quick view modals.
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_quickview_integration' ), 999 );
			// Resolves image update mixups in quickview modals.
			add_filter( 'woocommerce_bundled_product_gallery_classes', array( __CLASS__, 'bundled_product_gallery_classes' ) );
			// Lowers the responsive styling breakpoint to prevent issues in quickview modals.
			add_filter( 'woocommerce_bundle_front_end_params', array( __CLASS__, 'adjust_responsive_breakpoint' ), 10 );
		}
	}

	/**
	 * Initializes bundles in quick view modals.
	 *
	 * @return void
	 */
	public static function add_quickview_integration() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wc-bundle-css' );
		wp_enqueue_script( 'wc-add-to-cart-bundle' );

		wp_register_script( 'wc-bundle-flatsome-quickview', WC_PB()->plugin_url() . '/assets/js/frontend/integrations/bundle-flatsome-quickview' . $suffix . '.js', array( 'jquery', 'wc-add-to-cart-variation', 'wc-add-to-cart-bundle' ), WC_PB()->version, true );
		wp_script_add_data( 'wc-bundle-flatsome-quickview', 'strategy', 'defer' );
		wp_enqueue_script( 'wc-bundle-flatsome-quickview' );
	}

	/**
	 * Lower the responsive styling breakpoint for Flatsome.
	 *
	 * @param  array $params Frontend params.
	 * @return array
	 */
	public static function adjust_responsive_breakpoint( $params ) {
		$params['responsive_breakpoint'] = 320;
		return $params;
	}

	/**
	 * Resolve image update mixups in quickview modals.
	 *
	 * @param  WC_Bundled_Item $bundled_item Bundled item.
	 * @return array
	 */
	public static function bundled_product_gallery_classes( $bundled_item ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return array( 'bundled_product_images' );
	}
}

WC_PB_FS_Compatibility::init();
