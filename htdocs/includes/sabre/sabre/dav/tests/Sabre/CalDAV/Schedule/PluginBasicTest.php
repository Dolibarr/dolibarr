<?php

namespace Sabre\CalDAV\Schedule;

class PluginBasicTest extends \Sabre\DAVServerTest {

    public $setupCalDAV = true;
    public $setupCalDAVScheduling = true;

    function testSimple() {

        $plugin = new Plugin();
        $this->assertEquals(
            'caldav-schedule',
            $plugin->getPluginInfo()['name']
        );

    }

    function testOptions() {

        $plugin = new Plugin();
        $expected = [
            'calendar-auto-schedule',
            'calendar-availability',
        ];
        $this->assertEquals($expected, $plugin->getFeatures());

    }

    function testGetHTTPMethods() {

        $this->assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('notfound'));
        $this->assertEquals([], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1'));
        $this->assertEquals(['POST'], $this->caldavSchedulePlugin->getHTTPMethods('calendars/user1/outbox'));

    }

}
