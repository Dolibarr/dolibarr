<?php

namespace Sabre\VObject;

/**
 * Google produces vcards with a weird escaping of urls.
 *
 * VObject will provide a workaround for this, so end-user still get expected
 * values.
 */
class GoogleColonEscapingTest extends \PHPUnit_Framework_TestCase {

    function testDecode() {

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:3.0
FN:Evert Pot
N:Pot;Evert;;;
EMAIL;TYPE=INTERNET;TYPE=WORK:evert@fruux.com
BDAY:1985-04-07
item7.URL:http\://www.rooftopsolutions.nl/
END:VCARD
VCF;

        $vobj = Reader::read($vcard);
        $this->assertEquals('http://www.rooftopsolutions.nl/', $vobj->URL->getValue());

    }

}
