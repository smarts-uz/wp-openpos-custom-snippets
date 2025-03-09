<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * PaymentMethod objects represent your customer's payment instruments.
 * You can use them with <a href="https://stripe.com/docs/payments/payment-intents">PaymentIntents</a> to collect payments or save them to
 * Customer objects to store instrument details for future payments.
 *
 * Related guides: <a href="https://stripe.com/docs/payments/payment-methods">Payment Methods</a> and <a href="https://stripe.com/docs/payments/more-payment-scenarios">More Payment Scenarios</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $acss_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $affirm
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $afterpay_clearpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $alipay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $au_becs_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $bacs_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $bancontact
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $billing_details
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $blik
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $boleto
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $card
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $card_present
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $cashapp
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Customer $customer The ID of the Customer to which this PaymentMethod is saved. This will not be set when the PaymentMethod has not been saved to a Customer.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $customer_balance
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $eps
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $fpx
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $giropay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $grabpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $ideal
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $interac_present
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $klarna
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $konbini
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $link
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $oxxo
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $p24
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $paynow
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $paypal
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $pix
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $promptpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $radar_options Options to configure Radar. See <a href="https://stripe.com/docs/radar/radar-session">Radar Session</a> for more information.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $revolut_pay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $sepa_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $sofort
 * @property string $type The type of the PaymentMethod. An additional hash is included on the PaymentMethod with a name matching this value. It contains additional information specific to the PaymentMethod type.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $us_bank_account
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $wechat_pay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $zip
 */
class PaymentMethod extends ApiResource {

	const OBJECT_NAME = 'payment_method';

	use ApiOperations\All;
	use ApiOperations\Create;
	use ApiOperations\Retrieve;
	use ApiOperations\Update;

	const TYPE_ACSS_DEBIT        = 'acss_debit';
	const TYPE_AFFIRM            = 'affirm';
	const TYPE_AFTERPAY_CLEARPAY = 'afterpay_clearpay';
	const TYPE_ALIPAY            = 'alipay';
	const TYPE_AU_BECS_DEBIT     = 'au_becs_debit';
	const TYPE_BACS_DEBIT        = 'bacs_debit';
	const TYPE_BANCONTACT        = 'bancontact';
	const TYPE_BLIK              = 'blik';
	const TYPE_BOLETO            = 'boleto';
	const TYPE_CARD              = 'card';
	const TYPE_CARD_PRESENT      = 'card_present';
	const TYPE_CASHAPP           = 'cashapp';
	const TYPE_CUSTOMER_BALANCE  = 'customer_balance';
	const TYPE_EPS               = 'eps';
	const TYPE_FPX               = 'fpx';
	const TYPE_GIROPAY           = 'giropay';
	const TYPE_GRABPAY           = 'grabpay';
	const TYPE_IDEAL             = 'ideal';
	const TYPE_INTERAC_PRESENT   = 'interac_present';
	const TYPE_KLARNA            = 'klarna';
	const TYPE_KONBINI           = 'konbini';
	const TYPE_LINK              = 'link';
	const TYPE_OXXO              = 'oxxo';
	const TYPE_P24               = 'p24';
	const TYPE_PAYNOW            = 'paynow';
	const TYPE_PAYPAL            = 'paypal';
	const TYPE_PIX               = 'pix';
	const TYPE_PROMPTPAY         = 'promptpay';
	const TYPE_REVOLUT_PAY       = 'revolut_pay';
	const TYPE_SEPA_DEBIT        = 'sepa_debit';
	const TYPE_SOFORT            = 'sofort';
	const TYPE_US_BANK_ACCOUNT   = 'us_bank_account';
	const TYPE_WECHAT_PAY        = 'wechat_pay';
	const TYPE_ZIP               = 'zip';

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\PaymentMethod the attached payment method
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function attach( $params = null, $opts = null ) {
		$url                   = $this->instanceUrl() . '/attach';
		list($response, $opts) = $this->_request( 'post', $url, $params, $opts );
		$this->refreshFrom( $response, $opts );

		return $this;
	}

	/**
	 * @param null|array        $params
	 * @param null|array|string $opts
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\PaymentMethod the detached payment method
	 * @throws \Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException if the request fails
	 */
	public function detach( $params = null, $opts = null ) {
		$url                   = $this->instanceUrl() . '/detach';
		list($response, $opts) = $this->_request( 'post', $url, $params, $opts );
		$this->refreshFrom( $response, $opts );

		return $this;
	}
}
