<?php

namespace Stripe;

/**
 * Class Event
 *
 * @property string $id
 * @property string $object
 * @property string $account
 * @property string $api_version
 * @property int    $created
 * @property mixed  $data
 * @property bool   $livemode
 * @property int    $pending_webhooks
 * @property mixed  $request
 * @property string $type
 *
 * @package Stripe
 */
class Event extends ApiResource
{

    const OBJECT_NAME = "event";

    /**
     * Possible string representations of event types.
     * @link https://stripe.com/docs/api#event_types
     */
    const ACCOUNT_UPDATED                           = 'account.updated';
    const ACCOUNT_APPLICATION_AUTHORIZED            = 'account.application.authorized';
    const ACCOUNT_APPLICATION_DEAUTHORIZED          = 'account.application.deauthorized';
    const ACCOUNT_EXTERNAL_ACCOUNT_CREATED          = 'account.external_account.created';
    const ACCOUNT_EXTERNAL_ACCOUNT_DELETED          = 'account.external_account.deleted';
    const ACCOUNT_EXTERNAL_ACCOUNT_UPDATED          = 'account.external_account.updated';
    const APPLICATION_FEE_CREATED                   = 'application_fee.created';
    const APPLICATION_FEE_REFUNDED                  = 'application_fee.refunded';
    const APPLICATION_FEE_REFUND_UPDATED            = 'application_fee.refund.updated';
    const BALANCE_AVAILABLE                         = 'balance.available';
    const CHARGE_CAPTURED                           = 'charge.captured';
    const CHARGE_EXPIRED                            = 'charge.expired';
    const CHARGE_FAILED                             = 'charge.failed';
    const CHARGE_PENDING                            = 'charge.pending';
    const CHARGE_REFUNDED                           = 'charge.refunded';
    const CHARGE_SUCCEEDED                          = 'charge.succeeded';
    const CHARGE_UPDATED                            = 'charge.updated';
    const CHARGE_DISPUTE_CLOSED                     = 'charge.dispute.closed';
    const CHARGE_DISPUTE_CREATED                    = 'charge.dispute.created';
    const CHARGE_DISPUTE_FUNDS_REINSTATED           = 'charge.dispute.funds_reinstated';
    const CHARGE_DISPUTE_FUNDS_WITHDRAWN            = 'charge.dispute.funds_withdrawn';
    const CHARGE_DISPUTE_UPDATED                    = 'charge.dispute.updated';
    const CHARGE_REFUND_UPDATED                     = 'charge.refund.updated';
    const CHECKOUT_SESSION_COMPLETED                = 'checkout.session.completed';
    const COUPON_CREATED                            = 'coupon.created';
    const COUPON_DELETED                            = 'coupon.deleted';
    const COUPON_UPDATED                            = 'coupon.updated';
    const CREDIT_NOTE_CREATED                       = 'credit_note.created';
    const CREDIT_NOTE_UPDATED                       = 'credit_note.updated';
    const CREDIT_NOTE_VOIDED                        = 'credit_note.voided';
    const CUSTOMER_CREATED                          = 'customer.created';
    const CUSTOMER_DELETED                          = 'customer.deleted';
    const CUSTOMER_UPDATED                          = 'customer.updated';
    const CUSTOMER_DISCOUNT_CREATED                 = 'customer.discount.created';
    const CUSTOMER_DISCOUNT_DELETED                 = 'customer.discount.deleted';
    const CUSTOMER_DISCOUNT_UPDATED                 = 'customer.discount.updated';
    const CUSTOMER_SOURCE_CREATED                   = 'customer.source.created';
    const CUSTOMER_SOURCE_DELETED                   = 'customer.source.deleted';
    const CUSTOMER_SOURCE_EXPIRING                  = 'customer.source.expiring';
    const CUSTOMER_SOURCE_UPDATED                   = 'customer.source.updated';
    const CUSTOMER_SUBSCRIPTION_CREATED             = 'customer.subscription.created';
    const CUSTOMER_SUBSCRIPTION_DELETED             = 'customer.subscription.deleted';
    const CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END      = 'customer.subscription.trial_will_end';
    const CUSTOMER_SUBSCRIPTION_UPDATED             = 'customer.subscription.updated';
    const FILE_CREATED                              = 'file.created';
    const INVOICE_CREATED                           = 'invoice.created';
    const INVOICE_DELETED                           = 'invoice.deleted';
    const INVOICE_FINALIZED                         = 'invoice.finalized';
    const INVOICE_MARKED_UNCOLLECTIBLE              = 'invoice.marked_uncollectible';
    const INVOICE_PAYMENT_ACTION_REQUIRED           = 'invoice.payment_action_required';
    const INVOICE_PAYMENT_FAILED                    = 'invoice.payment_failed';
    const INVOICE_PAYMENT_SUCCEEDED                 = 'invoice.payment_succeeded';
    const INVOICE_SENT                              = 'invoice.sent';
    const INVOICE_UPCOMING                          = 'invoice.upcoming';
    const INVOICE_UPDATED                           = 'invoice.updated';
    const INVOICE_VOIDED                            = 'invoice.voided';
    const INVOICEITEM_CREATED                       = 'invoiceitem.created';
    const INVOICEITEM_DELETED                       = 'invoiceitem.deleted';
    const INVOICEITEM_UPDATED                       = 'invoiceitem.updated';
    const ISSUER_FRAUD_RECORD_CREATED               = 'issuer_fraud_record.created';
    const ISSUING_AUTHORIZATION_CREATED             = 'issuing_authorization.created';
    const ISSUING_AUTHORIZATION_REQUEST             = 'issuing_authorization.request';
    const ISSUING_AUTHORIZATION_UPDATED             = 'issuing_authorization.updated';
    const ISSUING_CARD_CREATED                      = 'issuing_card.created';
    const ISSUING_CARD_UPDATED                      = 'issuing_card.updated';
    const ISSUING_CARDHOLDER_CREATED                = 'issuing_cardholder.created';
    const ISSUING_CARDHOLDER_UPDATED                = 'issuing_cardholder.updated';
    const ISSUING_DISPUTE_CREATED                   = 'issuing_dispute.created';
    const ISSUING_DISPUTE_UPDATED                   = 'issuing_dispute.updated';
    const ISSUING_TRANSACTION_CREATED               = 'issuing_transaction.created';
    const ISSUING_TRANSACTION_UPDATED               = 'issuing_transaction.updated';
    const ORDER_CREATED                             = 'order.created';
    const ORDER_PAYMENT_FAILED                      = 'order.payment_failed';
    const ORDER_PAYMENT_SUCCEEDED                   = 'order.payment_succeeded';
    const ORDER_UPDATED                             = 'order.updated';
    const ORDER_RETURN_CREATED                      = 'order_return.created';
    const PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED  = 'payment_intent.amount_capturable_updated';
    const PAYMENT_INTENT_CREATED                    = 'payment_intent.created';
    const PAYMENT_INTENT_PAYMENT_FAILED             = 'payment_intent.payment_failed';
    const PAYMENT_INTENT_SUCCEEDED                  = 'payment_intent.succeeded';
    const PAYMENT_METHOD_ATTACHED                   = 'payment_method.attached';
    const PAYMENT_METHOD_CARD_AUTOMATICALLY_UPDATED = 'payment_method.card_automatically_updated';
    const PAYMENT_METHOD_DETACHED                   = 'payment_method.detached';
    const PAYMENT_METHOD_UPDATED                    = 'payment_method.updated';
    const PAYOUT_CANCELED                           = 'payout.canceled';
    const PAYOUT_CREATED                            = 'payout.created';
    const PAYOUT_FAILED                             = 'payout.failed';
    const PAYOUT_PAID                               = 'payout.paid';
    const PAYOUT_UPDATED                            = 'payout.updated';
    const PERSON_CREATED                            = 'person.created';
    const PERSON_DELETED                            = 'person.deleted';
    const PERSON_UPDATED                            = 'person.updated';
    const PING                                      = 'ping';
    const PLAN_CREATED                              = 'plan.created';
    const PLAN_DELETED                              = 'plan.deleted';
    const PLAN_UPDATED                              = 'plan.updated';
    const PRODUCT_CREATED                           = 'product.created';
    const PRODUCT_DELETED                           = 'product.deleted';
    const PRODUCT_UPDATED                           = 'product.updated';
    const RECIPIENT_CREATED                         = 'recipient.created';
    const RECIPIENT_DELETED                         = 'recipient.deleted';
    const RECIPIENT_UPDATED                         = 'recipient.updated';
    const REPORTING_REPORT_RUN_FAILED               = 'reporting.report_run.failed';
    const REPORTING_REPORT_RUN_SUCCEEDED            = 'reporting.report_run.succeeded';
    const REPORTING_REPORT_TYPE_UPDATED             = 'reporting.report_type.updated';
    const REVIEW_CLOSED                             = 'review.closed';
    const REVIEW_OPENED                             = 'review.opened';
    const SIGMA_SCHEDULED_QUERY_RUN_CREATED         = 'sigma.scheduled_query_run.created';
    const SKU_CREATED                               = 'sku.created';
    const SKU_DELETED                               = 'sku.deleted';
    const SKU_UPDATED                               = 'sku.updated';
    const SOURCE_CANCELED                           = 'source.canceled';
    const SOURCE_CHARGEABLE                         = 'source.chargeable';
    const SOURCE_FAILED                             = 'source.failed';
    const SOURCE_MANDATE_NOTIFICATION               = 'source.mandate_notification';
    const SOURCE_REFUND_ATTRIBUTES_REQUIRED         = 'source.refund_attributes_required';
    const SOURCE_TRANSACTION_CREATED                = 'source.transaction.created';
    const SOURCE_TRANSACTION_UPDATED                = 'source.transaction.updated';
    const SUBSCRIPTION_SCHEDULE_ABORTED             = 'subscription_schedule.aborted';
    const SUBSCRIPTION_SCHEDULE_CANCELED            = 'subscription_schedule.canceled';
    const SUBSCRIPTION_SCHEDULE_COMPLETED           = 'subscription_schedule.completed';
    const SUBSCRIPTION_SCHEDULE_CREATED             = 'subscription_schedule.created';
    const SUBSCRIPTION_SCHEDULE_EXPIRING            = 'subscription_schedule.expiring';
    const SUBSCRIPTION_SCHEDULE_RELEASED            = 'subscription_schedule.released';
    const SUBSCRIPTION_SCHEDULE_UPDATED             = 'subscription_schedule.updated';
    const TAX_RATE_CREATED                          = 'tax_rate.created';
    const TAX_RATE_UPDATED                          = 'tax_rate.updated';
    const TOPUP_CANCELED                            = 'topup.canceled';
    const TOPUP_CREATED                             = 'topup.created';
    const TOPUP_FAILED                              = 'topup.failed';
    const TOPUP_REVERSED                            = 'topup.reversed';
    const TOPUP_SUCCEEDED                           = 'topup.succeeded';
    const TRANSFER_CREATED                          = 'transfer.created';
    const TRANSFER_REVERSED                         = 'transfer.reversed';
    const TRANSFER_UPDATED                          = 'transfer.updated';

    use ApiOperations\All;
    use ApiOperations\Retrieve;
}
