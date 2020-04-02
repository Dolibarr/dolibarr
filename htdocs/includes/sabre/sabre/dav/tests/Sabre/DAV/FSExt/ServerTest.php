<?php

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerTest extends DAV\AbstractServer{

    protected function getRootNode() {

        return new Directory($this->tempDir);

    }

    function testGet() {

        $request = new HTTP\Request('GET', '/test.txt');
        $filename = $this->tempDir . '/test.txt';
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(200, $this->response->getStatus(), 'Invalid status code received.');
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($filename)))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );


        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    function testHEAD() {

        $request = new HTTP\Request('HEAD', '/test.txt');
        $filename = $this->tempDir . '/test.txt';
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($this->tempDir . '/test.txt')))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testPut() {

        $request = new HTTP\Request('PUT', '/testput.txt');
        $filename = $this->tempDir . '/testput.txt';
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing new file', file_get_contents($filename));

    }

    function testPutAlreadyExists() {

        $request = new HTTP\Request('PUT', '/test.txt', ['If-None-Match' => '*']);
        $request->setBody('Testing new file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(412, $this->response->status);
        $this->assertNotEquals('Testing new file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testMkcol() {

        $request = new HTTP\Request('MKCOL', '/testcol');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertTrue(is_dir($this->tempDir . '/testcol'));

    }

    function testPutUpdate() {

        $request = new HTTP\Request('PUT', '/test.txt');
        $request->setBody('Testing updated file');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals('0', $this->response->getHeader('Content-Length'));

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertEquals('Testing updated file', file_get_contents($this->tempDir . '/test.txt'));

    }

    function testDelete() {

        $request = new HTTP\Request('DELETE', '/test.txt');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertFalse(file_exists($this->tempDir . '/test.txt'));

    }

    function testDeleteDirectory() {

        mkdir($this->tempDir . '/testcol');
        file_put_contents($this->tempDir . '/testcol/test.txt', 'Hi! I\'m a file with a short lifespan');

        $request = new HTTP\Request('DELETE', '/testcol');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
        ], $this->response->getHeaders());
        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('', $this->response->body);
        $this->assertFalse(file_exists($this->tempDir . '/testcol'));

    }

    function testOptions() {

        $request = new HTTP\Request('OPTIONS', '/');
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testMove() {

        mkdir($this->tempDir . '/testcol');

        $request = new HTTP\Request('MOVE', '/test.txt', ['Destination' => '/testcol/test2.txt']);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);

        $this->assertEquals([
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertTrue(
            is_file($this->tempDir . '/testcol/test2.txt')
        );


    }

    /**
     * This test checks if it's possible to move a non-FSExt collection into a
     * FSExt collection.
     *
     * The moveInto function *should* ignore the object and let sabredav itself
     * execute the slow move.
     */
    function testMoveOtherObject() {

        mkdir($this->tempDir . '/tree1');
        mkdir($this->tempDir . '/tree2');

        $tree = new DAV\Tree(new DAV\SimpleCollection('root', [
            new DAV\FS\Directory($this->tempDir . '/tree1'),
            new DAV\FSExt\Directory($this->tempDir . '/tree2'),
        ]));
        $this->server->tree = $tree;

        $request = new HTTP\Request('MOVE', '/tree1', ['Destination' => '/tree2/tree1']);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals(201, $this->response->status);
        $this->assertEquals('', $this->response->body);

        $this->assertEquals([
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [DAV\Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertTrue(
            is_dir($this->tempDir . '/tree2/tree1')
        );

    }
}
