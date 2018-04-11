<?php

namespace Sabre\CalDAV\Xml\Request;

use DateTimeImmutable;
use DateTimeZone;
use Sabre\DAV\Xml\XmlTest;

class CalendarQueryReportTest extends XmlTest {

    protected $elementMap = [
        '{urn:ietf:params:xml:ns:caldav}calendar-query' => 'Sabre\\CalDAV\\Xml\\Request\CalendarQueryReport',
    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $result = $this->parse($xml);
        $calendarQueryReport = new CalendarQueryReport();
        $calendarQueryReport->properties = [
            '{DAV:}getetag',
        ];
        $calendarQueryReport->filters = [
            'name'           => 'VCALENDAR',
            'is-not-defined' => false,
            'comp-filters'   => [],
            'prop-filters'   => [],
            'time-range'     => false,
        ];

        $this->assertEquals(
            $calendarQueryReport,
            $result['value']
        );

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeNoFilter() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
    </d:prop>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    function testDeserializeComplex() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data content-type="application/json+calendar" version="2.0">
            <c:expand start="20150101T000000Z" end="20160101T000000Z" />
      </c:calendar-data>
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR">
            <c:comp-filter name="VEVENT">
                <c:time-range start="20150101T000000Z" end="20160101T000000Z" />
                <c:prop-filter name="UID" />
                <c:comp-filter name="VALARM">
                    <c:is-not-defined />
                </c:comp-filter>
                <c:prop-filter name="X-PROP">
                    <c:param-filter name="X-PARAM" />
                    <c:param-filter name="X-PARAM2">
                        <c:is-not-defined />
                    </c:param-filter>
                    <c:param-filter name="X-PARAM3">
                        <c:text-match negate-condition="yes">hi</c:text-match>
                    </c:param-filter>
                </c:prop-filter>
                <c:prop-filter name="X-PROP2">
                    <c:is-not-defined />
                </c:prop-filter>
                <c:prop-filter name="X-PROP3">
                    <c:time-range start="20150101T000000Z" end="20160101T000000Z" />
                </c:prop-filter>
                <c:prop-filter name="X-PROP4">
                    <c:text-match>Hello</c:text-match>
                </c:prop-filter>
            </c:comp-filter>
        </c:comp-filter>
    </c:filter>
</c:calendar-query>
XML;

        $result = $this->parse($xml);
        $calendarQueryReport = new CalendarQueryReport();
        $calendarQueryReport->version = '2.0';
        $calendarQueryReport->contentType = 'application/json+calendar';
        $calendarQueryReport->properties = [
            '{DAV:}getetag',
            '{urn:ietf:params:xml:ns:caldav}calendar-data',
        ];
        $calendarQueryReport->expand = [
            'start' => new DateTimeImmutable('2015-01-01 00:00:00', new DateTimeZone('UTC')),
            'end'   => new DateTimeImmutable('2016-01-01 00:00:00', new DateTimeZone('UTC')),
        ];
        $calendarQueryReport->filters = [
            'name'           => 'VCALENDAR',
            'is-not-defined' => false,
            'comp-filters'   => [
                [
                    'name'           => 'VEVENT',
                    'is-not-defined' => false,
                    'comp-filters'   => [
                        [
                            'name'           => 'VALARM',
                            'is-not-defined' => true,
                            'comp-filters'   => [],
                            'prop-filters'   => [],
                            'time-range'     => false,
                        ],
                    ],
                    'prop-filters' => [
                        [
                            'name'           => 'UID',
                            'is-not-defined' => false,
                            'time-range'     => false,
                            'text-match'     => null,
                            'param-filters'  => [],
                        ],
                        [
                            'name'           => 'X-PROP',
                            'is-not-defined' => false,
                            'time-range'     => false,
                            'text-match'     => null,
                            'param-filters'  => [
                                [
                                    'name'           => 'X-PARAM',
                                    'is-not-defined' => false,
                                    'text-match'     => null,
                                ],
                                [
                                    'name'           => 'X-PARAM2',
                                    'is-not-defined' => true,
                                    'text-match'     => null,
                                ],
                                [
                                    'name'           => 'X-PARAM3',
                                    'is-not-defined' => false,
                                    'text-match'     => [
                                        'negate-condition' => true,
                                        'collation'        => 'i;ascii-casemap',
                                        'value'            => 'hi',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name'           => 'X-PROP2',
                            'is-not-defined' => true,
                            'time-range'     => false,
                            'text-match'     => null,
                            'param-filters'  => [],
                        ],
                        [
                            'name'           => 'X-PROP3',
                            'is-not-defined' => false,
                            'time-range'     => [
                                'start' => new DateTimeImmutable('2015-01-01 00:00:00', new DateTimeZone('UTC')),
                                'end'   => new DateTimeImmutable('2016-01-01 00:00:00', new DateTimeZone('UTC')),
                            ],
                            'text-match'    => null,
                            'param-filters' => [],
                        ],
                        [
                            'name'           => 'X-PROP4',
                            'is-not-defined' => false,
                            'time-range'     => false,
                            'text-match'     => [
                                'negate-condition' => false,
                                'collation'        => 'i;ascii-casemap',
                                'value'            => 'Hello',
                            ],
                            'param-filters' => [],
                        ],
                    ],
                    'time-range' => [
                        'start' => new DateTimeImmutable('2015-01-01 00:00:00', new DateTimeZone('UTC')),
                        'end'   => new DateTimeImmutable('2016-01-01 00:00:00', new DateTimeZone('UTC')),
                    ]
                ],
            ],
            'prop-filters' => [],
            'time-range'   => false,
        ];

        $this->assertEquals(
            $calendarQueryReport,
            $result['value']
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeDoubleTopCompFilter() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data content-type="application/json+calendar" version="2.0">
            <c:expand start="20150101T000000Z" end="20160101T000000Z" />
      </c:calendar-data>
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeMissingExpandEnd() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data content-type="application/json+calendar" version="2.0">
            <c:expand start="20150101T000000Z" />
      </c:calendar-data>
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeExpandEndBeforeStart() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data content-type="application/json+calendar" version="2.0">
            <c:expand start="20150101T000000Z" end="20140101T000000Z" />
      </c:calendar-data>
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeTimeRangeOnVCALENDAR() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR">
            <c:time-range start="20150101T000000Z" end="20160101T000000Z" />
        </c:comp-filter>
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeTimeRangeEndBeforeStart() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR">
            <c:comp-filter name="VEVENT">
                <c:time-range start="20150101T000000Z" end="20140101T000000Z" />
            </c:comp-filter>
        </c:comp-filter>
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeTimeRangePropEndBeforeStart() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
      <d:getetag />
      <c:calendar-data />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR">
            <c:comp-filter name="VEVENT">
                <c:prop-filter name="DTSTART">
                    <c:time-range start="20150101T000000Z" end="20140101T000000Z" />
                </c:prop-filter>
            </c:comp-filter>
        </c:comp-filter>
    </c:filter>
</c:calendar-query>
XML;

        $this->parse($xml);

    }
}
