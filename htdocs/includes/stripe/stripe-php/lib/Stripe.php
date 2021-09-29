<?php

namespace Stripe;

/**
 * Class Stripe.
 */
class Stripe
{
    /** @var string The Stripe API key to be used for requests. */
    public static $apiKey;

    /** @var string The Stripe client_id to be used for Connect requests. */
    public static $clientId;

    /** @var string The base URL for the Stripe API. */
    public static $apiBase = 'https://api.stripe.com';

    /** @var string The base URL for the OAuth API. */
    public static $connectBase = 'https://connect.stripe.com';

    /** @var string The base URL for the Stripe API uploads endpoint. */
    public static $apiUploadBase = 'https://files.stripe.com';

    /** @var null|string The version of the Stripe API to use for requests. */
    public static $apiVersion = null;

    /** @var null|string The account ID for connected accounts requests. */
    public static $accountId = null;

    /** @var string Path to the CA bundle used to verify SSL certificates */
    public static $caBundlePath = null;

    /** @var bool Defaults to true. */
    public static $verifySslCerts = true;

    /** @var array The application's information (name, version, URL) */
    public static $appInfo = null;

    /**
     * @var null|Util\LoggerInterface the logger to which the library will
     *   produce messages
     */
    public static $logger = null;

    /** @var int Maximum number of request retries */
    public static $maxNetworkRetries = 0;

    /** @var bool Whether client telemetry is enabled. Defaults to true. */
    public static $enableTelemetry = true;

    /** @var float Maximum delay between retries, in seconds */
    private static $maxNetworkRetryDelay = 2.0;

    /** @var float Maximum delay between retries, in seconds, that will be respected from the Stripe API */
    private static $maxRetryAfter = 60.0;

    /** @var float Initial delay between retries, in seconds */
    private static $initialNetworkRetryDelay = 0.5;

    const VERSION = '7.67.0';

    /**
     * @return string the API key used for requests
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * @return string the client_id used for Connect requests
     */
    public static function getClientId()
    {
        return self::$clientId;
    }

    /**
     * @return Util\LoggerInterface the logger to which the library will
     *   produce messages
     */
    public static function getLogger()
    {
        if (null === self::$logger) {
            return new Util\DefaultLogger();
        }

        return self::$logger;
    }

    /**
     * @param Util\LoggerInterface $logger the logger to which the library
     *   will produce messages
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
    }

    /**
     * Sets the API key to be used for requests.
     *
     * @param string $apiKey
     */
    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    /**
     * Sets the client_id to be used for Connect requests.
     *
     * @param string $clientId
     */
    public static function setClientId($clientId)
    {
        self::$clientId = $clientId;
    }

    /**
     * @return string The API version used for requests. null if we're using the
     *    latest version.
     */
    public static function getApiVersion()
    {
        return self::$apiVersion;
    }

    /**
     * @param string $apiVersion the API version to use for requests
     */
    public static function setApiVersion($apiVersion)
    {
        self::$apiVersion = $apiVersion;
    }

    /**
     * @return string
     */
    private static function getDefaultCABundlePath()
    {
        return \realpath(__DIR__ . '/../data/ca-certificates.crt');
    }

    /**
     * @return string
     */
    public static function getCABundlePath()
    {
        return self::$caBundlePath ?: self::getDefaultCABundlePath();
    }

    /**
     * @param string $caBundlePath
     */
    public static function setCABundlePath($caBundlePath)
    {
        self::$caBundlePath = $caBundlePath;
    }

    /**
     * @return bool
     */
    public static function getVerifySslCerts()
    {
        return self::$verifySslCerts;
    }

    /**
     * @param bool $verify
     */
    public static function setVerifySslCerts($verify)
    {
        self::$verifySslCerts = $verify;
    }

    /**
     * @return string | null The Stripe account ID for connected account
     *   requests
     */
    public static function getAccountId()
    {
        return self::$accountId;
    }

    /**
     * @param string $accountId the Stripe account ID to set for connected
     *   account requests
     */
    public static function setAccountId($accountId)
    {
        self::$accountId = $accountId;
    }

    /**
     * @return array | null The application's information
     */
    public static function getAppInfo()
    {
        return self::$appInfo;
    }

    /**
     * @param string $appName The application's name
     * @param null|string $appVersion The application's version
     * @param null|string $appUrl The application's URL
     * @param null|string $appPartnerId The application's partner ID
     */
    public static function setAppInfo($appName, $appVersion = null, $appUrl = null, $appPartnerId = null)
    {
        self::$appInfo = self::$appInfo ?: [];
        self::$appInfo['name'] = $appName;
        self::$appInfo['partner_id'] = $appPartnerId;
        self::$appInfo['url'] = $appUrl;
        self::$appInfo['version'] = $appVersion;
    }

    /**
     * @return int Maximum number of request retries
     */
    public static function getMaxNetworkRetries()
    {
        return self::$maxNetworkRetries;
    }

    /**
     * @param int $maxNetworkRetries Maximum number of request retries
     */
    public static function setMaxNetworkRetries($maxNetworkRetries)
    {
        self::$maxNetworkRetries = $maxNetworkRetries;
    }

    /**
     * @return float Maximum delay between retries, in seconds
     */
    public static function getMaxNetworkRetryDelay()
    {
        return self::$maxNetworkRetryDelay;
    }

    /**
     * @return float Maximum delay between retries, in seconds, that will be respected from the Stripe API
     */
    public static function getMaxRetryAfter()
    {
        return self::$maxRetryAfter;
    }

    /**
     * @return float Initial delay between retries, in seconds
     */
    public static function getInitialNetworkRetryDelay()
    {
        return self::$initialNetworkRetryDelay;
    }

    /**
     * @return bool Whether client telemetry is enabled
     */
    public static function getEnableTelemetry()
    {
        return self::$enableTelemetry;
    }

    /**
     * @param bool $enableTelemetry Enables client telemetry.
     *
     * Client telemetry enables timing and request metrics to be sent back to Stripe as an HTTP Header
     * with the current request. This enables Stripe to do latency and metrics analysis without adding extra
     * overhead (such as extra network calls) on the client.
     */
    public static function setEnableTelemetry($enableTelemetry)
    {
        self::$enableTelemetry = $enableTelemetry;
    }
}
