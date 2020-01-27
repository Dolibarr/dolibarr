<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class MapGetToPropFindTest extends DAV\AbstractServer {

    function setUp() {

        parent::setUp();
        $this->server->addPlugin(new MapGetToPropFind());

    }

    function testCollectionGet() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'GET',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(207, $this->response->status, 'Incorrect status response received. Full response body: ' . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            'DAV'             => ['1, 3, extended-mkcol'],
            'Vary'            => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

    }


}
