<?php

namespace Sabre\VObject\ITip;

class BrokerUpdateEventTest extends BrokerTester {

    function testInviteChange() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two:mailto:two@example.org
ATTENDEE;CN=Three:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'CANCEL',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => true,
                'message'           => <<<ICS
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
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:two@example.org',
                'recipientName'     => 'Two',
                'significantChange' => false,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two;PARTSTAT=NEEDS-ACTION:mailto:two@example.org
ATTENDEE;CN=Three;PARTSTAT=NEEDS-ACTION:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:three@example.org',
                'recipientName'     => 'Three',
                'significantChange' => true,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two;PARTSTAT=NEEDS-ACTION:mailto:two@example.org
ATTENDEE;CN=Three;PARTSTAT=NEEDS-ACTION:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteChangeFromNonSchedulingToSchedulingObject() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REQUEST',
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
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One;PARTSTAT=NEEDS-ACTION:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteChangeFromSchedulingToNonSchedulingObject() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'       => 'foobar',
                'method'    => 'CANCEL',
                'component' => 'VEVENT',
                'message'   => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testNoAttendees() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [];
        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testRemoveInstance() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART;TZID=America/Toronto:20140716T120000
DTEND;TZID=America/Toronto:20140716T130000
RRULE:FREQ=WEEKLY
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART;TZID=America/Toronto:20140716T120000
DTEND;TZID=America/Toronto:20140716T130000
RRULE:FREQ=WEEKLY
EXDATE;TZID=America/Toronto:20140724T120000
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REQUEST',
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
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One;PARTSTAT=NEEDS-ACTION:mailto:one@example.org
DTSTART;TZID=America/Toronto:20140716T120000
DTEND;TZID=America/Toronto:20140716T130000
RRULE:FREQ=WEEKLY
EXDATE;TZID=America/Toronto:20140724T120000
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    /**
     * This test is identical to the first test, except this time we change the
     * DURATION property.
     *
     * This should ensure that the message is significant for every attendee,
     */
    function testInviteChangeSignificantChange() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
DURATION:PT1H
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
DURATION:PT2H
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two:mailto:two@example.org
ATTENDEE;CN=Three:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'CANCEL',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => true,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:CANCEL
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:2
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:two@example.org',
                'recipientName'     => 'Two',
                'significantChange' => true,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
DURATION:PT2H
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two;PARTSTAT=NEEDS-ACTION:mailto:two@example.org
ATTENDEE;CN=Three;PARTSTAT=NEEDS-ACTION:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:three@example.org',
                'recipientName'     => 'Three',
                'significantChange' => true,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
DURATION:PT2H
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=Two;PARTSTAT=NEEDS-ACTION:mailto:two@example.org
ATTENDEE;CN=Three;PARTSTAT=NEEDS-ACTION:mailto:three@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteNoChange() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => false,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One;PARTSTAT=NEEDS-ACTION:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteNoChangeForceSend() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;SCHEDULE-FORCE-SEND=REQUEST;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => true,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;PARTSTAT=ACCEPTED:mailto:strunk@example.org
ATTENDEE;CN=One;PARTSTAT=NEEDS-ACTION:mailto:one@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteRemoveAttendees() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
SUMMARY:foo
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
SUMMARY:foo
DTSTART:20140716T120000Z
DTEND:20140716T130000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'CANCEL',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => true,
                'message'           => <<<ICS
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
                'uid'               => 'foobar',
                'method'            => 'CANCEL',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:two@example.org',
                'recipientName'     => 'Two',
                'significantChange' => true,
                'message'           => <<<ICS
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

        $result = $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }

    function testInviteChangeExdateOrder() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.10.1//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:foobar
SEQUENCE:0
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;CUTYPE=INDIVIDUAL;EMAIL=strunk@example.org;PARTSTAT=ACCE
 PTED:mailto:strunk@example.org
ATTENDEE;CN=One;CUTYPE=INDIVIDUAL;EMAIL=one@example.org;PARTSTAT=ACCEPTED;R
 OLE=REQ-PARTICIPANT;SCHEDULE-STATUS="1.2;Message delivered locally":mailto
 :one@example.org
SUMMARY:foo
DTSTART:20141211T160000Z
DTEND:20141211T170000Z
RRULE:FREQ=WEEKLY
EXDATE:20141225T160000Z,20150101T160000Z
EXDATE:20150108T160000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.10.1//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;CUTYPE=INDIVIDUAL;EMAIL=strunk@example.org;PARTSTAT=ACCE
 PTED:mailto:strunk@example.org
ATTENDEE;CN=One;CUTYPE=INDIVIDUAL;EMAIL=one@example.org;PARTSTAT=ACCEPTED;R
 OLE=REQ-PARTICIPANT;SCHEDULE-STATUS=1.2:mailto:one@example.org
DTSTART:20141211T160000Z
DTEND:20141211T170000Z
RRULE:FREQ=WEEKLY
EXDATE:20150101T160000Z
EXDATE:20150108T160000Z,20141225T160000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [
            [
                'uid'               => 'foobar',
                'method'            => 'REQUEST',
                'component'         => 'VEVENT',
                'sender'            => 'mailto:strunk@example.org',
                'senderName'        => 'Strunk',
                'recipient'         => 'mailto:one@example.org',
                'recipientName'     => 'One',
                'significantChange' => false,
                'message'           => <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject $version//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Strunk;CUTYPE=INDIVIDUAL;EMAIL=strunk@example.org;PARTSTAT=ACCE
 PTED:mailto:strunk@example.org
ATTENDEE;CN=One;CUTYPE=INDIVIDUAL;EMAIL=one@example.org;PARTSTAT=ACCEPTED;R
 OLE=REQ-PARTICIPANT:mailto:one@example.org
DTSTART:20141211T160000Z
DTEND:20141211T170000Z
RRULE:FREQ=WEEKLY
EXDATE:20150101T160000Z
EXDATE:20150108T160000Z,20141225T160000Z
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];

        $this->parse($oldMessage, $newMessage, $expected, 'mailto:strunk@example.org');

    }
}
