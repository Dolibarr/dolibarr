<?php

namespace Stripe;

/**
 * Class ApplicationFeeRefund
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $balance_transaction
 * @property int $created
 * @property string $currency
 * @property string $fee
 * @property StripeObject $metadata
 *
 * @package Stripe
 */
class ApplicationFeeRefund extends ApiResource
{
    use ApiOperations\Update {
        save as protected _save;
    }

    /**
     * @return string The API URL for this Stripe refund.
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $fee = $this['fee'];
        if (!$id) {
            throw new Error\InvalidRequest(
                "Could not determine which URL to request: " .
                "class instance has invalid ID: $id",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $fee = Util\Util::utf8($fee);

        $base = ApplicationFee::classUrl();
        $feeExtn = urlencode($fee);
        $extn = urlencode($id);
        return "$base/$feeExtn/refunds/$extn";
    }

    /**
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund The saved refund.
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }
}
