<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ServerPreconditionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchNoNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/bar', ['If-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     */
    function testIfMatchHasNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfMatchWrongEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-Match' => '1234']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     */
    function testIfMatchCorrectEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-Match' => '"abc123"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     * Evolution sometimes uses \" instead of " for If-Match headers.
     *
     * @depends testIfMatchCorrectEtag
     */
    function testIfMatchEvolutionEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-Match' => '\\"abc123\\"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     */
    function testIfMatchMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-Match' => '"hellothere", "abc123"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     */
    function testIfNoneMatchNoNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/bar', ['If-None-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchHasNode() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('POST', '/foo', ['If-None-Match' => '*']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     */
    function testIfNoneMatchWrongEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('POST', '/foo', ['If-None-Match' => '"1234"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     */
    function testIfNoneMatchWrongEtagMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('POST', '/foo', ['If-None-Match' => '"1234", "5678"']);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchCorrectEtag() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('POST', '/foo', ['If-None-Match' => '"abc123"']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfNoneMatchCorrectEtagMultiple() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('POST', '/foo', ['If-None-Match' => '"1234, "abc123"']);
        $httpResponse = new HTTP\Response();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     */
    function testIfNoneMatchCorrectEtagAsGet() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-None-Match' => '"abc123"']);
        $server->httpResponse = new HTTP\ResponseMock();

        $this->assertFalse($server->checkPreconditions($httpRequest, $server->httpResponse));
        $this->assertEquals(304, $server->httpResponse->getStatus());
        $this->assertEquals(['ETag' => ['"abc123"']], $server->httpResponse->getHeaders());

    }

    /**
     * This was a test written for issue #515.
     */
    function testNoneMatchCorrectEtagEnsureSapiSent() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $server->sapi = new HTTP\SapiMock();
        HTTP\SapiMock::$sent = 0;
        $httpRequest = new HTTP\Request('GET', '/foo', ['If-None-Match' => '"abc123"']);
        $server->httpRequest = $httpRequest;
        $server->httpResponse = new HTTP\ResponseMock();

        $server->exec();

        $this->assertFalse($server->checkPreconditions($httpRequest, $server->httpResponse));
        $this->assertEquals(304, $server->httpResponse->getStatus());
        $this->assertEquals([
            'ETag'            => ['"abc123"'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $server->httpResponse->getHeaders());
        $this->assertEquals(1, HTTP\SapiMock::$sent);

    }

    /**
     */
    function testIfModifiedSinceUnModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'            => '/foo'
        ]);
        $server->httpResponse = new HTTP\ResponseMock();
        $this->assertFalse($server->checkPreconditions($httpRequest, $server->httpResponse));

        $this->assertEquals(304, $server->httpResponse->status);
        $this->assertEquals([
            'Last-Modified' => ['Sat, 06 Apr 1985 23:30:00 GMT'],
        ], $server->httpResponse->getHeaders());

    }


    /**
     */
    function testIfModifiedSinceModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_MODIFIED_SINCE' => 'Tue, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'            => '/foo'
        ]);

        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     */
    function testIfModifiedSinceInvalidDate() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_MODIFIED_SINCE' => 'Your mother',
            'REQUEST_URI'            => '/foo'
        ]);
        $httpResponse = new HTTP\ResponseMock();

        // Invalid dates must be ignored, so this should return true
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }

    /**
     */
    function testIfModifiedSinceInvalidDate2() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_MODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 EST',
            'REQUEST_URI'            => '/foo'
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }


    /**
     */
    function testIfUnmodifiedSinceUnModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1994 08:49:37 GMT',
            'REQUEST_URI'              => '/foo'
        ]);
        $httpResponse = new HTTP\Response();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }


    /**
     * @expectedException Sabre\DAV\Exception\PreconditionFailed
     */
    function testIfUnmodifiedSinceModified() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_UNMODIFIED_SINCE' => 'Tue, 06 Nov 1984 08:49:37 GMT',
            'REQUEST_URI'              => '/foo'
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $server->checkPreconditions($httpRequest, $httpResponse);

    }

    /**
     */
    function testIfUnmodifiedSinceInvalidDate() {

        $root = new SimpleCollection('root', [new ServerPreconditionsNode()]);
        $server = new Server($root);
        $httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_IF_UNMODIFIED_SINCE' => 'Sun, 06 Nov 1984 08:49:37 CET',
            'REQUEST_URI'              => '/foo'
        ]);
        $httpResponse = new HTTP\ResponseMock();
        $this->assertTrue($server->checkPreconditions($httpRequest, $httpResponse));

    }


}

class ServerPreconditionsNode extends File {

    function getETag() {

        return '"abc123"';

    }

    function getLastModified() {

        /* my birthday & time, I believe */
        return strtotime('1985-04-07 01:30 +02:00');

    }

    function getName() {

        return 'foo';

    }

}
