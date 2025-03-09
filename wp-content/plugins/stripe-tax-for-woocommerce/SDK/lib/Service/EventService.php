<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service;

class EventService extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractService {

	/**
	 * List events, going back up to 30 days. Each event data is rendered according to
	 * Stripe\StripeTaxForWooCommerce\SDK\lib API version at its creation time, specified in <a
	 * href="/docs/api/events/object">event object</a> <code>api_version</code>
	 * attribute (not according to your current Stripe\StripeTaxForWooCommerce\SDK\lib API version or
	 * <code>Stripe\StripeTaxForWooCommerce\SDK\lib-Version</code> header).
	 *
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Collection<\Stripe\StripeTaxForWooCommerce\SDK\lib\Event>
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function all( $params = null, $opts = null ) {
		return $this->requestCollection( 'get', '/v1/events', $params, $opts );
	}

	/**
	 * Retrieves the details of an event. Supply the unique identifier of the event,
	 * which you might have received in a webhook.
	 *
	 * @param string                                                                 $id
	 * @param null|array                                                             $params
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\RequestOptions $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Event
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieve( $id, $params = null, $opts = null ) {
		return $this->request( 'get', $this->buildPath( '/v1/events/%s', $id ), $params, $opts );
	}
}
