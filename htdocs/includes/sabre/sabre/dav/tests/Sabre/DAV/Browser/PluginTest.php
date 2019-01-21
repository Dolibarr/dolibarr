<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer{

    protected $plugin;

    function setUp() {

        parent::setUp();
        $this->server->addPlugin($this->plugin = new Plugin());
        $this->server->tree->getNodeForPath('')->createDirectory('dir2');

    }

    function testCollectionGet() {

        $request = new HTTP\Request('GET', '/dir');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), "Incorrect status received. Full response body: " . $this->response->getBodyAsString());
        $this->assertEquals(
            [
                'X-Sabre-Version'         => [DAV\Version::VERSION],
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        $this->assertTrue(strpos($body, '<title>dir') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/child.txt">') !== false);

    }

    /**
     * Adding the If-None-Match should have 0 effect, but it threw an error.
     */
    function testCollectionGetIfNoneMatch() {

        $request = new HTTP\Request('GET', '/dir');
        $request->setHeader('If-None-Match', '"foo-bar"');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), "Incorrect status received. Full response body: " . $this->response->getBodyAsString());
        $this->assertEquals(
            [
                'X-Sabre-Version'         => [DAV\Version::VERSION],
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        $this->assertTrue(strpos($body, '<title>dir') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/child.txt">') !== false);

    }
    function testCollectionGetRoot() {

        $request = new HTTP\Request('GET', '/');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(200, $this->response->status, "Incorrect status received. Full response body: " . $this->response->getBodyAsString());
        $this->assertEquals(
            [
                'X-Sabre-Version'         => [DAV\Version::VERSION],
                'Content-Type'            => ['text/html; charset=utf-8'],
                'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
            ],
            $this->response->getHeaders()
        );

        $body = $this->response->getBodyAsString();
        $this->assertTrue(strpos($body, '<title>/') !== false, $body);
        $this->assertTrue(strpos($body, '<a href="/dir/">') !== false);
        $this->assertTrue(strpos($body, '<span class="btn disabled">') !== false);

    }

    function testGETPassthru() {

        $request = new HTTP\Request('GET', '/random');
        $response = new HTTP\Response();
        $this->assertNull(
            $this->plugin->httpGet($request, $response)
        );

    }

    function testPostOtherContentType() {

        $request = new HTTP\Request('POST', '/', ['Content-Type' => 'text/xml']);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(501, $this->response->status);

    }

    function testPostNoSabreAction() {

        $request = new HTTP\Request('POST', '/', ['Content-Type' => 'application/x-www-form-urlencoded']);
        $request->setPostData([]);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(501, $this->response->status);

    }

    function testPostMkCol() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
        ];
        $postVars = [
            'sabreAction' => 'mkcol',
            'name'        => 'new_collection',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setPostData($postVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(302, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Location'        => ['/'],
        ], $this->response->getHeaders());

        $this->assertTrue(is_dir(SABRE_TEMPDIR . '/new_collection'));

    }

    function testGetAsset() {

        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=favicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), 'Error: ' . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Version'         => [DAV\Version::VERSION],
            'Content-Type'            => ['image/vnd.microsoft.icon'],
            'Content-Length'          => ['4286'],
            'Cache-Control'           => ['public, max-age=1209600'],
            'Content-Security-Policy' => ["default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';"]
        ], $this->response->getHeaders());

    }

    function testGetAsset404() {

        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=flavicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(404, $this->response->getStatus(), 'Error: ' . $this->response->body);

    }

    function testGetAssetEscapeBasePath() {

        $request = new HTTP\Request('GET', '/?sabreAction=asset&assetName=./../assets/favicon.ico');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(404, $this->response->getStatus(), 'Error: ' . $this->response->body);

    }
}
