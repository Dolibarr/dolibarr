<?php

namespace Sabre\VObject\Recur\EventIterator;

use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;

class Issue26Test extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Sabre\VObject\InvalidDataException
     */
    function testExpand() {

        $input = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:bae5d57a98
RRULE:FREQ=MONTHLY;BYDAY=0MO,0TU,0WE,0TH,0FR;INTERVAL=1
DTSTART;VALUE=DATE:20130401
DTEND;VALUE=DATE:20130402
END:VEVENT
END:VCALENDAR
ICS;

        $vcal = Reader::read($input);
        $this->assertInstanceOf('Sabre\\VObject\\Component\\VCalendar', $vcal);

        $it = new EventIterator($vcal, 'bae5d57a98');

    }

}
