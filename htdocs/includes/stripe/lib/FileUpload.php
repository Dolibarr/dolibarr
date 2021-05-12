<?php

namespace Stripe;

<<<<<<< HEAD
/**
 * Class FileUpload
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property string $purpose
 * @property int $size
 * @property string $type
 *
 * @package Stripe
 */
class FileUpload extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;

    public static function baseUrl()
    {
        return Stripe::$apiUploadBase;
    }

    public static function className()
    {
        return 'file';
    }
}
=======
// For backwards compatibility, the `File` class is aliased to `FileUpload`.
class_alias('Stripe\\File', 'Stripe\\FileUpload');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
