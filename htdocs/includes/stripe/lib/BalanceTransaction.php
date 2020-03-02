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

    const OBJECT_NAME = "balance_transaction";

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * Possible string representations of the type of balance transaction.
     * @link https://stripe.com/docs/api/balance/balance_transaction#balance_transaction_object-type
     */
    const TYPE_ADJUSTMENT                    = 'adjustment';
    const TYPE_ADVANCE                       = 'advance';
    const TYPE_ADVANCE_FUNDING               = 'advance_funding';
    const TYPE_APPLICATION_FEE               = 'application_fee';
    const TYPE_APPLICATION_FEE_REFUND        = 'application_fee_refund';
    const TYPE_CHARGE                        = 'charge';
    const TYPE_CONNECT_COLLECTION_TRANSFER   = 'connect_collection_transfer';
    const TYPE_ISSUING_AUTHORIZATION_HOLD    = 'issuing_authorization_hold';
    const TYPE_ISSUING_AUTHORIZATION_RELEASE = 'issuing_authorization_release';
    const TYPE_ISSUING_TRANSACTION           = 'issuing_transaction';
    const TYPE_PAYMENT                       = 'payment';
    const TYPE_PAYMENT_FAILURE_REFUND        = 'payment_failure_refund';
    const TYPE_PAYMENT_REFUND                = 'payment_refund';
    const TYPE_PAYOUT                        = 'payout';
    const TYPE_PAYOUT_CANCEL                 = 'payout_cancel';
    const TYPE_PAYOUT_FAILURE                = 'payout_failure';
    const TYPE_REFUND                        = 'refund';
    const TYPE_REFUND_FAILURE                = 'refund_failure';
    const TYPE_RESERVE_TRANSACTION           = 'reserve_transaction';
    const TYPE_RESERVED_FUNDS                = 'reserved_funds';
    const TYPE_STRIPE_FEE                    = 'stripe_fee';
    const TYPE_STRIPE_FX_FEE                 = 'stripe_fx_fee';
    const TYPE_TAX_FEE                       = 'tax_fee';
    const TYPE_TOPUP                         = 'topup';
    const TYPE_TOPUP_REVERSAL                = 'topup_reversal';
    const TYPE_TRANSFER                      = 'transfer';
    const TYPE_TRANSFER_CANCEL               = 'transfer_cancel';
    const TYPE_TRANSFER_FAILURE              = 'transfer_failure';
    const TYPE_TRANSFER_REFUND               = 'transfer_refund';
}
