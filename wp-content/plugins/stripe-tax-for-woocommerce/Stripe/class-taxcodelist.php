<?php
/**
 * Tax Code list service
 * Retrieves from API, stores and formats Stripe Tax Codes
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * Class provides tax code list
 */
class TaxCodeList {
	use StripeClientTrait;

	const CACHE_GROUP = 'stripe-tax-for-woocommerce';
	const CACHE_KEY   = 'tax-codes';
	const TABLE_NAME  = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'tax_codes';
	/**
	 * Stripe API key
	 *
	 * @var string $stripe_secret_key Stripe API secret key
	 */
	protected $stripe_secret_key;

	/**
	 * Creates TaxCodeList object
	 *
	 * @param string $stripe_secret_key Stripe API secret key.
	 */
	public function __construct( string $stripe_secret_key ) {
		$this->stripe_secret_key = $stripe_secret_key;
	}

	/**
	 * Formats array of tax_code objects converting "{name}" property of each object in "{id} - {localized_name}" format
	 *
	 * @param array $items Array of tax_code objects with "id" and "name" mandatory properties.
	 *
	 * @return array Array of tax_code objects in "{id} - {localized_name}" format
	 */
	protected function format_items( array $items ): array {
		$formatted_items = array();
		foreach ( $items as $tax_code => $tax_code_object ) {
			$formatted_items[ $tax_code ]       = new \stdClass();
			$formatted_items[ $tax_code ]->id   = $tax_code;
			$formatted_items[ $tax_code ]->name = $tax_code . ' - ' . $tax_code_object->name;
		}

		return $formatted_items;
	}

	/**
	 * Formats associative array of tax_codes converting value into "{id} - {localized_name}" format
	 *
	 * @param array $items Array of tax_code objects with "id" and "name" mandatory properties.
	 *
	 * @return array Array of tax_code objects in "{id} - {localized_name}" format
	 */
	protected function format_items_key_value( array $items ): array {
		$formatted_items = array();
		foreach ( $items as $tax_code => $tax_code_name ) {
			$formatted_items[ $tax_code ] = $tax_code . ' - ' . $tax_code_name;
		}

		return $formatted_items;
	}

	/**
	 * Tries to return tax codes from WordPress object cache
	 *
	 * @return array Cached tax codes
	 */
	protected function get_from_object_cache(): array {
		$items = wp_cache_get( static::CACHE_KEY, static::CACHE_GROUP );

		if ( $items ) {
			return $items;
		} else {
			return array();
		}
	}

