<?php

namespace Sabre\VObject\Recur\EventIterator;

use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;

class MainTest extends \PHPUnit_Framework_TestCase {

    function testValues() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');
        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY;BYHOUR=10;BYMINUTE=5;BYSECOND=16;BYWEEKNO=32;BYYEARDAY=100,200';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07'));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $this->assertTrue($it->isInfinite());

    }

    /**
     * @expectedException \Sabre\VObject\InvalidDataException
     * @depends testValues
     */
    function testInvalidFreq() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');
        $ev->RRULE = 'FREQ=SMONTHLY;INTERVAL=3;UNTIL=20111025T000000Z';
        $ev->UID = 'foo';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);
        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testVCalendarNoUID() {

        $vcal = new VCalendar();
        $it = new EventIterator($vcal);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testVCalendarInvalidUID() {

        $vcal = new VCalendar();
        $it = new EventIterator($vcal, 'foo');

    }

    /**
     * @depends testValues
     */
    function testHourly() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=HOURLY;INTERVAL=3;UNTIL=20111025T000000Z';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07 12:00:00', new DateTimeZone('UTC')));

        $ev->add($dtStart);
        $vcal->add($ev);

        $it = new EventIterator($vcal, $ev->UID);

        // Max is to prevent overflow
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07 12:00:00', $tz),
                new DateTimeImmutable('2011-10-07 15:00:00', $tz),
                new DateTimeImmutable('2011-10-07 18:00:00', $tz),
                new DateTimeImmutable('2011-10-07 21:00:00', $tz),
                new DateTimeImmutable('2011-10-08 00:00:00', $tz),
                new DateTimeImmutable('2011-10-08 03:00:00', $tz),
                new DateTimeImmutable('2011-10-08 06:00:00', $tz),
                new DateTimeImmutable('2011-10-08 09:00:00', $tz),
                new DateTimeImmutable('2011-10-08 12:00:00', $tz),
                new DateTimeImmutable('2011-10-08 15:00:00', $tz),
                new DateTimeImmutable('2011-10-08 18:00:00', $tz),
                new DateTimeImmutable('2011-10-08 21:00:00', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testDaily() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY;INTERVAL=3;UNTIL=20111025T000000Z';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, $ev->UID);

        // Max is to prevent overflow
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2011-10-10', $tz),
                new DateTimeImmutable('2011-10-13', $tz),
                new DateTimeImmutable('2011-10-16', $tz),
                new DateTimeImmutable('2011-10-19', $tz),
                new DateTimeImmutable('2011-10-22', $tz),
                new DateTimeImmutable('2011-10-25', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testNoRRULE() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, $ev->UID);

        // Max is to prevent overflow
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testDailyByDayByHour() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY;BYDAY=SA,SU;BYHOUR=6,7';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-08 06:00:00', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-08 06:00:00', $tz),
                new DateTimeImmutable('2011-10-08 07:00:00', $tz),
                new DateTimeImmutable('2011-10-09 06:00:00', $tz),
                new DateTimeImmutable('2011-10-09 07:00:00', $tz),
                new DateTimeImmutable('2011-10-15 06:00:00', $tz),
                new DateTimeImmutable('2011-10-15 07:00:00', $tz),
                new DateTimeImmutable('2011-10-16 06:00:00', $tz),
                new DateTimeImmutable('2011-10-16 07:00:00', $tz),
                new DateTimeImmutable('2011-10-22 06:00:00', $tz),
                new DateTimeImmutable('2011-10-22 07:00:00', $tz),
                new DateTimeImmutable('2011-10-23 06:00:00', $tz),
                new DateTimeImmutable('2011-10-23 07:00:00', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testDailyByHour() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY;INTERVAL=2;BYHOUR=10,11,12,13,14,15';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2012-10-11 12:00:00', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2012-10-11 12:00:00', $tz),
                new DateTimeImmutable('2012-10-11 13:00:00', $tz),
                new DateTimeImmutable('2012-10-11 14:00:00', $tz),
                new DateTimeImmutable('2012-10-11 15:00:00', $tz),
                new DateTimeImmutable('2012-10-13 10:00:00', $tz),
                new DateTimeImmutable('2012-10-13 11:00:00', $tz),
                new DateTimeImmutable('2012-10-13 12:00:00', $tz),
                new DateTimeImmutable('2012-10-13 13:00:00', $tz),
                new DateTimeImmutable('2012-10-13 14:00:00', $tz),
                new DateTimeImmutable('2012-10-13 15:00:00', $tz),
                new DateTimeImmutable('2012-10-15 10:00:00', $tz),
                new DateTimeImmutable('2012-10-15 11:00:00', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testDailyByDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY;INTERVAL=2;BYDAY=TU,WE,FR';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2011-10-11', $tz),
                new DateTimeImmutable('2011-10-19', $tz),
                new DateTimeImmutable('2011-10-21', $tz),
                new DateTimeImmutable('2011-10-25', $tz),
                new DateTimeImmutable('2011-11-02', $tz),
                new DateTimeImmutable('2011-11-04', $tz),
                new DateTimeImmutable('2011-11-08', $tz),
                new DateTimeImmutable('2011-11-16', $tz),
                new DateTimeImmutable('2011-11-18', $tz),
                new DateTimeImmutable('2011-11-22', $tz),
                new DateTimeImmutable('2011-11-30', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testWeekly() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;COUNT=10';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Max is to prevent overflow
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2011-10-21', $tz),
                new DateTimeImmutable('2011-11-04', $tz),
                new DateTimeImmutable('2011-11-18', $tz),
                new DateTimeImmutable('2011-12-02', $tz),
                new DateTimeImmutable('2011-12-16', $tz),
                new DateTimeImmutable('2011-12-30', $tz),
                new DateTimeImmutable('2012-01-13', $tz),
                new DateTimeImmutable('2012-01-27', $tz),
                new DateTimeImmutable('2012-02-10', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testWeeklyByDayByHour() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,WE,FR;WKST=MO;BYHOUR=8,9,10';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07 08:00:00', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 15;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07 08:00:00', $tz),
                new DateTimeImmutable('2011-10-07 09:00:00', $tz),
                new DateTimeImmutable('2011-10-07 10:00:00', $tz),
                new DateTimeImmutable('2011-10-18 08:00:00', $tz),
                new DateTimeImmutable('2011-10-18 09:00:00', $tz),
                new DateTimeImmutable('2011-10-18 10:00:00', $tz),
                new DateTimeImmutable('2011-10-19 08:00:00', $tz),
                new DateTimeImmutable('2011-10-19 09:00:00', $tz),
                new DateTimeImmutable('2011-10-19 10:00:00', $tz),
                new DateTimeImmutable('2011-10-21 08:00:00', $tz),
                new DateTimeImmutable('2011-10-21 09:00:00', $tz),
                new DateTimeImmutable('2011-10-21 10:00:00', $tz),
                new DateTimeImmutable('2011-11-01 08:00:00', $tz),
                new DateTimeImmutable('2011-11-01 09:00:00', $tz),
                new DateTimeImmutable('2011-11-01 10:00:00', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testWeeklyByDaySpecificHour() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,WE,FR;WKST=SU';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07 18:00:00', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07 18:00:00', $tz),
                new DateTimeImmutable('2011-10-18 18:00:00', $tz),
                new DateTimeImmutable('2011-10-19 18:00:00', $tz),
                new DateTimeImmutable('2011-10-21 18:00:00', $tz),
                new DateTimeImmutable('2011-11-01 18:00:00', $tz),
                new DateTimeImmutable('2011-11-02 18:00:00', $tz),
                new DateTimeImmutable('2011-11-04 18:00:00', $tz),
                new DateTimeImmutable('2011-11-15 18:00:00', $tz),
                new DateTimeImmutable('2011-11-16 18:00:00', $tz),
                new DateTimeImmutable('2011-11-18 18:00:00', $tz),
                new DateTimeImmutable('2011-11-29 18:00:00', $tz),
                new DateTimeImmutable('2011-11-30 18:00:00', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testWeeklyByDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,WE,FR;WKST=SU';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // Grabbing the next 12 items
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2011-10-18', $tz),
                new DateTimeImmutable('2011-10-19', $tz),
                new DateTimeImmutable('2011-10-21', $tz),
                new DateTimeImmutable('2011-11-01', $tz),
                new DateTimeImmutable('2011-11-02', $tz),
                new DateTimeImmutable('2011-11-04', $tz),
                new DateTimeImmutable('2011-11-15', $tz),
                new DateTimeImmutable('2011-11-16', $tz),
                new DateTimeImmutable('2011-11-18', $tz),
                new DateTimeImmutable('2011-11-29', $tz),
                new DateTimeImmutable('2011-11-30', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testMonthly() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=3;COUNT=5';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-12-05', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 14;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-12-05', $tz),
                new DateTimeImmutable('2012-03-05', $tz),
                new DateTimeImmutable('2012-06-05', $tz),
                new DateTimeImmutable('2012-09-05', $tz),
                new DateTimeImmutable('2012-12-05', $tz),
            ],
            $result
        );


    }

    /**
     * @depends testValues
     */
    function testMonthlyEndOfMonth() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=2;COUNT=12';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-12-31', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 14;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-12-31', $tz),
                new DateTimeImmutable('2012-08-31', $tz),
                new DateTimeImmutable('2012-10-31', $tz),
                new DateTimeImmutable('2012-12-31', $tz),
                new DateTimeImmutable('2013-08-31', $tz),
                new DateTimeImmutable('2013-10-31', $tz),
                new DateTimeImmutable('2013-12-31', $tz),
                new DateTimeImmutable('2014-08-31', $tz),
                new DateTimeImmutable('2014-10-31', $tz),
                new DateTimeImmutable('2014-12-31', $tz),
                new DateTimeImmutable('2015-08-31', $tz),
                new DateTimeImmutable('2015-10-31', $tz),
            ],
            $result
        );


    }

    /**
     * @depends testValues
     */
    function testMonthlyByMonthDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=5;COUNT=9;BYMONTHDAY=1,31,-7';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-01-01', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 14;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-01-01', $tz),
                new DateTimeImmutable('2011-01-25', $tz),
                new DateTimeImmutable('2011-01-31', $tz),
                new DateTimeImmutable('2011-06-01', $tz),
                new DateTimeImmutable('2011-06-24', $tz),
                new DateTimeImmutable('2011-11-01', $tz),
                new DateTimeImmutable('2011-11-24', $tz),
                new DateTimeImmutable('2012-04-01', $tz),
                new DateTimeImmutable('2012-04-24', $tz),
            ],
            $result
        );

    }

    /**
     * A pretty slow test. Had to be marked as 'medium' for phpunit to not die
     * after 1 second. Would be good to optimize later.
     *
     * @depends testValues
     * @medium
     */
    function testMonthlyByDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;INTERVAL=2;COUNT=16;BYDAY=MO,-2TU,+1WE,3TH';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-01-03', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-01-03', $tz),
                new DateTimeImmutable('2011-01-05', $tz),
                new DateTimeImmutable('2011-01-10', $tz),
                new DateTimeImmutable('2011-01-17', $tz),
                new DateTimeImmutable('2011-01-18', $tz),
                new DateTimeImmutable('2011-01-20', $tz),
                new DateTimeImmutable('2011-01-24', $tz),
                new DateTimeImmutable('2011-01-31', $tz),
                new DateTimeImmutable('2011-03-02', $tz),
                new DateTimeImmutable('2011-03-07', $tz),
                new DateTimeImmutable('2011-03-14', $tz),
                new DateTimeImmutable('2011-03-17', $tz),
                new DateTimeImmutable('2011-03-21', $tz),
                new DateTimeImmutable('2011-03-22', $tz),
                new DateTimeImmutable('2011-03-28', $tz),
                new DateTimeImmutable('2011-05-02', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testMonthlyByDayByMonthDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;COUNT=10;BYDAY=MO;BYMONTHDAY=1';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-08-01', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-08-01', $tz),
                new DateTimeImmutable('2012-10-01', $tz),
                new DateTimeImmutable('2013-04-01', $tz),
                new DateTimeImmutable('2013-07-01', $tz),
                new DateTimeImmutable('2014-09-01', $tz),
                new DateTimeImmutable('2014-12-01', $tz),
                new DateTimeImmutable('2015-06-01', $tz),
                new DateTimeImmutable('2016-02-01', $tz),
                new DateTimeImmutable('2016-08-01', $tz),
                new DateTimeImmutable('2017-05-01', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testMonthlyByDayBySetPos() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=MONTHLY;COUNT=10;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-01-03', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-01-03', $tz),
                new DateTimeImmutable('2011-01-31', $tz),
                new DateTimeImmutable('2011-02-01', $tz),
                new DateTimeImmutable('2011-02-28', $tz),
                new DateTimeImmutable('2011-03-01', $tz),
                new DateTimeImmutable('2011-03-31', $tz),
                new DateTimeImmutable('2011-04-01', $tz),
                new DateTimeImmutable('2011-04-29', $tz),
                new DateTimeImmutable('2011-05-02', $tz),
                new DateTimeImmutable('2011-05-31', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testYearly() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=10;INTERVAL=3';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-01-01', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-01-01', $tz),
                new DateTimeImmutable('2014-01-01', $tz),
                new DateTimeImmutable('2017-01-01', $tz),
                new DateTimeImmutable('2020-01-01', $tz),
                new DateTimeImmutable('2023-01-01', $tz),
                new DateTimeImmutable('2026-01-01', $tz),
                new DateTimeImmutable('2029-01-01', $tz),
                new DateTimeImmutable('2032-01-01', $tz),
                new DateTimeImmutable('2035-01-01', $tz),
                new DateTimeImmutable('2038-01-01', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testYearlyLeapYear() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=3';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2012-02-29', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2012-02-29', $tz),
                new DateTimeImmutable('2016-02-29', $tz),
                new DateTimeImmutable('2020-02-29', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testYearlyByMonth() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=8;INTERVAL=4;BYMONTH=4,10';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-04-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-04-07', $tz),
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2015-04-07', $tz),
                new DateTimeImmutable('2015-10-07', $tz),
                new DateTimeImmutable('2019-04-07', $tz),
                new DateTimeImmutable('2019-10-07', $tz),
                new DateTimeImmutable('2023-04-07', $tz),
                new DateTimeImmutable('2023-10-07', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testYearlyByMonthByDay() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=8;INTERVAL=5;BYMONTH=4,10;BYDAY=1MO,-1SU';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-04-04', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-04-04', $tz),
                new DateTimeImmutable('2011-04-24', $tz),
                new DateTimeImmutable('2011-10-03', $tz),
                new DateTimeImmutable('2011-10-30', $tz),
                new DateTimeImmutable('2016-04-04', $tz),
                new DateTimeImmutable('2016-04-24', $tz),
                new DateTimeImmutable('2016-10-03', $tz),
                new DateTimeImmutable('2016-10-30', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testFastForward() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=8;INTERVAL=5;BYMONTH=4,10;BYDAY=1MO,-1SU';
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-04-04', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        // The idea is that we're fast-forwarding too far in the future, so
        // there will be no results left.
        $it->fastForward(new DateTimeImmutable('2020-05-05', new DateTimeZone('UTC')));

        $max = 20;
        $result = [];
        while ($item = $it->current()) {

            $result[] = $item;
            $max--;

            if (!$max) break;
            $it->next();

        }

        $this->assertEquals([], $result);

    }

    /**
     * @depends testValues
     */
    function testFastForwardAllDayEventThatStopAtTheStartTime() {
        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=DAILY';

        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-04-04', new DateTimeZone('UTC')));
        $ev->add($dtStart);

        $dtEnd = $vcal->createProperty('DTSTART');
        $dtEnd->setDateTime(new DateTimeImmutable('2011-04-05', new DateTimeZone('UTC')));
        $ev->add($dtEnd);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $it->fastForward(new DateTimeImmutable('2011-04-05T000000', new DateTimeZone('UTC')));

        $this->assertEquals(new DateTimeImmutable('2011-04-06'), $it->getDTStart());
    }

    /**
     * @depends testValues
     */
    function testComplexExclusions() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RRULE = 'FREQ=YEARLY;COUNT=10';
        $dtStart = $vcal->createProperty('DTSTART');

        $tz = new DateTimeZone('Canada/Eastern');
        $dtStart->setDateTime(new DateTimeImmutable('2011-01-01 13:50:20', $tz));

        $exDate1 = $vcal->createProperty('EXDATE');
        $exDate1->setDateTimes([new DateTimeImmutable('2012-01-01 13:50:20', $tz), new DateTimeImmutable('2014-01-01 13:50:20', $tz)]);
        $exDate2 = $vcal->createProperty('EXDATE');
        $exDate2->setDateTimes([new DateTimeImmutable('2016-01-01 13:50:20', $tz)]);

        $ev->add($dtStart);
        $ev->add($exDate1);
        $ev->add($exDate2);

        $vcal->add($ev);

        $it = new EventIterator($vcal, (string)$ev->UID);

        $max = 20;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-01-01 13:50:20', $tz),
                new DateTimeImmutable('2013-01-01 13:50:20', $tz),
                new DateTimeImmutable('2015-01-01 13:50:20', $tz),
                new DateTimeImmutable('2017-01-01 13:50:20', $tz),
                new DateTimeImmutable('2018-01-01 13:50:20', $tz),
                new DateTimeImmutable('2019-01-01 13:50:20', $tz),
                new DateTimeImmutable('2020-01-01 13:50:20', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     */
    function testOverridenEvent() {

        $vcal = new VCalendar();

        $ev1 = $vcal->createComponent('VEVENT');
        $ev1->UID = 'overridden';
        $ev1->RRULE = 'FREQ=DAILY;COUNT=10';
        $ev1->DTSTART = '20120107T120000Z';
        $ev1->SUMMARY = 'baseEvent';

        $vcal->add($ev1);

        // ev2 overrides an event, and puts it on 2pm instead.
        $ev2 = $vcal->createComponent('VEVENT');
        $ev2->UID = 'overridden';
        $ev2->{'RECURRENCE-ID'} = '20120110T120000Z';
        $ev2->DTSTART = '20120110T140000Z';
        $ev2->SUMMARY = 'Event 2';

        $vcal->add($ev2);

        // ev3 overrides an event, and puts it 2 days and 2 hours later
        $ev3 = $vcal->createComponent('VEVENT');
        $ev3->UID = 'overridden';
        $ev3->{'RECURRENCE-ID'} = '20120113T120000Z';
        $ev3->DTSTART = '20120115T140000Z';
        $ev3->SUMMARY = 'Event 3';

        $vcal->add($ev3);

        $it = new EventIterator($vcal, 'overridden');

        $dates = [];
        $summaries = [];
        while ($it->valid()) {

            $dates[] = $it->getDTStart();
            $summaries[] = (string)$it->getEventObject()->SUMMARY;
            $it->next();

        }

        $tz = new DateTimeZone('UTC');
        $this->assertEquals([
            new DateTimeImmutable('2012-01-07 12:00:00', $tz),
            new DateTimeImmutable('2012-01-08 12:00:00', $tz),
            new DateTimeImmutable('2012-01-09 12:00:00', $tz),
            new DateTimeImmutable('2012-01-10 14:00:00', $tz),
            new DateTimeImmutable('2012-01-11 12:00:00', $tz),
            new DateTimeImmutable('2012-01-12 12:00:00', $tz),
            new DateTimeImmutable('2012-01-14 12:00:00', $tz),
            new DateTimeImmutable('2012-01-15 12:00:00', $tz),
            new DateTimeImmutable('2012-01-15 14:00:00', $tz),
            new DateTimeImmutable('2012-01-16 12:00:00', $tz),
        ], $dates);

        $this->assertEquals([
            'baseEvent',
            'baseEvent',
            'baseEvent',
            'Event 2',
            'baseEvent',
            'baseEvent',
            'baseEvent',
            'baseEvent',
            'Event 3',
            'baseEvent',
        ], $summaries);

    }

    /**
     * @depends testValues
     */
    function testOverridenEvent2() {

        $vcal = new VCalendar();

        $ev1 = $vcal->createComponent('VEVENT');
        $ev1->UID = 'overridden';
        $ev1->RRULE = 'FREQ=WEEKLY;COUNT=3';
        $ev1->DTSTART = '20120112T120000Z';
        $ev1->SUMMARY = 'baseEvent';

        $vcal->add($ev1);

        // ev2 overrides an event, and puts it 6 days earlier instead.
        $ev2 = $vcal->createComponent('VEVENT');
        $ev2->UID = 'overridden';
        $ev2->{'RECURRENCE-ID'} = '20120119T120000Z';
        $ev2->DTSTART = '20120113T120000Z';
        $ev2->SUMMARY = 'Override!';

        $vcal->add($ev2);

        $it = new EventIterator($vcal, 'overridden');

        $dates = [];
        $summaries = [];
        while ($it->valid()) {

            $dates[] = $it->getDTStart();
            $summaries[] = (string)$it->getEventObject()->SUMMARY;
            $it->next();

        }

        $tz = new DateTimeZone('UTC');
        $this->assertEquals([
            new DateTimeImmutable('2012-01-12 12:00:00', $tz),
            new DateTimeImmutable('2012-01-13 12:00:00', $tz),
            new DateTimeImmutable('2012-01-26 12:00:00', $tz),

        ], $dates);

        $this->assertEquals([
            'baseEvent',
            'Override!',
            'baseEvent',
        ], $summaries);

    }

    /**
     * @depends testValues
     */
    function testOverridenEventNoValuesExpected() {

        $vcal = new VCalendar();
        $ev1 = $vcal->createComponent('VEVENT');

        $ev1->UID = 'overridden';
        $ev1->RRULE = 'FREQ=WEEKLY;COUNT=3';
        $ev1->DTSTART = '20120124T120000Z';
        $ev1->SUMMARY = 'baseEvent';

        $vcal->add($ev1);

        // ev2 overrides an event, and puts it 6 days earlier instead.
        $ev2 = $vcal->createComponent('VEVENT');
        $ev2->UID = 'overridden';
        $ev2->{'RECURRENCE-ID'} = '20120131T120000Z';
        $ev2->DTSTART = '20120125T120000Z';
        $ev2->SUMMARY = 'Override!';

        $vcal->add($ev2);

        $it = new EventIterator($vcal, 'overridden');

        $dates = [];
        $summaries = [];

        // The reported problem was specifically related to the VCALENDAR
        // expansion. In this parcitular case, we had to forward to the 28th of
        // january.
        $it->fastForward(new DateTimeImmutable('2012-01-28 23:00:00'));

        // We stop the loop when it hits the 6th of februari. Normally this
        // iterator would hit 24, 25 (overriden from 31) and 7 feb but because
        // we 'filter' from the 28th till the 6th, we should get 0 results.
        while ($it->valid() && $it->getDTStart() < new DateTimeImmutable('2012-02-06 23:00:00')) {

            $dates[] = $it->getDTStart();
            $summaries[] = (string)$it->getEventObject()->SUMMARY;
            $it->next();

        }

        $this->assertEquals([], $dates);
        $this->assertEquals([], $summaries);

    }

    /**
     * @depends testValues
     */
    function testRDATE() {

        $vcal = new VCalendar();
        $ev = $vcal->createComponent('VEVENT');

        $ev->UID = 'bla';
        $ev->RDATE = [
            new DateTimeImmutable('2014-08-07', new DateTimeZone('UTC')),
            new DateTimeImmutable('2014-08-08', new DateTimeZone('UTC')),
        ];
        $dtStart = $vcal->createProperty('DTSTART');
        $dtStart->setDateTime(new DateTimeImmutable('2011-10-07', new DateTimeZone('UTC')));

        $ev->add($dtStart);

        $vcal->add($ev);

        $it = new EventIterator($vcal, $ev->UID);

        // Max is to prevent overflow
        $max = 12;
        $result = [];
        foreach ($it as $item) {

            $result[] = $item;
            $max--;

            if (!$max) break;

        }

        $tz = new DateTimeZone('UTC');

        $this->assertEquals(
            [
                new DateTimeImmutable('2011-10-07', $tz),
                new DateTimeImmutable('2014-08-07', $tz),
                new DateTimeImmutable('2014-08-08', $tz),
            ],
            $result
        );

    }

    /**
     * @depends testValues
     * @expectedException \InvalidArgumentException
     */
    function testNoMasterBadUID() {

        $vcal = new VCalendar();
        // ev2 overrides an event, and puts it on 2pm instead.
        $ev2 = $vcal->createComponent('VEVENT');
        $ev2->UID = 'overridden';
        $ev2->{'RECURRENCE-ID'} = '20120110T120000Z';
        $ev2->DTSTART = '20120110T140000Z';
        $ev2->SUMMARY = 'Event 2';

        $vcal->add($ev2);

        // ev3 overrides an event, and puts it 2 days and 2 hours later
        $ev3 = $vcal->createComponent('VEVENT');
        $ev3->UID = 'overridden';
        $ev3->{'RECURRENCE-ID'} = '20120113T120000Z';
        $ev3->DTSTART = '20120115T140000Z';
        $ev3->SUMMARY = 'Event 3';

        $vcal->add($ev3);

        $it = new EventIterator($vcal, 'broken');

    }
}
