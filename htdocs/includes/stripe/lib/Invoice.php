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
 * @property bool $auto_advance
 * @property string $billing
 * @property string $billing_reason
 * @property string $charge
 * @property string $currency
 * @property string $customer
 * @property int $date
 * @property string $description
 * @property Discount $discount
 * @property int $due_date
 * @property int $ending_balance
 * @property string $hosted_invoice_url
 * @property string $invoice_pdf
 * @property int $last_payment_attempt
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
 * @property string $status
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

    const OBJECT_NAME = "invoice";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The finalized invoice.
     */
    public function finalizeInvoice($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/finalize';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The uncollectible invoice.
     */
    public function markUncollectible($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/mark_uncollectible';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The paid invoice.
     */
    public function pay($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/pay';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The sent invoice.
     */
    public function sendInvoice($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/send';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

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
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Invoice The voided invoice.
     */
    public function voidInvoice($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/void';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
