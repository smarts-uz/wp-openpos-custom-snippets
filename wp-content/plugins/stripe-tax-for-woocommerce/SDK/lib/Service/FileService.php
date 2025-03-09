<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service;

class FileService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Returns a list of the files that your account has access to. Stripe\StripeTaxForWooCommerce\SDK\lib sorts and
	 * returns the files by their creation dates, placing the most recently created
	 * files at the top.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection<\Stripe\StripeTaxForWooCommerce\SDK\lib\File>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/files', $params, $opts );
	}

	/**
	 * Retrieves the details of an existing file object. After you supply a unique file
	 * ID, Stripe\StripeTaxForWooCommerce\SDK\lib returns the corresponding file object. Learn how to <a
	 * href="/docs/file-upload#download-file-contents">access file contents</a>.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\File
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/files/%s', $id ), $params, $opts );
	}

	/**
	 * Create a file.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\File
	 */
	public function create( $params = null, $opts = null ) {
		$opts = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions::parse( $opts );
		if ( ! isset( $opts->apiBase ) ) {
			$opts->apiBase = $this->getClient()->getFilesBase();
		}

		// Manually flatten params, otherwise curl's multipart encoder will
		// choke on nested null|arrays.
		$flatParams = \array_column( \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\Util::flattenParams( $params ), 1, 0 );

		return $this->request( 'post', '/v1/files', $flatParams, $opts );
	}
}
