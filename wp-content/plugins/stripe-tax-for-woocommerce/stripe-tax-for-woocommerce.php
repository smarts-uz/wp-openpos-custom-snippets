<?php
/**
 * Plugin Name: Stripe Tax - Sales tax automation for WooCommerce
 * Description: Automate sales tax, VAT, and GST compliance for all your transactions.
 * Version: 1.1.9
 * Text Domain: stripe-tax-for-woocommerce
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License: Expat
 * Tested up to: 6.7
 *
 * @package Stripe\StripeTaxForWooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

// Register autoload for "\Stripe\StripeTaxForWooCommerce" namespace.
spl_autoload_register(
	function ( $class_name ) {
		$namespace        = 'Stripe\\StripeTaxForWooCommerce\\';
		$namespace_length = strlen( $namespace );
		$namespace_found  = ( strpos( $class_name, $namespace ) === 0 );
		if ( $namespace_found ) {
			$path_without_namespace = substr( $class_name, $namespace_length );
			if ( strpos( $class_name, $namespace . 'SDK\\lib\\' ) === 0 ) {
				$path_without_namespace = str_replace( '\\', DIRECTORY_SEPARATOR, $path_without_namespace );
				$full_path              = __DIR__ . DIRECTORY_SEPARATOR . $path_without_namespace . '.php';
			} else {
				$exact_class_name_position                   = strrpos( $path_without_namespace, '\\' ) + 1;
				$exact_class_name                            = substr( $path_without_namespace, $exact_class_name_position );
				$path_without_namespace_and_exact_class_name = substr( $path_without_namespace, 0, $exact_class_name_position );
				$path_without_namespace_and_exact_class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $path_without_namespace_and_exact_class_name );
				$full_path                                   = __DIR__ . DIRECTORY_SEPARATOR . $path_without_namespace_and_exact_class_name . 'class-' . strtolower( $exact_class_name ) . '.php';
			}
			include $full_path;
		}
	}
);

use Stripe\StripeTaxForWooCommerce\SDK\lib\Stripe;
use Stripe\StripeTaxForWooCommerce\WordPress\Hooks;
use Stripe\StripeTaxForWooCommerce\WordPress\PluginActivate;

global $wpdb;

define( 'STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX', "{$wpdb->prefix}stripe_tax_for_wc_" );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_DIR', __DIR__ . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_URL', plugin_dir_url( __FILE__ ) );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_DIR . 'assets' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_URL', STRIPE_TAX_FOR_WOOCOMMERCE_URL . 'assets/' );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_CSS_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_DIR . 'css' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_CSS_URL', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_URL . 'css/' );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_JS_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_DIR . 'js' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_JS_URL', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_URL . 'js/' );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_JSON_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_DIR . 'json' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_DIR . 'img' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_URL', STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_URL . 'img/' );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_DIR . 'templates' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_PARTS_DIR', STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_DIR . 'parts' . DIRECTORY_SEPARATOR );
define( 'STRIPE_TAX_FOR_WOOCOMMERCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

register_activation_hook( __FILE__, array( PluginActivate::class, 'trigger_activate' ) );

add_action( 'plugins_loaded', array( Hooks::class, 'init' ) );
add_action( 'init', array( Hooks::class, 'set_app_info' ) );
