<?php

namespace Sabre\Event;

class ContinueCallbackTest extends \PHPUnit_Framework_TestCase {

    function testContinueCallBack() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return true;
        });
        $this->assertTrue($r);
        $this->assertEquals(3, $handlerCounter);
        $this->assertEquals(2, $continueCounter);

    }

    function testContinueCallBackBreak() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertTrue($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(1, $continueCounter);

    }

    function testContinueCallBackBreakByHandler() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
            return false;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertFalse($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(0, $continueCounter);

    }
}
