<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class ScheduleCalendarTranspTest extends DAV\Xml\XmlTest {

    function setUp() {

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';


    }

    function testSimple() {

        $prop = new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE);
        $this->assertEquals(
            ScheduleCalendarTransp::OPAQUE,
            $prop->getValue()
        );

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testBadValue() {

        new ScheduleCalendarTransp('ahhh');

    }

    /**
     * @depends testSimple
     */
    function testSerializeOpaque() {

        $property = new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE);
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cal:opaque />
</d:root>
', $xml);

    }

    /**
     * @depends testSimple
     */
    function testSerializeTransparent() {

        $property = new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT);
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cal:transparent />
</d:root>
', $xml);

    }

    function testUnserializeTransparent() {

        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

$xml = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="$cal" xmlns:cs="$cs">
  <cal:transparent />
</d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\ScheduleCalendarTransp']
        );

        $this->assertEquals(
            new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
            $result['value']
        );

    }

    function testUnserializeOpaque() {

        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

$xml = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="$cal" xmlns:cs="$cs">
  <cal:opaque />
</d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\ScheduleCalendarTransp']
        );

        $this->assertEquals(
            new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE),
            $result['value']
        );

    }
}
