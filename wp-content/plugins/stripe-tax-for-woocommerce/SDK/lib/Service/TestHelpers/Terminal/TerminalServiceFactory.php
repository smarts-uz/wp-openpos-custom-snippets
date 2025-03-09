<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers\Terminal;

/**
 * Service factory class for API resources in the Terminal namespace.
 *
 * @property ReaderService $readers
 */
class TerminalServiceFactory extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractServiceFactory {

	/**
	 * @var array<string, string>
	 */
	private static $classMap = array(
		'readers' => ReaderService::class,
	);

	protected function getServiceClass( $name ) {
		return \array_key_exists( $name, self::$classMap ) ? self::$classMap[ $name ] : null;
	}
}
