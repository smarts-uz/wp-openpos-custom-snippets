<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers\Terminal;

class ReaderService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Presents a payment method on a simulated reader. Can be used to simulate
	 * accepting a payment, saving a card or refunding a transaction.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Terminal\Reader
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function presentPaymentMethod( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/terminal/readers/%s/present_payment_method', $id ), $params, $opts );
	}
}
