<?php

namespace Sabre\CalDAV;

class TestUtil {

    static function getBackend() {

        $backend = new Backend\Mock();
        $calendarId = $backend->createCalendar(
            'principals/user1',
            'UUID-123467',
            [
                '{DAV:}displayname'                                   => 'user1 calendar',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order'           => '1',
                '{http://apple.com/ns/ical/}calendar-color'           => '#FF0000',
            ]
        );
        $backend->createCalendar(
            'principals/user1',
            'UUID-123468',
            [
                '{DAV:}displayname'                                   => 'user1 calendar2',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Calendar description',
                '{http://apple.com/ns/ical/}calendar-order'           => '1',
                '{http://apple.com/ns/ical/}calendar-color'           => '#FF0000',
            ]
        );
        $backend->createCalendarObject($calendarId, 'UUID-2345', self::getTestCalendarData());
        return $backend;

    }

    static function getTestCalendarData($type = 1) {

        $calendarData = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.1//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Asia/Seoul
BEGIN:DAYLIGHT
TZOFFSETFROM:+0900
RRULE:FREQ=YEARLY;UNTIL=19880507T150000Z;BYMONTH=5;BYDAY=2SU
DTSTART:19870510T000000
TZNAME:GMT+09:00
TZOFFSETTO:+1000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1000
DTSTART:19881009T000000
TZNAME:GMT+09:00
TZOFFSETTO:+0900
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20100225T154229Z
UID:39A6B5ED-DD51-4AFE-A683-C35EE3749627
TRANSP:TRANSPARENT
SUMMARY:Something here
DTSTAMP:20100228T130202Z';

        switch ($type) {
            case 1 :
                $calendarData .= "\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDTEND;TZID=Asia/Seoul:20100223T070000\n";
                break;
            case 2 :
                $calendarData .= "\nDTSTART:20100223T060000\nDTEND:20100223T070000\n";
                break;
            case 3 :
                $calendarData .= "\nDTSTART;VALUE=DATE:20100223\nDTEND;VALUE=DATE:20100223\n";
                break;
            case 4 :
                $calendarData .= "\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDURATION:PT1H\n";
                break;
            case 5 :
                $calendarData .= "\nDTSTART;TZID=Asia/Seoul:20100223T060000\nDURATION:-P5D\n";
                break;
            case 6 :
                $calendarData .= "\nDTSTART;VALUE=DATE:20100223\n";
                break;
            case 7 :
                $calendarData .= "\nDTSTART;VALUE=DATETIME:20100223T060000\n";
                break;

            // No DTSTART, so intentionally broken
            case 'X' :
                $calendarData .= "\n";
                break;
        }


        $calendarData .= 'ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:lisa@example.com
SEQUENCE:2
END:VEVENT
END:VCALENDAR';

        return $calendarData;

    }

    static function getTestTODO($type = 'due') {

        switch ($type) {

            case 'due' :
                $extra = "DUE:20100104T000000Z";
                break;
            case 'due2' :
                $extra = "DUE:20060104T000000Z";
                break;
            case 'due_date' :
                $extra = "DUE;VALUE=DATE:20060104";
                break;
            case 'due_tz' :
                $extra = "DUE;TZID=Asia/Seoul:20060104T000000Z";
                break;
            case 'due_dtstart' :
                $extra = "DTSTART:20050223T060000Z\nDUE:20060104T000000Z";
                break;
            case 'due_dtstart2' :
                $extra = "DTSTART:20090223T060000Z\nDUE:20100104T000000Z";
                break;
            case 'dtstart' :
                $extra = 'DTSTART:20100223T060000Z';
                break;
            case 'dtstart2' :
                $extra = 'DTSTART:20060223T060000Z';
                break;
            case 'dtstart_date' :
                $extra = 'DTSTART;VALUE=DATE:20100223';
                break;
            case 'dtstart_tz' :
                $extra = 'DTSTART;TZID=Asia/Seoul:20100223T060000Z';
                break;
            case 'dtstart_duration' :
                $extra = "DTSTART:20061023T060000Z\nDURATION:PT1H";
                break;
            case 'dtstart_duration2' :
                $extra = "DTSTART:20101023T060000Z\nDURATION:PT1H";
                break;
            case 'completed' :
                $extra = 'COMPLETED:20060601T000000Z';
                break;
            case 'completed2' :
                $extra = 'COMPLETED:20090601T000000Z';
                break;
            case 'created' :
                $extra = 'CREATED:20060601T000000Z';
                break;
            case 'created2' :
                $extra = 'CREATED:20090601T000000Z';
                break;
            case 'completedcreated' :
                $extra = "CREATED:20060601T000000Z\nCOMPLETED:20070101T000000Z";
                break;
            case 'completedcreated2' :
                $extra = "CREATED:20090601T000000Z\nCOMPLETED:20100101T000000Z";
                break;
            case 'notime' :
                $extra = 'X-FILLER:oh hello';
                break;
            default :
                throw new Exception('Unknown type: ' . $type);

        }

        $todo = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Example Corp.//CalDAV Client//EN
BEGIN:VTODO
DTSTAMP:20060205T235335Z
' . $extra . '
STATUS:NEEDS-ACTION
SUMMARY:Task #1
UID:DDDEEB7915FA61233B861457@example.com
BEGIN:VALARM
ACTION:AUDIO
TRIGGER;RELATED=START:-PT10M
END:VALARM
END:VTODO
END:VCALENDAR';

        return $todo;

    }

}
