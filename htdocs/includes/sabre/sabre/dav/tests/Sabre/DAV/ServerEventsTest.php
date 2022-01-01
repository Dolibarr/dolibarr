<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerEventsTest extends AbstractServer {

    private $tempPath;

    private $exception;

    function testAfterBind() {

        $this->server->on('afterBind', [$this, 'afterBindHandler']);
        $newPath = 'afterBind';

        $this->tempPath = '';
        $this->server->createFile($newPath, 'body');
        $this->assertEquals($newPath, $this->tempPath);

    }

    function afterBindHandler($path) {

       $this->tempPath = $path;

    }

    function testAfterResponse() {

        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['afterResponseCallback'])
            ->getMock();
        $mock->expects($this->once())->method('afterResponseCallback');

        $this->server->on('afterResponse', [$mock, 'afterResponseCallback']);

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/test.txt',
        ]);

        $this->server->exec();

    }

    function testBeforeBindCancel() {

        $this->server->on('beforeBind', [$this, 'beforeBindCancelHandler']);
        $this->assertFalse($this->server->createFile('bla', 'body'));

        // Also testing put()
        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/barbar',
        ]);

        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertEquals(500, $this->server->httpResponse->getStatus());

    }

    function beforeBindCancelHandler($path) {

        return false;

    }

    function testException() {

        $this->server->on('exception', [$this, 'exceptionHandler']);

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/not/exisitng',
        ]);
        $this->server->httpRequest = $req;
        $this->server->exec();

        $this->assertInstanceOf('Sabre\\DAV\\Exception\\NotFound', $this->exception);

    }

    function exceptionHandler(Exception $exception) {

        $this->exception = $exception;

    }

    function testMethod() {

        $k = 1;
        $this->server->on('method', function($request, $response) use (&$k) {

            $k += 1;

            return false;

        });
        $this->server->on('method', function($request, $response) use (&$k) {

            $k += 2;

            return false;

        });

        try {
            $this->server->invokeMethod(
                new HTTP\Request('BLABLA', '/'),
                new HTTP\Response(),
                false
            );
        } catch (Exception $e) {}

        // Fun fact, PHP 7.1 changes the order when sorting-by-callback.
        $this->assertTrue($k >= 2 && $k <= 3);

    }

}
