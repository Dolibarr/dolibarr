<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\DAV;

class PluginPropertiesTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVScheduling = true;
    protected $setupPropertyStorage = true;

    function setUp() {

        parent::setUp();
        $this->caldavBackend->createCalendar(
            'principals/user1',
            'default',
            [

            ]
        );
        $this->principalBackend->addPrincipal([
            'uri' => 'principals/user1/calendar-proxy-read'
        ]);

    }

    function testPrincipalProperties() {

        $props = $this->server->getPropertiesForPath('/principals/user1', [
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-type',
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        $this->assertArrayHasKey(0, $props);
        $this->assertArrayHasKey(200, $props[0]);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL'];
        $this->assertTrue($prop instanceof DAV\Xml\Property\Href);
        $this->assertEquals('calendars/user1/outbox/', $prop->getHref());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL'];
        $this->assertTrue($prop instanceof DAV\Xml\Property\Href);
        $this->assertEquals('calendars/user1/inbox/', $prop->getHref());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-address-set', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'];
        $this->assertTrue($prop instanceof DAV\Xml\Property\Href);
        $this->assertEquals(['mailto:user1.sabredav@sabredav.org', '/principals/user1/'], $prop->getHrefs());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-type', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-type'];
        $this->assertEquals('INDIVIDUAL', $prop);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL'];
        $this->assertEquals('calendars/user1/default/', $prop->getHref());

    }
    function testPrincipalPropertiesBadPrincipal() {

        $props = $this->server->getPropertiesForPath('principals/user1/calendar-proxy-read', [
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL',
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set',
            '{urn:ietf:params:xml:ns:caldav}calendar-user-type',
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        $this->assertArrayHasKey(0, $props);
        $this->assertArrayHasKey(200, $props[0]);
        $this->assertArrayHasKey(404, $props[0]);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL', $props[0][404]);
        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL', $props[0][404]);

        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-address-set'];
        $this->assertTrue($prop instanceof DAV\Xml\Property\Href);
        $this->assertEquals(['/principals/user1/calendar-proxy-read/'], $prop->getHrefs());

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}calendar-user-type', $props[0][200]);
        $prop = $props[0][200]['{urn:ietf:params:xml:ns:caldav}calendar-user-type'];
        $this->assertEquals('INDIVIDUAL', $prop);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][404]);

    }
    function testNoDefaultCalendar() {

        foreach ($this->caldavBackend->getCalendarsForUser('principals/user1') as $calendar) {
            $this->caldavBackend->deleteCalendar($calendar['id']);
        }
        $props = $this->server->getPropertiesForPath('/principals/user1', [
            '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
        ]);

        $this->assertArrayHasKey(0, $props);
        $this->assertArrayHasKey(404, $props[0]);

        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL', $props[0][404]);

    }

    /**
     * There are two properties for availability. The server should
     * automatically map the old property to the standard property.
     */
    function testAvailabilityMapping() {

        $path = 'calendars/user1/inbox';
        $oldProp = '{http://calendarserver.org/ns/}calendar-availability';
        $newProp = '{urn:ietf:params:xml:ns:caldav}calendar-availability';
        $value1 = 'first value';
        $value2 = 'second value';

        // Storing with the old name
        $this->server->updateProperties($path, [
            $oldProp => $value1
        ]);

        // Retrieving with the new name
        $this->assertEquals(
            [$newProp => $value1],
            $this->server->getProperties($path, [$newProp])
        );

        // Storing with the new name
        $this->server->updateProperties($path, [
            $newProp => $value2
        ]);

        // Retrieving with the old name
        $this->assertEquals(
            [$oldProp => $value2],
            $this->server->getProperties($path, [$oldProp])
        );

    }

}
