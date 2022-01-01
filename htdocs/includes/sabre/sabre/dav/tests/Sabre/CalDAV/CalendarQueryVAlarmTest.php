<?php

namespace Sabre\CalDAV;

use Sabre\VObject;

class CalendarQueryVAlarmTest extends \PHPUnit_Framework_TestCase {

    /**
     * This test is specifically for a time-range query on a VALARM, contained
     * in a VEVENT that's recurring
     */
    function testValarm() {

        $vcalendar = new VObject\Component\VCalendar();

        $vevent = $vcalendar->createComponent('VEVENT');
        $vevent->RRULE = 'FREQ=MONTHLY';
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = $vcalendar->createComponent('VALARM');
        $valarm->TRIGGER = '-P15D';
        $vevent->add($valarm);


        $vcalendar->add($vevent);

        $filter = [
            'name'           => 'VCALENDAR',
            'is-not-defined' => false,
            'time-range'     => null,
            'prop-filters'   => [],
            'comp-filters'   => [
                [
                    'name'           => 'VEVENT',
                    'is-not-defined' => false,
                    'time-range'     => null,
                    'prop-filters'   => [],
                    'comp-filters'   => [
                        [
                            'name'           => 'VALARM',
                            'is-not-defined' => false,
                            'prop-filters'   => [],
                            'comp-filters'   => [],
                            'time-range'     => [
                                'start' => new \DateTime('2012-05-10'),
                                'end'   => new \DateTime('2012-05-20'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $validator = new CalendarQueryValidator();
        $this->assertTrue($validator->validate($vcalendar, $filter));

        $vcalendar = new VObject\Component\VCalendar();

        // A limited recurrence rule, should return false
        $vevent = $vcalendar->createComponent('VEVENT');
        $vevent->RRULE = 'FREQ=MONTHLY;COUNT=1';
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = $vcalendar->createComponent('VALARM');
        $valarm->TRIGGER = '-P15D';
        $vevent->add($valarm);

        $vcalendar->add($vevent);

        $this->assertFalse($validator->validate($vcalendar, $filter));
    }

    function testAlarmWayBefore() {

        $vcalendar = new VObject\Component\VCalendar();

        $vevent = $vcalendar->createComponent('VEVENT');
        $vevent->DTSTART = '20120101T120000Z';
        $vevent->UID = 'bla';

        $valarm = $vcalendar->createComponent('VALARM');
        $valarm->TRIGGER = '-P2W1D';
        $vevent->add($valarm);

        $vcalendar->add($vevent);

        $filter = [
            'name'           => 'VCALENDAR',
            'is-not-defined' => false,
            'time-range'     => null,
            'prop-filters'   => [],
            'comp-filters'   => [
                [
                    'name'           => 'VEVENT',
                    'is-not-defined' => false,
                    'time-range'     => null,
                    'prop-filters'   => [],
                    'comp-filters'   => [
                        [
                            'name'           => 'VALARM',
                            'is-not-defined' => false,
                            'prop-filters'   => [],
                            'comp-filters'   => [],
                            'time-range'     => [
                                'start' => new \DateTime('2011-12-10'),
                                'end'   => new \DateTime('2011-12-20'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $validator = new CalendarQueryValidator();
        $this->assertTrue($validator->validate($vcalendar, $filter));

    }

}
