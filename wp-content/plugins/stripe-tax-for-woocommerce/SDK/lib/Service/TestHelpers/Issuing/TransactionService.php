<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers\Issuing;

class TransactionService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Allows the user to capture an arbitrary amount, also known as a forced capture.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function createForceCapture( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/test_helpers/issuing/transactions/create_force_capture', $params, $opts );
	}

	/**
	 * Allows the user to refund an arbitrary amount, also known as a unlinked refund.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function createUnlinkedRefund( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/test_helpers/issuing/transactions/create_unlinked_refund', $params, $opts );
	}

	/**
	 * Refund a test-mode Transaction.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Transaction
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function refund( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/issuing/transactions/%s/refund', $id ), $params, $opts );
	}
}
