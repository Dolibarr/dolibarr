<?php

namespace Sabre\DAV;

class ExceptionTest extends \PHPUnit_Framework_TestCase {

    function testStatus() {

        $e = new Exception();
        $this->assertEquals(500, $e->getHTTPCode());

    }

    function testExceptionStatuses() {

        $c = [
            'Sabre\\DAV\\Exception\\NotAuthenticated'    => 401,
            'Sabre\\DAV\\Exception\\InsufficientStorage' => 507,
        ];

        foreach ($c as $class => $status) {

            $obj = new $class();
            $this->assertEquals($status, $obj->getHTTPCode());

        }

    }

}
