<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * PaymentMethodConfigurations control which payment methods are displayed to your customers when you don't explicitly specify payment method types. You can have multiple configurations with different sets of payment methods for different scenarios.
 *
 * There are two types of PaymentMethodConfigurations. Which is used depends on the <a href="https://stripe.com/docs/connect/charges">charge type</a>:
 *
 * <strong>Direct</strong> configurations apply to payments created on your account, including Connect destination charges, Connect separate charges and transfers, and payments not involving Connect.
 *
 * <strong>Child</strong> configurations apply to payments created on your connected accounts using direct charges, and charges with the on_behalf_of parameter.
 *
 * Child configurations have a <code>parent</code> that sets default values and controls which settings connected accounts may override. You can specify a parent ID at payment time, and Stripe\StripeTaxForWooCommerce\SDK\lib will automatically resolve the connected account’s associated child configuration. Parent configurations are <a href="https://dashboard.stripe.com/settings/payment_methods/connected_accounts">managed in the dashboard</a> and are not available in this API.
 *
 * Related guides:
 * - <a href="https://stripe.com/docs/connect/payment-method-configurations">Payment Method Configurations API</a>
 * - <a href="https://stripe.com/docs/payments/multiple-payment-method-configs">Multiple configurations on dynamic payment methods</a>
 * - <a href="https://stripe.com/docs/connect/multiple-payment-method-configurations">Multiple configurations for your Connect accounts</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $acss_debit
 * @property bool $active Whether the configuration can be used for new payments.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $affirm
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $afterpay_clearpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $alipay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $apple_pay
 * @property null|string $application For child configs, the Connect application associated with the configuration.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $au_becs_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $bacs_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $bancontact
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $blik
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $boleto
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $card
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $cartes_bancaires
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $cashapp
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $eps
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $fpx
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $giropay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $google_pay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $grabpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $id_bank_transfer
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $ideal
 * @property bool $is_default The default configuration is used whenever a payment method configuration is not specified.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $jcb
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $klarna
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $konbini
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $link
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $multibanco
 * @property string $name The configuration's name.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $netbanking
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $oxxo
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $p24
 * @property null|string $parent For child configs, the configuration's parent configuration.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $pay_by_bank
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $paynow
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $paypal
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $promptpay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $revolut_pay
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $sepa_debit
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $sofort
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $upi
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $us_bank_account
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $wechat_pay
 */
class PaymentMethodConfiguration extends ApiResource {

	const OBJECT_NAME = 'payment_method_configuration';

	use ApiOperations\All;
	use ApiOperations\Create;
	use ApiOperations\Retrieve;
	use ApiOperations\Update;
}
