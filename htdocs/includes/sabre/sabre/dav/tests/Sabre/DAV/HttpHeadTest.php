<?php

namespace Sabre\DAV;

use Sabre\DAVServerTest;
use Sabre\HTTP;

/**
 * Tests related to the HEAD request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HttpHeadTest extends DAVServerTest {

    /**
     * Sets up the DAV tree.
     *
     * @return void
     */
    function setUpTree() {

        $this->tree = new Mock\Collection('root', [
            'file1' => 'foo',
            new Mock\Collection('dir', []),
            new Mock\StreamingFile('streaming', 'stream')
        ]);

    }

    function testHEAD() {

        $request = new HTTP\Request('HEAD', '//file1');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        // Removing Last-Modified because it keeps changing.
        $response->removeHeader('Last-Modified');

        $this->assertEquals(
            [
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/octet-stream'],
                'Content-Length'  => [3],
                'ETag'            => ['"' . md5('foo') . '"'],
            ],
            $response->getHeaders()
        );

        $this->assertEquals('', $response->getBodyAsString());

    }

    /**
     * According to the specs, HEAD should behave identical to GET. But, broken
     * clients needs HEAD requests on collections to respond with a 200, so
     * that's what we do.
     */
    function testHEADCollection() {

        $request = new HTTP\Request('HEAD', '/dir');
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

    }

    /**
     * HEAD automatically internally maps to GET via a sub-request.
     * The Auth plugin must not be triggered twice for these, so we'll
     * test for that.
     */
    function testDoubleAuth() {

        $count = 0;

        $authBackend = new Auth\Backend\BasicCallBack(function($userName, $password) use (&$count) {
            $count++;
            return true;
        });
        $this->server->addPlugin(
            new Auth\Plugin(
                $authBackend
            )
        );
        $request = new HTTP\Request('HEAD', '/file1', ['Authorization' => 'Basic ' . base64_encode('user:pass')]);
        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());

        $this->assertEquals(1, $count, 'Auth was triggered twice :(');

    }

}
