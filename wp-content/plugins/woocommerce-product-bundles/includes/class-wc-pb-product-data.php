<?php
/**
 * WC_PB_Product_Data
 *
 * @package  WooCommerce Product Bundles
 * @since    7.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product data parallel structure for storing product properties.
 *
 * @class    WC_PB_Product_Data
 * @version  7.0.1
 */
class WC_PB_Product_Data {

	/**
	 * The single instance of the class.
	 *
	 * @var WC_PB_Product_Data
	 *
	 * @since 7.0.0
	 */
	protected static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * The instance's data.
	 *
	 * @var array
	 *
	 * @since 7.0.0
	 */
	protected $data = array();

	/**
	 * Main WC_PB_Product_Data Instance.
	 *
	 * Ensures only one instance of WC_PB_Product_Data is loaded or can be loaded.
	 *
	 * @static
	 * @return WC_PB_Product_Data - Main instance
	 * @since 7.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Overriding the constructor with a private one prevents calling it directly.
	 *
	 * @since 7.0.0
	 */
	private function __construct() {
		// Nothing is needed here.
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-product-bundles' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-product-bundles' ), '1.0.0' );
	}

	/**
	 * Gets product data.
	 *
	 * @since 7.0.0
	 *
	 * @param WC_Product  $product - the product object.
	 * @param string      $key - the data key.
	 * @param null|string $default - the default value.
	 *
	 * @return string
	 */
	public function get( $product, $key, $default = null ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return $default;
		}

		$identifier = spl_object_hash( $product ) . $product->get_id();

		if ( ! isset( $this->data[ $identifier ] ) ) {
			return $default;
		}

		if ( ! isset( $this->data[ $identifier ][ $key ] ) ) {
			return $default;
		}

		return $this->data[ $identifier ][ $key ];
	}

	/**
	 * Sets product data.
	 *
	 * @since 7.0.0
	 *
	 * @param WC_Product $product - the product object.
	 * @param string     $key - the data key.
	 * @param string     $value - the data value.
	 */
	public function set( $product, $key, $value ) {

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}

		$identifier = spl_object_hash( $product ) . $product->get_id();

		if ( ! isset( $this->data[ $identifier ] ) ) {
			$this->data[ $identifier ] = array();
		}

		$this->data[ $identifier ][ $key ] = $value;
	}

	/**
	 * Deletes product data.
	 *
	 * @since 7.0.0
	 *
	 * @param WC_Product $product - the product object.
	 * @param string     $key - the data key.
	 *
	 * @return boolean
	 */
	public function delete( $product, $key ) {

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return false;
		}

		$identifier = spl_object_hash( $product ) . $product->get_id();

		if ( ! isset( $this->data[ $identifier ] ) ) {
			return false;
		}

		if ( ! isset( $this->data[ $identifier ][ $key ] ) ) {
			return false;
		}

		unset( $this->data[ $identifier ][ $key ] );

		return true;
	}
}
