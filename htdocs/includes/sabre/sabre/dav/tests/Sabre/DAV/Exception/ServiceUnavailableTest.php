<?php

namespace Sabre\DAV\Exception;

class ServiceUnavailableTest extends \PHPUnit_Framework_TestCase {

    function testGetHTTPCode() {

        $ex = new ServiceUnavailable();
        $this->assertEquals(503, $ex->getHTTPCode());

    }

}
