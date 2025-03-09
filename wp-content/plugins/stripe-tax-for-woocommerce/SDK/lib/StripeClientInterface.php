<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * Interface for a Stripe\StripeTaxForWooCommerce\SDK\lib client.
 */
interface StripeClientInterface extends BaseStripeClientInterface {

	/**
	 * Sends a request to Stripe\StripeTaxForWooCommerce\SDK\lib's API.
	 *
	 * @param 'delete'|'get'|'post'                                             $method the HTTP method
	 * @param string                                                            $path the path of the request
	 * @param array                                                             $params the parameters of the request
	 * @param array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts the special modifiers of the request
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject the object returned by Stripe\StripeTaxForWooCommerce\SDK\lib's API
	 */
	public function request( $method, $path, $params, $opts );
}
