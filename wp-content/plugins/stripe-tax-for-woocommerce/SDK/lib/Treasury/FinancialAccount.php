<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Treasury;

/**
 * Stripe\StripeTaxForWooCommerce\SDK\lib Treasury provides users with a container for money called a FinancialAccount that is separate from their Payments balance.
 * FinancialAccounts serve as the source and destination of Treasury’s money movement APIs.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string[] $active_features The array of paths to active Features in the Features hash.
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $balance Balance information for the FinancialAccount
 * @property string $country Two-letter country code (<a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 alpha-2</a>).
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\Treasury\FinancialAccountFeatures $features Encodes whether a FinancialAccount has access to a particular Feature, with a <code>status</code> enum and associated <code>status_details</code>. Stripe\StripeTaxForWooCommerce\SDK\lib or the platform can control Features via the requested field.
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject[] $financial_addresses The set of credentials that resolve to a FinancialAccount.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string[] $pending_features The array of paths to pending Features in the Features hash.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $platform_restrictions The set of functionalities that the platform can restrict on the FinancialAccount.
 * @property null|string[] $restricted_features The array of paths to restricted Features in the Features hash.
 * @property string $status The enum specifying what state the account is in.
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $status_details
 * @property string[] $supported_currencies The currencies the FinancialAccount can hold a balance in. Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase.
 */
class FinancialAccount extends \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiResource {

	const OBJECT_NAME = 'treasury.financial_account';

	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\All;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Create;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Retrieve;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Update;

	const STATUS_CLOSED = 'closed';
	const STATUS_OPEN   = 'open';

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Treasury\FinancialAccountFeatures the retrieved financial account features
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function retrieveFeatures( $params = null, $opts = null ) {
		$url                   = $this->instanceUrl() . '/features';
		list($response, $opts) = $this->_request( 'get', $url, $params, $opts );
		$obj                   = \Stripe\StripeTaxForWooCommerce\SDK\lib\Util\Util::convertToStripeObject( $response, $opts );
		$obj->setLastResponse( $response );

		return $obj;
	}

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Treasury\FinancialAccountFeatures the updated financial account features
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function updateFeatures( $params = null, $opts = null ) {
		$url                   = $this->instanceUrl() . '/features';
		list($response, $opts) = $this->_request( 'post', $url, $params, $opts );
		$this->refreshFrom( $response, $opts );

		return $this;
	}
}
