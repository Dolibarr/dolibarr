<?php

namespace Sabre\CalDAV;

use Sabre\VObject;

class Issue172Test extends \PHPUnit_Framework_TestCase {

    // DateTimeZone() native name: America/Los_Angeles (GMT-8 in January)
    function testBuiltInTimezoneName() {
        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART;TZID=America/Los_Angeles:20120118T204500
DTEND;TZID=America/Los_Angeles:20120118T214500
END:VEVENT
END:VCALENDAR
HI;
        $validator = new CalendarQueryValidator();
        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => new \DateTime('2012-01-18 21:00:00 GMT-08:00'),
                        'end'   => new \DateTime('2012-01-18 21:00:00 GMT-08:00'),
                    ],
                ],
            ],
            'prop-filters' => [],
        ];
        $input = VObject\Reader::read($input);
        $this->assertTrue($validator->validate($input, $filters));
    }

    // Pacific Standard Time, translates to America/Los_Angeles (GMT-8 in January)
    function testOutlookTimezoneName() {
        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VTIMEZONE
TZID:Pacific Standard Time
BEGIN:STANDARD
DTSTART:16010101T030000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T020000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VEVENT
DTSTART;TZID=Pacific Standard Time:20120113T100000
DTEND;TZID=Pacific Standard Time:20120113T110000
END:VEVENT
END:VCALENDAR
HI;
        $validator = new CalendarQueryValidator();
        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => new \DateTime('2012-01-13 10:30:00 GMT-08:00'),
                        'end'   => new \DateTime('2012-01-13 10:30:00 GMT-08:00'),
                    ],
                ],
            ],
            'prop-filters' => [],
        ];
        $input = VObject\Reader::read($input);
        $this->assertTrue($validator->validate($input, $filters));
    }

    // X-LIC-LOCATION, translates to America/Los_Angeles (GMT-8 in January)
    function testLibICalLocationName() {
        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VTIMEZONE
TZID:My own timezone name
X-LIC-LOCATION:America/Los_Angeles
BEGIN:STANDARD
DTSTART:16010101T030000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T020000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VEVENT
DTSTART;TZID=My own timezone name:20120113T100000
DTEND;TZID=My own timezone name:20120113T110000
END:VEVENT
END:VCALENDAR
HI;
        $validator = new CalendarQueryValidator();
        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => new \DateTime('2012-01-13 10:30:00 GMT-08:00'),
                        'end'   => new \DateTime('2012-01-13 10:30:00 GMT-08:00'),
                    ],
                ],
            ],
            'prop-filters' => [],
        ];
        $input = VObject\Reader::read($input);
        $this->assertTrue($validator->validate($input, $filters));
    }
}
