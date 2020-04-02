<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCalendarComponentSetTest extends DAV\Xml\XmlTest {

    function setUp() {

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';

    }

    function testSimple() {

        $prop = new SupportedCalendarComponentSet(['VEVENT']);
        $this->assertEquals(
            ['VEVENT'],
            $prop->getValue()
        );

    }

    function testMultiple() {

        $prop = new SupportedCalendarComponentSet(['VEVENT', 'VTODO']);
        $this->assertEquals(
            ['VEVENT', 'VTODO'],
            $prop->getValue()
        );

    }

    /**
     * @depends testSimple
     * @depends testMultiple
     */
    function testSerialize() {

        $property = new SupportedCalendarComponentSet(['VEVENT', 'VTODO']);
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cal:comp name="VEVENT"/>
  <cal:comp name="VTODO"/>
</d:root>
', $xml);

    }

    function testUnserialize() {

        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

$xml = <<<XML
<?xml version="1.0"?>
 <d:root xmlns:cal="$cal" xmlns:cs="$cs" xmlns:d="DAV:">
   <cal:comp name="VEVENT"/>
   <cal:comp name="VTODO"/>
 </d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\SupportedCalendarComponentSet']
        );

        $this->assertEquals(
            new SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
            $result['value']
        );

    }

    /**
     * @expectedException \Sabre\Xml\ParseException
     */
    function testUnserializeEmpty() {

        $cal = CalDAV\Plugin::NS_CALDAV;
        $cs = CalDAV\Plugin::NS_CALENDARSERVER;

$xml = <<<XML
<?xml version="1.0"?>
 <d:root xmlns:cal="$cal" xmlns:cs="$cs" xmlns:d="DAV:">
 </d:root>
XML;

        $result = $this->parse(
            $xml,
            ['{DAV:}root' => 'Sabre\\CalDAV\\Xml\\Property\\SupportedCalendarComponentSet']
        );

    }

}
