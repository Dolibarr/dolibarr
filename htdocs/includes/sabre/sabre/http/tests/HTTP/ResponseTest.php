<?php

namespace Sabre\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getStatusText());

    }

    function testSetStatus() {

        $response = new Response();
        $response->setStatus('402 Where\'s my money?');
        $this->assertEquals(402, $response->getStatus());
        $this->assertEquals('Where\'s my money?', $response->getStatusText());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidStatus() {

        $response = new Response(1000);

    }

    function testToString() {

        $response = new Response(200, ['Content-Type' => 'text/xml']);
        $response->setBody('foo');

        $expected = <<<HI
HTTP/1.1 200 OK\r
Content-Type: text/xml\r
\r
foo
HI;
        $this->assertEquals($expected, (string)$response);

    }

}
