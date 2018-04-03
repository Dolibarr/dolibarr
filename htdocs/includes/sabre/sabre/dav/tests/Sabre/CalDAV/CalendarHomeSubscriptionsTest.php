<?php

namespace Sabre\CalDAV;

use Sabre\DAV\MkCol;

class CalendarHomeSubscriptionsTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance() {

        $props = [
            '{DAV:}displayname'                     => 'baz',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/test.ics'),
        ];
        $principal = [
            'uri' => 'principals/user1'
        ];
        $this->backend = new Backend\MockSubscriptionSupport([], []);
        $this->backend->createSubscription('principals/user1', 'uri', $props);

        return new CalendarHome($this->backend, $principal);

    }

    function testSimple() {

        $instance = $this->getInstance();
        $this->assertEquals('user1', $instance->getName());

    }

    function testGetChildren() {

        $instance = $this->getInstance();
        $children = $instance->getChildren();
        $this->assertEquals(1, count($children));
        foreach ($children as $child) {
            if ($child instanceof Subscriptions\Subscription) {
                return;
            }
        }
        $this->fail('There were no subscription nodes in the calendar home');

    }

    function testCreateSubscription() {

        $instance = $this->getInstance();
        $rt = ['{DAV:}collection', '{http://calendarserver.org/ns/}subscribed'];

        $props = [
            '{DAV:}displayname'                     => 'baz',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/test2.ics'),
        ];
        $instance->createExtendedCollection('sub2', new MkCol($rt, $props));

        $children = $instance->getChildren();
        $this->assertEquals(2, count($children));

    }

    /**
     * @expectedException \Sabre\DAV\Exception\InvalidResourceType
     */
    function testNoSubscriptionSupport() {

        $principal = [
            'uri' => 'principals/user1'
        ];
        $backend = new Backend\Mock([], []);
        $uC = new CalendarHome($backend, $principal);

        $rt = ['{DAV:}collection', '{http://calendarserver.org/ns/}subscribed'];

        $props = [
            '{DAV:}displayname'                     => 'baz',
            '{http://calendarserver.org/ns/}source' => new \Sabre\DAV\Xml\Property\Href('http://example.org/test2.ics'),
        ];
        $uC->createExtendedCollection('sub2', new MkCol($rt, $props));

    }

}
