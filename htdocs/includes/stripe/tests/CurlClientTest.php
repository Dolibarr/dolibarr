<?php

namespace Stripe;

use Stripe\HttpClient\CurlClient;

class CurlClientTest extends TestCase
{
    public function testTimeout()
    {
        $curl = new CurlClient();
        $this->assertSame(CurlClient::DEFAULT_TIMEOUT, $curl->getTimeout());
        $this->assertSame(CurlClient::DEFAULT_CONNECT_TIMEOUT, $curl->getConnectTimeout());

        // implicitly tests whether we're returning the CurlClient instance
        $curl = $curl->setConnectTimeout(1)->setTimeout(10);
        $this->assertSame(1, $curl->getConnectTimeout());
        $this->assertSame(10, $curl->getTimeout());

        $curl->setTimeout(-1);
        $curl->setConnectTimeout(-999);
        $this->assertSame(0, $curl->getTimeout());
        $this->assertSame(0, $curl->getConnectTimeout());
    }

    public function testDefaultOptions()
    {
        // make sure options array loads/saves properly
        $optionsArray = array(CURLOPT_PROXY => 'localhost:80');
        $withOptionsArray = new CurlClient($optionsArray);
        $this->assertSame($withOptionsArray->getDefaultOptions(), $optionsArray);

        // make sure closure-based options work properly, including argument passing
        $ref = null;
        $withClosure = new CurlClient(function ($method, $absUrl, $headers, $params, $hasFile) use (&$ref) {
            $ref = func_get_args();
            return array();
        });

        $withClosure->request('get', 'https://httpbin.org/status/200', array(), array(), false);
        $this->assertSame($ref, array('get', 'https://httpbin.org/status/200', array(), array(), false));

        // this is the last test case that will run, since it'll throw an exception at the end
        $withBadClosure = new CurlClient(function () {
            return 'thisShouldNotWork';
        });
        $this->setExpectedException('Stripe\Error\Api', "Non-array value returned by defaultOptions CurlClient callback");
        $withBadClosure->request('get', 'https://httpbin.org/status/200', array(), array(), false);
    }

    public function testEncode()
    {
        $a = array(
            'my' => 'value',
            'that' => array('your' => 'example'),
            'bar' => 1,
            'baz' => null
        );

        $enc = CurlClient::encode($a);
        $this->assertSame('my=value&that%5Byour%5D=example&bar=1', $enc);

        $a = array('that' => array('your' => 'example', 'foo' => null));
        $enc = CurlClient::encode($a);
        $this->assertSame('that%5Byour%5D=example', $enc);

        $a = array('that' => 'example', 'foo' => array('bar', 'baz'));
        $enc = CurlClient::encode($a);
        $this->assertSame('that=example&foo%5B%5D=bar&foo%5B%5D=baz', $enc);

        $a = array(
            'my' => 'value',
            'that' => array('your' => array('cheese', 'whiz', null)),
            'bar' => 1,
            'baz' => null
        );

        $enc = CurlClient::encode($a);
        $expected = 'my=value&that%5Byour%5D%5B%5D=cheese'
              . '&that%5Byour%5D%5B%5D=whiz&bar=1';
        $this->assertSame($expected, $enc);

        // Ignores an empty array
        $enc = CurlClient::encode(array('foo' => array(), 'bar' => 'baz'));
        $expected = 'bar=baz';
        $this->assertSame($expected, $enc);

        $a = array('foo' => array(array('bar' => 'baz'), array('bar' => 'bin')));
        $enc = CurlClient::encode($a);
        $this->assertSame('foo%5B0%5D%5Bbar%5D=baz&foo%5B1%5D%5Bbar%5D=bin', $enc);
    }

    public function testSslOption()
    {
        // make sure options array loads/saves properly
        $optionsArray = array(CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1);
        $withOptionsArray = new CurlClient($optionsArray);
        $this->assertSame($withOptionsArray->getDefaultOptions(), $optionsArray);
    }
}
