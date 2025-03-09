<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Issuing;

/**
 * Service factory class for API resources in the Issuing namespace.
 *
 * @property AuthorizationService $authorizations
 * @property CardholderService $cardholders
 * @property CardService $cards
 * @property DisputeService $disputes
 * @property TokenService $tokens
 * @property TransactionService $transactions
 */
class IssuingServiceFactory extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractServiceFactory {

	/**
	 * @var array<string, string>
	 */
	private static $classMap = array(
		'authorizations' => AuthorizationService::class,
		'cardholders'    => CardholderService::class,
		'cards'          => CardService::class,
		'disputes'       => DisputeService::class,
		'tokens'         => TokenService::class,
		'transactions'   => TransactionService::class,
	);

	protected function getServiceClass( $name ) {
		return \array_key_exists( $name, self::$classMap ) ? self::$classMap[ $name ] : null;
	}
}
