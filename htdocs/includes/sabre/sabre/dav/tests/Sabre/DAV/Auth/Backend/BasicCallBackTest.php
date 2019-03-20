<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP\Response;
use Sabre\HTTP\Sapi;

class BasicCallBackTest extends \PHPUnit_Framework_TestCase {

    function testCallBack() {

        $args = [];
        $callBack = function($user, $pass) use (&$args) {

            $args = [$user, $pass];
            return true;

        };

        $backend = new BasicCallBack($callBack);

        $request = Sapi::createFromServerArray([
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
        ]);
        $response = new Response();

        $this->assertEquals(
            [true, 'principals/foo'],
            $backend->check($request, $response)
        );

        $this->assertEquals(['foo', 'bar'], $args);

    }

}
