<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Issuing;

class TransactionService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Returns a list of Issuing <code>Transaction</code> objects. The objects are
	 * sorted in descending order by creation date, with the most recently created
	 * object appearing first.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection<\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/issuing/transactions', $params, $opts );
	}

	/**
	 * Retrieves an Issuing <code>Transaction</code> object.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/issuing/transactions/%s', $id ), $params, $opts );
	}

	/**
	 * Updates the specified Issuing <code>Transaction</code> object by setting the
	 * values of the parameters passed. Any parameters not provided will be left
	 * unchanged.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function update( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/issuing/transactions/%s', $id ), $params, $opts );
	}
}
