<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib;

/**
 * Client used to send requests to Stripe\StripeTaxForWooCommerce\SDK\lib's API.
 *
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\OAuthService $oauth
 * // The beginning of the section generated from our OpenAPI spec
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AccountLinkService $accountLinks
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AccountService $accounts
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AccountSessionService $accountSessions
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ApplePayDomainService $applePayDomains
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ApplicationFeeService $applicationFees
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Apps\AppsServiceFactory $apps
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\BalanceService $balance
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\BalanceTransactionService $balanceTransactions
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ChargeService $charges
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Climate\ClimateServiceFactory $climate
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CountrySpecService $countrySpecs
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CouponService $coupons
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CreditNoteService $creditNotes
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CustomerService $customers
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\DisputeService $disputes
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\EphemeralKeyService $ephemeralKeys
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\EventService $events
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ExchangeRateService $exchangeRates
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\FileLinkService $fileLinks
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\FileService $files
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Identity\IdentityServiceFactory $identity
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\InvoiceItemService $invoiceItems
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\InvoiceService $invoices
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Issuing\IssuingServiceFactory $issuing
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\MandateService $mandates
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PaymentIntentService $paymentIntents
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PaymentLinkService $paymentLinks
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PaymentMethodConfigurationService $paymentMethodConfigurations
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PaymentMethodDomainService $paymentMethodDomains
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PaymentMethodService $paymentMethods
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PayoutService $payouts
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PlanService $plans
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PriceService $prices
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ProductService $products
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\PromotionCodeService $promotionCodes
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\QuoteService $quotes
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Radar\RadarServiceFactory $radar
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\RefundService $refunds
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Reporting\ReportingServiceFactory $reporting
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ReviewService $reviews
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SetupAttemptService $setupAttempts
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SetupIntentService $setupIntents
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\ShippingRateService $shippingRates
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Sigma\SigmaServiceFactory $sigma
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SourceService $sources
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SubscriptionItemService $subscriptionItems
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SubscriptionService $subscriptions
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Tax\TaxServiceFactory $tax
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TaxCodeService $taxCodes
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TaxRateService $taxRates
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Terminal\TerminalServiceFactory $terminal
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TokenService $tokens
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TopupService $topups
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\TransferService $transfers
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Treasury\TreasuryServiceFactory $treasury
 * @property \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\WebhookEndpointService $webhookEndpoints
 * // The end of the section generated from our OpenAPI spec
 */
class StripeClient extends BaseStripeClient {

	/**
	 * @var \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CoreServiceFactory
	 */
	private $coreServiceFactory;

	public function __get( $name ) {
		return $this->getService( $name );
	}

	public function getService( $name ) {
		if ( null === $this->coreServiceFactory ) {
			$this->coreServiceFactory = new \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\CoreServiceFactory( $this );
		}

		return $this->coreServiceFactory->getService( $name );
	}
}
