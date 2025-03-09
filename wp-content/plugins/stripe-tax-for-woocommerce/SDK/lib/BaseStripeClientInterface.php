<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * Interface for a Stripe\StripeTaxForWooCommerce\SDK\lib client.
 */
interface BaseStripeClientInterface {

	/**
	 * Gets the API key used by the client to send requests.
	 *
	 * @return null|string the API key used by the client to send requests
	 */
	public function getApiKey();

	/**
	 * Gets the client ID used by the client in OAuth requests.
	 *
	 * @return null|string the client ID used by the client in OAuth requests
	 */
	public function getClientId();

	/**
	 * Gets the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's API.
	 *
	 * @return string the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's API
	 */
	public function getApiBase();

	/**
	 * Gets the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's OAuth API.
	 *
	 * @return string the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's OAuth API
	 */
	public function getConnectBase();

	/**
	 * Gets the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's Files API.
	 *
	 * @return string the base URL for Stripe\StripeTaxForWooCommerce\SDK\lib's Files API
	 */
	public function getFilesBase();
}
