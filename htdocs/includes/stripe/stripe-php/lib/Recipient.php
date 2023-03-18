<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * With <code>Recipient</code> objects, you can transfer money from your Stripe
 * account to a third-party bank account or debit card. The API allows you to
 * create, delete, and update your recipients. You can retrieve individual
 * recipients as well as a list of all your recipients.
 *
 * <strong><code>Recipient</code> objects have been deprecated in favor of <a
 * href="https://stripe.com/docs/connect">Connect</a>, specifically Connect's much
 * more powerful <a href="https://stripe.com/docs/api#account">Account objects</a>.
 * Stripe accounts that don't already use recipients can no longer begin doing so.
 * Please use <code>Account</code> objects instead.</strong>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\BankAccount $active_account Hash describing the current account on the recipient, if there is one.
 * @property null|\Stripe\Collection $cards
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\Card $default_card The default card to use for creating transfers to this recipient.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|string $email
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string|\Stripe\Account $migrated_to The ID of the <a href="https://stripe.com/docs/connect/custom-accounts">Custom account</a> this recipient was migrated to. If set, the recipient can no longer be updated, nor can transfers be made to it: use the Custom account instead.
 * @property null|string $name Full, legal name of the recipient.
 * @property string|\Stripe\Account $rolled_back_from
 * @property string $type Type of the recipient, one of <code>individual</code> or <code>corporation</code>.
 * @property bool $verified Whether the recipient has been verified. This field is non-standard, and maybe removed in the future
 */
class Recipient extends ApiResource
{
    const OBJECT_NAME = 'recipient';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
