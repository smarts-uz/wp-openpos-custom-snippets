<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Exception;

// TODO: remove this check once we drop support for PHP 5
if ( \interface_exists( \Throwable::class, false ) ) {
	/**
	 * The base interface for all Stripe\StripeTaxForWooCommerce\SDK\lib exceptions.
	 */
	interface ExceptionInterface extends \Throwable {

	}
} else {
	/**
	 * The base interface for all Stripe\StripeTaxForWooCommerce\SDK\lib exceptions.
	 */
    // phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
	interface ExceptionInterface {

	}
    // phpcs:enable
}
