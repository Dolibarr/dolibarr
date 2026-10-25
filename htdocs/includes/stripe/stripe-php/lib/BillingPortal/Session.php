<?php

// File generated from our OpenAPI spec

namespace Stripe\BillingPortal;

/**
 * The Billing customer portal is a Stripe-hosted UI for subscription and billing
 * management.
 *
 * A portal configuration describes the functionality and features that you want to
 * provide to your customers through the portal.
 *
 * A portal session describes the instantiation of the customer portal for a
 * particular customer. By visiting the session's URL, the customer can manage
 * their subscriptions and billing details. For security reasons, sessions are
 * short-lived and will expire if the customer does not visit the URL. Create
 * sessions on-demand when customers intend to manage their subscriptions and
 * billing details.
 *
 * Learn more in the <a
 * href="https://stripe.com/docs/billing/subscriptions/integrating-customer-portal">integration
 * guide</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property string|\Stripe\BillingPortal\Configuration $configuration The configuration used by this session, describing the features available.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $customer The ID of the customer for this session.
 * @property null|\Stripe\StripeObject $flow Information about a specific flow for the customer to go through. See the <a href="https://stripe.com/docs/customer-management/portal-deep-links">docs</a> to learn more about using customer portal deep links and flows.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $locale The IETF language tag of the locale Customer Portal is displayed in. If blank or auto, the customer’s <code>preferred_locales</code> or browser’s locale is used.
 * @property null|string $on_behalf_of The account for which the session was created on behalf of. When specified, only subscriptions and invoices with this <code>on_behalf_of</code> account appear in the portal. For more information, see the <a href="https://stripe.com/docs/connect/charges-transfers#on-behalf-of">docs</a>. Use the <a href="https://stripe.com/docs/api/accounts/object#account_object-settings-branding">Accounts API</a> to modify the <code>on_behalf_of</code> account's branding settings, which the portal displays.
 * @property null|string $return_url The URL to redirect customers to when they click on the portal's link to return to your website.
 * @property string $url The short-lived URL of the session that gives customers access to the customer portal.
 */
class Session extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'billing_portal.session';

    use \Stripe\ApiOperations\Create;
}
