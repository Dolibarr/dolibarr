<?php

namespace Sabre\VObject;

class VersionTest extends \PHPUnit_Framework_TestCase {

    function testString() {

        $v = Version::VERSION;
        $this->assertEquals(-1, version_compare('2.0.0', $v));

    }

}