	/**
	 * Tries to return tax codes from database
	 *
	 * @return array Associative array of objects, where each array's item key equals value od first column in database
	 */
	protected function get_from_db(): array {
		global $wpdb;

		if (
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->get_results(
				$wpdb->prepare(
					'SELECT %i, %i FROM %i',
					array(
						'id',
						'name',
						static::TABLE_NAME,
					)
				),
				OBJECT_K
			)
		) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT %i, %i FROM %i',
					array(
						'id',
						'name',
						static::TABLE_NAME,
					)
				),
				OBJECT_K
			);
		} else {
			return array();
		}
	}

	/**
	 * Saves tax codes into object cache
	 *
	 * @param array $items Tax codes.
	 *
	 * @return void
	 */
	protected function set_to_object_cache( array $items ): void {
		wp_cache_flush_group( static::CACHE_GROUP );
		wp_cache_set( static::CACHE_KEY, $items, static::CACHE_GROUP );
	}

	/**
	 * Tries to receive tax codes using Stripe API
	 *
	 * @return array Associative array of tax codes, where each array's item key equals tax_code id.
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/api/tax_codes/list
	 */
	protected function get_from_api_call(): array {
		$stripe = $this->get_stripe_client( $this->stripe_secret_key );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$api_response = $stripe->taxCodes->all( array( 'limit' => 100 ) );
		if ( ! isset( $api_response->object ) || 'list' !== $api_response->object ) {
			throw new Exception( esc_html__( 'Unexpected response from Stripe', 'stripe-tax-for-woocommerce' ) . ': ' . wp_json_encode( $api_response ) );
		}
		$counter   = 0;
		$tax_codes = array();
		foreach ( $api_response->autoPagingIterator() as $tax_code ) {
			$tax_codes[ $tax_code->id ] = $tax_code;
			++$counter;
			if ( $counter > 10000 ) {
				throw new Exception( esc_html__( 'Too many tax codes', 'stripe-tax-for-woocommerce' ) );
			}
		}

		return $tax_codes;
	}

	/**
	 * Truncates tax codes table
	 *
	 * @return void
	 */
	protected function truncate_table(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare( 'TRUNCATE %i', static::TABLE_NAME )
		);
	}

	/**
	 * Saves tax codes to database
	 *
	 * @param array $items_array Array of tax code objects.
	 *
	 * @return void
	 */
	protected function set_to_db( array $items_array ): void {
		global $wpdb;
		foreach ( $items_array as $object ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					'INSERT %i (%i, %i, %i) VALUES (%s, %s, %s)',
					array(
						static::TABLE_NAME,
						'id',
						'name',
						'description',
						$object->id,
						$object->name,
						$object->description,
					)
				)
			);
		}
	}

	/**
	 * Receive all tax codes
	 *
	 * @param bool $skip_object_cache Skips object cache and tries to receive tax_codes from database or Stripe API, depending on $skip_database_cache parameter.
	 * @param bool $skip_database_cache Skips database cache and tries to receive tax codes from Stripe API if object cache skipped or empty.
	 *
	 * @return array Array of tax codes
	 * @throws Exception If data is not correct.
	 */
	public function get( bool $skip_object_cache = false, bool $skip_database_cache = false ): array {
		if ( ! $skip_object_cache ) {
			$items = $this->get_from_object_cache();
			if ( $items ) {
				return $items;
			}
		}
		if ( ! $skip_database_cache ) {
			$items = $this->get_from_db();
			if ( $items ) {
				$this->set_to_object_cache( $items );

				return $items;
			}
		}
		$items = $this->get_from_api_call();

		if ( $items ) {
			$this->truncate_table();
			$this->set_to_db( $items );
			$this->set_to_object_cache( $items );
		}

		return $items;
	}

	/**
	 * Gets formatted and localized tax code list, where tax code name equals "{id} - {name}"
	 *
	 * @param bool $skip_object_cache Skips object cache and tries to receive tax_codes from database or Stripe API, depending on $skip_database_cache parameter.
	 * @param bool $skip_database_cache Skips database cache and tries to receive tax codes from Stripe API if object cache skipped or empty.
	 *
	 * @return array Formatted and localized tax code list, where tax code name equals "{id} - {name}"
	 * @throws Exception If data is not correct.
	 */
	public function get_formatted( bool $skip_object_cache = false, bool $skip_database_cache = false ): array {
		return $this->format_items( $this->get( $skip_object_cache, $skip_database_cache ) );
	}

	/**
	 * Returns tax codes as associative array, there key equals tax code id and value equals tax code name
	 *
	 * @param bool $skip_object_cache Skip object cache.
	 * @param bool $skip_database_cache Skip database cache.
	 *
	 * @return array Tax codes as associative array, there key equals tax code id and value equals tax code name.
	 * @throws Exception If data is not correct.
	 */
	public function get_as_key_value( bool $skip_object_cache = false, bool $skip_database_cache = false ): array {
		$cached_value = wp_cache_get( 'tax-codes-key-value', static::CACHE_GROUP );
		if ( $cached_value && ! $skip_object_cache ) {
			return $cached_value;
		}
		$items        = $this->get( $skip_object_cache, $skip_database_cache );
		$cached_value = array_combine( array_keys( $items ), array_column( $items, 'name' ) );
		wp_cache_set( 'tax-codes-key-value', $cached_value, static::CACHE_GROUP );

		return $cached_value;
	}

	/**
	 * Returns tax codes as associative array, where key equals tax code id and value equals {id} - {name}
	 *
	 * @param bool $skip_object_cache Skips object cache and tries to receive tax_codes from database or Stripe API, depending on $skip_database_cache parameter.
	 * @param bool $skip_database_cache Skips database cache and tries to receive tax codes from Stripe API if object cache skipped or empty.
	 *
	 * @return array Tax codes as associative array, where key equals tax code id and value equals {id} - {name}
	 * @throws Exception If data is not correct.
	 */
	public function get_as_key_value_formatted( bool $skip_object_cache = false, bool $skip_database_cache = false ): array {
		return $this->format_items_key_value( $this->get_as_key_value( $skip_object_cache, $skip_database_cache ) );
	}

	/**
	 * Formats single
	 *
	 * @param string $tax_code Tax code.
	 * @param string $api_key API key.
	 *
	 * @return mixed
	 * @throws Exception If data is not correct.
	 */
	public static function format_single( $tax_code, $api_key ) {
		$tax_code_list = new static( $api_key );

		return $tax_code_list->get()[ $tax_code ]->name;
	}
}
