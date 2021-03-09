<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * @deprecated Bitcoin receivers are deprecated. Please use the sources API instead.
 * @see https://stripe.com/docs/sources/bitcoin
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active True when this bitcoin receiver has received a non-zero amount of bitcoin.
 * @property int $amount The amount of <code>currency</code> that you are collecting as payment.
 * @property int $amount_received The amount of <code>currency</code> to which <code>bitcoin_amount_received</code> has been converted.
 * @property int $bitcoin_amount The amount of bitcoin that the customer should send to fill the receiver. The <code>bitcoin_amount</code> is denominated in Satoshi: there are 10^8 Satoshi in one bitcoin.
 * @property int $bitcoin_amount_received The amount of bitcoin that has been sent by the customer to this receiver.
 * @property string $bitcoin_uri This URI can be displayed to the customer as a clickable link (to activate their bitcoin client) or as a QR code (for mobile wallets).
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://stripe.com/docs/currencies">ISO code for the currency</a> to which the bitcoin will be converted.
 * @property null|string $customer The customer ID of the bitcoin receiver.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|string $email The customer's email address, set by the API call that creates the receiver.
 * @property bool $filled This flag is initially false and updates to true when the customer sends the <code>bitcoin_amount</code> to this receiver.
 * @property string $inbound_address A bitcoin address that is specific to this receiver. The customer can send bitcoin to this address to fill the receiver.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $payment The ID of the payment created from the receiver, if any. Hidden when viewing the receiver with a publishable key.
 * @property null|string $refund_address The refund address of this bitcoin receiver.
 * @property \Stripe\Collection $transactions A list with one entry for each time that the customer sent bitcoin to the receiver. Hidden when viewing the receiver with a publishable key.
 * @property bool $uncaptured_funds This receiver contains uncaptured funds that can be used for a payment or refunded.
 * @property null|bool $used_for_payment Indicate if this source is used for payment.
 */
class BitcoinReceiver extends ApiResource
{
    const OBJECT_NAME = 'bitcoin_receiver';

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * @return string The class URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public static function classUrl()
    {
        return '/v1/bitcoin/receivers';
    }

    /**
     * @return string The instance URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public function instanceUrl()
    {
        if ($this['customer']) {
            $base = Customer::classUrl();
            $parent = $this['customer'];
            $path = 'sources';
            $parentExtn = \urlencode(Util\Util::utf8($parent));
            $extn = \urlencode(Util\Util::utf8($this['id']));

            return "{$base}/{$parentExtn}/{$path}/{$extn}";
        }

        $base = BitcoinReceiver::classUrl();
        $extn = \urlencode(Util\Util::utf8($this['id']));

        return "{$base}/{$extn}";
    }
}
