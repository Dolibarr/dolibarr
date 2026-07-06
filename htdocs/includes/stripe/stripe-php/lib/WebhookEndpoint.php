<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * You can configure <a href="https://stripe.com/docs/webhooks/">webhook
 * endpoints</a> via the API to be notified about events that happen in your Stripe
 * account or connected accounts.
 *
 * Most users configure webhooks from <a
 * href="https://dashboard.stripe.com/webhooks">the dashboard</a>, which provides a
 * user interface for registering and testing your webhook endpoints.
 *
 * Related guide: <a href="https://stripe.com/docs/webhooks/configure">Setting up
 * Webhooks</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string $api_version The API version events are rendered as for this webhook endpoint.
 * @property null|string $application The ID of the associated Connect application.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $description An optional description of what the webhook is used for.
 * @property string[] $enabled_events The list of events to enable for this endpoint. <code>['*']</code> indicates that all events are enabled, except those that require explicit selection.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $secret The endpoint's secret, used to generate <a href="https://stripe.com/docs/webhooks/signatures">webhook signatures</a>. Only returned at creation.
 * @property string $status The status of the webhook. It can be <code>enabled</code> or <code>disabled</code>.
 * @property string $url The URL of the webhook endpoint.
 */
class WebhookEndpoint extends ApiResource
{
    const OBJECT_NAME = 'webhook_endpoint';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
