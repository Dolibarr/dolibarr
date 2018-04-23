<?php

namespace Sabre\VObject;

class Issue153Test extends \PHPUnit_Framework_TestCase {

    function testRead() {

        $obj = Reader::read(file_get_contents(dirname(__FILE__) . '/issue153.vcf'));
        $this->assertEquals('Test Benutzer', (string)$obj->FN);

    }

}
