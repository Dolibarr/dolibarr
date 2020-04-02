<?php

namespace Sabre\VObject\ITip;

class BrokerProcessReplyTest extends BrokerTester {

    function testReplyNoOriginal() {

        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $old = null;
        $expected = null;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyAccept() {

        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE;PARTSTAT=ACCEPTED;SCHEDULE-STATUS=2.0:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyRequestStatus() {

        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
UID:foobar
REQUEST-STATUS:2.3;foo-bar!
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foobar
SEQUENCE:2
ATTENDEE;PARTSTAT=ACCEPTED;SCHEDULE-STATUS=2.3:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }


    function testReplyPartyCrasher() {

        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:crasher@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
ATTENDEE;PARTSTAT=ACCEPTED:mailto:crasher@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyNewException() {

        // This is a reply to 1 instance of a recurring event. This should
        // automatically create an exception.
        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
RECURRENCE-ID:20140725T000000Z
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
DTSTART:20140725T000000Z
DTEND:20140725T010000Z
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
RECURRENCE-ID:20140725T000000Z
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyNewExceptionTz() {

        // This is a reply to 1 instance of a recurring event. This should
        // automatically create an exception.
        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
RECURRENCE-ID;TZID=America/Toronto:20140725T000000
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART;TZID=America/Toronto:20140724T000000
DTEND;TZID=America/Toronto:20140724T010000
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART;TZID=America/Toronto:20140724T000000
DTEND;TZID=America/Toronto:20140724T010000
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
DTSTART;TZID=America/Toronto:20140725T000000
DTEND;TZID=America/Toronto:20140725T010000
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
RECURRENCE-ID;TZID=America/Toronto:20140725T000000
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyPartyCrashCreateExcepton() {

        // IN this test there's a recurring event that has an exception. The
        // exception is missing the attendee.
        //
        // The attendee party crashes the instance, so it should show up in the
        // resulting object.
        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED;CN=Crasher!:mailto:crasher@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
RECURRENCE-ID:20140725T000000Z
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ORGANIZER:mailto:bar@example.org
END:VEVENT
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
DTSTART:20140725T000000Z
DTEND:20140725T010000Z
ORGANIZER:mailto:bar@example.org
RECURRENCE-ID:20140725T000000Z
ATTENDEE;PARTSTAT=ACCEPTED;CN=Crasher!:mailto:crasher@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyNewExceptionNoMasterEvent() {

        /**
         * This iTip message would normally create a new exception, but the
         * server is not able to create this new instance, because there's no
         * master event to clone from.
         *
         * This test checks if the message is ignored.
         */
        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED;CN=Crasher!:mailto:crasher@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
RECURRENCE-ID:20140725T000000Z
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
RECURRENCE-ID:20140724T000000Z
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = null;
        $result = $this->process($itip, $old, $expected);

    }

    /**
     * @depends testReplyAccept
     */
    function testReplyAcceptUpdateRSVP() {

        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE;RSVP=TRUE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
ATTENDEE;PARTSTAT=ACCEPTED;SCHEDULE-STATUS=2.0:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

    function testReplyNewExceptionFirstOccurence() {

        // This is a reply to 1 instance of a recurring event. This should
        // automatically create an exception.
        $itip = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
METHOD:REPLY
BEGIN:VEVENT
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
SEQUENCE:2
RECURRENCE-ID:20140724T000000Z
UID:foobar
END:VEVENT
END:VCALENDAR
ICS;

        $old = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
END:VCALENDAR
ICS;

        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
RRULE:FREQ=DAILY
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ATTENDEE:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
END:VEVENT
BEGIN:VEVENT
SEQUENCE:2
UID:foobar
DTSTART:20140724T000000Z
DTEND:20140724T010000Z
ATTENDEE;PARTSTAT=ACCEPTED:mailto:foo@example.org
ORGANIZER:mailto:bar@example.org
RECURRENCE-ID:20140724T000000Z
END:VEVENT
END:VCALENDAR
ICS;

        $result = $this->process($itip, $old, $expected);

    }

}
