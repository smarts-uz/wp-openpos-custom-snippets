<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers;

class CustomerService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Create an incoming testmode bank transfer.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\CustomerCashBalanceTransaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function fundCashBalance( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/customers/%s/fund_cash_balance', $id ), $params, $opts );
	}
}
