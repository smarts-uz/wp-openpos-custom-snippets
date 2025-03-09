<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service;

class BalanceService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Retrieves the current account balance, based on the authentication that was used
	 * to make the request.  For a sample request, see <a
	 * href="/docs/connect/account-balances#accounting-for-negative-balances">Accounting
	 * for negative balances</a>.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Balance
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $params = null, $opts = null ) {
		return $this->request( 'get', '/v1/balance', $params, $opts );
	}
}
