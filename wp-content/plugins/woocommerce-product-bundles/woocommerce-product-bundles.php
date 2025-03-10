<?php
/**
 * Plugin Name: WooCommerce Product Bundles
 * Plugin URI: https://woocommerce.com/products/product-bundles/
 * Description: Offer product bundles, bulk discount packages, and assembled products.
 * Version: 8.3.2
 * Author: Woo
 * Author URI: https://woocommerce.com/
 *
 * Woo: 18716:fbca839929aaddc78797a5b511c14da9
 *
 * Text Domain: woocommerce-product-bundles
 * Domain Path: /languages/
 *
 * Requires PHP: 7.4
 *
 * Requires at least: 6.2
 * Tested up to: 6.7
 *
 * WC requires at least: 8.2
 * WC tested up to: 9.6
 *
 * Requires Plugins: woocommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce Product Bundles
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * @class    WC_Bundles
 * @version  8.3.2
 */
class WC_Bundles {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '8.3.2';

	/**
	 * Minimum WooCommerce version required.
	 *
	 * @var string
	 */
	public $required = '8.2.0';

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Bundles
	 *
	 * @since 4.11.4
	 */
	protected static $_instance = null; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Product data object.
	 *
	 * @var WC_PB_Product_Data
	 *
	 * @since 7.0.0
	 */
	public $product_data;

