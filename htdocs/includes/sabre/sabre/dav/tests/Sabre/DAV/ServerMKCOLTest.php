<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerMKCOLTest extends AbstractServer {

    function testMkcol() {

        $serverVars = [
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownBody() {

        $serverVars = [
            'REQUEST_URI'    => '/testcol',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("Hello");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(415, $this->response->status);

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLBrokenXML() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody("Hello");
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->getStatus(), $this->response->getBodyAsString());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLUnknownXML() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?><html></html>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->getStatus());

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLNoResourceType() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <displayname>Evert</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(400, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMkcol
     */
    function testMKCOLIncorrectResourceType() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /><blabla /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(403, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLSuccess() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLWhiteSpaceResourceType() {

        $serverVars = [
            'REQUEST_URI'       => '/testcol',
            'REQUEST_METHOD'    => 'MKCOL',
            'HTTP_CONTENT_TYPE' => 'application/xml',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype>
            <collection />
        </resourcetype>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLNoParent() {

        $serverVars = [
            'REQUEST_URI'    => '/testnoparent/409me',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLParentIsNoCollection() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt/409me',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(409, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLIncorrectResourceType
     */
    function testMKCOLAlreadyExists() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'MKCOL',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
        ], $this->response->getHeaders());

        $this->assertEquals(405, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testMKCOLSuccess
     * @depends testMKCOLAlreadyExists
     */
    function testMKCOLAndProps() {

        $request = new HTTP\Request(
            'MKCOL',
            '/testcol',
            ['Content-Type' => 'application/xml']
        );
        $request->setBody('<?xml version="1.0"?>
<mkcol xmlns="DAV:">
  <set>
    <prop>
        <resourcetype><collection /></resourcetype>
        <displayname>my new collection</displayname>
    </prop>
  </set>
</mkcol>');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(207, $this->response->status, 'Wrong statuscode received. Full response body: ' . $this->response->body);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $responseBody = $this->response->getBodyAsString();

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
 <d:response>
  <d:href>/testcol</d:href>
  <d:propstat>
   <d:prop>
    <d:displayname />
   </d:prop>
   <d:status>HTTP/1.1 403 Forbidden</d:status>
  </d:propstat>
 </d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $responseBody
        );

    }

}
