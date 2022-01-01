<?php

namespace Sabre\DAV;

use Sabre\HTTP;

class ServerSimpleTest extends AbstractServer{

    function testConstructArray() {

        $nodes = [
            new SimpleCollection('hello')
        ];

        $server = new Server($nodes);
        $this->assertEquals($nodes[0], $server->tree->getNodeForPath('hello'));

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testConstructIncorrectObj() {

        $nodes = [
            new SimpleCollection('hello'),
            new \STDClass(),
        ];

        $server = new Server($nodes);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testConstructInvalidArg() {

        $server = new Server(1);

    }

    function testOptions() {

        $request = new HTTP\Request('OPTIONS', '/');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testOptionsUnmapped() {

        $request = new HTTP\Request('OPTIONS', '/unmapped');
        $this->server->httpRequest = $request;

        $this->server->exec();

        $this->assertEquals([
            'DAV'             => ['1, 3, extended-mkcol'],
            'MS-Author-Via'   => ['DAV'],
            'Allow'           => ['OPTIONS, GET, HEAD, DELETE, PROPFIND, PUT, PROPPATCH, COPY, MOVE, REPORT, MKCOL'],
            'Accept-Ranges'   => ['bytes'],
            'Content-Length'  => ['0'],
            'X-Sabre-Version' => [Version::VERSION],
        ], $this->response->getHeaders());

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('', $this->response->body);

    }

    function testNonExistantMethod() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'BLABLA',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(501, $this->response->status);


    }

    function testBaseUri() {

        $serverVars = [
            'REQUEST_URI'    => '/blabla/test.txt',
            'REQUEST_METHOD' => 'GET',
        ];
        $filename = $this->tempDir . '/test.txt';

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->setBaseUri('/blabla/');
        $this->assertEquals('/blabla/', $this->server->getBaseUri());
        $this->server->httpRequest = ($request);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/octet-stream'],
            'Content-Length'  => [13],
            'Last-Modified'   => [HTTP\Util::toHTTPDate(new \DateTime('@' . filemtime($filename)))],
            'ETag'            => ['"' . sha1(fileinode($filename) . filesize($filename) . filemtime($filename)) . '"'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(200, $this->response->status);
        $this->assertEquals('Test contents', stream_get_contents($this->response->body));

    }

    function testBaseUriAddSlash() {

        $tests = [
            '/'         => '/',
            '/foo'      => '/foo/',
            '/foo/'     => '/foo/',
            '/foo/bar'  => '/foo/bar/',
            '/foo/bar/' => '/foo/bar/',
        ];

        foreach ($tests as $test => $result) {
            $this->server->setBaseUri($test);

            $this->assertEquals($result, $this->server->getBaseUri());

        }

    }

    function testCalculateUri() {

        $uris = [
            'http://www.example.org/root/somepath',
            '/root/somepath',
            '/root/somepath/',
        ];

        $this->server->setBaseUri('/root/');

        foreach ($uris as $uri) {

            $this->assertEquals('somepath', $this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/root');

        foreach ($uris as $uri) {

            $this->assertEquals('somepath', $this->server->calculateUri($uri));

        }

        $this->assertEquals('', $this->server->calculateUri('/root'));

    }

    function testCalculateUriSpecialChars() {

        $uris = [
            'http://www.example.org/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3',
            '/root/%C3%A0fo%C3%B3/'
        ];

        $this->server->setBaseUri('/root/');

        foreach ($uris as $uri) {

            $this->assertEquals("\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/root');

        foreach ($uris as $uri) {

            $this->assertEquals("\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));

        }

        $this->server->setBaseUri('/');

        foreach ($uris as $uri) {

            $this->assertEquals("root/\xc3\xa0fo\xc3\xb3", $this->server->calculateUri($uri));

        }

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testCalculateUriBreakout() {

        $uri = '/path1/';

        $this->server->setBaseUri('/path2/');
        $this->server->calculateUri($uri);

    }

    /**
     */
    function testGuessBaseUri() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/root',
            'PATH_INFO'   => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    /**
     * @depends testGuessBaseUri
     */
    function testGuessBaseUriPercentEncoding() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO'   => '/dir/path2/path with spaces',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    /**
     * @depends testGuessBaseUri
     */
    /*
    function testGuessBaseUriPercentEncoding2() {

        $this->markTestIncomplete('This behaviour is not yet implemented');
        $serverVars = [
            'REQUEST_URI' => '/some%20directory+mixed/index.php/dir/path2/path%20with%20spaces',
            'PATH_INFO'   => '/dir/path2/path with spaces',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/some%20directory+mixed/index.php/', $server->guessBaseUri());

    }*/

    function testGuessBaseUri2() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/root/',
            'PATH_INFO'   => '/root/',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    function testGuessBaseUriNoPathInfo() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());

    }

    function testGuessBaseUriNoPathInfo2() {

        $serverVars = [
            'REQUEST_URI' => '/a/b/c/test.php',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/', $server->guessBaseUri());

    }


    /**
     * @depends testGuessBaseUri
     */
    function testGuessBaseUriQueryString() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/root?query_string=blabla',
            'PATH_INFO'   => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $this->assertEquals('/index.php/', $server->guessBaseUri());

    }

    /**
     * @depends testGuessBaseUri
     * @expectedException \Sabre\DAV\Exception
     */
    function testGuessBaseUriBadConfig() {

        $serverVars = [
            'REQUEST_URI' => '/index.php/root/heyyy',
            'PATH_INFO'   => '/root',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $server = new Server();
        $server->httpRequest = $httpRequest;

        $server->guessBaseUri();

    }

    function testTriggerException() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'FOO',
        ];

        $httpRequest = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $httpRequest;
        $this->server->on('beforeMethod', [$this, 'exceptionTrigger']);
        $this->server->exec();

        $this->assertEquals([
            'Content-Type' => ['application/xml; charset=utf-8'],
        ], $this->response->getHeaders());

        $this->assertEquals(500, $this->response->status);

    }

    function exceptionTrigger($request, $response) {

        throw new Exception('Hola');

    }

    function testReportNotFound() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'REPORT',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(415, $this->response->status, 'We got an incorrect status back. Full response body follows: ' . $this->response->body);

    }

    function testReportIntercepted() {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'REPORT',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = ($request);
        $this->server->httpRequest->setBody('<?xml version="1.0"?><bla:myreport xmlns:bla="http://www.rooftopsolutions.nl/NS"></bla:myreport>');
        $this->server->on('report', [$this, 'reportHandler']);
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [Version::VERSION],
            'testheader'      => ['testvalue'],
            ],
            $this->response->getHeaders()
        );

        $this->assertEquals(418, $this->response->status, 'We got an incorrect status back. Full response body follows: ' . $this->response->body);

    }

    function reportHandler($reportName, $result, $path) {

        if ($reportName == '{http://www.rooftopsolutions.nl/NS}myreport') {
            $this->server->httpResponse->setStatus(418);
            $this->server->httpResponse->setHeader('testheader', 'testvalue');
            return false;
        }
        else return;

    }

    function testGetPropertiesForChildren() {

        $result = $this->server->getPropertiesForChildren('', [
            '{DAV:}getcontentlength',
        ]);

        $expected = [
            'test.txt' => ['{DAV:}getcontentlength' => 13],
            'dir/'     => [],
        ];

        $this->assertEquals($expected, $result);

    }

    /**
     * There are certain cases where no HTTP status may be set. We need to
     * intercept these and set it to a default error message.
     */
    function testNoHTTPStatusSet() {

        $this->server->on('method:GET', function() { return false; }, 1);
        $this->server->httpRequest = new HTTP\Request('GET', '/');
        $this->server->exec();
        $this->assertEquals(500, $this->response->getStatus());

    }

}
