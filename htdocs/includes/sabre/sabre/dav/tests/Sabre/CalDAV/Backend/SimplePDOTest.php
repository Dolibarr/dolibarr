<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\PropPatch;

class SimplePDOTest extends \PHPUnit_Framework_TestCase {

    protected $pdo;

    function setUp() {

        if (!SABRE_HASSQLITE) $this->markTestSkipped('SQLite driver is not available');

        if (file_exists(SABRE_TEMPDIR . '/testdb.sqlite'))
            unlink(SABRE_TEMPDIR . '/testdb.sqlite');

        $pdo = new \PDO('sqlite:' . SABRE_TEMPDIR . '/testdb.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec(<<<SQL
CREATE TABLE simple_calendars (
    id INTEGER PRIMARY KEY ASC NOT NULL,
    uri TEXT NOT NULL,
    principaluri TEXT NOT NULL
)
SQL
        );
        $pdo->exec(<<<SQL
CREATE TABLE simple_calendarobjects (
    id INTEGER PRIMARY KEY ASC NOT NULL,
    calendarid INT UNSIGNED NOT NULL,
    uri TEXT NOT NULL,
    calendardata TEXT
);
SQL
        );

        $this->pdo = $pdo;

    }

    function testConstruct() {

        $backend = new SimplePDO($this->pdo);
        $this->assertTrue($backend instanceof SimplePDO);

    }

    /**
     * @depends testConstruct
     */
    function testGetCalendarsForUserNoCalendars() {

        $backend = new SimplePDO($this->pdo);
        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);

    }

    /**
     * @depends testConstruct
     */
    function testCreateCalendarAndFetch() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname'                                               => 'Hello!',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp'         => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);
        $calendars = $backend->getCalendarsForUser('principals/user2');

        $elementCheck = [
            'uri' => 'somerandomid',
        ];

        $this->assertInternalType('array', $calendars);
        $this->assertEquals(1, count($calendars));

        foreach ($elementCheck as $name => $value) {

            $this->assertArrayHasKey($name, $calendars[0]);
            $this->assertEquals($value, $calendars[0][$name]);

        }

    }

    /**
     * @depends testConstruct
     */
    function testUpdateCalendarAndFetch() {

        $backend = new SimplePDO($this->pdo);

        //Creating a new calendar
        $newId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $propPatch = new PropPatch([
            '{DAV:}displayname'                                       => 'myCalendar',
            '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('transparent'),
        ]);

        // Updating the calendar
        $backend->updateCalendar($newId, $propPatch);
        $result = $propPatch->commit();

        // Verifying the result of the update
        $this->assertFalse($result);

    }

    /**
     * @depends testCreateCalendarAndFetch
     */
    function testDeleteCalendar() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', [
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            '{DAV:}displayname'                                               => 'Hello!',
        ]);

        $backend->deleteCalendar($returnedId);

        $calendars = $backend->getCalendarsForUser('principals/user2');
        $this->assertEquals([], $calendars);

    }

    function testCreateCalendarObject() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $result = $this->pdo->query('SELECT calendardata FROM simple_calendarobjects WHERE uri = "random-id"');
        $this->assertEquals([
            'calendardata' => $object,
        ], $result->fetch(\PDO::FETCH_ASSOC));

    }
    function testGetMultipleObjects() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $backend->createCalendarObject($returnedId, 'id-1', $object);
        $backend->createCalendarObject($returnedId, 'id-2', $object);

        $check = [
            [
                'id'           => 1,
                'etag'         => '"' . md5($object) . '"',
                'uri'          => 'id-1',
                'size'         => strlen($object),
                'calendardata' => $object,
            ],
            [
                'id'           => 2,
                'etag'         => '"' . md5($object) . '"',
                'uri'          => 'id-2',
                'size'         => strlen($object),
                'calendardata' => $object,
            ],
        ];

        $result = $backend->getMultipleCalendarObjects($returnedId, ['id-1', 'id-2']);

        foreach ($check as $index => $props) {

            foreach ($props as $key => $value) {

                if ($key !== 'lastmodified') {
                    $this->assertEquals($value, $result[$index][$key]);
                } else {
                    $this->assertTrue(isset($result[$index][$key]));
                }

            }

        }

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjects() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $data = $backend->getCalendarObjects($returnedId);

        $this->assertEquals(1, count($data));
        $data = $data[0];

        $this->assertEquals('random-id', $data['uri']);
        $this->assertEquals(strlen($object), $data['size']);

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testGetCalendarObjectByUID() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:foo\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);

        $this->assertNull(
            $backend->getCalendarObjectByUID('principals/user2', 'bar')
        );
        $this->assertEquals(
            'somerandomid/random-id',
            $backend->getCalendarObjectByUID('principals/user2', 'foo')
        );

    }

    /**
     * @depends testCreateCalendarObject
     */
    function testUpdateCalendarObject() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $object2 = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20130101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->updateCalendarObject($returnedId, 'random-id', $object2);

        $data = $backend->getCalendarObject($returnedId, 'random-id');

        $this->assertEquals($object2, $data['calendardata']);
        $this->assertEquals('random-id', $data['uri']);


    }


    /**
     * @depends testCreateCalendarObject
     */
    function testDeleteCalendarObject() {

        $backend = new SimplePDO($this->pdo);
        $returnedId = $backend->createCalendar('principals/user2', 'somerandomid', []);

        $object = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $backend->createCalendarObject($returnedId, 'random-id', $object);
        $backend->deleteCalendarObject($returnedId, 'random-id');

        $data = $backend->getCalendarObject($returnedId, 'random-id');
        $this->assertNull($data);

    }


    function testCalendarQueryNoResult() {

        $abstract = new SimplePDO($this->pdo);
        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VJOURNAL',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => null,
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $this->assertEquals([
        ], $abstract->calendarQuery(1, $filters));

    }

    function testCalendarQueryTodo() {

        $backend = new SimplePDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VTODO',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => null,
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $this->assertEquals([
            "todo",
        ], $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTodoNotMatch() {

        $backend = new SimplePDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'         => 'VTODO',
                    'comp-filters' => [],
                    'prop-filters' => [
                        [
                            'name'           => 'summary',
                            'text-match'     => null,
                            'time-range'     => null,
                            'param-filters'  => [],
                            'is-not-defined' => false,
                        ],
                    ],
                    'is-not-defined' => false,
                    'time-range'     => null,
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $this->assertEquals([
        ], $backend->calendarQuery(1, $filters));

    }

    function testCalendarQueryNoFilter() {

        $backend = new SimplePDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name'           => 'VCALENDAR',
            'comp-filters'   => [],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $result = $backend->calendarQuery(1, $filters);
        $this->assertTrue(in_array('todo', $result));
        $this->assertTrue(in_array('event', $result));

    }

    function testCalendarQueryTimeRange() {

        $backend = new SimplePDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => new \DateTime('20120103'),
                        'end'   => new \DateTime('20120104'),
                    ],
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $this->assertEquals([
            "event2",
        ], $backend->calendarQuery(1, $filters));

    }
    function testCalendarQueryTimeRangeNoEnd() {

        $backend = new SimplePDO($this->pdo);
        $backend->createCalendarObject(1, "todo", "BEGIN:VCALENDAR\r\nBEGIN:VTODO\r\nEND:VTODO\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
        $backend->createCalendarObject(1, "event2", "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART:20120103\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => new \DateTime('20120102'),
                        'end'   => null,
                    ],
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ];

        $this->assertEquals([
            "event2",
        ], $backend->calendarQuery(1, $filters));

    }

}
