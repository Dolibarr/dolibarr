<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV\Backend;

class SchedulingObjectTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\Backend_PDO
     */
    protected $backend;
    /**
     * @var Sabre\CalDAV\Calendar
     */
    protected $calendar;
    protected $principalBackend;

    protected $data;
    protected $data2;

    function setup() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');
        $this->backend = new Backend\MockScheduling();

        $this->data = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
SEQUENCE:1
END:VEVENT
END:VCALENDAR
ICS;
        $this->data = <<<ICS
BEGIN:VCALENDAR
METHOD:REQUEST
BEGIN:VEVENT
SEQUENCE:2
END:VEVENT
END:VCALENDAR
ICS;

        $this->inbox = new Inbox($this->backend, 'principals/user1');
        $this->inbox->createFile('item1.ics', $this->data);

    }

    function teardown() {

        unset($this->inbox);
        unset($this->backend);

    }

    function testSetup() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $this->assertInternalType('string', $children[0]->getName());
        $this->assertInternalType('string', $children[0]->get());
        $this->assertInternalType('string', $children[0]->getETag());
        $this->assertEquals('text/calendar; charset=utf-8', $children[0]->getContentType());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidArg1() {

        $obj = new SchedulingObject(
            new Backend\MockScheduling([], []),
            [],
            []
        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidArg2() {

        $obj = new SchedulingObject(
            new Backend\MockScheduling([], []),
            [],
            ['calendarid' => '1']
        );

    }

    /**
     * @depends testSetup
     * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
     */
    function testPut() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $children[0]->put('');

    }

    /**
     * @depends testSetup
     */
    function testDelete() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $obj->delete();

        $children2 = $this->inbox->getChildren();
        $this->assertEquals(count($children) - 1, count($children2));

    }

    /**
     * @depends testSetup
     */
    function testGetLastModified() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        $lastMod = $obj->getLastModified();
        $this->assertTrue(is_int($lastMod) || ctype_digit($lastMod) || is_null($lastMod));

    }

    /**
     * @depends testSetup
     */
    function testGetSize() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        $size = $obj->getSize();
        $this->assertInternalType('int', $size);

    }

    function testGetOwner() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $this->assertEquals('principals/user1', $obj->getOwner());

    }

    function testGetGroup() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $this->assertNull($obj->getGroup());

    }

    function testGetACL() {

        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
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
            ],
        ];

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $this->assertEquals($expected, $obj->getACL());

    }

    function testDefaultACL() {

        $backend = new Backend\MockScheduling([], []);
        $calendarObject = new SchedulingObject($backend, ['calendarid' => 1, 'uri' => 'foo', 'principaluri' => 'principals/user1']);
        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
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
            ],
        ];
        $this->assertEquals($expected, $calendarObject->getACL());


    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];
        $obj->setACL([]);

    }

    function testGet() {

        $children = $this->inbox->getChildren();
        $this->assertTrue($children[0] instanceof SchedulingObject);

        $obj = $children[0];

        $this->assertEquals($this->data, $obj->get());

    }

    function testGetRefetch() {

        $backend = new Backend\MockScheduling();
        $backend->createSchedulingObject('principals/user1', 'foo', 'foo');

        $obj = new SchedulingObject($backend, [
            'calendarid'   => 1,
            'uri'          => 'foo',
            'principaluri' => 'principals/user1',
        ]);

        $this->assertEquals('foo', $obj->get());

    }

    function testGetEtag1() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'etag'         => 'bar',
            'calendarid'   => 1
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);

        $this->assertEquals('bar', $obj->getETag());

    }

    function testGetEtag2() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);

        $this->assertEquals('"' . md5('foo') . '"', $obj->getETag());

    }

    function testGetSupportedPrivilegesSet() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertNull($obj->getSupportedPrivilegeSet());

    }

    function testGetSize1() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertEquals(3, $obj->getSize());

    }

    function testGetSize2() {

        $objectInfo = [
            'uri'        => 'foo',
            'calendarid' => 1,
            'size'       => 4,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertEquals(4, $obj->getSize());

    }

    function testGetContentType() {

        $objectInfo = [
            'uri'        => 'foo',
            'calendarid' => 1,
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertEquals('text/calendar; charset=utf-8', $obj->getContentType());

    }

    function testGetContentType2() {

        $objectInfo = [
            'uri'        => 'foo',
            'calendarid' => 1,
            'component'  => 'VEVENT',
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertEquals('text/calendar; charset=utf-8; component=VEVENT', $obj->getContentType());

    }
    function testGetACL2() {

        $objectInfo = [
            'uri'        => 'foo',
            'calendarid' => 1,
            'acl'        => [],
        ];

        $backend = new Backend\MockScheduling([], []);
        $obj = new SchedulingObject($backend, $objectInfo);
        $this->assertEquals([], $obj->getACL());

    }
}
