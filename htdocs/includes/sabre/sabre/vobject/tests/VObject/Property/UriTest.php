<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Reader;

class UriTest extends \PHPUnit_Framework_TestCase {

    function testAlwaysEncodeUriVCalendar() {

        // Apple iCal has issues with URL properties that don't have
        // VALUE=URI specified. We added a workaround to vobject that
        // ensures VALUE=URI always appears for these.
        $input = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
URL:http://example.org/
END:VEVENT
END:VCALENDAR
ICS;
        $output = Reader::read($input)->serialize();
        $this->assertContains('URL;VALUE=URI:http://example.org/', $output);

    }

}
