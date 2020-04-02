<?php

namespace Sabre\VObject;

class Issue36WorkAroundTest extends \PHPUnit_Framework_TestCase {

    function testWorkaround() {

        // See https://github.com/fruux/sabre-vobject/issues/36
        $event = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:Titel
SEQUENCE:1
TRANSP:TRANSPARENT
RRULE:FREQ=YEARLY
LAST-MODIFIED:20130323T225737Z
DTSTAMP:20130323T225737Z
UID:1833bd44-188b-405c-9f85-1a12105318aa
CATEGORIES:Jubiläum
X-MOZ-GENERATION:3
RECURRENCE-ID;RANGE=THISANDFUTURE;VALUE=DATE:20131013
DTSTART;VALUE=DATE:20131013
CREATED:20100721T121914Z
DURATION:P1D
END:VEVENT
END:VCALENDAR
ICS;

        $obj = Reader::read($event);

        // If this does not throw an exception, it's all good.
        $it = new Recur\EventIterator($obj, '1833bd44-188b-405c-9f85-1a12105318aa');
        $this->assertInstanceOf('Sabre\\VObject\\Recur\\EventIterator', $it);

    }

}
