<?php

namespace Sabre\CalDAV;

class CalendarHomeNotificationsTest extends \PHPUnit_Framework_TestCase {

    function testGetChildrenNoSupport() {

        $backend = new Backend\Mock();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);

        $this->assertEquals(
            [],
            $calendarHome->getChildren()
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\NotFound
     */
    function testGetChildNoSupport() {

        $backend = new Backend\Mock();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);
        $calendarHome->getChild('notifications');

    }

    function testGetChildren() {

        $backend = new Backend\MockSharing();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);

        $result = $calendarHome->getChildren();
        $this->assertEquals('notifications', $result[0]->getName());

    }

    function testGetChild() {

        $backend = new Backend\MockSharing();
        $calendarHome = new CalendarHome($backend, ['uri' => 'principals/user']);
        $result = $calendarHome->getChild('notifications');
        $this->assertEquals('notifications', $result->getName());

    }

}
