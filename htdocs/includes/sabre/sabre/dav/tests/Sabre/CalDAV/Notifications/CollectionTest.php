<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;

class CollectionTest extends \PHPUnit_Framework_TestCase {

    protected $caldavBackend;
    protected $principalUri;
    protected $notification;

    function getInstance() {

        $this->principalUri = 'principals/user1';

        $this->notification = new CalDAV\Xml\Notification\SystemStatus(1, '"1"');

        $this->caldavBackend = new CalDAV\Backend\MockSharing([], [], [
            'principals/user1' => [
                $this->notification
            ]
        ]);

        return new Collection($this->caldavBackend, $this->principalUri);

    }

    function testGetChildren() {

        $col = $this->getInstance();
        $this->assertEquals('notifications', $col->getName());

        $this->assertEquals([
            new Node($this->caldavBackend, $this->principalUri, $this->notification)
        ], $col->getChildren());

    }

    function testGetOwner() {

        $col = $this->getInstance();
        $this->assertEquals('principals/user1', $col->getOwner());

    }

    function testGetGroup() {

        $col = $this->getInstance();
        $this->assertNull($col->getGroup());

    }

    function testGetACL() {

        $col = $this->getInstance();
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        $this->assertEquals($expected, $col->getACL());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

        $col = $this->getInstance();
        $col->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $col = $this->getInstance();
        $this->assertNull($col->getSupportedPrivilegeSet());

    }
}
