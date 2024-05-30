<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A payment link is a shareable URL that will take your customers to a hosted
 * payment page. A payment link can be shared and used multiple times.
 *
 * When a customer opens a payment link it will open a new <a
 * href="https://stripe.com/docs/api/checkout/sessions">checkout session</a> to
 * render the payment page. You can use <a
 * href="https://stripe.com/docs/api/events/types#event_types-checkout.session.completed">checkout
 * session events</a> to track payments through payment links.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/payments/payment-links/api">Payment Links API</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Whether the payment link's <code>url</code> is active. If <code>false</code>, customers visiting the URL will be shown a page saying that the link has been deactivated.
 * @property \Stripe\StripeObject $after_completion
 * @property bool $allow_promotion_codes Whether user redeemable promotion codes are enabled.
 * @property null|int $application_fee_amount The amount of the application fee (if any) that will be requested to be applied to the payment and transferred to the application owner's Stripe account.
 * @property null|float $application_fee_percent This represents the percentage of the subscription invoice subtotal that will be transferred to the application owner's Stripe account.
 * @property \Stripe\StripeObject $automatic_tax
 * @property string $billing_address_collection Configuration for collecting the customer's billing address.
 * @property null|\Stripe\StripeObject $consent_collection When set, provides configuration to gather active consent from customers.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property \Stripe\StripeObject[] $custom_fields Collect additional information from your customer using custom fields. Up to 2 fields are supported.
 * @property \Stripe\StripeObject $custom_text
 * @property string $customer_creation Configuration for Customer creation during checkout.
 * @property null|\Stripe\StripeObject $invoice_creation Configuration for creating invoice for payment mode payment links.
 * @property null|\Stripe\Collection<\Stripe\LineItem> $line_items The line items representing what is being sold.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string|\Stripe\Account $on_behalf_of The account on behalf of which to charge. See the <a href="https://support.stripe.com/questions/sending-invoices-on-behalf-of-connected-accounts">Connect documentation</a> for details.
 * @property null|\Stripe\StripeObject $payment_intent_data Indicates the parameters to be passed to PaymentIntent creation during checkout.
 * @property string $payment_method_collection Configuration for collecting a payment method during checkout.
 * @property null|string[] $payment_method_types The list of payment method types that customers can use. When <code>null</code>, Stripe will dynamically show relevant payment methods you've enabled in your <a href="https://dashboard.stripe.com/settings/payment_methods">payment method settings</a>.
 * @property \Stripe\StripeObject $phone_number_collection
 * @property null|\Stripe\StripeObject $shipping_address_collection Configuration for collecting the customer's shipping address.
 * @property \Stripe\StripeObject[] $shipping_options The shipping rate options applied to the session.
 * @property string $submit_type Indicates the type of transaction being performed which customizes relevant text on the page, such as the submit button.
 * @property null|\Stripe\StripeObject $subscription_data When creating a subscription, the specified configuration data will be used. There must be at least one line item with a recurring price to use <code>subscription_data</code>.
 * @property \Stripe\StripeObject $tax_id_collection
 * @property null|\Stripe\StripeObject $transfer_data The account (if any) the payments will be attributed to for tax reporting, and where funds from each payment will be transferred to.
 * @property string $url The public URL that can be shared with customers.
 */
class PaymentLink extends ApiResource
{
    const OBJECT_NAME = 'payment_link';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const BILLING_ADDRESS_COLLECTION_AUTO = 'auto';
    const BILLING_ADDRESS_COLLECTION_REQUIRED = 'required';

    const CUSTOMER_CREATION_ALWAYS = 'always';
    const CUSTOMER_CREATION_IF_REQUIRED = 'if_required';

    const PAYMENT_METHOD_COLLECTION_ALWAYS = 'always';
    const PAYMENT_METHOD_COLLECTION_IF_REQUIRED = 'if_required';

    const SUBMIT_TYPE_AUTO = 'auto';
    const SUBMIT_TYPE_BOOK = 'book';
    const SUBMIT_TYPE_DONATE = 'donate';
    const SUBMIT_TYPE_PAY = 'pay';

    /**
     * @param string $id
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\LineItem> list of LineItems
     */
    public static function allLineItems($id, $params = null, $opts = null)
    {
        $url = static::resourceUrl($id) . '/line_items';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }
}
