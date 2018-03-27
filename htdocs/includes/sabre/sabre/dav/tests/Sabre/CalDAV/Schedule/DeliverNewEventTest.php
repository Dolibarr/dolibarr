<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\HTTP\Request;
use Sabre\VObject;

class DeliverNewEventTest extends \Sabre\DAVServerTest {

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;
    public $setupACL = true;
    public $autoLogin = 'user1';

    function setUp() {

        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [

            ]
        );
        $this->caldavBackend->createCalendar(
            'principals/user2',
            'default',
            [

            ]
        );

    }

    function testDelivery() {

        $request = new Request('PUT', '/calendars/user1/default/foo.ics');
        $request->setBody(<<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.9.1//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
CREATED:20140109T204404Z
UID:AADC6438-18CF-4B52-8DD2-EF9AD75ADE83
DTEND;TZID=America/Toronto:20140107T110000
TRANSP:OPAQUE
ATTENDEE;CN="Administrator";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:mailto:user1.sabredav@sabredav.org
ATTENDEE;CN="Roxy Kesh";CUTYPE=INDIVIDUAL;EMAIL="user2.sabredav@sabrdav.org";
 PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICIPANT;RSVP=TRUE:mailto:user2.sabredav@sabredav.org
SUMMARY:Just testing!
DTSTART;TZID=America/Toronto:20140107T100000
DTSTAMP:20140109T204422Z
ORGANIZER;CN="Administrator":mailto:user1.sabredav@sabredav.org
SEQUENCE:4
END:VEVENT
END:VCALENDAR
ICS
    );

        $messages = [];
        $this->server->on('schedule', function($message) use (&$messages) {
            $messages[] = $message;
        });

        $response = $this->request($request);

        $this->assertEquals(201, $response->getStatus(), 'Incorrect status code received. Response body:' . $response->getBodyAsString());

        $result = $this->request(new Request('GET', '/calendars/user1/default/foo.ics'))->getBody();
        $resultVObj = VObject\Reader::read($result);

        $this->assertEquals(
            '1.2',
            $resultVObj->VEVENT->ATTENDEE[1]['SCHEDULE-STATUS']->getValue()
        );

        $this->assertEquals(1, count($messages));
        $message = $messages[0];

        $this->assertInstanceOf('\Sabre\VObject\ITip\Message', $message);
        $this->assertEquals('mailto:user2.sabredav@sabredav.org', $message->recipient);
        $this->assertEquals('Roxy Kesh', $message->recipientName);
        $this->assertEquals('mailto:user1.sabredav@sabredav.org', $message->sender);
        $this->assertEquals('Administrator', $message->senderName);
        $this->assertEquals('REQUEST', $message->method);

        $this->assertEquals('REQUEST', $message->message->METHOD->getValue());

    }

}
