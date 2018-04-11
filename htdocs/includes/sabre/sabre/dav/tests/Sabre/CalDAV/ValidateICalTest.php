<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';

class ValidateICalTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    /**
     * @var Sabre\CalDAV\Backend\Mock
     */
    protected $calBackend;

    function setUp() {

        $calendars = [
            [
                'id'                                                              => 'calendar1',
                'principaluri'                                                    => 'principals/admin',
                'uri'                                                             => 'calendar1',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO', 'VJOURNAL']),
            ],
            [
                'id'                                                              => 'calendar2',
                'principaluri'                                                    => 'principals/admin',
                'uri'                                                             => 'calendar2',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VJOURNAL']),
            ]
        ];

        $this->calBackend = new Backend\Mock($calendars, []);
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new CalendarRoot($principalBackend, $this->calBackend),
        ];

        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->debugExceptions = true;

        $plugin = new Plugin();
        $this->server->addPlugin($plugin);

        $response = new HTTP\ResponseMock();
        $this->server->httpResponse = $response;

    }

    function request(HTTP\Request $request) {

        $this->server->httpRequest = $request;
        $this->server->exec();

        return $this->server->httpResponse;

    }

    function testCreateFile() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);

    }

    function testCreateFileValid() {

        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict']
        );

        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request->setBody($ics);

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            'ETag'            => ['"' . md5($ics) . '"'],
        ], $response->getHeaders());

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileNoVersion() {

        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict']
        );

        $ics = <<<ICS
BEGIN:VCALENDAR
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request->setBody($ics);

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileNoVersionFixed() {

        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=lenient']
        );

        $ics = <<<ICS
BEGIN:VCALENDAR
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request->setBody($ics);

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $this->assertEquals([
            'X-Sabre-Version'  => [DAV\Version::VERSION],
            'Content-Length'   => ['0'],
            'X-Sabre-Ew-Gross' => ['iCalendar validation warning: VERSION MUST appear exactly once in a VCALENDAR component'],
        ], $response->getHeaders());

        $ics = <<<ICS
BEGIN:VCALENDAR\r
VERSION:2.0\r
PRODID:foo\r
BEGIN:VEVENT\r
UID:foo\r
DTSTAMP:20160406T052348Z\r
DTSTART:20160706T140000Z\r
END:VEVENT\r
END:VCALENDAR\r

ICS;

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileNoComponents() {

        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics',
            ['Prefer' => 'handling=strict']
        );
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
END:VCALENDAR
ICS;

        $request->setBody($ics);

        $response = $this->request($request);
        $this->assertEquals(403, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileNoUID() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileVCard() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCARD\r\nEND:VCARD\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFile2Components() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VJOURNAL\r\nUID:foo\r\nEND:VJOURNAL\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFile2UIDS() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:bar\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(415, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testCreateFileWrongComponent() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VFREEBUSY\r\nUID:foo\r\nEND:VFREEBUSY\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(403, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFile() {

        $this->calBackend->createCalendarObject('calendar1', 'blabla.ics', 'foo');
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar1/blabla.ics',
        ]);

        $response = $this->request($request);

        $this->assertEquals(415, $response->status);

    }

    function testUpdateFileParsableBody() {

        $this->calBackend->createCalendarObject('calendar1', 'blabla.ics', 'foo');
        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics'
        );
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request->setBody($ics);
        $response = $this->request($request);

        $this->assertEquals(204, $response->status);

        $expected = [
            'uri'          => 'blabla.ics',
            'calendardata' => $ics,
            'calendarid'   => 'calendar1',
            'lastmodified' => null,
        ];

        $this->assertEquals($expected, $this->calBackend->getCalendarObject('calendar1', 'blabla.ics'));

    }

    function testCreateFileInvalidComponent() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar2/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(403, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    function testUpdateFileInvalidComponent() {

        $this->calBackend->createCalendarObject('calendar2', 'blabla.ics', 'foo');
        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI'    => '/calendars/admin/calendar2/blabla.ics',
        ]);
        $request->setBody("BEGIN:VCALENDAR\r\nBEGIN:VTIMEZONE\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nUID:foo\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $response = $this->request($request);

        $this->assertEquals(403, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);

    }

    /**
     * What we are testing here, is if we send in a latin1 character, the
     * server should automatically transform this into UTF-8.
     *
     * More importantly. If any transformation happens, the etag must no longer
     * be returned by the server.
     */
    function testCreateFileModified() {

        $request = new HTTP\Request(
            'PUT',
            '/calendars/admin/calendar1/blabla.ics'
        );
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
UID:foo
SUMMARY:Meeting in M\xfcnster
DTSTAMP:20160406T052348Z
DTSTART:20160706T140000Z
END:VEVENT
END:VCALENDAR
ICS;

        $request->setBody($ics);

        $response = $this->request($request);

        $this->assertEquals(201, $response->status, 'Incorrect status returned! Full response body: ' . $response->body);
        $this->assertNull($response->getHeader('ETag'));

    }
}
