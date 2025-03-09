<?php
/**
 * Stripe Client Trait
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

declare( strict_types=1 );

namespace Stripe\StripeTaxForWooCommerce\Stripe;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Stripe\StripeTaxForWooCommerce\SDK\lib\StripeClient;

trait StripeClientTrait {

	/**
	 * StripeClient object
	 *
	 * @var StripeClient
	 */
	protected $stripe_client;

	/**
	 * Get StripeClient object
	 *
	 * @param string $api_key Stripe API key.
	 *
	 * @return StripeClient
	 */
	protected function get_stripe_client( string $api_key ): StripeClient {
		if ( is_null( $this->stripe_client ) ) {
			$this->stripe_client = new StripeClient( $api_key );
		}
		return $this->stripe_client;
	}
}
