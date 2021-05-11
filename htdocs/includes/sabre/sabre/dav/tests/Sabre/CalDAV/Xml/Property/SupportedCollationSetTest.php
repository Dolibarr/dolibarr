<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends DAV\Xml\XmlTest {

    function testSimple() {

        $scs = new SupportedCollationSet();
        $this->assertInstanceOf('Sabre\CalDAV\Xml\Property\SupportedCollationSet', $scs);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCollationSet();

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '">
<cal:supported-collation>i;ascii-casemap</cal:supported-collation>
<cal:supported-collation>i;octet</cal:supported-collation>
<cal:supported-collation>i;unicode-casemap</cal:supported-collation>
</d:root>', $xml);

    }

}
