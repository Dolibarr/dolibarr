<?php

namespace Sabre\VObject\ITip;

class MessageTest extends \PHPUnit_Framework_TestCase {

    function testNoScheduleStatus() {

        $message = new Message();
        $this->assertFalse($message->getScheduleStatus());

    }

    function testScheduleStatus() {

        $message = new Message();
        $message->scheduleStatus = '1.2;Delivered';

        $this->assertEquals('1.2', $message->getScheduleStatus());

    }

    function testUnexpectedScheduleStatus() {

        $message = new Message();
        $message->scheduleStatus = '9.9.9';

        $this->assertEquals('9.9.9', $message->getScheduleStatus());

    }

}
