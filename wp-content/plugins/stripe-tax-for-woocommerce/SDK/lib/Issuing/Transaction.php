<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing;

/**
 * Any use of an <a href="https://stripe.com/docs/issuing">issued card</a> that results in funds entering or leaving
 * your Stripe\StripeTaxForWooCommerce\SDK\lib account, such as a completed purchase or refund, is represented by an Issuing
 * <code>Transaction</code> object.
 *
 * Related guide: <a href="https://stripe.com/docs/issuing/purchases/transactions">Issued card transactions</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount The transaction amount, which will be reflected in your balance. This amount is in your currency and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $amount_details Detailed breakdown of amount components. These amounts are denominated in <code>currency</code> and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Authorization $authorization The <code>Authorization</code> object that led to this transaction.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\BalanceTransaction $balance_transaction ID of the <a href="https://stripe.com/docs/api/balance_transactions">balance transaction</a> associated with this transaction.
 * @property string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Card $card The card used to make this transaction.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Cardholder $cardholder The cardholder to whom this transaction belongs.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Dispute $dispute If you've disputed the transaction, the ID of the dispute.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property int $merchant_amount The amount that the merchant will receive, denominated in <code>merchant_currency</code> and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>. It will be different from <code>amount</code> if the merchant is taking payment in a different currency.
 * @property string $merchant_currency The currency with which the merchant is taking payment.
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $merchant_data
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $network_data Details about the transaction, such as processing dates, set by the card network.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $purchase_details Additional purchase information that is optionally provided by the merchant.
 * @property null|string|\Stripe\StripeTaxForWooCommerce\SDK\lib\Issuing\Token $token <a href="https://stripe.com/docs/api/issuing/tokens/object">Token</a> object used for this transaction. If a network token was not used for this transaction, this field will be null.
 * @property null|\Stripe\StripeTaxForWooCommerce\SDK\lib\StripeObject $treasury <a href="https://stripe.com/docs/api/treasury">Treasury</a> details related to this transaction if it was created on a [FinancialAccount](/docs/api/treasury/financial_accounts
 * @property string $type The nature of the transaction.
 * @property null|string $wallet The digital wallet used for this transaction. One of <code>apple_pay</code>, <code>google_pay</code>, or <code>samsung_pay</code>.
 */
class Transaction extends \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiResource {

	const OBJECT_NAME = 'issuing.transaction';

	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\All;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Retrieve;
	use \Stripe\StripeTaxForWooCommerce\SDK\lib\ApiOperations\Update;

	const TYPE_CAPTURE = 'capture';
	const TYPE_REFUND  = 'refund';

	const WALLET_APPLE_PAY   = 'apple_pay';
	const WALLET_GOOGLE_PAY  = 'google_pay';
	const WALLET_SAMSUNG_PAY = 'samsung_pay';
}
