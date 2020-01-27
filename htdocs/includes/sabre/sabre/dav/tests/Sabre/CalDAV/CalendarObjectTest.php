<?php

namespace Sabre\CalDAV;

require_once 'Sabre/CalDAV/TestUtil.php';

class CalendarObjectTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\Backend_PDO
     */
    protected $backend;
    /**
     * @var Sabre\CalDAV\Calendar
     */
    protected $calendar;
    protected $principalBackend;

    function setup() {

        $this->backend = TestUtil::getBackend();

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(2, count($calendars));
        $this->calendar = new Calendar($this->backend, $calendars[0]);

    }

    function teardown() {

        unset($this->calendar);
        unset($this->backend);

    }

    function testSetup() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $this->assertInternalType('string', $children[0]->getName());
        $this->assertInternalType('string', $children[0]->get());
        $this->assertInternalType('string', $children[0]->getETag());
        $this->assertEquals('text/calendar; charset=utf-8', $children[0]->getContentType());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidArg1() {

        $obj = new CalendarObject(
            new Backend\Mock([], []),
            [],
            []
        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testInvalidArg2() {

        $obj = new CalendarObject(
            new Backend\Mock([], []),
            [],
            ['calendarid' => '1']
        );

    }

    /**
     * @depends testSetup
     */
    function testPut() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);
        $newData = TestUtil::getTestCalendarData();

        $children[0]->put($newData);
        $this->assertEquals($newData, $children[0]->get());

    }

    /**
     * @depends testSetup
     */
    function testPutStream() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);
        $newData = TestUtil::getTestCalendarData();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $newData);
        rewind($stream);
        $children[0]->put($stream);
        $this->assertEquals($newData, $children[0]->get());

    }


    /**
     * @depends testSetup
     */
    function testDelete() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];
        $obj->delete();

        $children2 = $this->calendar->getChildren();
        $this->assertEquals(count($children) - 1, count($children2));

    }

    /**
     * @depends testSetup
     */
    function testGetLastModified() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];

        $lastMod = $obj->getLastModified();
        $this->assertTrue(is_int($lastMod) || ctype_digit($lastMod) || is_null($lastMod));

    }

    /**
     * @depends testSetup
     */
    function testGetSize() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];

        $size = $obj->getSize();
        $this->assertInternalType('int', $size);

    }

    function testGetOwner() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];
        $this->assertEquals('principals/user1', $obj->getOwner());

    }

    function testGetGroup() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];
        $this->assertNull($obj->getGroup());

    }

    function testGetACL() {

        $expected = [
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => 'principals/user1/calendar-proxy-write',
                'protected' => true,
            ],
        ];

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];
        $this->assertEquals($expected, $obj->getACL());

    }

    function testDefaultACL() {

        $backend = new Backend\Mock([], []);
        $calendarObject = new CalendarObject($backend, ['principaluri' => 'principals/user1'], ['calendarid' => 1, 'uri' => 'foo']);
        $expected = [
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
            ],
        ];
        $this->assertEquals($expected, $calendarObject->getACL());


    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];
        $obj->setACL([]);

    }

    function testGet() {

        $children = $this->calendar->getChildren();
        $this->assertTrue($children[0] instanceof CalendarObject);

        $obj = $children[0];

            $expected = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 4.0.1//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Asia/Seoul
BEGIN:DAYLIGHT
TZOFFSETFROM:+0900
RRULE:FREQ=YEARLY;UNTIL=19880507T150000Z;BYMONTH=5;BYDAY=2SU
DTSTART:19870510T000000
TZNAME:GMT+09:00
TZOFFSETTO:+1000
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+1000
DTSTART:19881009T000000
TZNAME:GMT+09:00
TZOFFSETTO:+0900
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20100225T154229Z
UID:39A6B5ED-DD51-4AFE-A683-C35EE3749627
TRANSP:TRANSPARENT
SUMMARY:Something here
DTSTAMP:20100228T130202Z
DTSTART;TZID=Asia/Seoul:20100223T060000
DTEND;TZID=Asia/Seoul:20100223T070000
ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:lisa@example.com
SEQUENCE:2
END:VEVENT
END:VCALENDAR";



        $this->assertEquals($expected, $obj->get());

    }

    function testGetRefetch() {

        $backend = new Backend\Mock([], [
            1 => [
                'foo' => [
                    'calendardata' => 'foo',
                    'uri'          => 'foo'
                ],
            ]
        ]);
        $obj = new CalendarObject($backend, ['id' => 1], ['uri' => 'foo']);

        $this->assertEquals('foo', $obj->get());

    }

    function testGetEtag1() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'etag'         => 'bar',
            'calendarid'   => 1
        ];

        $backend = new Backend\Mock([], []);
        $obj = new CalendarObject($backend, [], $objectInfo);

        $this->assertEquals('bar', $obj->getETag());

    }

    function testGetEtag2() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\Mock([], []);
        $obj = new CalendarObject($backend, [], $objectInfo);

        $this->assertEquals('"' . md5('foo') . '"', $obj->getETag());

    }

    function testGetSupportedPrivilegesSet() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\Mock([], []);
        $obj = new CalendarObject($backend, [], $objectInfo);
        $this->assertNull($obj->getSupportedPrivilegeSet());

    }

    function testGetSize1() {

        $objectInfo = [
            'calendardata' => 'foo',
            'uri'          => 'foo',
            'calendarid'   => 1
        ];

        $backend = new Backend\Mock([], []);
        $obj = new CalendarObject($backend, [], $objectInfo);
        $this->assertEquals(3, $obj->getSize());

    }

    function testGetSize2() {

        $objectInfo = [
            'uri'        => 'foo',
            'calendarid' => 1,
            'size'       => 4,
        ];

        $backend = new Backend\Mock([], []);
        $obj = new CalendarObject($backend, [], $objectInfo);
        $this->assertEquals(4, $obj->getSize());

    }
}
