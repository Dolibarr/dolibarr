<?php

namespace Sabre\VObject\ITip;

class BrokerAttendeeReplyTest extends BrokerTester {

    function testAccepted() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

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
SUMMARY:B-day party
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testRecurringReply() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
RRULE;FREQ=DAILY
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=One:mailto:one@example.org
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART:20140726T120000Z
RECURRENCE-ID:20140726T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART:20140724T120000Z
RECURRENCE-ID:20140724T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=TENTATIVE;CN=One:mailto:one@example.org
DTSTART:20140728T120000Z
RECURRENCE-ID:20140728T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART:20140729T120000Z
RECURRENCE-ID:20140729T120000Z
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART:20140725T120000Z
RECURRENCE-ID:20140725T120000Z
END:VEVENT
END:VCALENDAR
ICS;

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
DTSTART:20140726T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140726T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140724T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140728T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140728T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=TENTATIVE;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140729T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140729T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140725T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140725T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testRecurringAllDay() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140724
RRULE;FREQ=DAILY
END:VEVENT
END:VCALENDAR
ICS;


    $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140724
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140726
RECURRENCE-ID;VALUE=DATE:20140726
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140724
RECURRENCE-ID;VALUE=DATE:20140724
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=TENTATIVE;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140728
RECURRENCE-ID;VALUE=DATE:20140728
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140729
RECURRENCE-ID;VALUE=DATE:20140729
END:VEVENT
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140725
RECURRENCE-ID;VALUE=DATE:20140725
END:VEVENT
END:VCALENDAR
ICS;

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
DTSTART;VALUE=DATE:20140726
RECURRENCE-ID;VALUE=DATE:20140726
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART;VALUE=DATE:20140724
RECURRENCE-ID;VALUE=DATE:20140724
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART;VALUE=DATE:20140728
RECURRENCE-ID;VALUE=DATE:20140728
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=TENTATIVE;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART;VALUE=DATE:20140729
RECURRENCE-ID;VALUE=DATE:20140729
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART;VALUE=DATE:20140725
RECURRENCE-ID;VALUE=DATE:20140725
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testNoChange() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $expected = [];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testNoChangeForceSend() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;SCHEDULE-FORCE-SEND=REPLY;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

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
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS
            ]

        ];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testNoRelevantAttendee() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=Two:mailto:two@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

        $expected = [];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * In this test, an event exists in an attendees calendar. The event
     * is recurring, and the attendee deletes 1 instance of the event.
     * This instance shows up in EXDATE
     *
     * This should automatically generate a DECLINED message for that
     * specific instance.
     */
    function testCreateReplyByException() {


        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140811T200000Z
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140811T200000Z
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
EXDATE:20140818T200000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;
        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REPLY',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:one@example.org',
                'senderName'    => null,
                'recipient'     => 'mailto:organizer@example.org',
                'recipientName' => null,
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
DTSTART:20140818T200000Z
RECURRENCE-ID:20140818T200000Z
ORGANIZER:mailto:organizer@example.org
ATTENDEE;PARTSTAT=DECLINED:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * This test is identical to the last, but now we're working with
     * timezones.
     *
     * @depends testCreateReplyByException
     */
    function testCreateReplyByExceptionTz() {


        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART;TZID=America/Toronto:20140811T200000
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART;TZID=America/Toronto:20140811T200000
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
EXDATE;TZID=America/Toronto:20140818T200000
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;
        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REPLY',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:one@example.org',
                'senderName'    => null,
                'recipient'     => 'mailto:organizer@example.org',
                'recipientName' => null,
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
DTSTART;TZID=America/Toronto:20140818T200000
RECURRENCE-ID;TZID=America/Toronto:20140818T200000
ORGANIZER:mailto:organizer@example.org
ATTENDEE;PARTSTAT=DECLINED:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * @depends testCreateReplyByException
     */
    function testCreateReplyByExceptionAllDay() {


        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:Weekly meeting
UID:foobar
SEQUENCE:1
DTSTART;VALUE=DATE:20140811
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:Weekly meeting
UID:foobar
SEQUENCE:1
DTSTART;VALUE=DATE:20140811
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
EXDATE;VALUE=DATE:20140818
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;
        $expected = [
            [
                'uid'           => 'foobar',
                'method'        => 'REPLY',
                'component'     => 'VEVENT',
                'sender'        => 'mailto:one@example.org',
                'senderName'    => null,
                'recipient'     => 'mailto:organizer@example.org',
                'recipientName' => null,
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
DTSTART;VALUE=DATE:20140818
SUMMARY:Weekly meeting
RECURRENCE-ID;VALUE=DATE:20140818
ORGANIZER:mailto:organizer@example.org
ATTENDEE;PARTSTAT=DECLINED:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],
        ];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testDeclined() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

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
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testDeclinedCancelledEvent() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
STATUS:CANCELLED
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
STATUS:CANCELLED
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=DECLINED;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * In this test, a new exception is created by an attendee as well.
     *
     * Except in this case, there was already an overridden event, and the
     * overridden event was marked as cancelled by the attendee.
     *
     * For any other attendence status, the new status would have been
     * declined, but for this, no message should we sent.
     */
    function testDontCreateReplyWhenEventWasDeclined() {


        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140811T200000Z
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
END:VEVENT
BEGIN:VEVENT
RECURRENCE-ID:20140818T200000Z
UID:foobar
SEQUENCE:1
DTSTART:20140818T200000Z
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE;PARTSTAT=DECLINED:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
DTSTART:20140811T200000Z
RRULE:FREQ=WEEKLY
ORGANIZER:mailto:organizer@example.org
ATTENDEE:mailto:one@example.org
EXDATE:20140818T200000Z
END:VEVENT
END:VCALENDAR
ICS;

        $expected = [];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testScheduleAgentOnOrganizer() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;SCHEDULE-AGENT=CLIENT;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART:20140716T120000Z
END:VEVENT
END:VCALENDAR
ICS;

        $version = \Sabre\VObject\Version::VERSION;

        $expected = [];
        $this->parse($oldMessage, $newMessage, $expected);

    }

    function testAcceptedAllDay() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140716
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART;VALUE=DATE:20140716
END:VEVENT
END:VCALENDAR
ICS;

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
DTSTART;VALUE=DATE:20140716
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * This function tests an attendee updating their status to an event where
     * they don't have the master event of.
     *
     * This is possible in cases an organizer created a recurring event, and
     * invited an attendee for one instance of the event.
     */
    function testReplyNoMasterEvent() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;CN=One:mailto:one@example.org
RECURRENCE-ID:20140724T120000Z
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
RECURRENCE-ID:20140724T120000Z
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
END:VEVENT
END:VCALENDAR
ICS;

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
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140724T120000Z
SUMMARY:Daily sprint
RECURRENCE-ID:20140724T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR
ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }

    /**
     * A party crasher is an attendee that accepted an event, but was not in
     * any original invite.
     *
     * @depends testAccepted
     */
    function testPartyCrasher() {

        $oldMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
DTSTART:20140716T120000Z
RRULE:FREQ=DAILY
END:VEVENT
BEGIN:VEVENT
UID:foobar
RECURRENCE-ID:20140717T120000Z
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
DTSTART:20140717T120000Z
RRULE:FREQ=DAILY
END:VEVENT
END:VCALENDAR
ICS;


        $newMessage = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
DTSTART:20140716T120000Z
RRULE:FREQ=DAILY
END:VEVENT
BEGIN:VEVENT
UID:foobar
RECURRENCE-ID:20140717T120000Z
SUMMARY:B-day party
SEQUENCE:1
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
DTSTART:20140717T120000Z
RRULE:FREQ=DAILY
END:VEVENT
END:VCALENDAR
ICS;

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
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VEVENT
UID:foobar
DTSTAMP:**ANY**
SEQUENCE:1
DTSTART:20140717T120000Z
SUMMARY:B-day party
RECURRENCE-ID:20140717T120000Z
ORGANIZER;CN=Strunk:mailto:strunk@example.org
ATTENDEE;PARTSTAT=ACCEPTED;CN=One:mailto:one@example.org
END:VEVENT
END:VCALENDAR

ICS

            ],

        ];

        $this->parse($oldMessage, $newMessage, $expected);

    }
}
