<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;
use Sabre\VObject;

class ICSExportPluginTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;

    protected $icsExportPlugin;

    function setUp() {

        parent::setUp();
        $this->icsExportPlugin = new ICSExportPlugin();
        $this->server->addPlugin(
            $this->icsExportPlugin
        );

        $id = $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-123467',
            [
                '{DAV:}displayname'                         => 'Hello!',
                '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
            ]
        );

        $this->caldavBackend->createCalendarObject(
            $id,
            'event-1',
            <<<ICS
BEGIN:VCALENDAR
BEGIN:VTIMEZONE
TZID:Europe/Amsterdam
END:VTIMEZONE
BEGIN:VEVENT
UID:event-1
DTSTART;TZID=Europe/Amsterdam:20151020T000000
END:VEVENT
END:VCALENDAR
ICS
        );
        $this->caldavBackend->createCalendarObject(
            $id,
            'todo-1',
            <<<ICS
BEGIN:VCALENDAR
BEGIN:VTODO
UID:todo-1
END:VTODO
END:VCALENDAR
ICS
        );


    }

    function testInit() {

        $this->assertEquals(
            $this->icsExportPlugin,
            $this->server->getPlugin('ics-export')
        );
        $this->assertEquals($this->icsExportPlugin, $this->server->getPlugin('ics-export'));
        $this->assertEquals('ics-export', $this->icsExportPlugin->getPluginInfo()['name']);

    }

    function testBeforeMethod() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->body);

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));
        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));
        $this->assertEquals("Hello!", $obj->{"X-WR-CALNAME"});
        $this->assertEquals("#AA0000FF", $obj->{"X-APPLE-CALENDAR-COLOR"});

    }
    function testBeforeMethodNoVersion() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );
        DAV\Server::$exposeVersion = false;
        $response = $this->request($request);
        DAV\Server::$exposeVersion = true;

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->body);

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));
        $this->assertFalse(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testBeforeMethodNoExport() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467'
        );
        $response = new HTTP\Response();
        $this->assertNull($this->icsExportPlugin->httpGet($request, $response));

    }

    function testACLIntegrationBlocked() {

        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $this->request($request, 403);

    }

    function testACLIntegrationNotBlocked() {

        $aclPlugin = new DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $this->server->addPlugin(
            $aclPlugin
        );
        $this->server->addPlugin(
            new Plugin()
        );

        $this->autoLogin('admin');

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request, 200);
        $this->assertEquals('text/calendar', $response->getHeader('Content-Type'));

        $obj = VObject\Reader::read($response->body);

        $this->assertEquals(8, count($obj->children()));
        $this->assertEquals(1, count($obj->VERSION));
        $this->assertEquals(1, count($obj->CALSCALE));
        $this->assertEquals(1, count($obj->PRODID));
        $this->assertTrue(strpos((string)$obj->PRODID, DAV\Version::VERSION) !== false);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testBadStartParam() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=foo'
        );
        $this->request($request, 400);

    }

    function testBadEndParam() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&end=foo'
        );
        $this->request($request, 400);

    }

    function testFilterStartEnd() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2'
        );
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody());

        $this->assertEquals(0, count($obj->VTIMEZONE));
        $this->assertEquals(0, count($obj->VEVENT));

    }

    function testExpandNoStart() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&expand=1&end=2'
        );
        $this->request($request, 400);

    }

    function testExpand() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&start=1&end=2000000000&expand=1'
        );
        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->getBody());

        $this->assertEquals(0, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));

    }

    function testJCal() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeader('Content-Type'));

    }

    function testJCalInUrl() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&accept=jcal'
        );

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeader('Content-Type'));

    }

    function testNegotiateDefault() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'text/plain']
        );

        $response = $this->request($request, 200);
        $this->assertEquals('text/calendar', $response->getHeader('Content-Type'));

    }

    function testFilterComponentVEVENT() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VEVENT'
        );

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->body);
        $this->assertEquals(1, count($obj->VTIMEZONE));
        $this->assertEquals(1, count($obj->VEVENT));
        $this->assertEquals(0, count($obj->VTODO));

    }

    function testFilterComponentVTODO() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VTODO'
        );

        $response = $this->request($request, 200);

        $obj = VObject\Reader::read($response->body);

        $this->assertEquals(0, count($obj->VTIMEZONE));
        $this->assertEquals(0, count($obj->VEVENT));
        $this->assertEquals(1, count($obj->VTODO));

    }

    function testFilterComponentBadComponent() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export&componentType=VVOODOO'
        );

        $response = $this->request($request, 400);

    }

    function testContentDisposition() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export'
        );

        $response = $this->request($request, 200);
        $this->assertEquals('text/calendar', $response->getHeader('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-123467-' . date('Y-m-d') . '.ics"',
            $response->getHeader('Content-Disposition')
        );

    }

    function testContentDispositionJson() {

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-123467?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-123467-' . date('Y-m-d') . '.json"',
            $response->getHeader('Content-Disposition')
        );

    }

    function testContentDispositionBadChars() {

        $this->caldavBackend->createCalendar(
            'principals/admin',
            'UUID-b_ad"(ch)ars',
            [
                '{DAV:}displayname'                         => 'Test bad characters',
                '{http://apple.com/ns/ical/}calendar-color' => '#AA0000FF',
            ]
        );

        $request = new HTTP\Request(
            'GET',
            '/calendars/admin/UUID-b_ad"(ch)ars?export',
            ['Accept' => 'application/calendar+json']
        );

        $response = $this->request($request, 200);
        $this->assertEquals('application/calendar+json', $response->getHeader('Content-Type'));
        $this->assertEquals(
            'attachment; filename="UUID-b_adchars-' . date('Y-m-d') . '.json"',
            $response->getHeader('Content-Disposition')
        );

    }

}
