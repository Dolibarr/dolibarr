<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Events are our way of letting you know when something interesting happens in
 * your account. When an interesting event occurs, we create a new
 * <code>Event</code> object. For example, when a charge succeeds, we create a
 * <code>charge.succeeded</code> event; and when an invoice payment attempt fails,
 * we create an <code>invoice.payment_failed</code> event. Note that many API
 * requests may cause multiple events to be created. For example, if you create a
 * new subscription for a customer, you will receive both a
 * <code>customer.subscription.created</code> event and a
 * <code>charge.succeeded</code> event.
 *
 * Events occur when the state of another API resource changes. The state of that
 * resource at the time of the change is embedded in the event's data field. For
 * example, a <code>charge.succeeded</code> event will contain a charge, and an
 * <code>invoice.payment_failed</code> event will contain an invoice.
 *
 * As with other API resources, you can use endpoints to retrieve an <a
 * href="https://stripe.com/docs/api#retrieve_event">individual event</a> or a <a
 * href="https://stripe.com/docs/api#list_events">list of events</a> from the API.
 * We also have a separate <a
 * href="http://en.wikipedia.org/wiki/Webhook">webhooks</a> system for sending the
 * <code>Event</code> objects directly to an endpoint on your server. Webhooks are
 * managed in your <a href="https://dashboard.stripe.com/account/webhooks">account
 * settings</a>, and our <a href="https://stripe.com/docs/webhooks">Using
 * Webhooks</a> guide will help you get set up.
 *
 * When using <a href="https://stripe.com/docs/connect">Connect</a>, you can also
 * receive notifications of events that occur in connected accounts. For these
 * events, there will be an additional <code>account</code> attribute in the
 * received <code>Event</code> object.
 *
 * <strong>NOTE:</strong> Right now, access to events through the <a
 * href="https://stripe.com/docs/api#retrieve_event">Retrieve Event API</a> is
 * guaranteed only for 30 days.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property string $account The connected account that originated the event.
 * @property null|string $api_version The Stripe API version used to render <code>data</code>. <em>Note: This property is populated only for events on or after October 31, 2014</em>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property \Stripe\StripeObject $data
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property int $pending_webhooks Number of webhooks that have yet to be successfully delivered (i.e., to return a 20x response) to the URLs you've specified.
 * @property null|\Stripe\StripeObject $request Information on the API request that instigated the event.
 * @property string $type Description of the event (e.g., <code>invoice.created</code> or <code>charge.refunded</code>).
 */
