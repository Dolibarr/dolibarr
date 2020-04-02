<?php

namespace Sabre\CardDAV\Xml\Property;

use Sabre\CardDAV;
use Sabre\DAV;

class SupportedCollationSetTest extends DAV\Xml\XmlTest {

    function testSimple() {

        $property = new SupportedCollationSet();
        $this->assertInstanceOf('Sabre\CardDAV\Xml\Property\SupportedCollationSet', $property);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedCollationSet();
        
        $this->namespaceMap[CardDAV\Plugin::NS_CARDDAV] = 'card';
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:card="' . CardDAV\Plugin::NS_CARDDAV . '" xmlns:d="DAV:">' .
'<card:supported-collation>i;ascii-casemap</card:supported-collation>' .
'<card:supported-collation>i;octet</card:supported-collation>' .
'<card:supported-collation>i;unicode-casemap</card:supported-collation>' .
'</d:root>
', $xml);

    }

}
