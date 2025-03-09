<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Terminal;

/**
 * A Configurations object represents how features should be configured for terminal readers.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $bbpos_wisepos_e
 * @property null|bool $is_account_default Whether this Configuration is the default for your account
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $offline
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $tipping
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $verifone_p400
 */
class Configuration extends \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiResource {

	const OBJECT_NAME = 'terminal.configuration';

	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\All;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Create;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Delete;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Retrieve;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Update;
}
