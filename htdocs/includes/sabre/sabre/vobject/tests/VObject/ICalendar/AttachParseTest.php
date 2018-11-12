<?php

namespace Sabre\VObject\ICalendar;

use Sabre\VObject\Reader;

class AttachParseTest extends \PHPUnit_Framework_TestCase {

    /**
     * See issue #128 for more info.
     */
    function testParseAttach() {

        $vcal = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
ATTACH;FMTTYPE=application/postscript:ftp://example.com/pub/reports/r-960812.ps
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($vcal);
        $prop = $vcal->VEVENT->ATTACH;

        $this->assertInstanceOf('Sabre\\VObject\\Property\\URI', $prop);
        $this->assertEquals('ftp://example.com/pub/reports/r-960812.ps', $prop->getValue());


    }

}
