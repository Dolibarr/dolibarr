<?php

namespace Sabre\VObject\Property\VCard;

use Sabre\VObject;

class LanguageTagTest extends \PHPUnit_Framework_TestCase {

    function testMimeDir() {

        $input = "BEGIN:VCARD\r\nVERSION:4.0\r\nLANG:nl\r\nEND:VCARD\r\n";
        $mimeDir = new VObject\Parser\MimeDir($input);

        $result = $mimeDir->parse($input);

        $this->assertInstanceOf('Sabre\VObject\Property\VCard\LanguageTag', $result->LANG);

        $this->assertEquals('nl', $result->LANG->getValue());

        $this->assertEquals(
            $input,
            $result->serialize()
        );

    }

    function testChangeAndSerialize() {

        $input = "BEGIN:VCARD\r\nVERSION:4.0\r\nLANG:nl\r\nEND:VCARD\r\n";
        $mimeDir = new VObject\Parser\MimeDir($input);

        $result = $mimeDir->parse($input);

        $this->assertInstanceOf('Sabre\VObject\Property\VCard\LanguageTag', $result->LANG);
        // This replicates what the vcard converter does and triggered a bug in
        // the past.
        $result->LANG->setValue(['de']);

        $this->assertEquals('de', $result->LANG->getValue());

        $expected = "BEGIN:VCARD\r\nVERSION:4.0\r\nLANG:de\r\nEND:VCARD\r\n";
        $this->assertEquals(
            $expected,
            $result->serialize()
        );
    }

}
