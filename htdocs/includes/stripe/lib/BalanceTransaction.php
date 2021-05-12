<?php

namespace Stripe;

/**
 * Class BalanceTransaction
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $available_on
 * @property int $created
 * @property string $currency
 * @property string $description
 * @property float $exchange_rate
 * @property int $fee
 * @property mixed $fee_details
 * @property int $net
 * @property string $source
 * @property string $status
 * @property string $type
 *
 * @package Stripe
 */
class BalanceTransaction extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "balance_transaction";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * @return string The class URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public static function classUrl()
    {
        return "/v1/balance/history";
    }
}
