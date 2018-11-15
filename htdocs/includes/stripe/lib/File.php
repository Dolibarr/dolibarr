<?php

namespace Stripe;

/**
 * Class File
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property string $filename
 * @property Collection $links
 * @property string $purpose
 * @property int $size
 * @property string $title
 * @property string $type
 * @property string $url
 *
 * @package Stripe
 */
class File extends ApiResource
{
    // This resource can have two different object names. In latter API
    // versions, only `file` is used, but since stripe-php may be used with
    // any API version, we need to support deserializing the older
    // `file_upload` object into the same class.
    const OBJECT_NAME = "file";
    const OBJECT_NAME_ALT = "file_upload";

    use ApiOperations\All;
    use ApiOperations\Create {
        create as protected _create;
    }
    use ApiOperations\Retrieve;

    public static function classUrl()
    {
        return '/v1/files';
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return \Stripe\File The created resource.
     */
    public static function create($params = null, $options = null)
    {
        $opts = \Stripe\Util\RequestOptions::parse($options);
        if (is_null($opts->apiBase)) {
            $opts->apiBase = Stripe::$apiUploadBase;
        }
        return static::_create($params, $opts);
    }
}
