<?php

namespace Sabre\CalDAV;

use Sabre\HTTP;

/**
 * This unittest is created to check if the time-range filter is working correctly with all-day-events
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Issue228Test extends \Sabre\DAVServerTest {

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
UID:20120730T113415CEST-6804EGphkd@xxxxxx.de
DTSTAMP:20120730T093415Z
DTSTART;VALUE=DATE:20120729
DTEND;VALUE=DATE:20120730
SUMMARY:sunday event
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR
',
            ],
        ],
    ];

    function testIssue228() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD'    => 'REPORT',
            'HTTP_CONTENT_TYPE' => 'application/xml',
            'REQUEST_URI'       => '/calendars/user1/calendar1',
            'HTTP_DEPTH'        => '1',
        ]);

        $request->setBody('<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <C:calendar-data>
  <C:expand start="20120730T095609Z"
            end="20120813T095609Z"/>
</C:calendar-data>
    <D:getetag/>
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT">
        <C:time-range start="20120730T095609Z" end="20120813T095609Z"/>
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>');

        $response = $this->request($request);

        // We must check if absolutely nothing was returned from this query.
        $this->assertFalse(strpos($response->body, 'BEGIN:VCALENDAR'));

    }
}
