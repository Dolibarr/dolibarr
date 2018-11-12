<?php

namespace Sabre\CalDAV;

use Sabre\HTTP;

/**
 * This unittest is created to check for an endless loop in CalendarQueryValidator
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Issue220Test extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;

    protected $caldavCalendars = [
        [
            'id'           => 1,
            'name'         => 'Calendar',
            'principaluri' => 'principals/user1',
            'uri'          => 'calendar1',
        ]
    ];

    protected $caldavCalendarObjects = [
        1 => [
            'event.ics' => [
                'calendardata' => 'BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART;TZID=Europe/Berlin:20120601T180000
SUMMARY:Brot backen
RRULE:FREQ=DAILY;INTERVAL=1;WKST=MO
TRANSP:OPAQUE
DURATION:PT20M
LAST-MODIFIED:20120601T064634Z
CREATED:20120601T064634Z
DTSTAMP:20120601T064634Z
UID:b64f14c5-dccc-4eda-947f-bdb1f763fbcd
BEGIN:VALARM
TRIGGER;VALUE=DURATION:-PT5M
ACTION:DISPLAY
DESCRIPTION:Default Event Notification
X-WR-ALARMUID:cd952c1b-b3d6-41fb-b0a6-ec3a1a5bdd58
END:VALARM
END:VEVENT
BEGIN:VEVENT
DTSTART;TZID=Europe/Berlin:20120606T180000
SUMMARY:Brot backen
TRANSP:OPAQUE
STATUS:CANCELLED
DTEND;TZID=Europe/Berlin:20120606T182000
LAST-MODIFIED:20120605T094310Z
SEQUENCE:1
RECURRENCE-ID:20120606T160000Z
UID:b64f14c5-dccc-4eda-947f-bdb1f763fbcd
END:VEVENT
END:VCALENDAR
',
            ],
        ],
    ];

    function testIssue220() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI'       => '/calendars/user1/calendar1',
            'HTTP_DEPTH'        => '1',
        ]);

        $request->setBody('<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
    <D:prop>
        <C:calendar-data/>
        <D:getetag/>
    </D:prop>
    <C:filter>
        <C:comp-filter name="VCALENDAR">
            <C:comp-filter name="VEVENT">
                <C:comp-filter name="VALARM">
                    <C:time-range start="20120607T161646Z" end="20120612T161646Z"/>
                </C:comp-filter>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>');

        $response = $this->request($request);

        $this->assertFalse(strpos($response->body, '<s:exception>PHPUnit_Framework_Error_Warning</s:exception>'), 'Error Warning occurred: ' . $response->body);
        $this->assertFalse(strpos($response->body, 'Invalid argument supplied for foreach()'), 'Invalid argument supplied for foreach(): ' . $response->body);

        $this->assertEquals(207, $response->status);
    }
}
