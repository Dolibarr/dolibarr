<?php

namespace Sabre\VObject\Recur\EventIterator;

use DateTime;
use Sabre\VObject\Reader;
use Sabre\VObject\Settings;

class MaxInstancesTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \Sabre\VObject\Recur\MaxInstancesExceededException
     */
    function testExceedMaxRecurrences() {

        $input = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
DTSTART:20140803T120000Z
RRULE:FREQ=WEEKLY
SUMMARY:Original
END:VEVENT
END:VCALENDAR
ICS;

        $temp = Settings::$maxRecurrences;
        Settings::$maxRecurrences = 4;
        try {

            $vcal = Reader::read($input);
            $vcal->expand(new DateTime('2014-08-01'), new DateTime('2014-09-01'));

        } finally {
            Settings::$maxRecurrences = $temp;
        }

    }

}
