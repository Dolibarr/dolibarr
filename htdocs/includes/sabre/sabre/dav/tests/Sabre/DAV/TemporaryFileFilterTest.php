<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class TemporaryFileFilterTest extends AbstractServer {

    function setUp() {

        parent::setUp();
        $plugin = new TemporaryFileFilterPlugin(SABRE_TEMPDIR . '/tff');
        $this->server->addPlugin($plugin);

    }

    function testPutNormal() {

        $request = new HTTP\Request('PUT', '/testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('0', $this->response->getHeader('Content-Length'));

        $this->assertEquals('Testing new file', file_get_contents(SABRE_TEMPDIR . '/testput.txt'));

    }

    function testPutTemp() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');

    }

    function testPutTempIfNoneMatch() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', ['If-None-Match' => '*'], 'Testing new file');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testput.txt'), '._testput.txt should not exist in the regular file structure.');


        $this->server->exec();

        $this->assertEquals(412, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

    }

    function testPutGet() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('GET', '/._testput.txt');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp'   => ['true'],
            'Content-Length' => [16],
            'Content-Type'   => ['application/octet-stream'],
        ], $this->response->getHeaders());

        $this->assertEquals('Testing new file', stream_get_contents($this->response->body));

    }

    function testLockNonExistant() {

        mkdir(SABRE_TEMPDIR . '/locksdir');
        $locksBackend = new Locks\Backend\File(SABRE_TEMPDIR . '/locks');
        $locksPlugin = new Locks\Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('LOCK', '/._testput.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');
        $this->assertEquals('true', $this->response->getHeader('X-Sabre-Temp'));

        $this->assertFalse(file_exists(SABRE_TEMPDIR . '/._testlock.txt'), '._testlock.txt should not exist in the regular file structure.');

    }

    function testPutDelete() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('DELETE', '/._testput.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(204, $this->response->status, "Incorrect status code received. Full body:\n" . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $this->assertEquals('', $this->response->body);

    }

    function testPutPropfind() {

        // mimicking an OS/X resource fork
        $request = new HTTP\Request('PUT', '/._testput.txt', [], 'Testing new file');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('', $this->response->body);
        $this->assertEquals(201, $this->response->status);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
        ], $this->response->getHeaders());

        $request = new HTTP\Request('PROPFIND', '/._testput.txt');

        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(207, $this->response->status, 'Incorrect status code returned. Body: ' . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Temp' => ['true'],
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/._testput.txt', (string)$data, 'href element should have been /._testput.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1, count($data));

    }

}
