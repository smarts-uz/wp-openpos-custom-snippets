<?php
/**
 * Class, responsible for TaxBehaviorException
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe\Exception
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe\Exception;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * TaxBehaviorException to be thrown, if incorrect Stripe Tax Behavior received on user profile settings save action.
 */
class TaxBehaviorException extends \Exception {
}
