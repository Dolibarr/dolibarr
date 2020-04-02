<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

class ApacheTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $backend = new Apache();
        $this->assertInstanceOf('Sabre\DAV\Auth\Backend\Apache', $backend);

    }

    function testNoHeader() {

        $request = new HTTP\Request();
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testRemoteUser() {

        $request = HTTP\Sapi::createFromServerArray([
            'REMOTE_USER' => 'username',
        ]);
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRedirectRemoteUser() {

        $request = HTTP\Sapi::createFromServerArray([
            'REDIRECT_REMOTE_USER' => 'username',
        ]);
        $response = new HTTP\Response();
        $backend = new Apache();

        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRequireAuth() {

        $request = new HTTP\Request();
        $response = new HTTP\Response();

        $backend = new Apache();
        $backend->challenge($request, $response);

        $this->assertNull(
            $response->getHeader('WWW-Authenticate')
        );

    }
}
