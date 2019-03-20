<?php

namespace Sabre\DAV\Mount;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new Plugin());

    }

    function testPassThrough() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(501, $this->response->status, 'We expected GET to not be implemented for Directories. Response body: ' . $this->response->body);

    }

    function testMountResponse() {

        $serverVars = [
            'REQUEST_URI'    => '/?mount',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING'   => 'mount',
            'HTTP_HOST'      => 'example.org',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(200, $this->response->status);

        $xml = simplexml_load_string($this->response->body);
        $this->assertInstanceOf('SimpleXMLElement', $xml, 'Response was not a valid xml document. The list of errors:' . print_r(libxml_get_errors(), true) . '. xml body: ' . $this->response->body . '. What type we got: ' . gettype($xml) . ' class, if object: ' . get_class($xml));

        $xml->registerXPathNamespace('dm', 'http://purl.org/NET/webdav/mount');
        $url = $xml->xpath('//dm:url');
        $this->assertEquals('http://example.org/', (string)$url[0]);

    }

}
