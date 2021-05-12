<?php

namespace Stripe;

/**
 * Class Payout
 *
 * @property string $id
 * @property string $object
 * @property int $amount
<<<<<<< HEAD
 * @property string $balance_transaction
 * @property string $cancellation_balance_transaction
 * @property int $created
 * @property string $currency
 * @property int $arrival_date
 * @property string $destination
=======
 * @property int $arrival_date
 * @property bool $automatic
 * @property string $balance_transaction
 * @property int $created
 * @property string $currency
 * @property string $description
 * @property string $destination
 * @property string $failure_balance_transaction
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property string $failure_code
 * @property string $failure_message
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $method
<<<<<<< HEAD
 * @property string $recipient
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property string $source_type
 * @property string $statement_descriptor
 * @property string $status
 * @property string $type
 *
 * @package Stripe
 */
class Payout extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "payout";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
<<<<<<< HEAD
=======
     * Types of payout failure codes.
     * @link https://stripe.com/docs/api#payout_failures
     */
    const FAILURE_ACCOUNT_CLOSED                = 'account_closed';
    const FAILURE_ACCOUNT_FROZEN                = 'account_frozen';
    const FAILURE_BANK_ACCOUNT_RESTRICTED       = 'bank_account_restricted';
    const FAILURE_BANK_OWNERSHIP_CHANGED        = 'bank_ownership_changed';
    const FAILURE_COULD_NOT_PROCESS             = 'could_not_process';
    const FAILURE_DEBIT_NOT_AUTHORIZED          = 'debit_not_authorized';
    const FAILURE_DECLINED                      = 'declined';
    const FAILURE_INCORRECT_ACCOUNT_HOLDER_NAME = 'incorrect_account_holder_name';
    const FAILURE_INSUFFICIENT_FUNDS            = 'insufficient_funds';
    const FAILURE_INVALID_ACCOUNT_NUMBER        = 'invalid_account_number';
    const FAILURE_INVALID_CURRENCY              = 'invalid_currency';
    const FAILURE_NO_ACCOUNT                    = 'no_account';
    const FAILURE_UNSUPPORTED_CARD              = 'unsupported_card';

    /**
     * Possible string representations of the payout methods.
     * @link https://stripe.com/docs/api/payouts/object#payout_object-method
     */
    const METHOD_STANDARD = 'standard';
    const METHOD_INSTANT  = 'instant';

    /**
     * Possible string representations of the status of the payout.
     * @link https://stripe.com/docs/api/payouts/object#payout_object-status
     */
    const STATUS_CANCELED   = 'canceled';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_FAILED     = 'failed';
    const STATUS_PAID       = 'paid';
    const STATUS_PENDING    = 'pending';

    /**
     * Possible string representations of the type of payout.
     * @link https://stripe.com/docs/api/payouts/object#payout_object-type
     */
    const TYPE_BANK_ACCOUNT = 'bank_account';
    const TYPE_CARD         = 'card';

    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     * @return Payout The canceled payout.
     */
    public function cancel()
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
