<?php

namespace Sabre\CardDAV\Xml\Property;

use Sabre\CardDAV;
use Sabre\DAV;

class SupportedAddressDataTest extends DAV\Xml\XmlTest {

    function testSimple() {

        $property = new SupportedAddressData();
        $this->assertInstanceOf('Sabre\CardDAV\Xml\Property\SupportedAddressData', $property);

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new SupportedAddressData();

        $this->namespaceMap[CardDAV\Plugin::NS_CARDDAV] = 'card';
        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:card="' . CardDAV\Plugin::NS_CARDDAV . '" xmlns:d="DAV:">' .
'<card:address-data-type content-type="text/vcard" version="3.0"/>' .
'<card:address-data-type content-type="text/vcard" version="4.0"/>' .
'<card:address-data-type content-type="application/vcard+json" version="4.0"/>' .
'</d:root>
', $xml);

    }

}
