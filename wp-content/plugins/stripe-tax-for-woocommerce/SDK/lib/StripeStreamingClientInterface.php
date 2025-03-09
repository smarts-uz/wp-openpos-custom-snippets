<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * Interface for a Stripe\StripeTaxForWooCommerce\SDK\lib client.
 */
interface StripeStreamingClientInterface extends BaseStripeClientInterface {

	public function requestStream( $method, $path, $readBodyChunkCallable, $params, $opts );
}
