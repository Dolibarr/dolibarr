<?php

namespace Stripe;

/**
 * Class ApiResource.
 */
abstract class ApiResource extends StripeObject
{
    use ApiOperations\Request;

    /**
     * @return \Stripe\Util\Set A list of fields that can be their own type of
     * API resource (say a nested card under an account for example), and if
     * that resource is set, it should be transmitted to the API on a create or
     * update. Doing so is not the default behavior because API resources
     * should normally be persisted on their own RESTful endpoints.
     */
    public static function getSavedNestedResources()
    {
        static $savedNestedResources = null;
        if (null === $savedNestedResources) {
            $savedNestedResources = new Util\Set();
        }

        return $savedNestedResources;
    }

    /**
     * @var bool A flag that can be set a behavior that will cause this
     * resource to be encoded and sent up along with an update of its parent
     * resource. This is usually not desirable because resources are updated
     * individually on their own endpoints, but there are certain cases,
     * replacing a customer's source for example, where this is allowed.
     */
    public $saveWithParent = false;

    public function __set($k, $v)
    {
        parent::__set($k, $v);
        $v = $this->{$k};
        if ((static::getSavedNestedResources()->includes($k))
            && ($v instanceof ApiResource)) {
            $v->saveWithParent = true;
        }
    }

    /**
     * @throws Exception\ApiErrorException
     *
     * @return ApiResource the refreshed resource
     */
    public function refresh()
    {
        $requestor = new ApiRequestor($this->_opts->apiKey, static::baseUrl());
        $url = $this->instanceUrl();

        list($response, $this->_opts->apiKey) = $requestor->request(
            'get',
            $url,
            $this->_retrieveOptions,
            $this->_opts->headers
        );
        $this->setLastResponse($response);
        $this->refreshFrom($response->json, $this->_opts);

        return $this;
    }

    /**
     * @return string the base URL for the given class
     */
    public static function baseUrl()
    {
        return Stripe::$apiBase;
    }

    /**
     * @return string the endpoint URL for the given class
     */
    public static function classUrl()
    {
        // Replace dots with slashes for namespaced resources, e.g. if the object's name is
        // "foo.bar", then its URL will be "/v1/foo/bars".

        /** @phpstan-ignore-next-line */
        $base = \str_replace('.', '/', static::OBJECT_NAME);

        return "/v1/{$base}s";
    }

    /**
     * @param null|string $id the ID of the resource
     *
     * @throws Exception\UnexpectedValueException if $id is null
     *
     * @return string the instance endpoint URL for the given class
     */
    public static function resourceUrl($id)
    {
        if (null === $id) {
            $class = static::class;
            $message = 'Could not determine which URL to request: '
               . "{$class} instance has invalid ID: {$id}";

            throw new Exception\UnexpectedValueException($message);
        }
        $id = Util\Util::utf8($id);
        $base = static::classUrl();
        $extn = \urlencode($id);

        return "{$base}/{$extn}";
    }

    /**
     * @return string the full API URL for this API resource
     */
    public function instanceUrl()
    {
        return static::resourceUrl($this['id']);
    }
}
