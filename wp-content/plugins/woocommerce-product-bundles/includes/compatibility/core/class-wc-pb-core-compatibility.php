<?php
/**
 * WC_PB_Core_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    4.7.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions for WC core back-compatibility.
 *
 * @class    WC_PB_Core_Compatibility
 * @version  8.2.0
 */
class WC_PB_Core_Compatibility {

	/**
	 * Cache 'gte' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gte = array();

	/**
	 * Cache 'gt' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gt = array();

	/**
	 * Cache 'gt' comparison results for WP version.
	 *
	 * @since  5.13.3
	 * @var    array
	 */
	private static $is_wp_version_gt = array();

	/**
	 * Cache 'gte' comparison results for WP version.
	 *
	 * @since  5.13.3
	 * @var    array
	 */
	private static $is_wp_version_gte = array();

	/**
	 * Cache 'is_wc_admin_active' result.
	 *
	 * @since 6.3.1
	 * @var   bool
	 */
	private static $is_wc_admin_active;

	/**
	 * Current REST request stack.
	 * An array containing WP_REST_Request instances.
	 *
	 * @since 7.1.0
	 *
	 * @var array
	 */
	private static $requests = array();

	/**
	 * Cache HPOS status.
	 *
	 * @since  6.18.3
	 *
	 * @var bool
	 */
	private static $is_hpos_enabled = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		// Save current rest request. Is there a better way to get it?
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'save_rest_request' ), 10, 3 );
		add_filter( 'woocommerce_hydration_dispatch_request', array( __CLASS__, 'save_hydration_request' ), 10, 2 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );
		add_filter( 'woocommerce_hydration_request_after_callbacks', array( __CLASS__, 'pop_rest_request' ), PHP_INT_MAX );

		// Refactoring of Analytics in WC 9.3 deprecated Query class (https://github.com/woocommerce/woocommerce/pull/49425).
		if ( version_compare( WC_VERSION, '9.3', '<' ) && ! class_exists( 'Automattic\WooCommerce\Admin\API\Reports\GenericQuery' ) ) {
			class_alias( 'Automattic\WooCommerce\Admin\API\Reports\Query', 'Automattic\WooCommerce\Admin\API\Reports\GenericQuery' );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Callbacks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Pops the current request from the execution stack.
	 *
	 * @since  7.1.0
	 *
	 * @param  WP_REST_Response $response The response object.
	 * @return mixed
	 */
	public static function pop_rest_request( $response ) {
		if ( ! empty( self::$requests ) && is_array( self::$requests ) ) {
			array_pop( self::$requests );
		}

		return $response;
	}

	/**
	 * Saves the current hydration request.
	 *
	 * @since  7.1.0
	 *
	 * @param  mixed           $result The hydration result.
	 * @param  WP_REST_Request $request The request object.
	 * @return mixed
	 */
	public static function save_hydration_request( $result, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/**
	 * Saves the current rest request.
	 *
	 * @since  6.15.0
	 *
	 * @param  mixed           $result The result.
	 * @param  WP_REST_Server  $server The REST server.
	 * @param  WP_REST_Request $request The request object.
	 * @return mixed
	 */
	public static function save_rest_request( $result, $server, $request ) {
		if ( ! is_array( self::$requests ) ) {
			self::$requests = array();
		}

		self::$requests[] = $request;
		return $result;
	}

	/*
	|--------------------------------------------------------------------------
	| Utilities.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @since  4.7.6
	 *
	 * @return string
	 */
	private static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @since  5.2.0
	 *
	 * @param  string $version The version to compare.
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gte( $version ) {
		if ( ! isset( self::$is_wc_version_gte[ $version ] ) ) {
			self::$is_wc_version_gte[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}
		return self::$is_wc_version_gte[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @since  4.7.6
	 *
	 * @param  string $version The version to compare.
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @since  5.13.3
	 *
	 * @param  string $version The version to compare.
	 * @return boolean
	 */
	public static function is_wp_version_gt( $version ) {
		if ( ! isset( self::$is_wp_version_gt[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gt[ $version ] = $wp_version && version_compare( WC_PB()->plugin_version( true, $wp_version ), $version, '>' );
		}
		return self::$is_wp_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @since  5.13.3
	 *
	 * @param  string $version  The version to compare.
	 * @return boolean
	 */
	public static function is_wp_version_gte( $version ) {
		if ( ! isset( self::$is_wp_version_gte[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gte[ $version ] = $wp_version && version_compare( WC_PB()->plugin_version( true, $wp_version ), $version, '>=' );
		}
		return self::$is_wp_version_gte[ $version ];
	}

	/**
	 * Whether this is a Store/REST API request.
	 *
	 * @since  6.15.0
	 *
	 * @return boolean
	 */
	public static function is_api_request() {
		return self::is_store_api_request() || self::is_rest_api_request();
	}

	/**
	 * Returns the current Store/REST API request or false.
	 *
	 * @since  6.15.0
	 *
	 * @return WP_REST_Request|false
	 */
	public static function get_api_request() {
		if ( empty( self::$requests ) || ! is_array( self::$requests ) ) {
			return false;
		}

		return end( self::$requests );
	}

	/**
	 * Whether this is a Store API request.
	 *
	 * @since  6.15.0
	 *
	 * @param  string $route The route to check.
	 * @return boolean
	 */
	public static function is_store_api_request( $route = '' ) {

		// Check the request URI.
		$request = self::get_api_request();

		if ( false !== $request && strpos( $request->get_route(), 'wc/store' ) !== false ) {
			if ( '' === $route || strpos( $request->get_route(), $route ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/*
	|--------------------------------------------------------------------------
	| Compatibility wrappers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Backwards compatible logging using 'WC_Logger' class.
	 *
	 * @since  5.2.0
	 *
	 * @param  string $message The message to log.
	 * @param  string $level  The log level.
	 * @param  string $context The log context.
	 */
	public static function log( $message, $level, $context ) {

		// Limit some types of logging to staging/dev environments only.
		/**
		 * Filter the contexts that should be logged in debug mode.
		 *
		 * @since 5.2.0
		 *
		 * @param array $contexts The contexts to log.
		 */
		if ( in_array( $context, apply_filters( 'woocommerce_bundles_debug_log_contexts', array( 'wc_pb_db_sync_tasks' ) ), true ) ) {
			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
				return;
			}
		}

		$logger = wc_get_logger();
		$logger->log( $level, $message, array( 'source' => $context ) );
	}

	/**
	 * Back-compat wrapper for 'is_rest_api_request'.
	 *
	 * @since  5.11.1
	 *
	 * @return boolean
	 */
	public static function is_rest_api_request() {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) || false === strpos( wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ), rest_get_url_prefix() ) ) {
			return false;
		}

		if ( false !== self::get_api_request() ) {
			return true;
		}

		return method_exists( WC(), 'is_rest_api_request' ) ? WC()->is_rest_api_request() : defined( 'REST_REQUEST' );
	}

	/**
	 *
	 * Whether this is a Store Editor REST API request.
	 *
	 * @since  6.22.3
	 *
	 * @param  string $route The route to check.
	 * @return boolean
	 */
	public static function is_block_editor_api_request( $route = '' ) {

		if ( ! self::is_rest_api_request() ) {
			return false;
		}

		$request = self::get_api_request();

		if ( false !== $request && strpos( $request->get_route(), '/pages/' ) !== false ) {
			if ( '' === $route || strpos( $request->get_route(), $route ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * True if 'wc-admin' is active.
	 *
	 * @since  6.3.1
	 *
	 * @return boolean
	 */
	public static function is_wc_admin_active() {

		if ( ! isset( self::$is_wc_admin_active ) ) {

			$enabled = defined( 'WC_ADMIN_VERSION_NUMBER' ) && version_compare( WC_ADMIN_VERSION_NUMBER, '1.0.0', '>=' );
			/**
			 * Filter to disable WooCommerce Admin.
			 *
			 * @since 6.3.1
			 *
			 * @param bool $enabled True if WooCommerce Admin is enabled.
			 */
			if ( $enabled && version_compare( WC_ADMIN_VERSION_NUMBER, '2.3.0', '>=' ) && true === apply_filters( 'woocommerce_admin_disabled', false ) ) {
				$enabled = false;
			}

			self::$is_wc_admin_active = $enabled;
		}

		return self::$is_wc_admin_active;
	}

	/**
	 * Returns true if is a react based admin page.
	 *
	 * @since  6.15.3
	 *
	 * @return boolean
	 */
	public static function is_admin_or_embed_page() {

		if ( class_exists( '\Automattic\WooCommerce\Admin\PageController' ) && method_exists( '\Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ) {

			return \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page();

		} elseif ( class_exists( '\Automattic\WooCommerce\Admin\Loader' ) && method_exists( '\Automattic\WooCommerce\Admin\Loader', 'is_admin_or_embed_page' ) ) {

			return \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page();
		}

		return false;
	}

	/**
	 * Returns true if site is using block theme.
	 *
	 * @since  6.19.0
	 *
	 * @return boolean
	 */
	public static function wc_current_theme_is_fse_theme() {
		return function_exists( 'wc_current_theme_is_fse_theme' ) ? wc_current_theme_is_fse_theme() : false;
	}

	/**
	 * Check if the usage of the custom orders table is enabled.
	 *
	 * @since  6.18.3
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled() {

		if ( ! isset( self::$is_hpos_enabled ) ) {
			self::$is_hpos_enabled = class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		return self::$is_hpos_enabled;
	}
}

WC_PB_Core_Compatibility::init();
