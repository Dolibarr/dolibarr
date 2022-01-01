<?php

namespace Sabre\DAV;

use DateTime;
use Sabre\HTTP;

/**
 * This file tests HTTP requests that use the Range: header.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ServerRangeTest extends \Sabre\DAVServerTest {

    protected $setupFiles = true;

    /**
     * We need this string a lot
     */
    protected $lastModified;

    function setUp() {

        parent::setUp();
        $this->server->createFile('files/test.txt', 'Test contents');

        $this->lastModified = HTTP\Util::toHTTPDate(
            new DateTime('@' . $this->server->tree->getNodeForPath('files/test.txt')->getLastModified())
        );

        $stream = popen('echo "Test contents"', 'r');
        $streamingFile = new Mock\StreamingFile(
                'no-seeking.txt',
                $stream
            );
        $streamingFile->setSize(12);
        $this->server->tree->getNodeForPath('files')->addNode($streamingFile);

    }

    function testRange() {

        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );
        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());

    }

    /**
     * @depends testRange
     */
    function testStartRange() {

        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=2-']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [11],
            'Content-Range'   => ['bytes 2-12/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st contents', $response->getBodyAsString());

    }

    /**
     * @depends testRange
     */
    function testEndRange() {

        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=-8']);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [8],
            'Content-Range'   => ['bytes 5-12/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('contents', $response->getBodyAsString());

    }

    /**
     * @depends testRange
     */
    function testTooHighRange() {

        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=100-200']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());

    }

    /**
     * @depends testRange
     */
    function testCrazyRange() {

        $request = new HTTP\Request('GET', '/files/test.txt', ['Range' => 'bytes=8-4']);
        $response = $this->request($request);

        $this->assertEquals(416, $response->getStatus());

    }

    function testNonSeekableStream() {

        $request = new HTTP\Request('GET', '/files/no-seeking.txt', ['Range' => 'bytes=2-5']);
        $response = $this->request($request);

        $this->assertEquals(206, $response->getStatus(), $response);
        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/12'],
            // 'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified' => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('st c', $response->getBodyAsString());

    }

    /**
     * @depends testRange
     */
    function testIfRangeEtag() {

        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '"' . md5('Test contents') . '"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());

    }

    /**
     * @depends testIfRangeEtag
     */
    function testIfRangeEtagIncorrect() {

        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '"foobar"',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('Test contents', $response->getBodyAsString());

    }

    /**
     * @depends testIfRangeEtag
     */
    function testIfRangeModificationDate() {

        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => 'tomorrow',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [4],
            'Content-Range'   => ['bytes 2-5/13'],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(206, $response->getStatus());
        $this->assertEquals('st c', $response->getBodyAsString());

    }

    /**
     * @depends testIfRangeModificationDate
     */
    function testIfRangeModificationDateModified() {

        $request = new HTTP\Request('GET', '/files/test.txt', [
            'Range'    => 'bytes=2-5',
            'If-Range' => '-2 years',
        ]);
        $response = $this->request($request);

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'ETag'            => ['"' . md5('Test contents') . '"'],
            'Last-Modified'   => [$this->lastModified],
            ],
            $response->getHeaders()
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('Test contents', $response->getBodyAsString());

    }

}