class Event extends ApiResource
{
    const OBJECT_NAME = 'event';

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * Possible string representations of event types.
     *
     * @see https://stripe.com/docs/api#event_types
     */
    const ACCOUNT_UPDATED = 'account.updated';
    const ACCOUNT_APPLICATION_AUTHORIZED = 'account.application.authorized';
    const ACCOUNT_APPLICATION_DEAUTHORIZED = 'account.application.deauthorized';
    const ACCOUNT_EXTERNAL_ACCOUNT_CREATED = 'account.external_account.created';
    const ACCOUNT_EXTERNAL_ACCOUNT_DELETED = 'account.external_account.deleted';
    const ACCOUNT_EXTERNAL_ACCOUNT_UPDATED = 'account.external_account.updated';
    const APPLICATION_FEE_CREATED = 'application_fee.created';
    const APPLICATION_FEE_REFUNDED = 'application_fee.refunded';
    const APPLICATION_FEE_REFUND_UPDATED = 'application_fee.refund.updated';
    const BALANCE_AVAILABLE = 'balance.available';
    const CAPABILITY_UPDATED = 'capability.updated';
    const CHARGE_CAPTURED = 'charge.captured';
    const CHARGE_EXPIRED = 'charge.expired';
    const CHARGE_FAILED = 'charge.failed';
    const CHARGE_PENDING = 'charge.pending';
    const CHARGE_REFUNDED = 'charge.refunded';
    const CHARGE_SUCCEEDED = 'charge.succeeded';
    const CHARGE_UPDATED = 'charge.updated';
    const CHARGE_DISPUTE_CLOSED = 'charge.dispute.closed';
    const CHARGE_DISPUTE_CREATED = 'charge.dispute.created';
    const CHARGE_DISPUTE_FUNDS_REINSTATED = 'charge.dispute.funds_reinstated';
    const CHARGE_DISPUTE_FUNDS_WITHDRAWN = 'charge.dispute.funds_withdrawn';
    const CHARGE_DISPUTE_UPDATED = 'charge.dispute.updated';
    const CHARGE_REFUND_UPDATED = 'charge.refund.updated';
    const CHECKOUT_SESSION_ASYNC_PAYMENT_FAILED = 'checkout.session.async_payment_failed';
    const CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED = 'checkout.session.async_payment_succeeded';
    const CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    const COUPON_CREATED = 'coupon.created';
    const COUPON_DELETED = 'coupon.deleted';
    const COUPON_UPDATED = 'coupon.updated';
    const CREDIT_NOTE_CREATED = 'credit_note.created';
    const CREDIT_NOTE_UPDATED = 'credit_note.updated';
    const CREDIT_NOTE_VOIDED = 'credit_note.voided';
    const CUSTOMER_CREATED = 'customer.created';
    const CUSTOMER_DELETED = 'customer.deleted';
    const CUSTOMER_UPDATED = 'customer.updated';
    const CUSTOMER_DISCOUNT_CREATED = 'customer.discount.created';
    const CUSTOMER_DISCOUNT_DELETED = 'customer.discount.deleted';
    const CUSTOMER_DISCOUNT_UPDATED = 'customer.discount.updated';
    const CUSTOMER_SOURCE_CREATED = 'customer.source.created';
    const CUSTOMER_SOURCE_DELETED = 'customer.source.deleted';
    const CUSTOMER_SOURCE_EXPIRING = 'customer.source.expiring';
    const CUSTOMER_SOURCE_UPDATED = 'customer.source.updated';
    const CUSTOMER_SUBSCRIPTION_CREATED = 'customer.subscription.created';
    const CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';
    const CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_APPLIED = 'customer.subscription.pending_update_applied';
    const CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_EXPIRED = 'customer.subscription.pending_update_expired';
    const CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END = 'customer.subscription.trial_will_end';
    const CUSTOMER_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';
    const CUSTOMER_TAX_ID_CREATED = 'customer.tax_id.created';
    const CUSTOMER_TAX_ID_DELETED = 'customer.tax_id.deleted';
    const CUSTOMER_TAX_ID_UPDATED = 'customer.tax_id.updated';
    const FILE_CREATED = 'file.created';
    const INVOICE_CREATED = 'invoice.created';
    const INVOICE_DELETED = 'invoice.deleted';
    const INVOICE_FINALIZATION_FAILED = 'invoice.finalization_failed';
    const INVOICE_FINALIZED = 'invoice.finalized';
    const INVOICE_MARKED_UNCOLLECTIBLE = 'invoice.marked_uncollectible';
    const INVOICE_PAID = 'invoice.paid';
    const INVOICE_PAYMENT_ACTION_REQUIRED = 'invoice.payment_action_required';
    const INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';
    const INVOICE_PAYMENT_SUCCEEDED = 'invoice.payment_succeeded';
    const INVOICE_SENT = 'invoice.sent';
    const INVOICE_UPCOMING = 'invoice.upcoming';
    const INVOICE_UPDATED = 'invoice.updated';
    const INVOICE_VOIDED = 'invoice.voided';
    const INVOICEITEM_CREATED = 'invoiceitem.created';
    const INVOICEITEM_DELETED = 'invoiceitem.deleted';
    const INVOICEITEM_UPDATED = 'invoiceitem.updated';
    const ISSUER_FRAUD_RECORD_CREATED = 'issuer_fraud_record.created';
    const ISSUING_AUTHORIZATION_CREATED = 'issuing_authorization.created';
    const ISSUING_AUTHORIZATION_REQUEST = 'issuing_authorization.request';
    const ISSUING_AUTHORIZATION_UPDATED = 'issuing_authorization.updated';
    const ISSUING_CARD_CREATED = 'issuing_card.created';
    const ISSUING_CARD_UPDATED = 'issuing_card.updated';
    const ISSUING_CARDHOLDER_CREATED = 'issuing_cardholder.created';
    const ISSUING_CARDHOLDER_UPDATED = 'issuing_cardholder.updated';
    const ISSUING_DISPUTE_CLOSED = 'issuing_dispute.closed';
    const ISSUING_DISPUTE_CREATED = 'issuing_dispute.created';
    const ISSUING_DISPUTE_FUNDS_REINSTATED = 'issuing_dispute.funds_reinstated';
    const ISSUING_DISPUTE_SUBMITTED = 'issuing_dispute.submitted';
    const ISSUING_DISPUTE_UPDATED = 'issuing_dispute.updated';
    const ISSUING_TRANSACTION_CREATED = 'issuing_transaction.created';
    const ISSUING_TRANSACTION_UPDATED = 'issuing_transaction.updated';
    const MANDATE_UPDATED = 'mandate.updated';
    const ORDER_CREATED = 'order.created';
    const ORDER_PAYMENT_FAILED = 'order.payment_failed';
    const ORDER_PAYMENT_SUCCEEDED = 'order.payment_succeeded';
    const ORDER_UPDATED = 'order.updated';
    const ORDER_RETURN_CREATED = 'order_return.created';
    const PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED = 'payment_intent.amount_capturable_updated';
    const PAYMENT_INTENT_CANCELED = 'payment_intent.canceled';
    const PAYMENT_INTENT_CREATED = 'payment_intent.created';
    const PAYMENT_INTENT_PAYMENT_FAILED = 'payment_intent.payment_failed';
    const PAYMENT_INTENT_PROCESSING = 'payment_intent.processing';
    const PAYMENT_INTENT_REQUIRES_ACTION = 'payment_intent.requires_action';
    const PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';
    const PAYMENT_METHOD_ATTACHED = 'payment_method.attached';
    const PAYMENT_METHOD_AUTOMATICALLY_UPDATED = 'payment_method.automatically_updated';
    const PAYMENT_METHOD_CARD_AUTOMATICALLY_UPDATED = 'payment_method.card_automatically_updated';
    const PAYMENT_METHOD_DETACHED = 'payment_method.detached';
    const PAYMENT_METHOD_UPDATED = 'payment_method.updated';
    const PAYOUT_CANCELED = 'payout.canceled';
    const PAYOUT_CREATED = 'payout.created';
    const PAYOUT_FAILED = 'payout.failed';
    const PAYOUT_PAID = 'payout.paid';
    const PAYOUT_UPDATED = 'payout.updated';
    const PERSON_CREATED = 'person.created';
    const PERSON_DELETED = 'person.deleted';
    const PERSON_UPDATED = 'person.updated';
    const PING = 'ping';
    const PLAN_CREATED = 'plan.created';
    const PLAN_DELETED = 'plan.deleted';
    const PLAN_UPDATED = 'plan.updated';
    const PRICE_CREATED = 'price.created';
    const PRICE_DELETED = 'price.deleted';
    const PRICE_UPDATED = 'price.updated';
    const PRODUCT_CREATED = 'product.created';
    const PRODUCT_DELETED = 'product.deleted';
    const PRODUCT_UPDATED = 'product.updated';
    const PROMOTION_CODE_CREATED = 'promotion_code.created';
    const PROMOTION_CODE_DELETED = 'promotion_code.deleted';
    const PROMOTION_CODE_UPDATED = 'promotion_code.updated';
    const RADAR_EARLY_FRAUD_WARNING_CREATED = 'radar.early_fraud_warning.created';
    const RADAR_EARLY_FRAUD_WARNING_UPDATED = 'radar.early_fraud_warning.updated';
    const RECIPIENT_CREATED = 'recipient.created';
    const RECIPIENT_DELETED = 'recipient.deleted';
    const RECIPIENT_UPDATED = 'recipient.updated';
    const REPORTING_REPORT_RUN_FAILED = 'reporting.report_run.failed';
    const REPORTING_REPORT_RUN_SUCCEEDED = 'reporting.report_run.succeeded';
    const REPORTING_REPORT_TYPE_UPDATED = 'reporting.report_type.updated';
    const REVIEW_CLOSED = 'review.closed';
    const REVIEW_OPENED = 'review.opened';
    const SETUP_INTENT_CANCELED = 'setup_intent.canceled';
    const SETUP_INTENT_CREATED = 'setup_intent.created';
    const SETUP_INTENT_REQUIRES_ACTION = 'setup_intent.requires_action';
    const SETUP_INTENT_SETUP_FAILED = 'setup_intent.setup_failed';
    const SETUP_INTENT_SUCCEEDED = 'setup_intent.succeeded';
    const SIGMA_SCHEDULED_QUERY_RUN_CREATED = 'sigma.scheduled_query_run.created';
    const SKU_CREATED = 'sku.created';
    const SKU_DELETED = 'sku.deleted';
    const SKU_UPDATED = 'sku.updated';
    const SOURCE_CANCELED = 'source.canceled';
    const SOURCE_CHARGEABLE = 'source.chargeable';
    const SOURCE_FAILED = 'source.failed';
    const SOURCE_MANDATE_NOTIFICATION = 'source.mandate_notification';
    const SOURCE_REFUND_ATTRIBUTES_REQUIRED = 'source.refund_attributes_required';
    const SOURCE_TRANSACTION_CREATED = 'source.transaction.created';
    const SOURCE_TRANSACTION_UPDATED = 'source.transaction.updated';
    const SUBSCRIPTION_SCHEDULE_ABORTED = 'subscription_schedule.aborted';
    const SUBSCRIPTION_SCHEDULE_CANCELED = 'subscription_schedule.canceled';
    const SUBSCRIPTION_SCHEDULE_COMPLETED = 'subscription_schedule.completed';
    const SUBSCRIPTION_SCHEDULE_CREATED = 'subscription_schedule.created';
    const SUBSCRIPTION_SCHEDULE_EXPIRING = 'subscription_schedule.expiring';
    const SUBSCRIPTION_SCHEDULE_RELEASED = 'subscription_schedule.released';
    const SUBSCRIPTION_SCHEDULE_UPDATED = 'subscription_schedule.updated';
    const TAX_RATE_CREATED = 'tax_rate.created';
    const TAX_RATE_UPDATED = 'tax_rate.updated';
    const TOPUP_CANCELED = 'topup.canceled';
    const TOPUP_CREATED = 'topup.created';
    const TOPUP_FAILED = 'topup.failed';
    const TOPUP_REVERSED = 'topup.reversed';
    const TOPUP_SUCCEEDED = 'topup.succeeded';
    const TRANSFER_CREATED = 'transfer.created';
    const TRANSFER_REVERSED = 'transfer.reversed';
    const TRANSFER_UPDATED = 'transfer.updated';
}
