<?php

namespace Stripe\Util;

use Stripe\Error;

class RequestOptions
{
    /**
     * @var array A list of headers that should be persisted across requests.
     */
    public static $HEADERS_TO_PERSIST = [
        'Stripe-Account',
        'Stripe-Version',
    ];

    public $headers;
    public $apiKey;
    public $apiBase;

    public function __construct($key = null, $headers = [], $base = null)
    {
        $this->apiKey = $key;
        $this->headers = $headers;
        $this->apiBase = $base;
    }

    /**
     * Unpacks an options array and merges it into the existing RequestOptions
     * object.
     * @param array|string|null $options a key => value array
     *
     * @return RequestOptions
     */
    public function merge($options)
    {
        $other_options = self::parse($options);
        if ($other_options->apiKey === null) {
            $other_options->apiKey = $this->apiKey;
        }
        if ($other_options->apiBase === null) {
            $other_options->apiBase = $this->apiBase;
        }
        $other_options->headers = array_merge($this->headers, $other_options->headers);
        return $other_options;
    }

    /**
     * Discards all headers that we don't want to persist across requests.
     */
    public function discardNonPersistentHeaders()
    {
        foreach ($this->headers as $k => $v) {
            if (!in_array($k, self::$HEADERS_TO_PERSIST)) {
                unset($this->headers[$k]);
            }
        }
    }

    /**
     * Unpacks an options array into an RequestOptions object
     * @param array|string|null $options a key => value array
     *
     * @return RequestOptions
     */
    public static function parse($options)
    {
        if ($options instanceof self) {
            return $options;
        }

        if (is_null($options)) {
            return new RequestOptions(null, [], null);
        }

        if (is_string($options)) {
            return new RequestOptions($options, [], null);
        }

        if (is_array($options)) {
            $headers = [];
            $key = null;
            $base = null;
            if (array_key_exists('api_key', $options)) {
                $key = $options['api_key'];
            }
            if (array_key_exists('idempotency_key', $options)) {
                $headers['Idempotency-Key'] = $options['idempotency_key'];
            }
            if (array_key_exists('stripe_account', $options)) {
                $headers['Stripe-Account'] = $options['stripe_account'];
            }
            if (array_key_exists('stripe_version', $options)) {
                $headers['Stripe-Version'] = $options['stripe_version'];
            }
            if (array_key_exists('api_base', $options)) {
                $base = $options['api_base'];
            }
            return new RequestOptions($key, $headers, $base);
        }

        $message = 'The second argument to Stripe API method calls is an '
           . 'optional per-request apiKey, which must be a string, or '
           . 'per-request options, which must be an array. (HINT: you can set '
           . 'a global apiKey by "Stripe::setApiKey(<apiKey>)")';
        throw new Error\Api($message);
    }
}
