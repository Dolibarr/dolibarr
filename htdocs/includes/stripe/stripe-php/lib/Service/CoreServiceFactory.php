<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

/**
 * Service factory class for API resources in the root namespace.
 *
 * @property AccountLinkService $accountLinks
 * @property AccountService $accounts
 * @property ApplePayDomainService $applePayDomains
 * @property ApplicationFeeService $applicationFees
 * @property Apps\AppsServiceFactory $apps
 * @property BalanceService $balance
 * @property BalanceTransactionService $balanceTransactions
 * @property BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property ChargeService $charges
 * @property Checkout\CheckoutServiceFactory $checkout
 * @property CountrySpecService $countrySpecs
 * @property CouponService $coupons
 * @property CreditNoteService $creditNotes
 * @property CustomerService $customers
 * @property DisputeService $disputes
 * @property EphemeralKeyService $ephemeralKeys
 * @property EventService $events
 * @property ExchangeRateService $exchangeRates
 * @property FileLinkService $fileLinks
 * @property FileService $files
 * @property FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property Identity\IdentityServiceFactory $identity
 * @property InvoiceItemService $invoiceItems
 * @property InvoiceService $invoices
 * @property Issuing\IssuingServiceFactory $issuing
 * @property MandateService $mandates
 * @property OAuthService $oauth
 * @property PaymentIntentService $paymentIntents
 * @property PaymentLinkService $paymentLinks
 * @property PaymentMethodService $paymentMethods
 * @property PayoutService $payouts
 * @property PlanService $plans
 * @property PriceService $prices
 * @property ProductService $products
 * @property PromotionCodeService $promotionCodes
 * @property QuoteService $quotes
 * @property Radar\RadarServiceFactory $radar
 * @property RefundService $refunds
 * @property Reporting\ReportingServiceFactory $reporting
 * @property ReviewService $reviews
 * @property SetupAttemptService $setupAttempts
 * @property SetupIntentService $setupIntents
 * @property ShippingRateService $shippingRates
 * @property Sigma\SigmaServiceFactory $sigma
 * @property SourceService $sources
 * @property SubscriptionItemService $subscriptionItems
 * @property SubscriptionService $subscriptions
 * @property SubscriptionScheduleService $subscriptionSchedules
 * @property TaxCodeService $taxCodes
 * @property TaxRateService $taxRates
 * @property Terminal\TerminalServiceFactory $terminal
 * @property TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property TokenService $tokens
 * @property TopupService $topups
 * @property TransferService $transfers
 * @property Treasury\TreasuryServiceFactory $treasury
 * @property WebhookEndpointService $webhookEndpoints
 */
class CoreServiceFactory extends \Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'accountLinks' => AccountLinkService::class,
        'accounts' => AccountService::class,
        'applePayDomains' => ApplePayDomainService::class,
        'applicationFees' => ApplicationFeeService::class,
        'apps' => Apps\AppsServiceFactory::class,
        'balance' => BalanceService::class,
        'balanceTransactions' => BalanceTransactionService::class,
        'billingPortal' => BillingPortal\BillingPortalServiceFactory::class,
        'charges' => ChargeService::class,
        'checkout' => Checkout\CheckoutServiceFactory::class,
        'countrySpecs' => CountrySpecService::class,
        'coupons' => CouponService::class,
        'creditNotes' => CreditNoteService::class,
        'customers' => CustomerService::class,
        'disputes' => DisputeService::class,
        'ephemeralKeys' => EphemeralKeyService::class,
        'events' => EventService::class,
        'exchangeRates' => ExchangeRateService::class,
        'fileLinks' => FileLinkService::class,
        'files' => FileService::class,
        'financialConnections' => FinancialConnections\FinancialConnectionsServiceFactory::class,
        'identity' => Identity\IdentityServiceFactory::class,
        'invoiceItems' => InvoiceItemService::class,
        'invoices' => InvoiceService::class,
        'issuing' => Issuing\IssuingServiceFactory::class,
        'mandates' => MandateService::class,
        'oauth' => OAuthService::class,
        'paymentIntents' => PaymentIntentService::class,
        'paymentLinks' => PaymentLinkService::class,
        'paymentMethods' => PaymentMethodService::class,
        'payouts' => PayoutService::class,
        'plans' => PlanService::class,
        'prices' => PriceService::class,
        'products' => ProductService::class,
        'promotionCodes' => PromotionCodeService::class,
        'quotes' => QuoteService::class,
        'radar' => Radar\RadarServiceFactory::class,
        'refunds' => RefundService::class,
        'reporting' => Reporting\ReportingServiceFactory::class,
        'reviews' => ReviewService::class,
        'setupAttempts' => SetupAttemptService::class,
        'setupIntents' => SetupIntentService::class,
        'shippingRates' => ShippingRateService::class,
        'sigma' => Sigma\SigmaServiceFactory::class,
        'sources' => SourceService::class,
        'subscriptionItems' => SubscriptionItemService::class,
        'subscriptions' => SubscriptionService::class,
        'subscriptionSchedules' => SubscriptionScheduleService::class,
        'taxCodes' => TaxCodeService::class,
        'taxRates' => TaxRateService::class,
        'terminal' => Terminal\TerminalServiceFactory::class,
        'testHelpers' => TestHelpers\TestHelpersServiceFactory::class,
        'tokens' => TokenService::class,
        'topups' => TopupService::class,
        'transfers' => TransferService::class,
        'treasury' => Treasury\TreasuryServiceFactory::class,
        'webhookEndpoints' => WebhookEndpointService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
