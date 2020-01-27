<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class DurationTest extends \PHPUnit_Framework_TestCase {

    function testGetDateInterval() {

        $vcal = new VCalendar();
        $event = $vcal->add('VEVENT', ['DURATION' => ['PT1H']]);

        $this->assertEquals(
            new \DateInterval('PT1H'),
            $event->{'DURATION'}->getDateInterval()
        );
    }
}
