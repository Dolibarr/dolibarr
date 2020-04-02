<?php

namespace Sabre\VObject\Recur\EventIterator;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Reader;

/**
 * This is a unittest for Issue #53.
 */
class HandleRDateExpandTest extends \PHPUnit_Framework_TestCase {

    function testExpand() {

        $input = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:2CD5887F7CF4600F7A3B1F8065099E40-240BDA7121B61224
DTSTAMP;VALUE=DATE-TIME:20151014T110604Z
CREATED;VALUE=DATE-TIME:20151014T110245Z
LAST-MODIFIED;VALUE=DATE-TIME:20151014T110541Z
DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20151012T020000
DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:20151012T013000
SUMMARY:Test
SEQUENCE:2
RDATE;VALUE=DATE-TIME;TZID=Europe/Berlin:20151015T020000,20151017T020000,20
 151018T020000,20151020T020000
TRANSP:OPAQUE
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($input);
        $this->assertInstanceOf('Sabre\\VObject\\Component\\VCalendar', $vcal);

        $vcal = $vcal->expand(new DateTime('2015-01-01'), new DateTime('2015-12-01'));

        $result = iterator_to_array($vcal->VEVENT);

        $this->assertEquals(5, count($result));

        $utc = new DateTimeZone('UTC');
        $expected = [
            new DateTimeImmutable("2015-10-12", $utc),
            new DateTimeImmutable("2015-10-15", $utc),
            new DateTimeImmutable("2015-10-17", $utc),
            new DateTimeImmutable("2015-10-18", $utc),
            new DateTimeImmutable("2015-10-20", $utc),
        ];

        $result = array_map(function($ev) {return $ev->DTSTART->getDateTime();}, $result);
        $this->assertEquals($expected, $result);
    
    }

}
