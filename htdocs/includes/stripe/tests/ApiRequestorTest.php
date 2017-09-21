<?php

namespace Stripe;

use Stripe\HttpClient\CurlClient;

class ApiRequestorTest extends TestCase
{
    public function testEncodeObjects()
    {
        $reflector = new \ReflectionClass('Stripe\\ApiRequestor');
        $method = $reflector->getMethod('_encodeObjects');
        $method->setAccessible(true);

        $a = array('customer' => new Customer('abcd'));
        $enc = $method->invoke(null, $a);
        $this->assertSame($enc, array('customer' => 'abcd'));

        // Preserves UTF-8
        $v = array('customer' => "☃");
        $enc = $method->invoke(null, $v);
        $this->assertSame($enc, $v);

        // Encodes latin-1 -> UTF-8
        $v = array('customer' => "\xe9");
        $enc = $method->invoke(null, $v);
        $this->assertSame($enc, array('customer' => "\xc3\xa9"));
    }

    public function testHttpClientInjection()
    {
        $reflector = new \ReflectionClass('Stripe\\ApiRequestor');
        $method = $reflector->getMethod('httpClient');
        $method->setAccessible(true);

        $curl = new CurlClient();
        $curl->setTimeout(10);
        ApiRequestor::setHttpClient($curl);

        $injectedCurl = $method->invoke(new ApiRequestor());
        $this->assertSame($injectedCurl, $curl);
    }

    public function testDefaultHeaders()
    {
        $reflector = new \ReflectionClass('Stripe\\ApiRequestor');
        $method = $reflector->getMethod('_defaultHeaders');
        $method->setAccessible(true);

        // no way to stub static methods with PHPUnit 4.x :(
        Stripe::setAppInfo('MyTestApp', '1.2.34', 'https://mytestapp.example');
        $apiKey = 'sk_test_notarealkey';

        $headers = $method->invoke(null, $apiKey);

        $ua = json_decode($headers['X-Stripe-Client-User-Agent']);
        $this->assertSame($ua->application->name, 'MyTestApp');
        $this->assertSame($ua->application->version, '1.2.34');
        $this->assertSame($ua->application->url, 'https://mytestapp.example');

        $this->assertSame(
            $headers['User-Agent'],
            'Stripe/v1 PhpBindings/' . Stripe::VERSION . ' MyTestApp/1.2.34 (https://mytestapp.example)'
        );

        $this->assertSame($headers['Authorization'], 'Bearer ' . $apiKey);
    }
}
