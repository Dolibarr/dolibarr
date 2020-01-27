<?php

namespace Sabre\DAV;

class PSR3Test extends \PHPUnit_Framework_TestCase {

    function testIsLoggerAware() {

        $server = new Server();
        $this->assertInstanceOf(
            'Psr\Log\LoggerAwareInterface',
            $server
        );

    }

    function testGetNullLoggerByDefault() {

        $server = new Server();
        $this->assertInstanceOf(
            'Psr\Log\NullLogger',
            $server->getLogger()
        );

    }

    function testSetLogger() {

        $server = new Server();
        $logger = new MockLogger();

        $server->setLogger($logger);

        $this->assertEquals(
            $logger,
            $server->getLogger()
        );

    }

    /**
     * Start the server, trigger an exception and see if the logger captured
     * it.
     */
    function testLogException() {

        $server = new Server();
        $logger = new MockLogger();

        $server->setLogger($logger);

        // Creating a fake environment to execute http requests in.
        $request = new \Sabre\HTTP\Request(
            'GET',
            '/not-found',
            []
        );
        $response = new \Sabre\HTTP\Response();

        $server->httpRequest = $request;
        $server->httpResponse = $response;
        $server->sapi = new \Sabre\HTTP\SapiMock();

        // Executing the request.
        $server->exec();

        // The request should have triggered a 404 status.
        $this->assertEquals(404, $response->getStatus());

        // We should also see this in the PSR-3 log.
        $this->assertEquals(1, count($logger->logs));

        $logItem = $logger->logs[0];

        $this->assertEquals(
            \Psr\Log\LogLevel::INFO,
            $logItem[0]
        );

        $this->assertInstanceOf(
            'Exception',
            $logItem[2]['exception']
        );

    }

}
