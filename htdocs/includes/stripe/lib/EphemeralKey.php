<?php

namespace Stripe;

/**
 * Class EphemeralKey
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property int $expires
 * @property bool $livemode
 * @property string $secret
 * @property array $associated_objects
 *
 * @package Stripe
 */
class EphemeralKey extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "ephemeral_key";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\Create {
        create as protected _create;
    }
    use ApiOperations\Delete;

    /**
<<<<<<< HEAD
     * This is a special case because the ephemeral key endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'ephemeral_key';
    }

    /**
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return EphemeralKey The created key.
     */
    public static function create($params = null, $opts = null)
    {
        if (!$opts['stripe_version']) {
            throw new \InvalidArgumentException('stripe_version must be specified to create an ephemeral key');
        }
        return self::_create($params, $opts);
    }
}
