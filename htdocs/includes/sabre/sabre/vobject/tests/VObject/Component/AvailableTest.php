<?php

namespace Sabre\VObject\Component;

use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject\Reader;

/**
 * We use `RFCxxx` has a placeholder for the
 * https://tools.ietf.org/html/draft-daboo-calendar-availability-05 name.
 */
class AvailableTest extends \PHPUnit_Framework_TestCase {

    function testAvailableComponent() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:AVAILABLE
END:AVAILABLE
END:VCALENDAR
VCAL;
        $document = Reader::read($vcal);
        $this->assertInstanceOf(__NAMESPACE__ . '\Available', $document->AVAILABLE);

    }

    function testGetEffectiveStartEnd() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:AVAILABLE
DTSTART:20150717T162200Z
DTEND:20150717T172200Z
END:AVAILABLE
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $tz = new DateTimeZone('UTC');
        $this->assertEquals(
            [
                new DateTimeImmutable('2015-07-17 16:22:00', $tz),
                new DateTimeImmutable('2015-07-17 17:22:00', $tz),
            ],
            $document->AVAILABLE->getEffectiveStartEnd()
        );

    }

    function testGetEffectiveStartEndDuration() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:AVAILABLE
DTSTART:20150717T162200Z
DURATION:PT1H
END:AVAILABLE
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $tz = new DateTimeZone('UTC');
        $this->assertEquals(
            [
                new DateTimeImmutable('2015-07-17 16:22:00', $tz),
                new DateTimeImmutable('2015-07-17 17:22:00', $tz),
            ],
            $document->AVAILABLE->getEffectiveStartEnd()
        );

    }
}
