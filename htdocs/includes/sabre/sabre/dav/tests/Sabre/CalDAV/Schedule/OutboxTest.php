<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\DAV;

class OutboxTest extends \PHPUnit_Framework_TestCase {

    function testSetup() {

        $outbox = new Outbox('principals/user1');
        $this->assertEquals('outbox', $outbox->getName());
        $this->assertEquals([], $outbox->getChildren());
        $this->assertEquals('principals/user1', $outbox->getOwner());
        $this->assertEquals(null, $outbox->getGroup());

        $this->assertEquals([
            [
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-send',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-send',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
        ], $outbox->getACL());

    }

}
