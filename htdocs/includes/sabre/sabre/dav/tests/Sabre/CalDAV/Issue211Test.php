<?php

namespace Sabre\CalDAV;

use Sabre\HTTP;

/**
 * This unittest is created to check for an endless loop in Sabre\CalDAV\CalendarQueryValidator
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Issue211Test extends \Sabre\DAVServerTest {

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
UID:20120418T172519CEST-3510gh1hVw
DTSTAMP:20120418T152519Z
DTSTART;VALUE=DATE:20120330
DTEND;VALUE=DATE:20120531
EXDATE;TZID=Europe/Berlin:20120330T000000
RRULE:FREQ=YEARLY;INTERVAL=1
SEQUENCE:1
SUMMARY:Birthday
TRANSP:TRANSPARENT
BEGIN:VALARM
ACTION:EMAIL
ATTENDEE:MAILTO:xxx@domain.de
DESCRIPTION:Dies ist eine Kalender Erinnerung
SUMMARY:Kalender Alarm Erinnerung
TRIGGER;VALUE=DATE-TIME:20120329T060000Z
END:VALARM
END:VEVENT
END:VCALENDAR
',
            ],
        ],
    ];

    function testIssue211() {

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
                    <C:time-range start="20120426T220000Z" end="20120427T215959Z"/>
                </C:comp-filter>
            </C:comp-filter>
        </C:comp-filter>
    </C:filter>
</C:calendar-query>');

        $response = $this->request($request);

        // if this assert is reached, the endless loop is gone
        // There should be no matching events
        $this->assertFalse(strpos('BEGIN:VEVENT', $response->body));

    }
}