	/**
	 * Main WC_Bundles instance. Ensures only one instance of WC_Bundles is loaded or can be loaded - @see 'WC_PB()'.
	 *
	 * @static
	 * @return WC_Bundles
	 * @since  4.11.4
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 4.11.4
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-product-bundles' ), '4.11.4' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 4.11.4
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-product-bundles' ), '4.11.4' );
	}

	/**
	 * Make stuff.
	 */
	protected function __construct() {
		// Entry point.
		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ), 9 );
	}

	/**
	 * Auto-load in-accessible properties.
	 *
	 * @param  mixed $key Key.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'compatibility', 'modules', 'cart', 'order', 'display' ), true ) ) {
			$classname = 'WC_PB_' . ucfirst( $key );
			return call_user_func( array( $classname, 'instance' ) );
		}
	}

	/**
	 * Plugin URL getter.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin path getter.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Indicates whether the plugin has been fully initialized.
	 *
	 * @since  6.2.0
	 *
	 * @return boolean
	 */
	public function plugin_initialized() {
		return class_exists( 'WC_PB_Helpers' );
	}

	/**
	 * Define constants if not present.
	 *
	 * @since  6.2.0
	 *
	 * @param string $name Constant name.
	 * @param mixed  $value Constant value.
	 * @return void
	 */
	protected function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Plugin version getter.
	 *
	 * @since  5.8.0
	 *
	 * @param  boolean $base   Whether to return the base version.
	 * @param  string  $version Version string.
	 * @return string
	 */
	public function plugin_version( $base = false, $version = '' ) {

		$version = $version ? $version : $this->version;

		if ( $base ) {
			$version_parts = explode( '-', $version );
			$version       = count( $version_parts ) > 1 ? $version_parts[0] : $version;
		}

		return $version;
	}

	/**
	 * Fire in the hole!
	 */
	public function initialize_plugin() {

		$this->define_constants();
		$this->maybe_create_store();

		// WC version sanity check.
		if ( ! function_exists( 'WC' ) || version_compare( WC()->version, $this->required ) < 0 ) {
			/* translators: Version */
			$notice = sprintf( __( 'WooCommerce Product Bundles requires at least WooCommerce <strong>%s</strong>.', 'woocommerce-product-bundles' ), $this->required );
			require_once WC_PB_ABSPATH . 'includes/admin/class-wc-pb-admin-notices.php';
			WC_PB_Admin_Notices::add_notice( $notice, 'error' );

			return false;
		}

		// PHP version check.
		if ( ! function_exists( 'phpversion' ) || version_compare( phpversion(), '7.4.0', '<' ) ) {
			$notice = sprintf(
				/* translators: %1$s: Version %, %2$s: Update PHP doc URL */
				__(
					'WooCommerce Product Bundles requires at least PHP <strong>%1$s</strong>. Learn <a href="%2$s">how to update PHP</a>.',
					'woocommerce-product-bundles'
				),
				'7.4.0',
				$this->get_resource_url( 'update-php' )
			);
			require_once WC_PB_ABSPATH . 'includes/admin/class-wc-pb-admin-notices.php';
			WC_PB_Admin_Notices::add_notice( $notice, 'error' );
		}

		$this->includes();

		WC_PB_Compatibility::instance();
		WC_PB_Modules::instance();

		WC_PB_Cart::instance();
		$this->modules->load_components( 'cart' );

		WC_PB_Order::instance();
		$this->modules->load_components( 'order' );

		WC_PB_Display::instance();
		$this->modules->load_components( 'display' );

		// Load translations hook.
		add_action( 'init', array( $this, 'load_translation' ) );
	}

	/**
	 * Constants.
	 */
	public function define_constants() {

		$this->maybe_define_constant( 'WC_PB_VERSION', $this->version );
		$this->maybe_define_constant( 'WC_PB_SUPPORT_URL', 'https://woocommerce.com/my-account/marketplace-ticket-form/' );
		$this->maybe_define_constant( 'WC_PB_ABSPATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		/*
		 * Available debug constants:
		 *
		 * 'WC_PB_DEBUG_STOCK_CACHE' - Used to disable bundled item stock caching.
		 *
		 * 'WC_PB_DEBUG_STOCK_SYNC' - Used to disable bundled item stock syncing in the background.
		 *
		 * 'WC_PB_DEBUG_STOCK_PARENT_SYNC' - Used to disable stock status and visibility syncing for bundle containers.
		 *
		 * 'WC_PB_DEBUG_TRANSIENTS' - Used to disable transients caching.
		 *
		 * 'WC_PB_DEBUG_OBJECT_CACHE' - Used to disable object caching.
		 *
		 * 'WC_PB_DEBUG_RUNTIME_CACHE' - Used to disable runtime object caching.
		 */

		if ( defined( 'WC_PB_DEBUG_STOCK_CACHE' ) ) {
			/**
			 * 'WC_PB_DEBUG_STOCK_SYNC' constant.
			 *
			 * Used to disable bundled product stock meta syncing for bundled items.
			 */
			$this->maybe_define_constant( 'WC_PB_DEBUG_STOCK_SYNC', true );
		}

		if ( defined( 'WC_PB_DEBUG_STOCK_SYNC' ) || ! function_exists( 'WC' ) || version_compare( WC()->version, '3.3.0' ) < 0 ) {
			/**
			 * 'WC_PB_DEBUG_STOCK_PARENT_SYNC' constant.
			 *
			 * Used to disable stock status and visibility syncing for bundles.
			 * Requires the 'WC_Background_Process' class introduced in WC 3.3.
			 */
			$this->maybe_define_constant( 'WC_PB_DEBUG_STOCK_PARENT_SYNC', true );
		}
	}

	/**
	 * A simple dumb datastore for sharing information accross our plugins.
	 *
	 * @since  6.3.0
	 *
	 * @return void
	 */
	private function maybe_create_store() {
		if ( ! isset( $GLOBALS['sw_store'] ) ) {
			$GLOBALS['sw_store'] = array();
		}
	}

	/**
	 * Includes.
	 */
	public function includes() {

		// Product data.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-product-data.php';

		$this->product_data = WC_PB_Product_Data::instance();

		// Extensions compatibility functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/compatibility/class-wc-pb-compatibility.php';

		// Modules.
		require_once WC_PB_ABSPATH . 'includes/modules/class-wc-pb-modules.php';

		// Data classes.
		require_once WC_PB_ABSPATH . 'includes/data/class-wc-pb-data.php';

		// Install.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-install.php';

		// Functions.
		require_once WC_PB_ABSPATH . 'includes/wc-pb-functions.php';

		// Helper functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-helpers.php';

		// Data syncing between products and bundled items.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-db-sync.php';

		// Product price filters and price-related functions.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-product-prices.php';

		// Bundled Item class.
		require_once WC_PB_ABSPATH . 'includes/class-wc-bundled-item.php';

		// Product Bundle class.
		require_once WC_PB_ABSPATH . 'includes/class-wc-product-bundle.php';

		// Stock mgr class.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-stock-manager.php';

		// Cart-related functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-cart.php';

		// Order-related functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-order.php';

		// Order-again functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-order-again.php';

		// Coupon-related functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-coupon.php';

		// Front-end filters and templates.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-display.php';

		// Front-end AJAX handlers.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-ajax.php';

		// REST API hooks.
		require_once WC_PB_ABSPATH . 'includes/api/class-wc-pb-rest-api.php';

		// Notices handling.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-notices.php';

		// Stock notifications handling.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-stock-notifications.php';

		// Admin includes.
		if ( is_admin() ) {
			$this->admin_includes();
		}

		// Analytics.
		require_once WC_PB_ABSPATH . 'includes/admin/analytics/class-wc-pb-admin-analytics.php';

		// Tracking.
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-tracker.php';
		require_once WC_PB_ABSPATH . 'includes/class-wc-pb-tracks.php';

		// WP-CLI includes.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WC_PB_ABSPATH . 'includes/class-wc-pb-cli.php';
		}
	}

	/**
	 * Admin & AJAX functions and hooks.
	 */
	public function admin_includes() {

		// Admin notices handling.
		require_once WC_PB_ABSPATH . 'includes/admin/class-wc-pb-admin-notices.php';

		// Admin functions and hooks.
		require_once WC_PB_ABSPATH . 'includes/admin/class-wc-pb-admin.php';
	}

	/**
	 * Load textdomain.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'woocommerce-product-bundles', false, dirname( $this->plugin_basename() ) . '/languages/' );
		// Subscribe to automated translations.
		add_filter( 'woocommerce_translations_updates_for_' . basename( __FILE__, '.php' ), '__return_true' );
	}

	/**
	 * Returns URL to a doc or support resource.
	 *
	 * @since  6.3.0
	 *
	 * @param  string $handle Resource handle.
	 * @return string
	 */
	public function get_resource_url( $handle ) {

		$resource = false;

		if ( 'pricing-options' === $handle ) {
			$resource = 'https://woocommerce.com/document/bundles/bundles-configuration/#pricing';
		} elseif ( 'shipping-options' === $handle ) {
			$resource = 'https://woocommerce.com/document/bundles/bundles-configuration/#shipping';
		} elseif ( 'update-php' === $handle ) {
			$resource = 'https://woocommerce.com/document/how-to-update-your-php-version/';
		} elseif ( 'docs-contents' === $handle ) {
			$resource = 'https://woocommerce.com/document/bundles/';
		} elseif ( 'max-input-vars' === $handle ) {
			$resource = 'https://woocommerce.com/document/bundles/bundles-faq/#faq_bundled_items_dont_save';
		} elseif ( 'updating' === $handle ) {
			$resource = 'https://woocommerce.com/document/how-to-update-woocommerce/';
		} elseif ( 'min-max' === $handle ) {
			$resource = 'https://wordpress.org/plugins/product-bundles-minmax-items-for-woocommerce/';
		} elseif ( 'bulk-discounts' === $handle ) {
			$resource = 'https://wordpress.org/plugins/product-bundles-bulk-discounts-for-woocommerce/';
		} elseif ( 'analytics-revenue' === $handle ) {
			$resource = 'https://woocommerce.com/document/bundles/bundles-configuration/#pb-analytics';
		} elseif ( 'ticket-form' === $handle ) {
			$resource = WC_PB_SUPPORT_URL;
		}

		return $resource;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @since 6.15.0
	 *
	 * @param  string $file File path.
	 * @return string
	 */
	public function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return $this->plugin_version();
	}

	/**
	 * Handle plugin activation process.
	 *
	 * @since  6.16.0
	 *
	 * @return void
	 */
	public function on_activation() {
		// Add daily maintenance process.
		if ( ! wp_next_scheduled( 'wc_pb_daily' ) ) {
			wp_schedule_event( time() + 10, 'daily', 'wc_pb_daily' );
		}

		// Add hourly maintenance process.
		if ( ! wp_next_scheduled( 'wc_pb_hourly' ) ) {
			wp_schedule_event( time() + 10, 'hourly', 'wc_pb_hourly' );
		}
	}

	/**
	 * Handle plugin deactivation process.
	 *
	 * @since  1.12.0
	 *
	 * @return void
	 */
	public function on_deactivation() {
		// Clear daily maintenance process.
		wp_clear_scheduled_hook( 'wc_pb_daily' );
		wp_clear_scheduled_hook( 'wc_pb_hourly' );
	}
}

/**
 * Returns the main instance of WC_Bundles to prevent the need to use globals.
 *
 * @since  4.11.4
 * @return WC_Bundles
 */
function WC_PB() { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WC_Bundles::instance();
}

$GLOBALS['woocommerce_bundles'] = WC_PB();

register_activation_hook( __FILE__, array( WC_PB(), 'on_activation' ) );
register_deactivation_hook( __FILE__, array( WC_PB(), 'on_deactivation' ) );
