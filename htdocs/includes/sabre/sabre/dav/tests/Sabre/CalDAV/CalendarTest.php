<?php

namespace Sabre\CalDAV;

use Sabre\DAV\PropPatch;

require_once 'Sabre/CalDAV/TestUtil.php';

class CalendarTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CalDAV\Backend\PDO
     */
    protected $backend;
    protected $principalBackend;
    /**
     * @var Sabre\CalDAV\Calendar
     */
    protected $calendar;
    /**
     * @var array
     */
    protected $calendars;

    function setup() {

        $this->backend = TestUtil::getBackend();

        $this->calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(2, count($this->calendars));
        $this->calendar = new Calendar($this->backend, $this->calendars[0]);


    }

    function teardown() {

        unset($this->backend);

    }

    function testSimple() {

        $this->assertEquals($this->calendars[0]['uri'], $this->calendar->getName());

    }

    /**
     * @depends testSimple
     */
    function testUpdateProperties() {

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'NewName',
        ]);

        $result = $this->calendar->propPatch($propPatch);
        $result = $propPatch->commit();

        $this->assertEquals(true, $result);

        $calendars2 = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals('NewName', $calendars2[0]['{DAV:}displayname']);

    }

    /**
     * @depends testSimple
     */
    function testGetProperties() {

        $question = [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set',
        ];

        $result = $this->calendar->getProperties($question);

        foreach ($question as $q) $this->assertArrayHasKey($q, $result);

        $this->assertEquals(['VEVENT', 'VTODO'], $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     * @depends testSimple
     */
    function testGetChildNotFound() {

        $this->calendar->getChild('randomname');

    }

    /**
     * @depends testSimple
     */
    function testGetChildren() {

        $children = $this->calendar->getChildren();
        $this->assertEquals(1, count($children));

        $this->assertTrue($children[0] instanceof CalendarObject);

    }

    /**
     * @depends testGetChildren
     */
    function testChildExists() {

        $this->assertFalse($this->calendar->childExists('foo'));

        $children = $this->calendar->getChildren();
        $this->assertTrue($this->calendar->childExists($children[0]->getName()));
    }



    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testCreateDirectory() {

        $this->calendar->createDirectory('hello');

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetName() {

        $this->calendar->setName('hello');

    }

    function testGetLastModified() {

        $this->assertNull($this->calendar->getLastModified());

    }

    function testCreateFile() {

        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $this->calendar->createFile('hello', $file);

        $file = $this->calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);

    }

    function testCreateFileNoSupportedComponents() {

        $file = fopen('php://memory', 'r+');
        fwrite($file, TestUtil::getTestCalendarData());
        rewind($file);

        $calendar = new Calendar($this->backend, $this->calendars[1]);
        $calendar->createFile('hello', $file);

        $file = $calendar->getChild('hello');
        $this->assertTrue($file instanceof CalendarObject);

    }

    function testDelete() {

        $this->calendar->delete();

        $calendars = $this->backend->getCalendarsForUser('principals/user1');
        $this->assertEquals(1, count($calendars));
    }

    function testGetOwner() {

        $this->assertEquals('principals/user1', $this->calendar->getOwner());

    }

    function testGetGroup() {

        $this->assertNull($this->calendar->getGroup());

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
                'privilege' => '{' . Plugin::NS_CALDAV . '}read-free-busy',
                'principal' => '{DAV:}authenticated',
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
        $this->assertEquals($expected, $this->calendar->getACL());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

        $this->calendar->setACL([]);

    }

    function testGetSyncToken() {

        $this->assertNull($this->calendar->getSyncToken());

    }

    function testGetSyncTokenNoSyncSupport() {

        $calendar = new Calendar(new Backend\Mock([], []), []);
        $this->assertNull($calendar->getSyncToken());

    }

    function testGetChanges() {

        $this->assertNull($this->calendar->getChanges(1, 1));

    }

}
