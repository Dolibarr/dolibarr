<?php

namespace Sabre\VObject\Recur;

use DateTime;
use Sabre\VObject\Reader;

class ByMonthInDailyTest extends \PHPUnit_Framework_TestCase {

    /**
     * This tests the expansion of dates with DAILY frequency in RRULE with BYMONTH restrictions
     */
    function testExpand() {

        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.4//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
TRANSP:OPAQUE
DTEND:20070925T183000Z
UID:uuid
DTSTAMP:19700101T000000Z
LOCATION:
DESCRIPTION:
STATUS:CONFIRMED
SEQUENCE:18
SUMMARY:Stuff
DTSTART:20070925T160000Z
CREATED:20071004T144642Z
RRULE:FREQ=DAILY;BYMONTH=9,10;BYDAY=SU
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($ics);
        $this->assertInstanceOf('Sabre\\VObject\\Component\\VCalendar', $vcal);

        $vcal = $vcal->expand(new DateTime('2013-09-28'), new DateTime('2014-09-11'));

        foreach ($vcal->VEVENT as $event) {
            $dates[] = $event->DTSTART->getValue();
        }

        $expectedDates = [
            "20130929T160000Z",
            "20131006T160000Z",
            "20131013T160000Z",
            "20131020T160000Z",
            "20131027T160000Z",
            "20140907T160000Z"
        ];

        $this->assertEquals($expectedDates, $dates, 'Recursed dates are restricted by month');
    }

}
