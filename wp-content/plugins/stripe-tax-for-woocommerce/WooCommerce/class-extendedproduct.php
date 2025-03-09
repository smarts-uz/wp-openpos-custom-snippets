<?php
/**
 * Extends WooCommerce product with additional fields.
 *
 * @package tripe\StripeTaxForWooCommerce\WooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce\WooCommerce;

// Exit if script started not from WordPress.
use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\Validate;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;
use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Product class replacement.
 */
class ExtendedProduct {

	/**
	 * Database table name for storing additional WooCommerce product fields
	 *
	 * @var string
	 */
	public const TABLE_NAME = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'products';

	/**
	 * WooCommerce Product id.
	 *
	 * @var int
	 */
	protected $product_id;

	/**
	 * Constructor for ExtendedProduct.
	 *
	 * @param int $product_id Product id.
	 */
	public function __construct( int $product_id = 0 ) {
		$this->product_id = $product_id;
	}

	/**
	 * Gets WooCommerce Product id.
	 */
	protected function get_product_id() {
		return $this->product_id;
	}

	/**
	 * Gets additional WooCommerce Product fields from WordPress object cache to reduce database requests.
	 *
	 * @return array
	 */
	protected function get_from_object_cache(): array {
		$product_id = (int) $this->get_product_id();
		$products   = wp_cache_get( 'products', 'stripe-tax-for-woocommerce' );

		if ( ! is_array( $products ) || ! array_key_exists( $product_id, $products ) ) {
			return array();
		}

		return $products[ $product_id ];
	}

	/**
	 * Sets additional WooCommerce Product fields into WordPress object cache.
	 *
	 * @param array $product Array of additional WooCommerce Product fields to store.
	 */
	protected function set_into_object_cache( array $product ) {
		$products = wp_cache_get( 'products', 'stripe-tax-for-woocommerce' );

		if ( ! is_array( $products ) ) {
			$products = array();
		}

		$products [ $this->product_id ] = array(
			'tax_code' => $product['tax_code'] ?? '',
		);

		wp_cache_set( 'products', $products, 'stripe-tax-for-woocommerce' );
	}

	/**
	 * Populate tax code from submitted form.
	 *
	 * @param WC_Data $wc_data WC_Data object. Usually here will be WooCommerce Product object.
	 *
	 * @return string
	 * @throws \Exception In case of invalid tax code entered.
	 */
	public static function get_on_save_post_parameter_tax_code( WC_Data $wc_data ): string {
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		$nonce  = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		$post_id = $wc_data->get_id();
		$post    = get_post( $post_id );

		if ( isset( $_POST['deletepost'] ) ) {
			$action = 'delete';
		} elseif ( isset( $_POST['wp-preview'] ) && 'dopreview' === $_POST['wp-preview'] ) {
			$action = 'preview';
		}

		switch ( $action ) {
			case 'post-quickdraft-save':
				check_admin_referer( 'add-' . $post->post_type );
				if ( ! wp_verify_nonce( $nonce, 'add-post' ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'postajaxpost':
			case 'post':
				check_admin_referer( 'add-' . $post->post_type );
				if ( ! wp_verify_nonce( $nonce, 'add-' . $post->post_type ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'editattachment':
			case 'editpost':
			case 'preview':
				check_admin_referer( 'update-post_' . $post_id );
				if ( ! wp_verify_nonce( $nonce, 'update-post_' . $post_id ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'trash':
				check_admin_referer( 'trash-post_' . $post_id );
				if ( ! wp_verify_nonce( $nonce, 'trash-post_' . $post_id ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'untrash':
				check_admin_referer( 'untrash-post_' . $post_id );
				if ( ! wp_verify_nonce( $nonce, 'untrash-post_' . $post_id ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'delete':
				check_admin_referer( 'delete-post_' . $post_id );
				if ( ! wp_verify_nonce( $nonce, 'delete-post_' . $post_id ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			case 'toggle-custom-fields':
				check_admin_referer( 'toggle-custom-fields', 'toggle-custom-fields-nonce' );
				if ( ! wp_verify_nonce( $nonce, 'toggle-custom-fields-nonce' ) ) {
					StripeTaxPluginHelper::do_exit();
				}
				break;
			default:
				return '';
		}

		$tax_code = sanitize_text_field( wp_unslash( $_POST['_stripe_tax_for_woocommerce_tax_code'] ?? '' ) );
		if ( '' !== $tax_code && 'stfwc_inherit' !== $tax_code ) { // Empty and "stfwc_inherit" "tax_code" values are valid and in this situation means "using global Stripe Tax Settings parameter".
			Validate::validate_tax_code( $tax_code, Options::get_live_mode_key() );
		}
		return $tax_code;
	}

	/**
	 * Gets additional WooCommerce Product fields from database.
	 *
	 * @return array
	 */
	protected function get_from_db() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT %i FROM %i WHERE %i = %d',
				array(
					'tax_code',
					static::TABLE_NAME,
					'product_id',
					$this->product_id,
				)
			),
			ARRAY_A
		);

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Store additional WooCommerce Product fields to database.
	 *
	 * @param array $product WooCommerce Product.
	 */
	public function set_into_db( array $product ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i ( %i, %i ) VALUES ( %d, %s ) ON DUPLICATE KEY UPDATE %i = %s',
				array(
					static::TABLE_NAME,
					'product_id',
					'tax_code',
					(int) $this->get_product_id(),
					$product['tax_code'] ?? '',
					'tax_code',
					$product['tax_code'] ?? '',
				)
			)
		);
	}

	/**
	 * Load additional WooCommerce Product fields from WordPress object cache or database.
	 *
	 * @param bool $skip_object_cache Skip object cache.
	 */
	public function get_extended_product( $skip_object_cache = false ): array {
		if ( ! $skip_object_cache ) {
			$product = $this->get_from_object_cache();
			if ( $product ) {
				return $product;
			}
		}

		$product = $this->get_from_db();
		if ( $product ) {
			$this->set_into_object_cache( $product );

			return $product;
		}

		return array(
			'tax_code' => '',
		);
	}

	/**
	 * Save additional WooCommerce Product fields into WordPress object cache and database.
	 *
	 * @param array $product WooCommerce Product.
	 */
	public function save_extended_product( $product ) {
		$new_product = array(
			'tax_code' => $product['tax_code'] ?? '',
		);

		if ( 'stfwc_inherit' === $new_product['tax_code'] ) {
			$new_product['tax_code'] = '';
		}

		$this->set_into_db( $new_product );
		$this->set_into_object_cache( $new_product );
	}
}
