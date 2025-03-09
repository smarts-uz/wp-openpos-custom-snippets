<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations;

/**
 * Trait for retrievable resources. Adds a `retrieve()` static method to the
 * class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Retrieve {

	/**
	 * @param array|string      $id the ID of the API resource to retrieve,
	 *          or an options array containing an `id` key
	 * @param null|array|string $opts
	 *
	 * @return static
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public static function retrieve( $id, $opts = null ) {
		$opts     = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions::parse( $opts );
		$instance = new static( $id, $opts );
		$instance->refresh();

		return $instance;
	}
}
