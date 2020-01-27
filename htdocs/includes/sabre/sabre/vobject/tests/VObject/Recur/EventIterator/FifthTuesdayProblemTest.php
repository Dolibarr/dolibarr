<?php

namespace Sabre\VObject\Recur\EventIterator;

use Sabre\VObject\Reader;
use Sabre\VObject\Recur;

class FifthTuesdayProblemTest extends \PHPUnit_Framework_TestCase {

    /**
     * A pretty slow test. Had to be marked as 'medium' for phpunit to not die
     * after 1 second. Would be good to optimize later.
     *
     * @medium
     */
    function testGetDTEnd() {

        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.4//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
TRANSP:OPAQUE
DTEND;TZID=America/New_York:20070925T170000
UID:uuid
DTSTAMP:19700101T000000Z
LOCATION:
DESCRIPTION:
STATUS:CONFIRMED
SEQUENCE:18
SUMMARY:Stuff
DTSTART;TZID=America/New_York:20070925T160000
CREATED:20071004T144642Z
RRULE:FREQ=MONTHLY;INTERVAL=1;UNTIL=20071030T035959Z;BYDAY=5TU
END:VEVENT
END:VCALENDAR
ICS;

        $vObject = Reader::read($ics);
        $it = new Recur\EventIterator($vObject, (string)$vObject->VEVENT->UID);

        while ($it->valid()) {
            $it->next();
        }

        // If we got here, it means we were successful. The bug that was in the
        // system before would fail on the 5th tuesday of the month, if the 5th
        // tuesday did not exist.
        $this->assertTrue(true);

    }

}
