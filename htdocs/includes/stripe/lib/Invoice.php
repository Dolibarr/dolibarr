<?php

namespace Stripe;

/**
 * Class Invoice
 *
 * @property string $id
 * @property string $object
 * @property int $amount_due
 * @property int $amount_paid
 * @property int $amount_remaining
 * @property int $application_fee
 * @property int $attempt_count
 * @property bool $attempted
 * @property string $billing
 * @property string $charge
 * @property bool $closed
 * @property string $currency
 * @property string $customer
 * @property int $date
 * @property string $description
 * @property mixed $discount
 * @property int $due_date
 * @property int $ending_balance
 * @property bool $forgiven
 * @property Collection $lines
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property int $next_payment_attempt
 * @property string $number
 * @property bool $paid
 * @property int $period_end
 * @property int $period_start
 * @property string $receipt_number
 * @property int $starting_balance
 * @property string $statement_descriptor
 * @property string $subscription
 * @property int $subscription_proration_date
 * @property int $subtotal
 * @property int $tax
 * @property float $tax_percent
 * @property int $total
 * @property int $webhooks_delivered_at
 *
 * @package Stripe
 */
class Invoice extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The upcoming invoice.
     */
    public static function upcoming($params = null, $opts = null)
    {
        $url = static::classUrl() . '/upcoming';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }

    /**
     * @return Invoice The paid invoice.
     */
    public function pay($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/pay';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
