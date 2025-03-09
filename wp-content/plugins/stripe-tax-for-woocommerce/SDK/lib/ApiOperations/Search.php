<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations;

/**
 * Trait for searchable resources.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Search {

	/**
	 * @param string            $searchUrl
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\SearchResult of ApiResources
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	protected static function _searchResource( $searchUrl, $params = null, $opts = null ) {
		self::_validateParams( $params );

		list($response, $opts) = static::_staticRequest( 'get', $searchUrl, $params, $opts );
		$obj                   = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\Util::convertToStripeObject( $response->json, $opts );
		if ( ! ( $obj instanceof \Stripe\StripeTaxForWooCommerce\SDK\lib\SearchResult ) ) {
			throw new \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\UnexpectedValueException(
				'Expected type ' . \Stripe\StripeTaxForWooCommerce\SDK\lib\SearchResult::class . ', got "' . \get_class( $obj ) . '" instead.'
			);
		}
		$obj->setLastResponse( $response );
		$obj->setFilters( $params );

		return $obj;
	}
}
