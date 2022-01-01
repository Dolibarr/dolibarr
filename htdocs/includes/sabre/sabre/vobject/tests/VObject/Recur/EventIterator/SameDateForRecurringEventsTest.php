<?php

namespace Sabre\VObject\Recur;

use Sabre\VObject\Reader;

/**
 * Testing case when overridden recurring events have same start date.
 *
 * Class SameDateForRecurringEventsTest
 */
class SameDateForRecurringEventsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Checking is all events iterated by EventIterator.
     */
    function testAllEventsArePresentInIterator()
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:1
DTSTART;TZID=Europe/Kiev:20160713T110000
DTEND;TZID=Europe/Kiev:20160713T113000
RRULE:FREQ=DAILY;INTERVAL=1;COUNT=3
END:VEVENT
BEGIN:VEVENT
UID:2
DTSTART;TZID=Europe/Kiev:20160713T110000
DTEND;TZID=Europe/Kiev:20160713T113000
RECURRENCE-ID;TZID=Europe/Kiev:20160714T110000
END:VEVENT
BEGIN:VEVENT
UID:3
DTSTART;TZID=Europe/Kiev:20160713T110000
DTEND;TZID=Europe/Kiev:20160713T113000
RECURRENCE-ID;TZID=Europe/Kiev:20160715T110000
END:VEVENT
BEGIN:VEVENT
UID:4
DTSTART;TZID=Europe/Kiev:20160713T110000
DTEND;TZID=Europe/Kiev:20160713T113000
RECURRENCE-ID;TZID=Europe/Kiev:20160716T110000
END:VEVENT
END:VCALENDAR


ICS;
        $vCalendar = Reader::read($ics);
        $eventIterator = new EventIterator($vCalendar->getComponents());

        $this->assertEquals(4, iterator_count($eventIterator), 'in ICS 4 events');
    }
}
