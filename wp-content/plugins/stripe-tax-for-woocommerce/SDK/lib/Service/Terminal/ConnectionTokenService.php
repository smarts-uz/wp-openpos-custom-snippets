<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Terminal;

class ConnectionTokenService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * To connect to a reader the Stripe\StripeTaxForWooCommerce\SDK\lib Terminal SDK needs to retrieve a short-lived
	 * connection token from Stripe\StripeTaxForWooCommerce\SDK\lib, proxied through your server. On your backend, add
	 * an endpoint that creates and returns a connection token.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Terminal\ConnectionToken
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/terminal/connection_tokens', $params, $opts );
	}
}
