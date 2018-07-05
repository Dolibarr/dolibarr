<?php

namespace Sabre\VObject\Recur;

use DateTimeImmutable;
use DateTimeZone;

class RDateIteratorTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $utc = new DateTimeZone('UTC');
        $it = new RDateIterator('20140901T000000Z,20141001T000000Z', new DateTimeImmutable('2014-08-01 00:00:00', $utc));

        $expected = [
            new DateTimeImmutable('2014-08-01 00:00:00', $utc),
            new DateTimeImmutable('2014-09-01 00:00:00', $utc),
            new DateTimeImmutable('2014-10-01 00:00:00', $utc),
        ];

        $this->assertEquals(
            $expected,
            iterator_to_array($it)
        );

        $this->assertFalse($it->isInfinite());

    }

    function testTimezone() {

        $tz = new DateTimeZone('Europe/Berlin');
        $it = new RDateIterator('20140901T000000,20141001T000000', new DateTimeImmutable('2014-08-01 00:00:00', $tz));

        $expected = [
            new DateTimeImmutable('2014-08-01 00:00:00', $tz),
            new DateTimeImmutable('2014-09-01 00:00:00', $tz),
            new DateTimeImmutable('2014-10-01 00:00:00', $tz),
        ];

        $this->assertEquals(
            $expected,
            iterator_to_array($it)
        );


        $this->assertFalse($it->isInfinite());

    }


    function testFastForward() {

        $utc = new DateTimeZone('UTC');
        $it = new RDateIterator('20140901T000000Z,20141001T000000Z', new DateTimeImmutable('2014-08-01 00:00:00', $utc));

        $it->fastForward(new DateTimeImmutable('2014-08-15 00:00:00'));

        $result = [];
        while ($it->valid()) {
            $result[] = $it->current();
            $it->next();
        }

        $expected = [
            new DateTimeImmutable('2014-09-01 00:00:00', $utc),
            new DateTimeImmutable('2014-10-01 00:00:00', $utc),
        ];

        $this->assertEquals(
            $expected,
            $result
        );

        $this->assertFalse($it->isInfinite());

    }
}
