<?php

namespace Sabre\VObject\ITip;

use Sabre\VObject\Reader;

class BrokerTimezoneInParseEventInfoWithoutMasterTest extends \PHPUnit_Framework_TestCase {

    function testTimezoneInParseEventInfoWithoutMaster()
    {
        $calendar = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.9.5//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Minsk
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;UNTIL=20100328T000000Z;BYMONTH=3;BYDAY=-1SU
DTSTART:19930328T020000
TZNAME:GMT+3
TZOFFSETTO:+0300
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
DTSTART:20110327T020000
TZNAME:GMT+3
TZOFFSETTO:+0300
RDATE:20110327T020000
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20160331T163031Z
UID:B9301437-417C-4136-8DB3-8D1555863791
DTEND;TZID=Europe/Minsk:20160405T100000
TRANSP:OPAQUE
ATTENDEE;CN=User Invitee;CUTYPE=INDIVIDUAL;EMAIL=invitee@test.com;PARTSTAT=
 ACCEPTED;ROLE=REQ-PARTICIPANT:mailto:invitee@test.com
ATTENDEE;CN=User Organizer;CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:mailto:organ
 izer@test.com
SUMMARY:Event title
DTSTART;TZID=Europe/Minsk:20160405T090000
DTSTAMP:20160331T164108Z
ORGANIZER;CN=User Organizer:mailto:organizer@test.com
SEQUENCE:6
RECURRENCE-ID;TZID=Europe/Minsk:20160405T090000
END:VEVENT
BEGIN:VEVENT
CREATED:20160331T163031Z
UID:B9301437-417C-4136-8DB3-8D1555863791
DTEND;TZID=Europe/Minsk:20160406T100000
TRANSP:OPAQUE
ATTENDEE;CN=User Invitee;CUTYPE=INDIVIDUAL;EMAIL=invitee@test.com;PARTSTAT=
 ACCEPTED;ROLE=REQ-PARTICIPANT:mailto:invitee@test.com
ATTENDEE;CN=User Organizer;CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:mailto:organ
 izer@test.com
SUMMARY:Event title
DTSTART;TZID=Europe/Minsk:20160406T090000
DTSTAMP:20160331T165845Z
ORGANIZER;CN=User Organizer:mailto:organizer@test.com
SEQUENCE:6
RECURRENCE-ID;TZID=Europe/Minsk:20160406T090000
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = Reader::read($calendar);
        $broker = new Broker();

        $reflectionMethod = new \ReflectionMethod($broker, 'parseEventInfo');
        $reflectionMethod->setAccessible(true);
        $data = $reflectionMethod->invoke($broker, $calendar);
        $this->assertInstanceOf('DateTimeZone', $data['timezone']);
        $this->assertEquals($data['timezone']->getName(), 'Europe/Minsk');
    }
}
