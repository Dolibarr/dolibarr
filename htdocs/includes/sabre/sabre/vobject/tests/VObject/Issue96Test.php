<?php

namespace Sabre\VObject;

class Issue96Test extends \PHPUnit_Framework_TestCase {

    function testRead() {

        $input = <<<VCF
BEGIN:VCARD
VERSION:2.1
SOURCE:Yahoo Contacts (http://contacts.yahoo.com)
URL;CHARSET=utf-8;ENCODING=QUOTED-PRINTABLE:=
http://www.example.org
END:VCARD
VCF;

        $vcard = Reader::read($input, Reader::OPTION_FORGIVING);
        $this->assertInstanceOf('Sabre\\VObject\\Component\\VCard', $vcard);
        $this->assertEquals("http://www.example.org", $vcard->URL->getValue());

    }

}
