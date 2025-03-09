<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service;

class ProductService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Returns a list of your products. The products are returned sorted by creation
	 * date, with the most recently created products appearing first.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection<\Stripe\StripeTaxForWooCommerce\SDK\lib\Product>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/products', $params, $opts );
	}

	/**
	 * Creates a new product object.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Product
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function create( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/products', $params, $opts );
	}

	/**
	 * Delete a product. Deleting a product is only possible if it has no prices
	 * associated with it. Additionally, deleting a product with <code>type=good</code>
	 * is only possible if it has no SKUs associated with it.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Product
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function delete( $id, $params = null, $opts = null ) {
		return $this->request( 'delete', $this->buildPath( '/v1/products/%s', $id ), $params, $opts );
	}

	/**
	 * Retrieves the details of an existing product. Supply the unique product ID from
	 * either a product creation request or the product list, and Stripe\StripeTaxForWooCommerce\SDK\lib will return
	 * the corresponding product information.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Product
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/products/%s', $id ), $params, $opts );
	}

	/**
	 * Search for products you’ve previously created using Stripe\StripeTaxForWooCommerce\SDK\lib’s <a
	 * href="/docs/search#search-query-language">Search Query Language</a>. Don’t use
	 * search in read-after-write flows where strict consistency is necessary. Under
	 * normal operating conditions, data is searchable in less than a minute.
	 * Occasionally, propagation of new or updated data can be up to an hour behind
	 * during outages. Search functionality is not available to merchants in India.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\SearchResult<\Stripe\StripeTaxForWooCommerce\SDK\lib\Product>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function search( $params = null, $opts = null ) {
		return $this->requestSearchResult( 'get', '/v1/products/search', $params, $opts );
	}

	/**
	 * Updates the specific product by setting the values of the parameters passed. Any
	 * parameters not provided will be left unchanged.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Product
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function update( $id, $params = null, $opts = null ) {
		return $this->request( 'post', $this->buildPath( '/v1/products/%s', $id ), $params, $opts );
	}
}
