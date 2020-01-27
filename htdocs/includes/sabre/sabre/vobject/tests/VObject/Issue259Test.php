<?php

namespace Sabre\VObject;

class Issue259Test extends \PHPUnit_Framework_TestCase {

    function testParsingJcalWithUntil() {
        $jcalWithUntil = '["vcalendar",[],[["vevent",[["uid",{},"text","dd1f7d29"],["organizer",{"cn":"robert"},"cal-address","mailto:robert@robert.com"],["dtstart",{"tzid":"Europe/Berlin"},"date-time","2015-10-21T12:00:00"],["dtend",{"tzid":"Europe/Berlin"},"date-time","2015-10-21T13:00:00"],["transp",{},"text","OPAQUE"],["rrule",{},"recur",{"freq":"MONTHLY","until":"2016-01-01T22:00:00Z"}]],[]]]]';
        $parser = new Parser\Json();
        $parser->setInput($jcalWithUntil);

        $vcalendar = $parser->parse();
        $eventAsArray = $vcalendar->select('VEVENT');
        $event = reset($eventAsArray);
        $rruleAsArray = $event->select('RRULE');
        $rrule = reset($rruleAsArray);
        $this->assertNotNull($rrule);
        $this->assertEquals($rrule->getValue(), 'FREQ=MONTHLY;UNTIL=20160101T220000Z');
    }

}
