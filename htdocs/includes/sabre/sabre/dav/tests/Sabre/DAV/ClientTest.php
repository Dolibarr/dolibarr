<?php

namespace Sabre\DAV;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

require_once 'Sabre/DAV/ClientMock.php';

class ClientTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        if (!function_exists('curl_init')) {
            $this->markTestSkipped('CURL must be installed to test the client');
        }

    }

    function testConstruct() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);
        $this->assertInstanceOf('Sabre\DAV\ClientMock', $client);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testConstructNoBaseUri() {

        $client = new ClientMock([]);

    }

    function testAuth() {

        $client = new ClientMock([
            'baseUri'  => '/',
            'userName' => 'foo',
            'password' => 'bar',
        ]);

        $this->assertEquals("foo:bar", $client->curlSettings[CURLOPT_USERPWD]);
        $this->assertEquals(CURLAUTH_BASIC | CURLAUTH_DIGEST, $client->curlSettings[CURLOPT_HTTPAUTH]);

    }

    function testBasicAuth() {

        $client = new ClientMock([
            'baseUri'  => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_BASIC
        ]);

        $this->assertEquals("foo:bar", $client->curlSettings[CURLOPT_USERPWD]);
        $this->assertEquals(CURLAUTH_BASIC, $client->curlSettings[CURLOPT_HTTPAUTH]);

    }

    function testDigestAuth() {

        $client = new ClientMock([
            'baseUri'  => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_DIGEST
        ]);

        $this->assertEquals("foo:bar", $client->curlSettings[CURLOPT_USERPWD]);
        $this->assertEquals(CURLAUTH_DIGEST, $client->curlSettings[CURLOPT_HTTPAUTH]);

    }

    function testNTLMAuth() {

        $client = new ClientMock([
            'baseUri'  => '/',
            'userName' => 'foo',
            'password' => 'bar',
            'authType' => Client::AUTH_NTLM
        ]);

        $this->assertEquals("foo:bar", $client->curlSettings[CURLOPT_USERPWD]);
        $this->assertEquals(CURLAUTH_NTLM, $client->curlSettings[CURLOPT_HTTPAUTH]);

    }

    function testProxy() {

        $client = new ClientMock([
            'baseUri' => '/',
            'proxy'   => 'localhost:8888',
        ]);

        $this->assertEquals("localhost:8888", $client->curlSettings[CURLOPT_PROXY]);

    }

    function testEncoding() {

        $client = new ClientMock([
            'baseUri'  => '/',
            'encoding' => Client::ENCODING_IDENTITY | Client::ENCODING_GZIP | Client::ENCODING_DEFLATE,
        ]);

        $this->assertEquals("identity,deflate,gzip", $client->curlSettings[CURLOPT_ENCODING]);

    }

    function testPropFind() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir']);

        $this->assertEquals(['{DAV:}displayname' => 'bar'], $result);

        $request = $client->request;
        $this->assertEquals('PROPFIND', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Depth'        => ['0'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());

    }

    /**
     * @expectedException \Sabre\HTTP\ClientHttpException
     */
    function testPropFindError() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(405, []);
        $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir']);

    }

    function testPropFindDepth1() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propFind('foo', ['{DAV:}displayname', '{urn:zim}gir'], 1);

        $this->assertEquals([
            '/foo' => [
            '{DAV:}displayname' => 'bar'
            ],
        ], $result);

        $request = $client->request;
        $this->assertEquals('PROPFIND', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Depth'        => ['1'],
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());

    }

    function testPropPatch() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
  <response>
    <href>/foo</href>
    <propstat>
      <prop>
        <displayname>bar</displayname>
      </prop>
      <status>HTTP/1.1 200 OK</status>
    </propstat>
  </response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $result = $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);
        $this->assertTrue($result);
        $request = $client->request;
        $this->assertEquals('PROPPATCH', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'Content-Type' => ['application/xml'],
        ], $request->getHeaders());

    }

    /**
     * @depends testPropPatch
     * @expectedException \Sabre\HTTP\ClientHttpException
     */
    function testPropPatchHTTPError() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(403, [], '');
        $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);

    }

    /**
     * @depends testPropPatch
     * @expectedException Sabre\HTTP\ClientException
     */
    function testPropPatchMultiStatusError() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $responseBody = <<<XML
<?xml version="1.0"?>
<multistatus xmlns="DAV:">
<response>
  <href>/foo</href>
  <propstat>
    <prop>
      <displayname />
    </prop>
    <status>HTTP/1.1 403 Forbidden</status>
  </propstat>
</response>
</multistatus>
XML;

        $client->response = new Response(207, [], $responseBody);
        $client->propPatch('foo', ['{DAV:}displayname' => 'hi', '{urn:zim}gir' => null]);

    }

    function testOPTIONS() {

        $client = new ClientMock([
            'baseUri' => '/',
        ]);

        $client->response = new Response(207, [
            'DAV' => 'calendar-access, extended-mkcol',
        ]);
        $result = $client->options();

        $this->assertEquals(
            ['calendar-access', 'extended-mkcol'],
            $result
        );

        $request = $client->request;
        $this->assertEquals('OPTIONS', $request->getMethod());
        $this->assertEquals('/', $request->getUrl());
        $this->assertEquals([
        ], $request->getHeaders());

    }
}
