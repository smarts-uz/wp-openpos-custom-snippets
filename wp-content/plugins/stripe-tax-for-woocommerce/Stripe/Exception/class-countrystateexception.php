<?php
/**
 * Class, responsible for CountryStateException
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe\Exception
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe\Exception;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * CountryStateException to be thrown, if unsupported origin address state received for selected origin address country.
 */
class CountryStateException extends \Exception {
}
