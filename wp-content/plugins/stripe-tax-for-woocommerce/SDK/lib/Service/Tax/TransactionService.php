<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Tax;

class TransactionService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Retrieves the line items of a committed standalone transaction as a collection.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection<\Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\TransactionLineItem>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function allLineItems( $id, $params = null, $opts = null ) {
		return $this->requestCollection( 'get', $this->buildPath( '/v1/tax/transactions/%s/line_items', $id ), $params, $opts );
	}

	/**
	 * Creates a Tax <code>Transaction</code> from a calculation.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function createFromCalculation( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/tax/transactions/create_from_calculation', $params, $opts );
	}

	/**
	 * Partially or fully reverses a previously created <code>Transaction</code>.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function createReversal( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/tax/transactions/create_reversal', $params, $opts );
	}

	/**
	 * Retrieves a Tax <code>Transaction</code> object.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/tax/transactions/%s', $id ), $params, $opts );
	}
}
