<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\DAV;

class InboxTest extends \PHPUnit_Framework_TestCase {

    function testSetup() {

        $inbox = new Inbox(
            new CalDAV\Backend\MockScheduling(),
            'principals/user1'
        );
        $this->assertEquals('inbox', $inbox->getName());
        $this->assertEquals([], $inbox->getChildren());
        $this->assertEquals('principals/user1', $inbox->getOwner());
        $this->assertEquals(null, $inbox->getGroup());

        $this->assertEquals([
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-deliver',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ], $inbox->getACL());

        $ok = false;

    }

    /**
     * @depends testSetup
     */
    function testGetChildren() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            1,
            count($inbox->getChildren())
        );
        $this->assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        $this->assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );

    }

    /**
     * @depends testGetChildren
     */
    function testCreateFile() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $inbox->createFile('schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            1,
            count($inbox->getChildren())
        );
        $this->assertInstanceOf('Sabre\CalDAV\Schedule\SchedulingObject', $inbox->getChildren()[0]);
        $this->assertEquals(
            'schedule1.ics',
            $inbox->getChildren()[0]->getName()
        );

    }

    /**
     * @depends testSetup
     */
    function testCalendarQuery() {

        $backend = new CalDAV\Backend\MockScheduling();
        $inbox = new Inbox(
            $backend,
            'principals/user1'
        );

        $this->assertEquals(
            0,
            count($inbox->getChildren())
        );
        $backend->createSchedulingObject('principals/user1', 'schedule1.ics', "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        $this->assertEquals(
            ['schedule1.ics'],
            $inbox->calendarQuery([
                'name'           => 'VCALENDAR',
                'comp-filters'   => [],
                'prop-filters'   => [],
                'is-not-defined' => false
            ])
        );

    }
}
