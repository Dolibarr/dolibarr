<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * <a href="https://stripe.com/docs/connect">Stripe Connect</a> platforms can
 * reverse transfers made to a connected account, either entirely or partially, and
 * can also specify whether to refund any related application fees. Transfer
 * reversals add to the platform's balance and subtract from the destination
 * account's balance.
 *
 * Reversing a transfer that was made for a <a
 * href="/docs/connect/destination-charges">destination charge</a> is allowed only
 * up to the amount of the charge. It is possible to reverse a <a
 * href="https://stripe.com/docs/connect/charges-transfers#transfer-options">transfer_group</a>
 * transfer only if the destination account has enough balance to cover the
 * reversal.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/connect/charges-transfers#reversing-transfers">Reversing
 * Transfers</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount, in %s.
 * @property null|string|\Stripe\BalanceTransaction $balance_transaction Balance transaction that describes the impact on your account balance.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string|\Stripe\Refund $destination_payment_refund Linked payment refund for the transfer reversal.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string|\Stripe\Refund $source_refund ID of the refund responsible for the transfer reversal.
 * @property string|\Stripe\Transfer $transfer ID of the transfer that was reversed.
 */
class TransferReversal extends ApiResource
{
    const OBJECT_NAME = 'transfer_reversal';

    use ApiOperations\Update {
        save as protected _save;
    }

    /**
     * @return string the API URL for this Stripe transfer reversal
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $transfer = $this['transfer'];
        if (!$id) {
            throw new Exception\UnexpectedValueException(
                'Could not determine which URL to request: ' .
                "class instance has invalid ID: {$id}",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $transfer = Util\Util::utf8($transfer);

        $base = Transfer::classUrl();
        $transferExtn = \urlencode($transfer);
        $extn = \urlencode($id);

        return "{$base}/{$transferExtn}/reversals/{$extn}";
    }

    /**
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return TransferReversal the saved reversal
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }
}
