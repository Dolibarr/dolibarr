<?php

namespace Sabre\CardDAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class AddressBookQueryReportTest extends XmlTest {

    protected $elementMap = [
        '{urn:ietf:params:xml:ns:carddav}addressbook-query' => 'Sabre\\CardDAV\\Xml\\Request\AddressBookQueryReport',
    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>
XML;

        $result = $this->parse($xml);
        $addressBookQueryReport = new AddressBookQueryReport();
        $addressBookQueryReport->properties = [
            '{DAV:}getetag',
        ];
        $addressBookQueryReport->test = 'anyof';
        $addressBookQueryReport->filters = [
            [
                'name'           => 'uid',
                'test'           => 'anyof',
                'is-not-defined' => false,
                'param-filters'  => [],
                'text-matches'   => [],
            ]
        ];

        $this->assertEquals(
            $addressBookQueryReport,
            $result['value']
        );

    }

    function testDeserializeAllOf() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter test="allof">
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>
XML;

        $result = $this->parse($xml);
        $addressBookQueryReport = new AddressBookQueryReport();
        $addressBookQueryReport->properties = [
            '{DAV:}getetag',
        ];
        $addressBookQueryReport->test = 'allof';
        $addressBookQueryReport->filters = [
            [
                'name'           => 'uid',
                'test'           => 'anyof',
                'is-not-defined' => false,
                'param-filters'  => [],
                'text-matches'   => [],
            ]
        ];

        $this->assertEquals(
            $addressBookQueryReport,
            $result['value']
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeBadTest() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter test="bad">
        <c:prop-filter name="uid" />
    </c:filter>
</c:addressbook-query>
XML;

        $this->parse($xml);

    }

    /**
     * We should error on this, but KDE does this, so we chose to support it.
     */
    function testDeserializeNoFilter() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
</c:addressbook-query>
XML;

        $result = $this->parse($xml);
        $addressBookQueryReport = new AddressBookQueryReport();
        $addressBookQueryReport->properties = [
            '{DAV:}getetag',
        ];
        $addressBookQueryReport->test = 'anyof';
        $addressBookQueryReport->filters = [];

        $this->assertEquals(
            $addressBookQueryReport,
            $result['value']
        );

    }

    function testDeserializeComplex() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data content-type="application/vcard+json" version="4.0" />
    </d:prop>
    <c:filter>
        <c:prop-filter name="uid">
            <c:is-not-defined />
        </c:prop-filter>
        <c:prop-filter name="x-foo" test="allof">
            <c:param-filter name="x-param1" />
            <c:param-filter name="x-param2">
                <c:is-not-defined />
            </c:param-filter>
            <c:param-filter name="x-param3">
                <c:text-match match-type="contains">Hello!</c:text-match>
            </c:param-filter>
        </c:prop-filter>
        <c:prop-filter name="x-prop2">
            <c:text-match match-type="starts-with" negate-condition="yes">No</c:text-match>
        </c:prop-filter>
    </c:filter>
    <c:limit><c:nresults>10</c:nresults></c:limit>
</c:addressbook-query>
XML;

        $result = $this->parse($xml);
        $addressBookQueryReport = new AddressBookQueryReport();
        $addressBookQueryReport->properties = [
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:carddav}address-data',
        ];
        $addressBookQueryReport->test = 'anyof';
        $addressBookQueryReport->filters = [
            [
                'name'           => 'uid',
                'test'           => 'anyof',
                'is-not-defined' => true,
                'param-filters'  => [],
                'text-matches'   => [],
            ],
            [
                'name'           => 'x-foo',
                'test'           => 'allof',
                'is-not-defined' => false,
                'param-filters'  => [
                    [
                        'name'           => 'x-param1',
                        'is-not-defined' => false,
                        'text-match'     => null,
                    ],
                    [
                        'name'           => 'x-param2',
                        'is-not-defined' => true,
                        'text-match'     => null,
                    ],
                    [
                        'name'           => 'x-param3',
                        'is-not-defined' => false,
                        'text-match'     => [
                            'negate-condition' => false,
                            'value'            => 'Hello!',
                            'match-type'       => 'contains',
                            'collation'        => 'i;unicode-casemap',
                        ],
                    ],
                ],
                'text-matches' => [],
            ],
            [
                'name'           => 'x-prop2',
                'test'           => 'anyof',
                'is-not-defined' => false,
                'param-filters'  => [],
                'text-matches'   => [
                    [
                        'negate-condition' => true,
                        'value'            => 'No',
                        'match-type'       => 'starts-with',
                        'collation'        => 'i;unicode-casemap',
                    ],
                ],
            ]
        ];

        $addressBookQueryReport->version = '4.0';
        $addressBookQueryReport->contentType = 'application/vcard+json';
        $addressBookQueryReport->limit = 10;

        $this->assertEquals(
            $addressBookQueryReport,
            $result['value']
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeBadMatchType() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="x-foo" test="allof">
            <c:param-filter name="x-param3">
                <c:text-match match-type="bad">Hello!</c:text-match>
            </c:param-filter>
        </c:prop-filter>
    </c:filter>
</c:addressbook-query>
XML;
        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeBadMatchType2() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:prop-filter name="x-prop2">
            <c:text-match match-type="bad" negate-condition="yes">No</c:text-match>
        </c:prop-filter>
    </c:filter>
</c:addressbook-query>
XML;
        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeDoubleFilter() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
    </c:filter>
    <c:filter>
    </c:filter>
</c:addressbook-query>
XML;
        $this->parse($xml);

    }

    function testDeserializeAddressbookElements() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
    <d:prop>
      <d:getetag />
      <c:address-data>
        <c:prop name="VERSION"/>
        <c:prop name="UID"/>
        <c:prop name="NICKNAME"/>
        <c:prop name="EMAIL"/>
        <c:prop name="FN"/>
        <c:prop name="TEL"/>
      </c:address-data>
    </d:prop>
</c:addressbook-query>
XML;

        $result = $this->parse($xml);
        $addressBookQueryReport = new AddressBookQueryReport();
        $addressBookQueryReport->properties = [
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:carddav}address-data'
        ];
        $addressBookQueryReport->filters = [];
        $addressBookQueryReport->test = 'anyof';
        $addressBookQueryReport->contentType = 'text/vcard';
        $addressBookQueryReport->version = '3.0';
        $addressBookQueryReport->addressDataProperties = [
            'VERSION',
            'UID',
            'NICKNAME',
            'EMAIL',
            'FN',
            'TEL',
        ];

        $this->assertEquals(
            $addressBookQueryReport,
            $result['value']
        );

    }


}
