<?php

namespace Sabre\VObject\Component;

class VEventTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider timeRangeTestData
     */
    function testInTimeRange(VEvent $vevent, $start, $end, $outcome) {

        $this->assertEquals($outcome, $vevent->isInTimeRange($start, $end));

    }

    function timeRangeTestData() {

        $tests = [];

        $calendar = new VCalendar();

        $vevent = $calendar->createComponent('VEVENT');
        $vevent->DTSTART = '20111223T120000Z';
        $tests[] = [$vevent, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vevent2 = clone $vevent;
        $vevent2->DTEND = '20111225T120000Z';
        $tests[] = [$vevent2, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent2, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vevent3 = clone $vevent;
        $vevent3->DURATION = 'P1D';
        $tests[] = [$vevent3, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent3, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        $vevent4 = clone $vevent;
        $vevent4->DTSTART = '20111225';
        $vevent4->DTSTART['VALUE'] = 'DATE';
        $tests[] = [$vevent4, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent4, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];
        // Event with no end date should be treated as lasting the entire day.
        $tests[] = [$vevent4, new \DateTime('2011-12-25 16:00:00'), new \DateTime('2011-12-25 17:00:00'), true];
        // DTEND is non inclusive so all day events should not be returned on the next day.
        $tests[] = [$vevent4, new \DateTime('2011-12-26 00:00:00'), new \DateTime('2011-12-26 17:00:00'), false];
        // The timezone of timerange in question also needs to be considered.
        $tests[] = [$vevent4, new \DateTime('2011-12-26 00:00:00', new \DateTimeZone('Europe/Berlin')), new \DateTime('2011-12-26 17:00:00', new \DateTimeZone('Europe/Berlin')), false];

        $vevent5 = clone $vevent;
        $vevent5->DURATION = 'P1D';
        $vevent5->RRULE = 'FREQ=YEARLY';
        $tests[] = [$vevent5, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent5, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];
        $tests[] = [$vevent5, new \DateTime('2013-12-01'), new \DateTime('2013-12-31'), true];

        $vevent6 = clone $vevent;
        $vevent6->DTSTART = '20111225';
        $vevent6->DTSTART['VALUE'] = 'DATE';
        $vevent6->DTEND = '20111225';
        $vevent6->DTEND['VALUE'] = 'DATE';

        $tests[] = [$vevent6, new \DateTime('2011-01-01'), new \DateTime('2012-01-01'), true];
        $tests[] = [$vevent6, new \DateTime('2011-01-01'), new \DateTime('2011-11-01'), false];

        // Added this test to ensure that recurrence rules with no DTEND also
        // get checked for the entire day.
        $vevent7 = clone $vevent;
        $vevent7->DTSTART = '20120101';
        $vevent7->DTSTART['VALUE'] = 'DATE';
        $vevent7->RRULE = 'FREQ=MONTHLY';
        $tests[] = [$vevent7, new \DateTime('2012-02-01 15:00:00'), new \DateTime('2012-02-02'), true];
        // The timezone of timerange in question should also be considered.
        $tests[] = [$vevent7, new \DateTime('2012-02-02 00:00:00', new \DateTimeZone('Europe/Berlin')), new \DateTime('2012-02-03 00:00:00', new \DateTimeZone('Europe/Berlin')), false];

        // Added this test to check recurring events that have no instances.
        $vevent8 = clone $vevent;
        $vevent8->DTSTART = '20130329T140000';
        $vevent8->DTEND = '20130329T153000';
        $vevent8->RRULE = ['FREQ' => 'WEEKLY', 'BYDAY' => ['FR'], 'UNTIL' => '20130412T115959Z'];
        $vevent8->add('EXDATE', '20130405T140000');
        $vevent8->add('EXDATE', '20130329T140000');
        $tests[] = [$vevent8, new \DateTime('2013-03-01'), new \DateTime('2013-04-01'), false];

        // Added this test to check recurring all day event that repeat every day
        $vevent9 = clone $vevent;
        $vevent9->DTSTART = '20161027';
        $vevent9->DTEND = '20161028';
        $vevent9->RRULE = 'FREQ=DAILY';
        $tests[] = [$vevent9, new \DateTime('2016-10-31'), new \DateTime('2016-12-12'), true];

        return $tests;

    }

}
