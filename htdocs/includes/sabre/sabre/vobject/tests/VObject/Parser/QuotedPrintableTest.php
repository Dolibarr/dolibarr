<?php

namespace Sabre\VObject\Parser;

use
    Sabre\VObject\Reader;

class QuotedPrintableTest extends \PHPUnit_Framework_TestCase {

    function testReadQuotedPrintableSimple() {

        $data = "BEGIN:VCARD\r\nLABEL;ENCODING=QUOTED-PRINTABLE:Aach=65n\r\nEND:VCARD";

        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCARD', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertEquals("Aachen", $this->getPropertyValue($result->LABEL));

    }

    function testReadQuotedPrintableNewlineSoft() {

        $data = "BEGIN:VCARD\r\nLABEL;ENCODING=QUOTED-PRINTABLE:Aa=\r\n ch=\r\n en\r\nEND:VCARD";
        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCARD', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertEquals("Aachen", $this->getPropertyValue($result->LABEL));

    }

    function testReadQuotedPrintableNewlineHard() {

        $data = "BEGIN:VCARD\r\nLABEL;ENCODING=QUOTED-PRINTABLE:Aachen=0D=0A=\r\n Germany\r\nEND:VCARD";
        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCARD', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertEquals("Aachen\r\nGermany", $this->getPropertyValue($result->LABEL));


    }

    function testReadQuotedPrintableCompatibilityMS() {

        $data = "BEGIN:VCARD\r\nLABEL;ENCODING=QUOTED-PRINTABLE:Aachen=0D=0A=\r\nDeutschland:okay\r\nEND:VCARD";
        $result = Reader::read($data, Reader::OPTION_FORGIVING);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCARD', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertEquals("Aachen\r\nDeutschland:okay", $this->getPropertyValue($result->LABEL));

    }

    function testReadQuotesPrintableCompoundValues() {

        $data = <<<VCF
BEGIN:VCARD
VERSION:2.1
N:Doe;John;;;
FN:John Doe
ADR;WORK;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:;;M=C3=BCnster =
Str. 1;M=C3=BCnster;;48143;Deutschland
END:VCARD
VCF;

        $result = Reader::read($data, Reader::OPTION_FORGIVING);
        $this->assertEquals([
            '', '', 'Münster Str. 1', 'Münster', '', '48143', 'Deutschland'
        ], $result->ADR->getParts());


    }

    private function getPropertyValue(\Sabre\VObject\Property $property) {

        return (string)$property;

        /*
        $param = $property['encoding'];
        if ($param !== null) {
            $encoding = strtoupper((string)$param);
            if ($encoding === 'QUOTED-PRINTABLE') {
                $value = quoted_printable_decode($value);
            } else {
                throw new Exception();
            }
        }

        $param = $property['charset'];
        if ($param !== null) {
            $charset = strtoupper((string)$param);
            if ($charset !== 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $charset);
            }
        } else {
            $value = StringUtil::convertToUTF8($value);
        }

        return $value;
         */
    }
}
