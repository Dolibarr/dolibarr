<?php

namespace Sabre\VObject\Component;

use Sabre\VObject\Component;
use Sabre\VObject\Reader;

class VTodoTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    function testInTimeRange(VTodo $vtodo, $start, $end, $outcome) {

        $this->assertEquals($outcome, $vtodo->isInTimeRange($start, $end));

    }

    function timeRangeTestData() {

        $tests = [];

        $calendar = new VCalendar();

        $vtodo = $calendar->createComponent('VTODO');
        $vtodo->DTSTART = '20111223T120000Z';
        $tests[] = [$vtodo, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo2 = clone $vtodo;
        $vtodo2->DURATION = 'P1D';
        $tests[] = [$vtodo2, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo2, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo3 = clone $vtodo;
        $vtodo3->DUE = '20111225';
        $tests[] = [$vtodo3, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo3, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo4 = $calendar->createComponent('VTODO');
        $vtodo4->DUE = '20111225';
        $tests[] = [$vtodo4, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo4, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo5 = $calendar->createComponent('VTODO');
        $vtodo5->COMPLETED = '20111225';
        $tests[] = [$vtodo5, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo5, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo6 = $calendar->createComponent('VTODO');
        $vtodo6->CREATED = '20111225';
        $tests[] = [$vtodo6, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo6, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo7 = $calendar->createComponent('VTODO');
        $vtodo7->CREATED = '20111225';
        $vtodo7->COMPLETED = '20111226';
        $tests[] = [$vtodo7, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo7, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vtodo7 = $calendar->createComponent('VTODO');
        $tests[] = [$vtodo7, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vtodo7, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), true];

        return $tests;

    }

    function testValidate() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:1234-21355-123156
DTSTAMP:20140402T183400Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = [];
        foreach ($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals([], $messages);

    }

    function testValidateInvalid() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = [];
        foreach ($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals([
            "UID MUST appear exactly once in a VTODO component",
            "DTSTAMP MUST appear exactly once in a VTODO component",
        ], $messages);

    }

    function testValidateDUEDTSTARTMisMatch() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:FOO
DTSTART;VALUE=DATE-TIME:20140520T131600Z
DUE;VALUE=DATE:20140520
DTSTAMP;VALUE=DATE-TIME:20140520T131600Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = [];
        foreach ($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals([
            "The value type (DATE or DATE-TIME) must be identical for DUE and DTSTART",
        ], $messages);

    }

    function testValidateDUEbeforeDTSTART() {

        $input = <<<HI
BEGIN:VCALENDAR
VERSION:2.0
PRODID:YoYo
BEGIN:VTODO
UID:FOO
DTSTART;VALUE=DATE:20140520
DUE;VALUE=DATE:20140518
DTSTAMP;VALUE=DATE-TIME:20140520T131600Z
END:VTODO
END:VCALENDAR
HI;

        $obj = Reader::read($input);

        $warnings = $obj->validate();
        $messages = [];
        foreach ($warnings as $warning) {
            $messages[] = $warning['message'];
        }

        $this->assertEquals([
            "DUE must occur after DTSTART",
        ], $messages);

    }

}
