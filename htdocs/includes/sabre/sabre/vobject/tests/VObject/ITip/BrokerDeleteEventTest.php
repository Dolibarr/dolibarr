<?php

namespace Sabre\VObject\ITip;

class BrokerDeleteEventTest extends BrokerTester {

    function testOrganizerDeleteWithDtend() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = null;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'CANCEL',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:strunk@example.org',
                'senderName'    => 'Strunk',
                'recipient'     => 'mailto:one@example.org',
                'recipientName' => 'One',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:2
SUMMARY:foo
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS
            ],

            [
                'uid'           => 'foobar',
                'method'        => 'CANCEL',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:strunk@example.org',
                'senderName'    => 'Strunk',
                'recipient'     => 'mailto:two@example.org',
                'recipientName' => 'Two',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:2
SUMMARY:foo
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Two:mailto:two@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testOrganizerDeleteWithDuration() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = null;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'CANCEL',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:strunk@example.org',
                'senderName'    => 'Strunk',
                'recipient'     => 'mailto:one@example.org',
                'recipientName' => 'One',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:2
SUMMARY:foo
DTSTART:20140716T120000Z
DURATION:PT1H
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS
            ],

            [
                'uid'           => 'foobar',
                'method'        => 'CANCEL',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:strunk@example.org',
                'senderName'    => 'Strunk',
                'recipient'     => 'mailto:two@example.org',
                'recipientName' => 'Two',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:2
SUMMARY:foo
DTSTART:20140716T120000Z
DURATION:PT1H
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Two:mailto:two@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testAttendeeDeleteWithDtend() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = null;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REPLY',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:one@example.org',
                'senderName'    => 'One',
                'recipient'     => 'mailto:strunk@example.org',
                'recipientName' => 'Strunk',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS
            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:one@example.org');


    }

    function testAttendeeReplyWithDuration() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DURATION:PT1H
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = null;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REPLY',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:one@example.org',
                'senderName'    => 'One',
                'recipient'     => 'mailto:strunk@example.org',
                'recipientName' => 'Strunk',
                'message'       => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140716T120000Z
DURATION:PT1H
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS
            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:one@example.org');


    }

    function testAttendeeDeleteCancelledEvent() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
STATUS:CANCELLED
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = null;

        $expected = [];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:one@example.org');


    }

    function testNoCalendar() {

        $this->parse(null, null, [], 'mailto:one@example.org');

    }

    function testVTodo() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VTODO
UID:foobar
SEQUENCE:1
END:VTODO
END:VCALENDAR
ICS;
        $this->parse($oldMessage, null, [], 'mailto:one@example.org');

    }

}
