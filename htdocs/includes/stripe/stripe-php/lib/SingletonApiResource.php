<?php

namespace Stripe;

/**
 * Class SingletonApiResource.
 */
abstract class SingletonApiResource extends ApiResource
{
    /**
     * @return string the endpoint associated with this singleton class
     */
    public static function classUrl()
    {
        // Replace dots with slashes for namespaced resources, e.g. if the object's name is
        // "foo.bar", then its URL will be "/v1/foo/bar".

        /** @phpstan-ignore-next-line */
        $base = \str_replace('.', '/', static::OBJECT_NAME);

        return "/v1/{$base}";
    }

    /**
     * @return string the endpoint associated with this singleton API resource
     */
    public function instanceUrl()
    {
        return static::classUrl();
    }
}
