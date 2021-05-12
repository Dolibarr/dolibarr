<?php

namespace Stripe;

/**
 * Class Order
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $amount_returned
 * @property string $application
 * @property int $application_fee
 * @property string $charge
 * @property int $created
 * @property string $currency
 * @property string $customer
 * @property string $email
 * @property string $external_coupon_code
<<<<<<< HEAD
 * @property mixed $items
=======
 * @property OrderItem[] $items
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property Collection $returns
 * @property string $selected_shipping_method
 * @property mixed $shipping
 * @property mixed $shipping_methods
 * @property string $status
 * @property mixed $status_transitions
 * @property int $updated
 * @property string $upstream_id
 *
 * @package Stripe
 */
class Order extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "order";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @return Order The paid order.
     */
    public function pay($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/pay';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @return OrderReturn The newly created return.
     */
    public function returnOrder($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/returns';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        return Util\Util::convertToStripeObject($response, $opts);
    }
}
