<?php

namespace Sabre\VObject;

/**
 * Assorted vcard 2.1 tests.
 */
class VCard21Test extends \PHPUnit_Framework_TestCase {

    function testPropertyWithNoName() {

        $input = <<<VCF
BEGIN:VCARD\r
VERSION:2.1\r
EMAIL;HOME;WORK:evert@fruux.com\r
END:VCARD\r

VCF;

        $vobj = Reader::read($input);
        $output = $vobj->serialize();

        $this->assertEquals($input, $output);

    }

    function testPropertyPadValueCount() {

        $input = <<<VCF
BEGIN:VCARD
VERSION:2.1
N:Foo
END:VCARD

VCF;

        $vobj = Reader::read($input);
        $output = $vobj->serialize();

        $expected = <<<VCF
BEGIN:VCARD\r
VERSION:2.1\r
N:Foo;;;;\r
END:VCARD\r

VCF;


        $this->assertEquals($expected, $output);

    }
}
