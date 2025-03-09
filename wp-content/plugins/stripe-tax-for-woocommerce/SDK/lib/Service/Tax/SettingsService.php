<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Tax;

class SettingsService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * Retrieves Tax <code>Settings</code> for a merchant.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Settings
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $params = null, $opts = null ) {
		return $this->request( 'get', '/v1/tax/settings', $params, $opts );
	}

	/**
	 * Updates Tax <code>Settings</code> parameters used in tax calculations. All
	 * parameters are editable but none can be removed once set.
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Settings
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function update( $params = null, $opts = null ) {
		return $this->request( 'post', '/v1/tax/settings', $params, $opts );
	}
}
