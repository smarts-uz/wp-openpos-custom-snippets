<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\OAuth;

/**
 * Implements properties and methods common to all (non-SPL) Stripe\StripeTaxForWooCommerce\SDK\lib OAuth
 * exceptions.
 */
abstract class OAuthErrorException extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException {

	protected function constructErrorObject() {
		if ( null === $this->jsonBody ) {
			return null;
		}

		return \Stripe\StripeTaxForWooCommerce\SDK\lib\OAuthErrorObject::constructFrom( $this->jsonBody );
	}
}
