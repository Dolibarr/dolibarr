<?php

/*
 * This file is part of Raven.
 *
 * (c) Sentry Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Raven_Tests_ErrorHandlerTest extends PHPUnit_Framework_TestCase
{
    private $errorLevel;

    public function setUp()
    {
        $this->errorLevel = error_reporting();
    }

    public function tearDown()
    {
        error_reporting($this->errorLevel);
    }

    public function testErrorsAreLoggedAsExceptions()
    {
        $client = $this->getMock('Client', array('captureException', 'getIdent'));
        $client->expects($this->once())
               ->method('captureException')
               ->with($this->isInstanceOf('ErrorException'));

        $handler = new Raven_ErrorHandler($client);
        $handler->handleError(E_WARNING, 'message');
    }

    public function testExceptionsAreLogged()
    {
        $client = $this->getMock('Client', array('captureException', 'getIdent'));
        $client->expects($this->once())
               ->method('captureException')
               ->with($this->isInstanceOf('ErrorException'));

        $e = new ErrorException('message', 0, E_WARNING, '', 0);

        $handler = new Raven_ErrorHandler($client);
        $handler->handleException($e);
    }

    public function testErrorHandlerCheckSilentReporting()
    {
        $client = $this->getMock('Client', array('captureException', 'getIdent'));
        $client->expects($this->never())
               ->method('captureException');

        $handler = new Raven_ErrorHandler($client);
        $handler->registerErrorHandler(false);

        @trigger_error('Silent', E_USER_WARNING);
    }

    public function testErrorHandlerBlockErrorReporting()
    {
        $client = $this->getMock('Client', array('captureException', 'getIdent'));
        $client->expects($this->never())
               ->method('captureException');

        $handler = new Raven_ErrorHandler($client);
        $handler->registerErrorHandler(false);

        error_reporting(E_USER_ERROR);
        trigger_error('Warning', E_USER_WARNING);
    }

    public function testErrorHandlerPassErrorReportingPass()
    {
        $client = $this->getMock('Client', array('captureException', 'getIdent'));
        $client->expects($this->once())
               ->method('captureException');

        $handler = new Raven_ErrorHandler($client);
        $handler->registerErrorHandler(false);

        error_reporting(E_USER_WARNING);
        trigger_error('Warning', E_USER_WARNING);
    }
}
