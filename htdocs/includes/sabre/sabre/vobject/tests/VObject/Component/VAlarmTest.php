<?php

namespace Sabre\VObject\Component;

use DateTime;
use Sabre\VObject\Reader;

class VAlarmTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    function testInTimeRange(VAlarm $valarm, $start, $end, $outcome) {

        $this->assertEquals($outcome, $valarm->isInTimeRange($start, $end));

    }

    function timeRangeTestData() {

        $tests = [];

        $calendar = new VCalendar();

        // Hard date and time
        $valarm1 = $calendar->createComponent('VALARM');
        $valarm1->add(
            $calendar->createProperty('TRIGGER', '20120312T130000Z', ['VALUE' => 'DATE-TIME'])
        );

        $tests[] = [$valarm1, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-04-01 01:00:00'), true];
        $tests[] = [$valarm1, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-03-10 01:00:00'), false];

        // Relation to start time of event
        $valarm2 = $calendar->createComponent('VALARM');
        $valarm2->add(
            $calendar->createProperty('TRIGGER', '-P1D', ['VALUE' => 'DURATION'])
        );

        $vevent2 = $calendar->createComponent('VEVENT');
        $vevent2->DTSTART = '20120313T130000Z';
        $vevent2->add($valarm2);

        $tests[] = [$valarm2, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-04-01 01:00:00'), true];
        $tests[] = [$valarm2, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-03-10 01:00:00'), false];

        // Relation to end time of event
        $valarm3 = $calendar->createComponent('VALARM');
        $valarm3->add($calendar->createProperty('TRIGGER', '-P1D', ['VALUE' => 'DURATION', 'RELATED' => 'END']));

        $vevent3 = $calendar->createComponent('VEVENT');
        $vevent3->DTSTART = '20120301T130000Z';
        $vevent3->DTEND = '20120401T130000Z';
        $vevent3->add($valarm3);

        $tests[] = [$valarm3, new DateTime('2012-02-25 01:00:00'), new DateTime('2012-03-05 01:00:00'), false];
        $tests[] = [$valarm3, new DateTime('2012-03-25 01:00:00'), new DateTime('2012-04-05 01:00:00'), true];

        // Relation to end time of todo
        $valarm4 = $calendar->createComponent('VALARM');
        $valarm4->TRIGGER = '-P1D';
        $valarm4->TRIGGER['VALUE'] = 'DURATION';
        $valarm4->TRIGGER['RELATED'] = 'END';

        $vtodo4 = $calendar->createComponent('VTODO');
        $vtodo4->DTSTART = '20120301T130000Z';
        $vtodo4->DUE = '20120401T130000Z';
        $vtodo4->add($valarm4);

        $tests[] = [$valarm4, new DateTime('2012-02-25 01:00:00'), new DateTime('2012-03-05 01:00:00'), false];
        $tests[] = [$valarm4, new DateTime('2012-03-25 01:00:00'), new DateTime('2012-04-05 01:00:00'), true];

        // Relation to start time of event + repeat
        $valarm5 = $calendar->createComponent('VALARM');
        $valarm5->TRIGGER = '-P1D';
        $valarm5->TRIGGER['VALUE'] = 'DURATION';
        $valarm5->REPEAT = 10;
        $valarm5->DURATION = 'P1D';

        $vevent5 = $calendar->createComponent('VEVENT');
        $vevent5->DTSTART = '20120301T130000Z';
        $vevent5->add($valarm5);

        $tests[] = [$valarm5, new DateTime('2012-03-09 01:00:00'), new DateTime('2012-03-10 01:00:00'), true];

        // Relation to start time of event + duration, but no repeat
        $valarm6 = $calendar->createComponent('VALARM');
        $valarm6->TRIGGER = '-P1D';
        $valarm6->TRIGGER['VALUE'] = 'DURATION';
        $valarm6->DURATION = 'P1D';

        $vevent6 = $calendar->createComponent('VEVENT');
        $vevent6->DTSTART = '20120313T130000Z';
        $vevent6->add($valarm6);

        $tests[] = [$valarm6, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-04-01 01:00:00'), true];
        $tests[] = [$valarm6, new DateTime('2012-03-01 01:00:00'), new DateTime('2012-03-10 01:00:00'), false];


        // Relation to end time of event (DURATION instead of DTEND)
        $valarm7 = $calendar->createComponent('VALARM');
        $valarm7->TRIGGER = '-P1D';
        $valarm7->TRIGGER['VALUE'] = 'DURATION';
        $valarm7->TRIGGER['RELATED'] = 'END';

        $vevent7 = $calendar->createComponent('VEVENT');
        $vevent7->DTSTART = '20120301T130000Z';
        $vevent7->DURATION = 'P30D';
        $vevent7->add($valarm7);

        $tests[] = [$valarm7, new DateTime('2012-02-25 01:00:00'), new DateTime('2012-03-05 01:00:00'), false];
        $tests[] = [$valarm7, new DateTime('2012-03-25 01:00:00'), new DateTime('2012-04-05 01:00:00'), true];

        // Relation to end time of event (No DTEND or DURATION)
        $valarm7 = $calendar->createComponent('VALARM');
        $valarm7->TRIGGER = '-P1D';
        $valarm7->TRIGGER['VALUE'] = 'DURATION';
        $valarm7->TRIGGER['RELATED'] = 'END';

        $vevent7 = $calendar->createComponent('VEVENT');
        $vevent7->DTSTART = '20120301T130000Z';
        $vevent7->add($valarm7);

        $tests[] = [$valarm7, new DateTime('2012-02-25 01:00:00'), new DateTime('2012-03-05 01:00:00'), true];
        $tests[] = [$valarm7, new DateTime('2012-03-25 01:00:00'), new DateTime('2012-04-05 01:00:00'), false];


        return $tests;
    }

    /**
     * @expectedException \Sabre\VObject\InvalidDataException
     */
    function testInTimeRangeInvalidComponent() {

        $calendar = new VCalendar();
        $valarm = $calendar->createComponent('VALARM');
        $valarm->TRIGGER = '-P1D';
        $valarm->TRIGGER['RELATED'] = 'END';

        $vjournal = $calendar->createComponent('VJOURNAL');
        $vjournal->add($valarm);

        $valarm->isInTimeRange(new DateTime('2012-02-25 01:00:00'), new DateTime('2012-03-05 01:00:00'));

    }

    /**
     * This bug was found and reported on the mailing list.
     */
    function testInTimeRangeBuggy() {

$input = <<<BLA
BEGIN:VCALENDAR
BEGIN:VTODO
DTSTAMP:20121003T064931Z
UID:b848cb9a7bb16e464a06c222ca1f8102@examle.com
STATUS:NEEDS-ACTION
DUE:20121005T000000Z
SUMMARY:Task 1
CATEGORIES:AlarmCategory
BEGIN:VALARM
TRIGGER:-PT10M
ACTION:DISPLAY
DESCRIPTION:Task 1
END:VALARM
END:VTODO
END:VCALENDAR
BLA;

        $vobj = Reader::read($input);

        $this->assertTrue($vobj->VTODO->VALARM->isInTimeRange(new \DateTime('2012-10-01 00:00:00'), new \DateTime('2012-11-01 00:00:00')));

    }

}
