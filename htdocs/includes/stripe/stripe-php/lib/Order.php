<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Order objects are created to handle end customers' purchases of previously
 * defined <a href="https://stripe.com/docs/api#products">products</a>. You can
 * create, retrieve, and pay individual orders, as well as list all orders. Orders
 * are identified by a unique, random ID.
 *
 * Related guide: <a href="https://stripe.com/docs/orders">Tax, Shipping, and
 * Inventory</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount A positive integer in the smallest currency unit (that is, 100 cents for $1.00, or 1 for ¥1, Japanese Yen being a zero-decimal currency) representing the total amount for the order.
 * @property null|int $amount_returned The total amount that was returned to the customer.
 * @property null|string $application ID of the Connect Application that created the order.
 * @property null|int $application_fee A fee in cents that will be applied to the order and transferred to the application owner’s Stripe account. The request must be made with an OAuth key or the Stripe-Account header in order to take an application fee. For more information, see the application fees documentation.
 * @property null|string|\Stripe\Charge $charge The ID of the payment used to pay for the order. Present if the order status is <code>paid</code>, <code>fulfilled</code>, or <code>refunded</code>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string|\Stripe\Customer $customer The customer used for the order.
 * @property null|string $email The email address of the customer placing the order.
 * @property string $external_coupon_code External coupon code to load for this order.
 * @property \Stripe\OrderItem[] $items List of items constituting the order. An order can have up to 25 items.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\Collection $returns A list of returns that have taken place for this order.
 * @property null|string $selected_shipping_method The shipping method that is currently selected for this order, if any. If present, it is equal to one of the <code>id</code>s of shipping methods in the <code>shipping_methods</code> array. At order creation time, if there are multiple shipping methods, Stripe will automatically selected the first method.
 * @property null|\Stripe\StripeObject $shipping The shipping address for the order. Present if the order is for goods to be shipped.
 * @property null|\Stripe\StripeObject[] $shipping_methods A list of supported shipping methods for this order. The desired shipping method can be specified either by updating the order, or when paying it.
 * @property string $status Current order status. One of <code>created</code>, <code>paid</code>, <code>canceled</code>, <code>fulfilled</code>, or <code>returned</code>. More details in the <a href="https://stripe.com/docs/orders/guide#understanding-order-statuses">Orders Guide</a>.
 * @property null|\Stripe\StripeObject $status_transitions The timestamps at which the order status was updated.
 * @property null|int $updated Time at which the object was last updated. Measured in seconds since the Unix epoch.
 * @property string $upstream_id The user's order ID if it is different from the Stripe order ID.
 */
class Order extends ApiResource
{
    const OBJECT_NAME = 'order';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\OrderReturn the newly created return
     */
    public function returnOrder($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/returns';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);

        return Util\Util::convertToStripeObject($response, $opts);
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return Order the paid order
     */
    public function pay($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/pay';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
