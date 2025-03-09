<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations;

/**
 * Trait for listable resources. Adds a `all()` static method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait All {

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection of ApiResources
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public static function all( $params = null, $opts = null ) {
		self::_validateParams( $params );
		$url = static::classUrl();

		list($response, $opts) = static::_staticRequest( 'get', $url, $params, $opts );
		$obj                   = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\Util::convertToStripeObject( $response->json, $opts );
		if ( ! ( $obj instanceof \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection ) ) {
			throw new \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\UnexpectedValueException(
				'Expected type ' . \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection::class . ', got "' . \get_class( $obj ) . '" instead.'
			);
		}
		$obj->setLastResponse( $response );
		$obj->setFilters( $params );

		return $obj;
	}
}
