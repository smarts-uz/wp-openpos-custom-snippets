<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations;

/**
 * Trait for creatable resources. Adds a `create()` static method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Create {

	/**
	 * @param null|array        $params
	 * @param null|array|string $options
	 *
	 * @return static the created resource
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public static function create( $params = null, $options = null ) {
		self::_validateParams( $params );
		$url = static::classUrl();

		list($response, $opts) = static::_staticRequest( 'post', $url, $params, $options );
		$obj                   = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\Util::convertToStripeObject( $response->json, $opts );
		$obj->setLastResponse( $response );

		return $obj;
	}
}
