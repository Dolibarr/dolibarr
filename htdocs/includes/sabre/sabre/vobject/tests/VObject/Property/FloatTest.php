<?php

namespace Sabre\VObject\Property;

use Sabre\VObject;

class FloatTest extends \PHPUnit_Framework_TestCase {

    function testMimeDir() {

        $input = "BEGIN:VCARD\r\nVERSION:4.0\r\nX-FLOAT;VALUE=FLOAT:0.234;1.245\r\nEND:VCARD\r\n";
        $mimeDir = new VObject\Parser\MimeDir($input);

        $result = $mimeDir->parse($input);

        $this->assertInstanceOf('Sabre\VObject\Property\FloatValue', $result->{'X-FLOAT'});

        $this->assertEquals([
            0.234,
            1.245,
        ], $result->{'X-FLOAT'}->getParts());

        $this->assertEquals(
            $input,
            $result->serialize()
        );

    }

}
