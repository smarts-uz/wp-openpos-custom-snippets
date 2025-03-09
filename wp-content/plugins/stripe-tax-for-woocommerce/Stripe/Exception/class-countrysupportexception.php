<?php
/**
 * Class, responsible for CountrySupportException
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe\Exception
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe\Exception;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * CountrySupportException to be thrown, if country, that is not supported as Stripe Origin Address received.
 */
class CountrySupportException extends \Exception {
}
