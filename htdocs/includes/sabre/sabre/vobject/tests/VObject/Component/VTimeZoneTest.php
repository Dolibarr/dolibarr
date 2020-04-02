<?php

namespace Sabre\VObject\Component;

use Sabre\VObject\Reader;

class VTimeZoneTest extends \PHPUnit_Framework_TestCase {

    function testValidate() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTIMEZONE
TZID:America/Toronto
END:VTIMEZONE
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = [];
        foreach ($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals([], $messages);

    }

    function testGetTimeZone() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTIMEZONE
TZID:America/Toronto
END:VTIMEZONE
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $tz = new \DateTimeZone('America/Toronto');

        $this->assertEquals(
            $tz,
            $obj->VTIMEZONE->getTimeZone()
        );

    }

}
