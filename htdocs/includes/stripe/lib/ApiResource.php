<?php

namespace Stripe;

/**
 * Class ApiResource
 *
 * @package Stripe
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
        if ($savedNestedResources === null) {
            $savedNestedResources = new Util\Set();
        }
        return $savedNestedResources;
    }

    /**
     * @var boolean A flag that can be set a behavior that will cause this
     * resource to be encoded and sent up along with an update of its parent
     * resource. This is usually not desirable because resources are updated
     * individually on their own endpoints, but there are certain cases,
     * replacing a customer's source for example, where this is allowed.
     */
    public $saveWithParent = false;

    public function __set($k, $v)
    {
        parent::__set($k, $v);
        $v = $this->$k;
        if ((static::getSavedNestedResources()->includes($k)) &&
            ($v instanceof ApiResource)) {
            $v->saveWithParent = true;
        }
        return $v;
    }

    /**
     * @return ApiResource The refreshed resource.
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
<<<<<<< HEAD
     * @return string The name of the class, with namespacing and underscores
     *    stripped.
     */
    public static function className()
    {
        $class = get_called_class();
        // Useful for namespaces: Foo\Charge
        if ($postfixNamespaces = strrchr($class, '\\')) {
            $class = substr($postfixNamespaces, 1);
        }
        // Useful for underscored 'namespaces': Foo_Charge
        if ($postfixFakeNamespaces = strrchr($class, '')) {
            $class = $postfixFakeNamespaces;
        }
        if (substr($class, 0, strlen('Stripe')) == 'Stripe') {
            $class = substr($class, strlen('Stripe'));
        }
        $class = str_replace('_', '', $class);
        $name = urlencode($class);
        $name = strtolower($name);
        return $name;
    }

    /**
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     * @return string The base URL for the given class.
     */
    public static function baseUrl()
    {
        return Stripe::$apiBase;
    }

    /**
     * @return string The endpoint URL for the given class.
     */
    public static function classUrl()
    {
<<<<<<< HEAD
        $base = static::className();
=======
        // Replace dots with slashes for namespaced resources, e.g. if the object's name is
        // "foo.bar", then its URL will be "/v1/foo/bars".
        $base = str_replace('.', '/', static::OBJECT_NAME);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        return "/v1/${base}s";
    }

    /**
     * @return string The instance endpoint URL for the given class.
     */
    public static function resourceUrl($id)
    {
        if ($id === null) {
            $class = get_called_class();
            $message = "Could not determine which URL to request: "
               . "$class instance has invalid ID: $id";
            throw new Error\InvalidRequest($message, null);
        }
        $id = Util\Util::utf8($id);
        $base = static::classUrl();
        $extn = urlencode($id);
        return "$base/$extn";
    }

    /**
     * @return string The full API URL for this API resource.
     */
    public function instanceUrl()
    {
        return static::resourceUrl($this['id']);
    }
}
