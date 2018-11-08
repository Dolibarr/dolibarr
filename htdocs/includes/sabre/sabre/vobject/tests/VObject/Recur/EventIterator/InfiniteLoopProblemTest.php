<?php

namespace Sabre\VObject\Recur\EventIterator;

use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur;

class InfiniteLoopProblemTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        $this->vcal = new VCalendar();

    }

    /**
     * This bug came from a Fruux customer. This would result in a never-ending
     * request.
     */
    function testFastForwardTooFar() {

        $ev = $this->vcal->createComponent('VEVENT');
        $ev->UID = 'foobar';
        $ev->DTSTART = '20090420T180000Z';
        $ev->RRULE = 'FREQ=WEEKLY;BYDAY=MO;UNTIL=20090704T205959Z;INTERVAL=1';

        $this->assertFalse($ev->isInTimeRange(new DateTimeImmutable('2012-01-01 12:00:00'), new DateTimeImmutable('3000-01-01 00:00:00')));

    }

    /**
     * Different bug, also likely an infinite loop.
     */
    function testYearlyByMonthLoop() {

        $ev = $this->vcal->createComponent('VEVENT');
        $ev->UID = 'uuid';
        $ev->DTSTART = '20120101T154500';
        $ev->DTSTART['TZID'] = 'Europe/Berlin';
        $ev->RRULE = 'FREQ=YEARLY;INTERVAL=1;UNTIL=20120203T225959Z;BYMONTH=2;BYSETPOS=1;BYDAY=SU,MO,TU,WE,TH,FR,SA';
        $ev->DTEND = '20120101T164500';
        $ev->DTEND['TZID'] = 'Europe/Berlin';

        // This recurrence rule by itself is a yearly rule that should happen
        // every february.
        //
        // The BYDAY part expands this to every day of the month, but the
        // BYSETPOS limits this to only the 1st day of the month. Very crazy
        // way to specify this, and could have certainly been a lot easier.
        $this->vcal->add($ev);

        $it = new Recur\EventIterator($this->vcal, 'uuid');
        $it->fastForward(new DateTimeImmutable('2012-01-29 23:00:00', new DateTimeZone('UTC')));

        $collect = [];

        while ($it->valid()) {
            $collect[] = $it->getDtStart();
            if ($it->getDtStart() > new DateTimeImmutable('2013-02-05 22:59:59', new DateTimeZone('UTC'))) {
                break;
            }
            $it->next();

        }

        $this->assertEquals(
            [new DateTimeImmutable('2012-02-01 15:45:00', new DateTimeZone('Europe/Berlin'))],
            $collect
        );

    }

    /**
     * Something, somewhere produced an ics with an interval set to 0. Because
     * this means we increase the current day (or week, month) by 0, this also
     * results in an infinite loop.
     *
     * @expectedException \Sabre\VObject\InvalidDataException
     * @return void
     */
    function testZeroInterval() {

        $ev = $this->vcal->createComponent('VEVENT');
        $ev->UID = 'uuid';
        $ev->DTSTART = '20120824T145700Z';
        $ev->RRULE = 'FREQ=YEARLY;INTERVAL=0';
        $this->vcal->add($ev);

        $it = new Recur\EventIterator($this->vcal, 'uuid');
        $it->fastForward(new DateTimeImmutable('2013-01-01 23:00:00', new DateTimeZone('UTC')));

        // if we got this far.. it means we are no longer infinitely looping

    }

}
