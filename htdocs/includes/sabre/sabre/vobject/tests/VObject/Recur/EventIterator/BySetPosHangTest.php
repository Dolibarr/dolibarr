<?php

namespace Sabre\VObject\Recur;

use DateTime;
use Sabre\VObject\Reader;

class BySetPosHangTest extends \PHPUnit_Framework_TestCase {

    /**
     * Using this iCalendar object, including BYSETPOS=-2 causes the iterator
     * to hang, as reported in ticket #212.
     *
     * See: https://github.com/fruux/sabre-vobject/issues/212
     */
    function testExpand() {

        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 3.4.2//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
SUMMARY:Test event 1
DTSTART;TZID=Europe/Copenhagen:20150101T170000
RRULE:FREQ=MONTHLY;BYDAY=TH;BYSETPOS=-2
UID:b4071499-6fe4-418a-83b8-2b8d5ebb38e4
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($ics);
        $this->assertInstanceOf('Sabre\\VObject\\Component\\VCalendar', $vcal);

        $vcal = $vcal->expand(new DateTime('2015-01-01'), new DateTime('2016-01-01'));

        foreach ($vcal->VEVENT as $event) {
            $dates[] = $event->DTSTART->getValue();
        }

        $expectedDates = [
            "20150101T160000Z",
            "20150122T160000Z",
            "20150219T160000Z",
            "20150319T160000Z",
            "20150423T150000Z",
            "20150521T150000Z",
            "20150618T150000Z",
            "20150723T150000Z",
            "20150820T150000Z",
            "20150917T150000Z",
            "20151022T150000Z",
            "20151119T160000Z",
            "20151224T160000Z",
        ];

        $this->assertEquals($expectedDates, $dates);
    }

}
