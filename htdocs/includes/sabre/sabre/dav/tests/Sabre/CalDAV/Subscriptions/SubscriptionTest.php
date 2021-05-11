<?php

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Href;

class SubscriptionTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getSub($override = []) {

        $caldavBackend = new \Sabre\CalDAV\Backend\MockSubscriptionSupport([], []);

        $info = [
            '{http://calendarserver.org/ns/}source' => new Href('http://example.org/src', false),
            'lastmodified'                          => date('2013-04-06 11:40:00'), // tomorrow is my birthday!
            '{DAV:}displayname'                     => 'displayname',
        ];


        $id = $caldavBackend->createSubscription('principals/user1', 'uri', array_merge($info, $override));
        $subInfo = $caldavBackend->getSubscriptionsForUser('principals/user1');

        $this->assertEquals(1, count($subInfo));
        $subscription = new Subscription($caldavBackend, $subInfo[0]);

        $this->backend = $caldavBackend;
        return $subscription;

    }

    function testValues() {

        $sub = $this->getSub();

        $this->assertEquals('uri', $sub->getName());
        $this->assertEquals(date('2013-04-06 11:40:00'), $sub->getLastModified());
        $this->assertEquals([], $sub->getChildren());

        $this->assertEquals(
            [
                '{DAV:}displayname'                     => 'displayname',
                '{http://calendarserver.org/ns/}source' => new Href('http://example.org/src', false),
            ],
            $sub->getProperties(['{DAV:}displayname', '{http://calendarserver.org/ns/}source'])
        );

        $this->assertEquals('principals/user1', $sub->getOwner());
        $this->assertNull($sub->getGroup());

        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ]
        ];
        $this->assertEquals($acl, $sub->getACL());

        $this->assertNull($sub->getSupportedPrivilegeSet());

    }

    function testValues2() {

        $sub = $this->getSub([
            'lastmodified' => null,
        ]);

        $this->assertEquals(null, $sub->getLastModified());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

        $sub = $this->getSub();
        $sub->setACL([]);

    }

    function testDelete() {

        $sub = $this->getSub();
        $sub->delete();

        $this->assertEquals([], $this->backend->getSubscriptionsForUser('principals1/user1'));

    }

    function testUpdateProperties() {

        $sub = $this->getSub();
        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'foo',
        ]);
        $sub->propPatch($propPatch);
        $this->assertTrue($propPatch->commit());

        $this->assertEquals(
            'foo',
            $this->backend->getSubscriptionsForUser('principals/user1')[0]['{DAV:}displayname']
        );

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testBadConstruct() {

        $caldavBackend = new \Sabre\CalDAV\Backend\MockSubscriptionSupport([], []);
        new Subscription($caldavBackend, []);

    }

}
