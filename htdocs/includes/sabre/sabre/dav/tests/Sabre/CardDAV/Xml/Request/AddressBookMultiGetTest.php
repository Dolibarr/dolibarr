<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class AddressBookMultiGetTest extends XmlTest {

    protected $elementMap = [
        '{urn:ietf:params:xml:ns:carddav}addressbook-multiget' => 'Sabre\\CardDAV\\Xml\\Request\AddressBookMultiGetReport',
    ];

    function testDeserialize() {

        /* lines look a bit odd but this triggers an XML parsing bug */
        $xml = <<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<CARD:addressbook-multiget xmlns="DAV:" xmlns:CARD="urn:ietf:params:xml:ns:carddav">
  <prop>
    <getcontenttype />
    <getetag />
    <CARD:address-data content-type="text/vcard" version="4.0" /></prop><href>/foo.vcf</href>
</CARD:addressbook-multiget>
XML;

        $result = $this->parse($xml);
        $addressBookMultiGetReport = new AddressBookMultiGetReport();
        $addressBookMultiGetReport->properties = [
            '{DAV:}getcontenttype',
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:carddav}address-data',
        ];
        $addressBookMultiGetReport->hrefs = ['/foo.vcf'];
        $addressBookMultiGetReport->contentType = 'text/vcard';
        $addressBookMultiGetReport->version = '4.0';
        $addressBookMultiGetReport->addressDataProperties = [];


        $this->assertEquals(
            $addressBookMultiGetReport,
            $result['value']
        );

    }


}
