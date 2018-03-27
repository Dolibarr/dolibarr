<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\HTTP;

class OutboxPostTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';
    protected $setupCalDAVScheduling = true;

    function testPostPassThruNotFound() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/notfound',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);

        $this->assertHTTPStatus(501, $req);

    }

    function testPostPassThruNotTextCalendar() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/user1/outbox',
        ]);

        $this->assertHTTPStatus(501, $req);

    }

    function testPostPassThruNoOutBox() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/calendars',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);

        $this->assertHTTPStatus(501, $req);

    }

    function testInvalidIcalBody() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);
        $req->setBody('foo');

        $this->assertHTTPStatus(400, $req);

    }

    function testNoVEVENT() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);

        $body = [
            'BEGIN:VCALENDAR',
            'BEGIN:VTIMEZONE',
            'END:VTIMEZONE',
            'END:VCALENDAR',
        ];

        $req->setBody(implode("\r\n", $body));

        $this->assertHTTPStatus(400, $req);

    }

    function testNoMETHOD() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);

        $body = [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $req->setBody(implode("\r\n", $body));

        $this->assertHTTPStatus(400, $req);

    }

    function testUnsupportedMethod() {

        $req = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'POST',
            'REQUEST_URI'       => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR'   => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'    => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ]);

        $body = [
            'BEGIN:VCALENDAR',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $req->setBody(implode("\r\n", $body));

        $this->assertHTTPStatus(501, $req);

    }

}
