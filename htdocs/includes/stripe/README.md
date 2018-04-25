# Stripe PHP bindings

[![Build Status](https://travis-ci.org/stripe/stripe-php.svg?branch=master)](https://travis-ci.org/stripe/stripe-php)
[![Latest Stable Version](https://poser.pugx.org/stripe/stripe-php/v/stable.svg)](https://packagist.org/packages/stripe/stripe-php)
[![Total Downloads](https://poser.pugx.org/stripe/stripe-php/downloads.svg)](https://packagist.org/packages/stripe/stripe-php)
[![License](https://poser.pugx.org/stripe/stripe-php/license.svg)](https://packagist.org/packages/stripe/stripe-php)
[![Code Coverage](https://coveralls.io/repos/stripe/stripe-php/badge.svg?branch=master)](https://coveralls.io/r/stripe/stripe-php?branch=master)

You can sign up for a Stripe account at https://stripe.com.

## Requirements

PHP 5.3.3 and later.

## Composer

You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require stripe/stripe-php
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/00-intro.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Manual Installation

If you do not wish to use Composer, you can download the [latest release](https://github.com/stripe/stripe-php/releases). Then, to use the bindings, include the `init.php` file.

```php
require_once('/path/to/stripe-php/init.php');
```

## Dependencies

The bindings require the following extension in order to work properly:

- [`curl`](https://secure.php.net/manual/en/book.curl.php), although you can use your own non-cURL client if you prefer
- [`json`](https://secure.php.net/manual/en/book.json.php)
- [`mbstring`](https://secure.php.net/manual/en/book.mbstring.php) (Multibyte String)

If you use Composer, these dependencies should be handled automatically. If you install manually, you'll want to make sure that these extensions are available.

## Getting Started

Simple usage looks like:

```php
\Stripe\Stripe::setApiKey('sk_test_BQokikJOvBiI2HlWgH4olfQ2');
$charge = \Stripe\Charge::create(array('amount' => 2000, 'currency' => 'usd', 'source' => 'tok_189fqt2eZvKYlo2CTGBeg6Uq' ));
echo $charge;
```

## Documentation

Please see https://stripe.com/docs/api for up-to-date documentation.

## Legacy Version Support

If you are using PHP 5.2, you can download v1.18.0 ([zip](https://github.com/stripe/stripe-php/archive/v1.18.0.zip), [tar.gz](https://github.com/stripe/stripe-php/archive/v1.18.0.tar.gz)) from our [releases page](https://github.com/stripe/stripe-php/releases). This version will continue to work with new versions of the Stripe API for all common uses.

This legacy version may be included via `require_once("/path/to/stripe-php/lib/Stripe.php");`, and used like:

```php
Stripe::setApiKey('d8e8fca2dc0f896fd7cb4cb0031ba249');
$myCard = array('number' => '4242424242424242', 'exp_month' => 8, 'exp_year' => 2018);
$charge = Stripe_Charge::create(array('card' => $myCard, 'amount' => 2000, 'currency' => 'usd'));
echo $charge;
```

## Custom Request Timeouts

*NOTE:* We do not recommend decreasing the timeout for non-read-only calls (e.g. charge creation), since even if you locally timeout, the request on Stripe's side can still complete. If you are decreasing timeouts on these calls, make sure to use [idempotency tokens](https://stripe.com/docs/api/php#idempotent_requests) to avoid executing the same transaction twice as a result of timeout retry logic.

To modify request timeouts (connect or total, in seconds) you'll need to tell the API client to use a CurlClient other than its default. You'll set the timeouts in that CurlClient.

```php
// set up your tweaked Curl client
$curl = new \Stripe\HttpClient\CurlClient();
$curl->setTimeout(10); // default is \Stripe\HttpClient\CurlClient::DEFAULT_TIMEOUT
$curl->setConnectTimeout(5); // default is \Stripe\HttpClient\CurlClient::DEFAULT_CONNECT_TIMEOUT

echo $curl->getTimeout(); // 10
echo $curl->getConnectTimeout(); // 5

// tell Stripe to use the tweaked client
\Stripe\ApiRequestor::setHttpClient($curl);

// use the Stripe API client as you normally would
```

## Custom cURL Options (e.g. proxies)

Need to set a proxy for your requests? Pass in the requisite `CURLOPT_*` array to the CurlClient constructor, using the same syntax as `curl_stopt_array()`. This will set the default cURL options for each HTTP request made by the SDK, though many more common options (e.g. timeouts; see above on how to set those) will be overridden by the client even if set here.

```php
// set up your tweaked Curl client
$curl = new \Stripe\HttpClient\CurlClient(array(CURLOPT_PROXY => 'proxy.local:80'));
// tell Stripe to use the tweaked client
\Stripe\ApiRequestor::setHttpClient($curl);
```

Alternately, a callable can be passed to the CurlClient constructor that returns the above array based on request inputs. See `testDefaultOptions()` in `tests/CurlClientTest.php` for an example of this behavior. Note that the callable is called at the beginning of every API request, before the request is sent.

### SSL / TLS compatibility issues

Stripe's API now requires that [all connections use TLS 1.2](https://stripe.com/blog/upgrading-tls). Some systems (most notably some older CentOS and RHEL versions) are capable of using TLS 1.2 but will use TLS 1.0 or 1.1 by default. In this case, you'd get an `invalid_request_error` with the following error message: "Stripe no longer supports API requests made with TLS 1.0. Please initiate HTTPS connections with TLS 1.2 or later. You can learn more about this at [https://stripe.com/blog/upgrading-tls](https://stripe.com/blog/upgrading-tls).".

The recommended course of action is to [upgrade your cURL and OpenSSL packages](https://support.stripe.com/questions/how-do-i-upgrade-my-stripe-integration-from-tls-1-0-to-tls-1-2#php) so that TLS 1.2 is used by default, but if that is not possible, you might be able to solve the issue by setting the `CURLOPT_SSLVERSION` option to either `CURL_SSLVERSION_TLSv1` or `CURL_SSLVERSION_TLSv1_2`:

```php
$curl = new \Stripe\HttpClient\CurlClient(array(CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1));
\Stripe\ApiRequestor::setHttpClient($curl);
```

## Development

Install dependencies:

``` bash
composer install
```

## Tests

Install dependencies as mentioned above (which will resolve [PHPUnit](http://packagist.org/packages/phpunit/phpunit)), then you can run the test suite:

```bash
./vendor/bin/phpunit
```

Or to run an individual test file:

```bash
./vendor/bin/phpunit tests/UtilTest.php
```

## Attention plugin developers

Are you writing a plugin that integrates Stripe and embeds our library? Then please use the `setAppInfo` function to identify your plugin. For example:

```php
\Stripe\Stripe::setAppInfo("MyAwesomePlugin", "1.2.34", "https://myawesomeplugin.info");
```

The method should be called once, before any request is sent to the API. The second and third parameters are optional.

### SSL / TLS configuration option

See the "SSL / TLS compatibility issues" paragraph above for full context. If you want to ensure that your plugin can be used on all systems, you should add a configuration option to let your users choose between different values for `CURLOPT_SSLVERSION`: none (default), `CURL_SSLVERSION_TLSv1` and `CURL_SSLVERSION_TLSv1_2`.
