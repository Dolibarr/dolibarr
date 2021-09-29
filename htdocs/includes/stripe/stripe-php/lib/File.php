<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * This is an object representing a file hosted on Stripe's servers. The file may
 * have been uploaded by yourself using the <a
 * href="https://stripe.com/docs/api#create_file">create file</a> request (for
 * example, when uploading dispute evidence) or it may have been created by Stripe
 * (for example, the results of a <a href="#scheduled_queries">Sigma scheduled
 * query</a>).
 *
 * Related guide: <a href="https://stripe.com/docs/file-upload">File Upload
 * Guide</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|int $expires_at The time at which the file expires and is no longer available in epoch seconds.
 * @property null|string $filename A filename for the file, suitable for saving to a filesystem.
 * @property null|\Stripe\Collection $links A list of <a href="https://stripe.com/docs/api#file_links">file links</a> that point at this file.
 * @property string $purpose The <a href="https://stripe.com/docs/file-upload#uploading-a-file">purpose</a> of the uploaded file.
 * @property int $size The size in bytes of the file object.
 * @property null|string $title A user friendly title for the document.
 * @property null|string $type The type of the file returned (e.g., <code>csv</code>, <code>pdf</code>, <code>jpg</code>, or <code>png</code>).
 * @property null|string $url The URL from which the file can be downloaded using your live secret API key.
 */
class File extends ApiResource
{
    const OBJECT_NAME = 'file';

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    const PURPOSE_ADDITIONAL_VERIFICATION = 'additional_verification';
    const PURPOSE_BUSINESS_ICON = 'business_icon';
    const PURPOSE_BUSINESS_LOGO = 'business_logo';
    const PURPOSE_CUSTOMER_SIGNATURE = 'customer_signature';
    const PURPOSE_DISPUTE_EVIDENCE = 'dispute_evidence';
    const PURPOSE_IDENTITY_DOCUMENT = 'identity_document';
    const PURPOSE_PCI_DOCUMENT = 'pci_document';
    const PURPOSE_TAX_DOCUMENT_USER_UPLOAD = 'tax_document_user_upload';

    // This resource can have two different object names. In latter API
    // versions, only `file` is used, but since stripe-php may be used with
    // any API version, we need to support deserializing the older
    // `file_upload` object into the same class.
    const OBJECT_NAME_ALT = 'file_upload';

    use ApiOperations\Create {
        create as protected _create;
    }

    public static function classUrl()
    {
        return '/v1/files';
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\File the created file
     */
    public static function create($params = null, $opts = null)
    {
        $opts = \Stripe\Util\RequestOptions::parse($opts);
        if (null === $opts->apiBase) {
            $opts->apiBase = Stripe::$apiUploadBase;
        }
        // Manually flatten params, otherwise curl's multipart encoder will
        // choke on nested arrays.
        $flatParams = \array_column(\Stripe\Util\Util::flattenParams($params), 1, 0);

        return static::_create($flatParams, $opts);
    }
}
