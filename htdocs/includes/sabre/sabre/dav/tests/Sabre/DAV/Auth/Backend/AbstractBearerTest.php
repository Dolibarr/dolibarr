<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class AbstractBearerTest extends \PHPUnit_Framework_TestCase {

    function testCheckNoHeaders() {

        $request = new HTTP\Request();
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testCheckInvalidToken() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_AUTHORIZATION' => 'Bearer foo',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();

        $this->assertFalse(
            $backend->check($request, $response)[0]
        );

    }

    function testCheckSuccess() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_AUTHORIZATION' => 'Bearer valid',
        ]);
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $this->assertEquals(
            [true, 'principals/username'],
            $backend->check($request, $response)
        );

    }

    function testRequireAuth() {

        $request = new HTTP\Request();
        $response = new HTTP\Response();

        $backend = new AbstractBearerMock();
        $backend->setRealm('writing unittests on a saturday night');
        $backend->challenge($request, $response);

        $this->assertEquals(
            'Bearer realm="writing unittests on a saturday night"',
            $response->getHeader('WWW-Authenticate')
        );

    }

}


class AbstractBearerMock extends AbstractBearer {

    /**
     * Validates a bearer token
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $bearerToken
     * @return bool
     */
    function validateBearerToken($bearerToken) {

        return 'valid' === $bearerToken ? 'principals/username' : false;

    }

}
