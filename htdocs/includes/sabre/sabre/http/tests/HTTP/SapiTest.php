<?php

namespace Sabre\HTTP;

class SapiTest extends \PHPUnit_Framework_TestCase {

    function testConstructFromServerArray() {

        $request = Sapi::createFromServerArray([
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'HTTP_USER_AGENT' => 'Evert',
            'CONTENT_TYPE'    => 'text/xml',
            'CONTENT_LENGTH'  => '400',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
        ]);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'User-Agent'     => ['Evert'],
            'Content-Type'   => ['text/xml'],
            'Content-Length' => ['400'],
        ], $request->getHeaders());

        $this->assertEquals('1.0', $request->getHttpVersion());

        $this->assertEquals('400', $request->getRawServerValue('CONTENT_LENGTH'));
        $this->assertNull($request->getRawServerValue('FOO'));

    }

    function testConstructPHPAuth() {

        $request = Sapi::createFromServerArray([
            'REQUEST_URI'    => '/foo',
            'REQUEST_METHOD' => 'GET',
            'PHP_AUTH_USER'  => 'user',
            'PHP_AUTH_PW'    => 'pass',
        ]);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Authorization' => ['Basic ' . base64_encode('user:pass')],
        ], $request->getHeaders());

    }

    function testConstructPHPAuthDigest() {

        $request = Sapi::createFromServerArray([
            'REQUEST_URI'     => '/foo',
            'REQUEST_METHOD'  => 'GET',
            'PHP_AUTH_DIGEST' => 'blabla',
        ]);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Authorization' => ['Digest blabla'],
        ], $request->getHeaders());

    }

    function testConstructRedirectAuth() {

        $request = Sapi::createFromServerArray([
            'REQUEST_URI'                 => '/foo',
            'REQUEST_METHOD'              => 'GET',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic bla',
        ]);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Authorization' => ['Basic bla'],
        ], $request->getHeaders());

    }

    /**
     * @runInSeparateProcess
     *
     * Unfortunately we have no way of testing if the HTTP response code got
     * changed.
     */
    function testSend() {

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('XDebug needs to be installed for this test to run');
        }

        $response = new Response(204, ['Content-Type' => 'text/xml;charset=UTF-8']);

        // Second Content-Type header. Normally this doesn't make sense.
        $response->addHeader('Content-Type', 'application/xml');
        $response->setBody('foo');

        ob_start();

        Sapi::sendResponse($response);
        $headers = xdebug_get_headers();

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals(
            [
                "Content-Type: text/xml;charset=UTF-8",
                "Content-Type: application/xml",
            ],
            $headers
        );

        $this->assertEquals('foo', $result);

    }

    /**
     * @runInSeparateProcess
     * @depends testSend
     */
    function testSendLimitedByContentLengthString() {

        $response = new Response(200);

        $response->addHeader('Content-Length', 19);
        $response->setBody('Send this sentence. Ignore this one.');

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals('Send this sentence.', $result);

    }

    /**
     * @runInSeparateProcess
     * @depends testSend
     */
    function testSendLimitedByContentLengthStream() {

        $response = new Response(200, ['Content-Length' => 19]);

        $body = fopen('php://memory', 'w');
        fwrite($body, 'Ignore this. Send this sentence. Ignore this too.');
        rewind($body);
        fread($body, 13);
        $response->setBody($body);

        ob_start();

        Sapi::sendResponse($response);

        $result = ob_get_clean();
        header_remove();

        $this->assertEquals('Send this sentence.', $result);

    }

}
