<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers\Issuing;

class AuthorizationService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Capture a test-mode authorization.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function capture( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/issuing/authorizations/%s/capture', $id ), $params, $opts );
	}

	/**
	 * Create a test-mode authorization.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/test_helpers/issuing/authorizations', $params, $opts );
	}

	/**
	 * Expire a test-mode Authorization.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function expire( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/issuing/authorizations/%s/expire', $id ), $params, $opts );
	}

	/**
	 * Increment a test-mode Authorization.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function increment( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/issuing/authorizations/%s/increment', $id ), $params, $opts );
	}

	/**
	 * Reverse a test-mode Authorization.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function reverse( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/test_helpers/issuing/authorizations/%s/reverse', $id ), $params, $opts );
	}
}
