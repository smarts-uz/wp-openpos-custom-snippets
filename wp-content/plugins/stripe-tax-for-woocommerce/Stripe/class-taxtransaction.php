<?php
/**
 * Tax Transaction service
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

use Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * Tax Settings service
 */
class TaxTransaction {
	use StripeClientTrait;

	const TABLE_NAME = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'tax_transactions';

	/**
	 * Stripe API key
	 *
	 * @var string|null
	 */
	protected $api_key = null;

	/**
	 * Cache of Tax Transactions to reduce database calls
	 *
	 * @var array<int, \stdClass>
	 */
	protected static $transactions = array();

	/**
	 * Create TaxTransaction service
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Create Stripe Tax Transaction from Tax Calculation
	 *
	 * @param object $tax_calculation Tax calculation.
	 * @param int    $order_id WooCommerce Order ID.
	 *
	 * @return \stdClass
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/transactions/create_from_calculation
	 */
	public function create( object $tax_calculation, int $order_id ) {
		global $wpdb;

		$stripe_client   = $this->get_stripe_client( $this->api_key );
		$tax_transaction = $stripe_client->tax->transactions->createFromCalculation(
			array(
				'calculation' => $tax_calculation->id,
				'reference'   => 'Order ' . $order_id . ' order timestamp ' . time(),
				'expand'      => array( 'line_items' ),
			)
		);

		$json_tax_calculation = wp_json_encode( $tax_calculation );
		$json_tax_transaction = wp_json_encode( $tax_transaction );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (%i, %i, %i) VALUES (%d, %s, %s) ON DUPLICATE KEY UPDATE %i = %s, %i = %s',
				array(
					static::TABLE_NAME,
					'order_id',
					'tax_calculation',
					'tax_transaction',
					$order_id,
					$json_tax_calculation,
					$json_tax_transaction,
					'tax_calculation',
					$json_tax_calculation,
					'tax_transaction',
					$json_tax_transaction,
				)
			)
		);
		static::$transactions[ $order_id ]                  = new \stdClass();
		static::$transactions[ $order_id ]->order_id        = $order_id;
		static::$transactions[ $order_id ]->tax_calculation = $tax_calculation;
		static::$transactions[ $order_id ]->tax_transaction = $tax_transaction;

		return static::$transactions[ $order_id ];
	}

	/**
	 * Get Stripe Tax Transaction from cache or database
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return object|null
	 */
	public function get( int $order_id ) {
		global $wpdb;

		if ( array_key_exists( $order_id, static::$transactions ) ) {
			return static::$transactions[ $order_id ];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT %i, %i, %i FROM %i WHERE %i = %d',
				array(
					'order_id',
					'tax_calculation',
					'tax_transaction',
					static::TABLE_NAME,
					'order_id',
					$order_id,
				)
			)
		);

		if ( ! $result ) {
			$result = null;
		} else {
			$result->tax_calculation = json_decode( $result->tax_calculation );
			$result->tax_transaction = json_decode( $result->tax_transaction );
		}

		static::$transactions[ $order_id ] = $result;

		return $result;
	}

	/**
	 * Create reversal Tax Transaction
	 *
	 * @param int   $order_id Order ID.
	 * @param array $line_items Line items.
	 * @param array $shipping_cost Shipping cost.
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/transactions/create_reversal
	 */
	public function create_reversal( int $order_id, array $line_items = array(), array $shipping_cost = array() ) {
		$reversal_transaction_request_data = array(
			'mode' => $line_items ? 'partial' : 'full',
		);
		$tax_transaction                   = $this->get( $order_id )->tax_transaction;

		if ( ! $tax_transaction || ! $tax_transaction->id ) {
			return;
		}

		$reversal_transaction_request_data['original_transaction'] = $tax_transaction->id;
		$reversal_transaction_request_data['reference']            = 'Refund order ' . $order_id . ', refund timestamp ' . time();

		if ( $line_items ) {
			$reversal_transaction_request_data['line_items'] = $line_items;
		}

		if ( $shipping_cost ) {
			$reversal_transaction_request_data['shipping_cost'] = $shipping_cost;
		}

		$stripe_client = $this->get_stripe_client( $this->api_key );
		$stripe_client->tax->transactions->createReversal( $reversal_transaction_request_data );
	}
}
