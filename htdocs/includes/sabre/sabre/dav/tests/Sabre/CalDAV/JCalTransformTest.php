<?php

namespace Sabre\CalDAV;

use Sabre\HTTP\Request;
use Sabre\VObject;

class JCalTransformTest extends \Sabre\DAVServerTest {

    use VObject\PHPUnitAssertions;

    protected $setupCalDAV = true;
    protected $caldavCalendars = [
        [
            'id'           => 1,
            'principaluri' => 'principals/user1',
            'uri'          => 'foo',
        ]
    ];
    protected $caldavCalendarObjects = [
        1 => [
            'bar.ics' => [
                'uri'          => 'bar.ics',
                'calendarid'   => 1,
                'calendardata' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
                'lastmodified' => null
            ]
        ],
    ];

    function testGet() {

        $headers = [
            'Accept' => 'application/calendar+json',
        ];
        $request = new Request('GET', '/calendars/user1/foo/bar.ics', $headers);

        $response = $this->request($request);

        $body = $response->getBodyAsString();
        $this->assertEquals(200, $response->getStatus(), "Incorrect status code: " . $body);

        $response = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Json decoding error: ' . json_last_error_msg());
        }
        $this->assertEquals(
            [
                'vcalendar',
                [],
                [
                    [
                        'vevent',
                        [],
                        [],
                    ],
                ],
            ],
            $response
        );

    }

    function testMultiGet() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-multiget xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
    <d:prop>
        <c:calendar-data content-type="application/calendar+json" />
    </d:prop>
    <d:href>/calendars/user1/foo/bar.ics</d:href>
</c:calendar-multiget>
XML;

        $headers = [];
        $request = new Request('REPORT', '/calendars/user1/foo', $headers, $xml);

        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatus(), 'Full rsponse: ' . $response->getBodyAsString());

        $multiStatus = $this->server->xml->parse(
            $response->getBodyAsString()
        );

        $responses = $multiStatus->getResponses();
        $this->assertEquals(1, count($responses));

        $response = $responses[0]->getResponseProperties()[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"];

        $jresponse = json_decode($response, true);
        if (json_last_error()) {
            $this->fail('Json decoding error: ' . json_last_error_msg() . '. Full response: ' . $response);
        }
        $this->assertEquals(
            [
                'vcalendar',
                [],
                [
                    [
                        'vevent',
                        [],
                        [],
                    ],
                ],
            ],
            $jresponse
        );

    }

    function testCalendarQueryDepth1() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
    <d:prop>
        <c:calendar-data content-type="application/calendar+json" />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $headers = [
            'Depth' => '1',
        ];
        $request = new Request('REPORT', '/calendars/user1/foo', $headers, $xml);

        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatus(), "Invalid response code. Full body: " . $response->getBodyAsString());

        $multiStatus = $this->server->xml->parse(
            $response->getBodyAsString()
        );

        $responses = $multiStatus->getResponses();

        $this->assertEquals(1, count($responses));

        $response = $responses[0]->getResponseProperties()[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"];
        $response = json_decode($response, true);
        if (json_last_error()) {
            $this->fail('Json decoding error: ' . json_last_error_msg());
        }
        $this->assertEquals(
            [
                'vcalendar',
                [],
                [
                    [
                        'vevent',
                        [],
                        [],
                    ],
                ],
            ],
            $response
        );

    }

    function testCalendarQueryDepth0() {

        $xml = <<<XML
<?xml version="1.0"?>
<c:calendar-query xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:d="DAV:">
    <d:prop>
        <c:calendar-data content-type="application/calendar+json" />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>
XML;

        $headers = [
            'Depth' => '0',
        ];
        $request = new Request('REPORT', '/calendars/user1/foo/bar.ics', $headers, $xml);

        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatus(), "Invalid response code. Full body: " . $response->getBodyAsString());

        $multiStatus = $this->server->xml->parse(
            $response->getBodyAsString()
        );

        $responses = $multiStatus->getResponses();

        $this->assertEquals(1, count($responses));

        $response = $responses[0]->getResponseProperties()[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"];
        $response = json_decode($response, true);
        if (json_last_error()) {
            $this->fail('Json decoding error: ' . json_last_error_msg());
        }
        $this->assertEquals(
            [
                'vcalendar',
                [],
                [
                    [
                        'vevent',
                        [],
                        [],
                    ],
                ],
            ],
            $response
        );

    }

    function testValidateICalendar() {

        $input = [
            'vcalendar',
            [],
            [
                [
                    'vevent',
                    [
                        ['uid',     (object)[], 'text', 'foo'],
                        ['dtstart', (object)[], 'date', '2016-04-06'],
                    ],
                    [],
                ],
            ],
        ];
        $input = json_encode($input);
        $this->caldavPlugin->beforeWriteContent(
            'calendars/user1/foo/bar.ics',
            $this->server->tree->getNodeForPath('calendars/user1/foo/bar.ics'),
            $input,
            $modified
        );


        $expected = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:foo
DTSTART;VALUE=DATE:20160406
DTSTAMP:**ANY**
END:VEVENT
END:VCALENDAR
ICS;

        $this->assertVObjectEqualsVObject(
            $expected,
            $input
        );

    }

}
