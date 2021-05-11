<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Component\VCard;

class TextTest extends \PHPUnit_Framework_TestCase {

    function assertVCard21Serialization($propValue, $expected) {

        $doc = new VCard([
            'VERSION' => '2.1',
            'PROP'    => $propValue
        ], false);

        // Adding quoted-printable, because we're testing if it gets removed
        // automatically.
        $doc->PROP['ENCODING'] = 'QUOTED-PRINTABLE';
        $doc->PROP['P1'] = 'V1';


        $output = $doc->serialize();


        $this->assertEquals("BEGIN:VCARD\r\nVERSION:2.1\r\n$expected\r\nEND:VCARD\r\n", $output);

    }

    function testSerializeVCard21() {

        $this->assertVCard21Serialization(
            'f;oo',
            'PROP;P1=V1:f;oo'
        );

    }

    function testSerializeVCard21Array() {

        $this->assertVCard21Serialization(
            ['f;oo', 'bar'],
            'PROP;P1=V1:f\;oo;bar'
        );

    }
    function testSerializeVCard21Fold() {

        $this->assertVCard21Serialization(
            str_repeat('x', 80),
            'PROP;P1=V1:' . str_repeat('x', 64) . "\r\n " . str_repeat('x', 16)
        );

    }



    function testSerializeQuotedPrintable() {

        $this->assertVCard21Serialization(
            "foo\r\nbar",
            'PROP;P1=V1;ENCODING=QUOTED-PRINTABLE:foo=0D=0Abar'
        );
    }

    function testSerializeQuotedPrintableFold() {

        $this->assertVCard21Serialization(
            "foo\r\nbarxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
            "PROP;P1=V1;ENCODING=QUOTED-PRINTABLE:foo=0D=0Abarxxxxxxxxxxxxxxxxxxxxxxxxxx=\r\n xxx"
        );

    }

    function testValidateMinimumPropValue() {

        $vcard = <<<IN
BEGIN:VCARD
VERSION:4.0
UID:foo
FN:Hi!
N:A
END:VCARD
IN;

        $vcard = \Sabre\VObject\Reader::read($vcard);
        $this->assertEquals(1, count($vcard->validate()));

        $this->assertEquals(1, count($vcard->N->getParts()));

        $vcard->validate(\Sabre\VObject\Node::REPAIR);

        $this->assertEquals(5, count($vcard->N->getParts()));

    }

}
