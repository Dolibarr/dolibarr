<?php

namespace Sabre\CalDAV;

use Sabre\DAV;

class CalendarHomeSharedCalendarsTest extends \PHPUnit_Framework_TestCase {

    protected $backend;

    function getInstance() {

        $calendars = [
            [
                'id'           => 1,
                'principaluri' => 'principals/user1',
            ],
            [
                'id'                                        => 2,
                '{http://calendarserver.org/ns/}shared-url' => 'calendars/owner/cal1',
                '{http://sabredav.org/ns}owner-principal'   => 'principal/owner',
                '{http://sabredav.org/ns}read-only'         => false,
                'principaluri'                              => 'principals/user1',
            ],
        ];

        $this->backend = new Backend\MockSharing(
            $calendars,
            [],
            []
        );

        return new CalendarHome($this->backend, [
            'uri' => 'principals/user1'
        ]);

    }

    function testSimple() {

        $instance = $this->getInstance();
        $this->assertEquals('user1', $instance->getName());

    }

    function testGetChildren() {

        $instance = $this->getInstance();
        $children = $instance->getChildren();
        $this->assertEquals(3, count($children));

        // Testing if we got all the objects back.
        $sharedCalendars = 0;
        $hasOutbox = false;
        $hasNotifications = false;
        
        foreach ($children as $child) {

            if ($child instanceof ISharedCalendar) {
                $sharedCalendars++;
            }
            if ($child instanceof Notifications\ICollection) {
                $hasNotifications = true;
            }

        }
        $this->assertEquals(2, $sharedCalendars);
        $this->assertTrue($hasNotifications);

    }
    
    function testShareReply() {

        $instance = $this->getInstance();
        $result = $instance->shareReply('uri', DAV\Sharing\Plugin::INVITE_DECLINED, 'curi', '1');
        $this->assertNull($result);

    }

}
