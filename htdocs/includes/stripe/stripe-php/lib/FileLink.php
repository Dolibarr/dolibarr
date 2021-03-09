<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * To share the contents of a <code>File</code> object with non-Stripe users, you
 * can create a <code>FileLink</code>. <code>FileLink</code>s contain a URL that
 * can be used to retrieve the contents of the file without authentication.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property bool $expired Whether this link is already expired.
 * @property null|int $expires_at Time at which the link expires.
 * @property string|\Stripe\File $file The file object this link points to.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $url The publicly accessible URL to download the file.
 */
class FileLink extends ApiResource
{
    const OBJECT_NAME = 'file_link';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
