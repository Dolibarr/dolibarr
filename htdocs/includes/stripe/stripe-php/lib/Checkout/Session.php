<?php

// File generated from our OpenAPI spec

namespace Stripe\Checkout;

/**
 * A Checkout Session represents your customer's session as they pay for one-time
 * purchases or subscriptions through <a
 * href="https://stripe.com/docs/payments/checkout">Checkout</a>. We recommend
 * creating a new Session each time your customer attempts to pay.
 *
 * Once payment is successful, the Checkout Session will contain a reference to the
 * <a href="https://stripe.com/docs/api/customers">Customer</a>, and either the
 * successful <a
 * href="https://stripe.com/docs/api/payment_intents">PaymentIntent</a> or an
 * active <a href="https://stripe.com/docs/api/subscriptions">Subscription</a>.
 *
 * You can create a Checkout Session on your server and pass its ID to the client
 * to begin Checkout.
 *
 * Related guide: <a href="https://stripe.com/docs/payments/checkout/api">Checkout
 * Server Quickstart</a>.
 *
 * @property string $id Unique identifier for the object. Used to pass to <code>redirectToCheckout</code> in Stripe.js.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|bool $allow_promotion_codes Enables user redeemable promotion codes.
 * @property null|int $amount_subtotal Total of all items before discounts or taxes are applied.
 * @property null|int $amount_total Total of all items after discounts and taxes are applied.
 * @property null|string $billing_address_collection Describes whether Checkout should collect the customer's billing address.
 * @property string $cancel_url The URL the customer will be directed to if they decide to cancel payment and return to your website.
 * @property null|string $client_reference_id A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
 * @property null|string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string|\Stripe\Customer $customer The ID of the customer for this session. For Checkout Sessions in <code>payment</code> or <code>subscription</code> mode, Checkout will create a new customer object based on information provided during the session unless an existing customer was provided when the session was created.
 * @property null|string $customer_email If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file. To access information about the customer once a session is complete, use the <code>customer</code> attribute.
 * @property \Stripe\Collection $line_items The line items purchased by the customer.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $locale The IETF language tag of the locale Checkout is displayed in. If blank or <code>auto</code>, the browser's locale is used.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property string $mode The mode of the Checkout Session.
 * @property null|string|\Stripe\PaymentIntent $payment_intent The ID of the PaymentIntent for Checkout Sessions in <code>payment</code> mode.
 * @property string[] $payment_method_types A list of the types of payment methods (e.g. card) this Checkout Session is allowed to accept.
 * @property string $payment_status The payment status of the Checkout Session, one of <code>paid</code>, <code>unpaid</code>, or <code>no_payment_required</code>. You can use this value to decide when to fulfill your customer's order.
 * @property null|string|\Stripe\SetupIntent $setup_intent The ID of the SetupIntent for Checkout Sessions in <code>setup</code> mode.
 * @property null|\Stripe\StripeObject $shipping Shipping information for this Checkout Session.
 * @property null|\Stripe\StripeObject $shipping_address_collection When set, provides configuration for Checkout to collect a shipping address from a customer.
 * @property null|string $submit_type Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button. <code>submit_type</code> can only be specified on Checkout Sessions in <code>payment</code> mode, but not Checkout Sessions in <code>subscription</code> or <code>setup</code> mode.
 * @property null|string|\Stripe\Subscription $subscription The ID of the subscription for Checkout Sessions in <code>subscription</code> mode.
 * @property string $success_url The URL the customer will be directed to after the payment or subscription creation is successful.
 * @property null|\Stripe\StripeObject $total_details Tax and discount details for the computed total amount.
 */
class Session extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'checkout.session';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\NestedResource;
    use \Stripe\ApiOperations\Retrieve;

    const BILLING_ADDRESS_COLLECTION_AUTO = 'auto';
    const BILLING_ADDRESS_COLLECTION_REQUIRED = 'required';

    const PAYMENT_STATUS_NO_PAYMENT_REQUIRED = 'no_payment_required';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_UNPAID = 'unpaid';

    const SUBMIT_TYPE_AUTO = 'auto';
    const SUBMIT_TYPE_BOOK = 'book';
    const SUBMIT_TYPE_DONATE = 'donate';
    const SUBMIT_TYPE_PAY = 'pay';

    const PATH_LINE_ITEMS = '/line_items';

    /**
     * @param string $id the ID of the session on which to retrieve the items
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection the list of items
     */
    public static function allLineItems($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_LINE_ITEMS, $params, $opts);
    }
}
